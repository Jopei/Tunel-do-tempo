<?php

namespace App\Services;

use App\DTO\HistoriaCreateDTO;
use App\DTO\HistoriaUpdateDTO;
use App\Repositories\HistoriaRepository;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Pivots\HistoriaFotoRepository;
use App\Repositories\Pivots\HistoriaVideoRepository;
use App\Repositories\Pivots\HistoriaMusicaRepository;
use App\Repositories\Pivots\UsuarioHistoriaRepository;
use App\Mapping\HistoriaFotoMapping;
use App\Mapping\HistoriaMapping;
use App\Mapping\HistoriaVideoMapping;
use App\Mapping\HistoriaMusicaMapping;
use App\Mapping\UsuarioHistoriaMapping;
use App\Repositories\TipoHistoriaRepository;
use App\Repositories\UsuarioRepository;
use Illuminate\Support\Facades\DB;

class HistoriaService
{
    public function __construct(
        protected HistoriaRepository $historiaRepository,
        protected HistoriaFotoRepository $historiaFotoRepository,
        protected HistoriaVideoRepository $historiaVideoRepository,
        protected HistoriaMusicaRepository $historiaMusicaRepository,
        protected UsuarioHistoriaRepository $usuarioHistoriaRepository,
        protected TipoHistoriaRepository $tipoHistoriaRepository,
        protected UsuarioRepository $usuarioRepository,
    ) {}

    public function buscarHistoriasPorLimite(int $limite = 7): Collection
    {
        return $this->historiaRepository->buscarHistoriasPorLimite($limite);
    }

    public function vincularFotos(int $historiaId, array $fotoIds): void
    {
        $this->historiaFotoRepository->deleteByHistoria($historiaId);

        if (empty($fotoIds)) {
            return;
        }

        $data = [];

        foreach ($fotoIds as $fotoId) {
            $data[] = [
                HistoriaFotoMapping::HISTORIA_ID => $historiaId,
                HistoriaFotoMapping::FOTO_ID => $fotoId,
                HistoriaFotoMapping::CREATED_AT => now(),
                HistoriaFotoMapping::UPDATED_AT => now(),
            ];
        }

        $this->historiaFotoRepository->insert($data);
    }

    public function vincularVideos(int $historiaId, array $videoIds): void
    {
        $this->historiaVideoRepository->deleteByHistoria($historiaId);

        if (empty($videoIds)) {
            return;
        }

        $data = [];

        foreach ($videoIds as $videoId) {
            $data[] = [
                HistoriaVideoMapping::HISTORIA_ID => $historiaId,
                HistoriaVideoMapping::VIDEO_ID => $videoId,
                HistoriaVideoMapping::CREATED_AT => now(),
                HistoriaVideoMapping::UPDATED_AT => now(),
            ];
        }

        $this->historiaVideoRepository->insert($data);
    }

    public function vincularMusicas(int $historiaId, array $musicaIds): void
    {
        $this->historiaMusicaRepository->deleteByHistoria($historiaId);

        if (empty($musicaIds)) {
            return;
        }

        $data = [];

        foreach ($musicaIds as $musicaId) {
            $data[] = [
                HistoriaMusicaMapping::HISTORIA_ID => $historiaId,
                HistoriaMusicaMapping::MUSICA_ID => $musicaId,
                HistoriaMusicaMapping::CREATED_AT => now(),
                HistoriaMusicaMapping::UPDATED_AT => now(),
            ];
        }

        $this->historiaMusicaRepository->insert($data);
    }

    public function vincularUsuarios(int $historiaId, array $usuarioIds): void
    {
        $this->usuarioHistoriaRepository->deleteByHistoria($historiaId);

        if (empty($usuarioIds)) {
            return;
        }

        $data = [];

        foreach ($usuarioIds as $usuarioId) {
            $data[] = [
                UsuarioHistoriaMapping::HISTORIA_ID => $historiaId,
                UsuarioHistoriaMapping::USUARIO_ID => $usuarioId,
                UsuarioHistoriaMapping::CREATED_AT => now(),
                UsuarioHistoriaMapping::UPDATED_AT => now(),
            ];
        }

        $this->usuarioHistoriaRepository->insert($data);
    }

