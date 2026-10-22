<?php

namespace App\Services;

use App\DTO\FotoCreateDTO;
use App\DTO\MusicaCreateDTO;
use App\Enums\TipoImagemEnum;
use App\Mapping\FotoMapping;
use App\Mapping\TipoImagemMapping;
use App\Models\Foto;
use App\Models\TipoImagem;
use App\Mapping\MusicaMapping;
use App\Models\Musica;
use App\Repositories\MusicaRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MusicaService
{
    /** Disco privado (storage/app/private): audio so e servido por rota autenticada/assinada. */
    public const DISCO = 'local';

    public function __construct(
        protected MusicaRepository $musicaRepository,
        protected FotoService $fotoService
    ) {}

    public function listar(?string $q, string $ordem, int $page, int $perPage)
    {
        return $this->musicaRepository->paginar($q, $ordem, $page, $perPage);
    }

    public function atualizar(string $uuid, array $dados, bool $informouCapa): Musica
    {
        $musica = $this->musicaRepository->buscarPorUuid($uuid);

        $campos = array_intersect_key($dados, array_flip([
            MusicaMapping::TITULO,
            MusicaMapping::ARTISTA,
            MusicaMapping::ALBUM,
            MusicaMapping::ANO,
            MusicaMapping::DURACAO_SEGUNDOS,
        ]));

        if ($informouCapa) {
            $campos[MusicaMapping::CAPA_FOTO_ID] = !empty($dados['capa_foto_uuid'])
                ? Foto::where(FotoMapping::UUID, $dados['capa_foto_uuid'])->value(FotoMapping::ID)
                : null;
        }

        return $this->musicaRepository->atualizarCampos($musica, $campos);
    }

    public function excluir(string $uuid): void
    {
        $this->musicaRepository->excluir($this->musicaRepository->buscarPorUuid($uuid));
    }

    public function criar(MusicaCreateDTO $dto)
    {
        $nomeArquivo = Str::uuid() . '.' . $dto->musica->extension();

        $path = $dto->musica->storeAs(
            'uploads/musicas',
            $nomeArquivo,
            self::DISCO
        );

        try {
            return $this->registrar($dto, $path);
        } catch (\Throwable $e) {
            Storage::disk(self::DISCO)->delete($path);

            throw $e;
        }
    }

    private function registrar(MusicaCreateDTO $dto, string $path): Musica
    {
        $capaId = null;
        if ($dto->capa) {
            $tipo = TipoImagem::where(TipoImagemMapping::NOME, TipoImagemEnum::TUMB->value)->firstOrFail();
            $capaId = $this->fotoService->criarFoto(new FotoCreateDTO(
                imagem: $dto->capa,
                tipoImagemId: $tipo->id,
                titulo: 'Capa: ' . $dto->titulo,
            ))->id;
        } elseif ($dto->capaFotoUuid) {
            $capaId = Foto::where(FotoMapping::UUID, $dto->capaFotoUuid)->value(FotoMapping::ID);
        }

        return $this->musicaRepository->create([
            MusicaMapping::TITULO => $dto->titulo,
            MusicaMapping::PATH => $path,
            MusicaMapping::ARTISTA => $dto->artista,
            MusicaMapping::ALBUM => $dto->album,
            MusicaMapping::ANO => $dto->ano,
            MusicaMapping::DURACAO_SEGUNDOS => $dto->duracaoSegundos,
            MusicaMapping::CAPA_FOTO_ID => $capaId,
        ]);
    }

    public function criarArquivo(
        UploadedFile $arquivo,
        string $tituloHistoria
    ): Musica {
        $nomeArquivo = Str::uuid() . '.' . $arquivo->extension();

        $path = $arquivo->storeAs(
            'uploads/musicas',
            $nomeArquivo,
            self::DISCO
        );

        return Musica::create([
            MusicaMapping::UUID => Str::uuid(),
            MusicaMapping::TITULO => $tituloHistoria,
            MusicaMapping::PATH => $path,
        ]);
    }
}
