<?php

namespace App\Services;

use App\DTO\FotoCreateDTO;
use App\DTO\FotoPerfilCreateDTO;
use App\DTO\FotoUpdateDTO;
use App\Enums\TipoImagemEnum;
use App\Mapping\FotoMapping;
use App\Repositories\FotoRepository;
use App\Repositories\Pivots\UsuarioFotoRepository;
use App\Models\TipoImagem;
use App\Mapping\UsuarioFotoMapping;
use App\Mapping\TipoImagemMapping;
use App\Models\Foto;
use App\Models\Pivots\UsuarioFoto;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use DomainException;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FotoService
{
    public function __construct(
        protected FotoRepository $fotoRepository,
        protected UsuarioFotoRepository $usuarioFotoRepository,
        protected UsuarioService $usuarioService,
        protected MiniaturaService $miniaturaService
    ) {}

    /**
     * Dimensoes, data EXIF e miniatura de uma imagem ja salva no disco public.
     *
     * @return array{largura:?int,altura:?int,data_foto:?string,thumbnail_path:?string}
     */
    public function processarImagem(string $path): array
    {
        $abs = Storage::disk('public')->path($path);

        $meta = $this->miniaturaService->analisar($abs) + [
            'thumbnail_path' => $this->miniaturaService->gerar($abs, basename($path)),
        ];

        // Data/orientacao ja foram extraidas: regrava o original sem EXIF/GPS.
        $this->miniaturaService->removerMetadados($abs);

        return $meta;
    }

    public function criarFotoPerfil(FotoPerfilCreateDTO $dto)
    {
        $usuario = $this->usuarioService
            ->buscarUsuarioPorUuid($dto->usuarioUuid);

        return $this->definirFotoPerfil($usuario, $dto->imagem, $dto->titulo, $dto->descricao);
    }

    /**
     * Unica porta de entrada para trocar a foto de perfil: grava a Foto (tipo Perfil, cadastrante,
     * EXIF removido, miniatura e dimensoes) e substitui o vinculo de perfil anterior sem tocar
     * nas fotos de galeria do usuario.
     */
    public function definirFotoPerfil(
        Usuario $usuario,
        UploadedFile $imagem,
        ?string $titulo = null,
        ?string $descricao = null
    ): Foto {
        $tipoImagemId = $this->buscarTipoImagemId(TipoImagemEnum::PERFIL->value);

        $nomeArquivo = Str::uuid()->toString() . '.' . $imagem->extension();
        $path = $imagem->storeAs('uploads/fotos/perfil', $nomeArquivo, 'public');

        if (!$path) {
            throw new \RuntimeException('Falha ao gravar a imagem no disco.');
        }

        $meta = ['thumbnail_path' => null];
        $arquivosAntigos = [];

        try {
            $meta = $this->processarImagem($path);

            $foto = DB::transaction(function () use ($usuario, $titulo, $descricao, $path, $tipoImagemId, $meta, &$arquivosAntigos) {
                // Fotos de perfil anteriores (so tipo Perfil; galeria intacta): soft-delete + arquivos apos o commit.
                $antigas = Foto::where('tipo_imagem_id', $tipoImagemId)
                    ->whereIn('id', UsuarioFoto::where(UsuarioFotoMapping::USUARIO_ID, $usuario->id)->select(UsuarioFotoMapping::FOTO_ID))
                    ->get();
                $foto = $this->fotoRepository->create([
                    'titulo' => $titulo ?? 'Foto de perfil',
                    'descricao' => $descricao,
                    'path' => $path,
                    'tipo_imagem_id' => $tipoImagemId,
                    'usuario_cadastrou_id' => auth()->user()?->id ?? $usuario->id,
                    'data_foto' => $meta['data_foto'],
                    'largura' => $meta['largura'],
                    'altura' => $meta['altura'],
                    'thumbnail_path' => $meta['thumbnail_path'],
                ]);

                foreach ($antigas as $antiga) {
                    $arquivosAntigos[] = $antiga->path;
                    $arquivosAntigos[] = $antiga->thumbnail_path;
                    $antiga->delete();
                }

                $this->usuarioFotoRepository->deletePorTipoImagem($usuario->id, $tipoImagemId);

                $this->usuarioFotoRepository->insert([
                    UsuarioFotoMapping::USUARIO_ID => $usuario->id,
                    UsuarioFotoMapping::FOTO_ID => $foto->id,
                ]);

                return $foto;
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete(array_filter([$path, $meta['thumbnail_path'] ?? null]));

            throw $e;
        }

        Storage::disk('public')->delete(array_values(array_filter($arquivosAntigos)));

        return $foto;
    }

    public function criarFoto(FotoCreateDTO $dto, $usuario = null)
    {
        $usuarioId = $usuario ? $usuario->id : auth()->user()->id;
        
        $nomeArquivo = Str::uuid() . '.' . $dto->imagem->extension();

        $path = $dto->imagem->storeAs(
            'uploads/fotos',
            $nomeArquivo,
            'public'
        );

        $meta = ['thumbnail_path' => null];

        try {
            $meta = $this->processarImagem($path);

            $foto = $this->fotoRepository->create([
                'titulo' => $dto->titulo,
                'descricao' => $dto->descricao,
                'path' => $path,
                'tipo_imagem_id' => $dto->tipoImagemId,
                'usuario_cadastrou_id' => $usuarioId,
                'criado_em' => $dto->criadoEm,
                'data_foto' => $dto->dataFoto ?? $meta['data_foto'],
                'largura' => $meta['largura'],
                'altura' => $meta['altura'],
                'thumbnail_path' => $meta['thumbnail_path'],
            ]);

            foreach ($dto->usuariosUuid ?? [] as $usuarioUuid) {
                $pessoa = $this->usuarioService->buscarUsuarioPorUuid($usuarioUuid);

                $this->usuarioFotoRepository->insert([
                    UsuarioFotoMapping::USUARIO_ID => $pessoa->id,
                    UsuarioFotoMapping::FOTO_ID => $foto->id,
                ]);
            }
        } catch (\Throwable $e) {
            // Nao deixa arquivo orfao se o processamento/gravacao falhar.
            $disco = Storage::disk('public');
            $disco->delete(array_filter([$path, $meta['thumbnail_path'] ?? null]));

            throw $e;
        }

        return $foto;
    }

    public function editarFoto(FotoUpdateDTO $dto)
    {
        $foto = $this->fotoRepository->buscarPorUuid($dto->uuid);

        $dados = [
            'titulo' => $dto->titulo,
            'descricao' => $dto->descricao,
        ];

        if ($dto->informouDataFoto) {
            $dados['data_foto'] = $dto->dataFoto;
        }

        $this->fotoRepository->atualizar($foto, $dados);

        return $foto;
    }

    public function sincronizarUsuarios(string $fotoUuid, array $usuariosUuid): void
    {
        $foto = $this->fotoRepository->buscarPorUuid($fotoUuid);

        $usuariosAtuais = $this->usuarioFotoRepository
            ->listarUsuariosIdsPorFoto($foto->id);

        $usuariosNovos = collect($usuariosUuid)->map(function ($uuid) {
            return $this->usuarioService
                ->buscarUsuarioPorUuid($uuid)
                ->id;
        })->toArray();

        $usuariosParaRemover = array_diff($usuariosAtuais, $usuariosNovos);
        foreach ($usuariosParaRemover as $usuarioId) {
            $this->usuarioFotoRepository->deleteByUsuarioAndFoto(
                $usuarioId,
                $foto->id
            );
        }

        $usuariosParaAdicionar = array_diff($usuariosNovos, $usuariosAtuais);
        foreach ($usuariosParaAdicionar as $usuarioId) {
            $this->usuarioFotoRepository->insert([
                UsuarioFotoMapping::USUARIO_ID => $usuarioId,
                UsuarioFotoMapping::FOTO_ID => $foto->id,
            ]);
        }
    }

    public function criarGaleria(
        UploadedFile $arquivo,
        string $tituloHistoria
    ): Foto {
        $nomeArquivo = Str::uuid() . '.' . $arquivo->extension();

        $path = $arquivo->storeAs(
            'uploads/fotos',
            $nomeArquivo,
            'public'
        );

        $meta = $this->processarImagem($path);

        return Foto::create([
            FotoMapping::UUID => Str::uuid(),
            FotoMapping::DATA_FOTO => $meta['data_foto'],
            FotoMapping::LARGURA => $meta['largura'],
            FotoMapping::ALTURA => $meta['altura'],
            FotoMapping::THUMBNAIL_PATH => $meta['thumbnail_path'],
            FotoMapping::TITULO => $tituloHistoria,
            FotoMapping::DESCRICAO => 'null',
            FotoMapping::PATH => $path,
            FotoMapping::TIPO_IMAGEM_ID => $this->buscarTipoImagemId(
                TipoImagemEnum::GALERIA->value
            ),
            FotoMapping::USUARIO_CADASTROU_ID => auth()->user()->id,
            FotoMapping::EXTERNAL_LINK => 0,
        ]);
    }
    protected function buscarTipoImagemId(string $nome): int
    {
        $tipoImagem = TipoImagem::where(
            TipoImagemMapping::NOME,
            $nome
        )->firstOrFail();

        return $tipoImagem->id;
    }

    public function buscarPorUuid(string $uuid): Foto
    {
        return $this->fotoRepository->buscarPorUuid($uuid);
    }

    public function removerFoto(string $uuid): void
    {
        $foto = $this->fotoRepository->buscarPorUuid($uuid);

        if ($foto->trashed()) {
            throw new DomainException('Foto já removida.');
        }

        $this->apagarArquivos($foto);
        $this->fotoRepository->excluir($foto);
    }

    /**
     * Exclui varias fotos de uma vez (mesma regra de removerFoto). Uuids inexistentes ou
     * ja excluidos sao ignorados e devolvidos em "nao_encontradas".
     *
     * @return array{excluidas:int,nao_encontradas:array<int,string>}
     */
    public function removerLote(array $uuids): array
    {
        $fotos = $this->fotoRepository->buscarPorUuids($uuids);

        foreach ($fotos as $foto) {
            $this->apagarArquivos($foto);
            $this->fotoRepository->excluir($foto);
        }

        return [
            'excluidas' => $fotos->count(),
            'nao_encontradas' => array_values(array_diff($uuids, $fotos->pluck(FotoMapping::UUID)->all())),
        ];
    }

    private function apagarArquivos(Foto $foto): void
    {
        $disco = Storage::disk('public');

        if (!$foto->{FotoMapping::EXTERNAL_LINK} && $disco->exists($foto->{FotoMapping::PATH})) {
            $disco->delete($foto->{FotoMapping::PATH});
        }

        $thumb = $foto->{FotoMapping::THUMBNAIL_PATH};
        if ($thumb && $disco->exists($thumb)) {
            $disco->delete($thumb);
        }
    }
}