    public function criarHistoria(
        HistoriaCreateDTO $dto,
        FotoService $fotoService,
        VideoService $videoService,
        MusicaService $musicaService,
        UsuarioRepository $usuarioRepository,
        TipoHistoriaRepository $tipoHistoriaRepository
    ) {
        return DB::transaction(function () use (
            $dto,
            $fotoService,
            $videoService,
            $musicaService,
            $usuarioRepository,
            $tipoHistoriaRepository

        ) {
            $tipoHistoriaId = $tipoHistoriaRepository->buscarIdPorNome($dto->tipoHistoriaId);

            $historia = $this->historiaRepository->create([
                HistoriaMapping::TITULO => $dto->titulo,
                HistoriaMapping::DESCRICAO_CURTA => $dto->descricaoCurta,
                HistoriaMapping::CONTEUDO => $dto->conteudo,
                HistoriaMapping::DATA_HISTORIA => $dto->dataHistoria,
                HistoriaMapping::TIPO_HISTORIA_ID => $tipoHistoriaId,
            ]);

            if (!empty($dto->usuarios)) {
                $ids = $usuarioRepository->buscarIdsPorUuids($dto->usuarios);
                $this->vincularUsuarios($historia->id, $ids);
            }

            if (!empty($dto->fotos)) {
                $fotoIds = [];
                foreach ($dto->fotos as $foto) {
                    $fotoModel = $fotoService->criarGaleria($foto, $dto->titulo);
                    $fotoIds[] = $fotoModel->id;
                }
                $this->vincularFotos($historia->id, $fotoIds);
            }

            if (!empty($dto->videos)) {
                $videoIds = [];
                foreach ($dto->videos as $video) {
                    $videoModel = $videoService->criarArquivo($video, $dto->titulo);
                    $videoIds[] = $videoModel->id;
                }
                $this->vincularVideos($historia->id, $videoIds);
            }

            if (!empty($dto->musicas)) {
                $musicaIds = [];
                foreach ($dto->musicas as $musica) {
                    $musicaModel = $musicaService->criarArquivo($musica, $dto->titulo);
                    $musicaIds[] = $musicaModel->id;
                }
                $this->vincularMusicas($historia->id, $musicaIds);
            }

            return $historia;
        });
    }

    public function buscarPorUuidComUsuarios(string $uuid)
    {
        return $this->historiaRepository->buscarPorUuidComUsuarios($uuid);
    }

    public function buscarCompletaPorUuid(string $uuid)
    {
        return $this->historiaRepository->buscarCompletaPorUuid($uuid);
    }

    public function listarResumo()
    {
        return $this->historiaRepository->listarResumo();
    }

    public function atualizarHistoria(string $uuid, HistoriaUpdateDTO $dto)
    {
        return DB::transaction(function () use ($uuid, $dto) {

            $historia = $this->historiaRepository->buscarPorUuid($uuid);

            $tipoHistoriaId = $this->tipoHistoriaRepository
                ->buscarIdPorNome($dto->tipoHistoria);

            $this->historiaRepository->update($historia, [
                HistoriaMapping::TITULO => $dto->titulo,
                HistoriaMapping::DESCRICAO_CURTA => $dto->descricaoCurta,
                HistoriaMapping::TIPO_HISTORIA_ID => $tipoHistoriaId,
            ]);

            if (!empty($dto->usuarios)) {
                $ids = $this->usuarioRepository
                    ->buscarIdsPorUuids($dto->usuarios);

                $historia->usuarios()->sync($ids);
            }

            return $historia->fresh(['usuarios', 'tipo']);
        });
    }

    public function removerHistoria(string $uuid): void
    {
        $historia = $this->historiaRepository->buscarPorUuid($uuid);
        $historia->delete();
    }
}
