<?php

namespace App\Services;

use App\DTO\FotoCreateDTO;
use App\DTO\FotoPerfilCreateDTO;
use App\Enums\TipoImagemEnum;
use App\Mapping\FotoMapping;
use App\Repositories\FotoRepository;
use App\Repositories\Pivots\UsuarioFotoRepository;
use App\Models\TipoImagem;
use App\Mapping\UsuarioFotoMapping;
use App\Mapping\TipoImagemMapping;
use App\Models\Foto;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;


class FotoService
{
    public function __construct(
        protected FotoRepository $fotoRepository,
        protected UsuarioFotoRepository $usuarioFotoRepository,
        protected UsuarioService $usuarioService
    ) {}

    public function criarFotoPerfil(FotoPerfilCreateDTO $dto)
    {
        $usuario = $this->usuarioService
            ->buscarUsuarioPorUuid($dto->usuarioUuid);

        $tipoImagem = TipoImagem::where(
            TipoImagemMapping::NOME,
            TipoImagemEnum::PERFIL->value
        )->firstOrFail();

        $nomeArquivo = Str::uuid()->toString() . '.' . $dto->imagem->extension();
        $path = $dto->imagem->storeAs('uploads/usuarios/perfil', $nomeArquivo);

        $foto = $this->fotoRepository->create([
            'titulo' => $dto->titulo,
            'descricao' => $dto->descricao,
            'path' => $path,
            'tipo_imagem_id' => $tipoImagem->id,
        ]);

        $this->usuarioFotoRepository->delete($usuario->id);

        $this->usuarioFotoRepository->insert([
            UsuarioFotoMapping::USUARIO_ID => $usuario->id,
            UsuarioFotoMapping::FOTO_ID => $foto->id,
        ]);

        return $foto;
    }

    public function criarFoto(FotoCreateDTO $dto)
    {
        $nomeArquivo = Str::uuid()->toString() . '.' . $dto->imagem->extension();
        $path = $dto->imagem->storeAs('uploads/fotos', $nomeArquivo);

        $foto = $this->fotoRepository->create([
            'titulo' => $dto->titulo,
            'descricao' => $dto->descricao,
            'path' => $path,
            'tipo_imagem_id' => $dto->tipoImagemId,
        ]);

        if (!empty($dto->usuariosUuid)) {

            foreach ($dto->usuariosUuid as $usuarioUuid) {

                $usuario = $this->usuarioService
                    ->buscarUsuarioPorUuid($usuarioUuid);

                $this->usuarioFotoRepository->insert([
                    UsuarioFotoMapping::USUARIO_ID => $usuario->id,
                    UsuarioFotoMapping::FOTO_ID => $foto->id,
                ]);
            }
        }

        return $foto;
    }

    public function editarFoto(string $uuid, array $data)
    {
        $foto = $this->fotoRepository->buscarPorUuid($uuid);

        $foto->update([
            FotoMapping::TITULO => $data['titulo'] ?? $foto->titulo,
            FotoMapping::DESCRICAO => $data['descricao'] ?? $foto->descricao,
        ]);

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
            $nomeArquivo
        );

        return Foto::create([
            FotoMapping::UUID => Str::uuid(),
            FotoMapping::TITULO => $tituloHistoria,
            FotoMapping::DESCRICAO => 'null',
            FotoMapping::PATH => $path,
            FotoMapping::TIPO_IMAGEM_ID => $this->buscarTipoImagemId(
                TipoImagemEnum::GALERIA->value
            ),
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
}
