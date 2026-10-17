<?php

namespace App\Http\Controllers;

use App\Http\Requests\BuscaRequest;
use App\Http\Resources\MembroPublicoResource;
use App\Mapping\FotoMapping;
use App\Mapping\VideoMapping;
use App\Models\Foto;
use App\Models\Video;
use App\Repositories\HistoriaRepository;
use App\Repositories\UsuarioRepository;
use App\Support\Sanitizador;
use Carbon\Carbon;

class BuscaController extends Controller
{
    private const POR_GRUPO = 3;

    public function __invoke(
        BuscaRequest $request,
        HistoriaRepository $historias,
        UsuarioRepository $usuarios
    ) {
        $termo = trim($request->input('q'));
        $like = Sanitizador::like($termo);

        $fotos = Foto::query()
            ->where(FotoMapping::TIPO_IMAGEM_ID, '!=', 1)
            ->where(fn ($q) => $q->where(FotoMapping::TITULO, 'like', $like)
                ->orWhere(FotoMapping::DESCRICAO, 'like', $like))
            ->latest()->limit(self::POR_GRUPO)->get();

        $videos = Video::query()
            ->where(fn ($q) => $q->where(VideoMapping::TITULO, 'like', $like)
                ->orWhere(VideoMapping::DESCRICAO, 'like', $like))
            ->latest()->limit(self::POR_GRUPO)->get();

        return response()->json([
            'historias' => $historias->buscarPorTermo($termo, self::POR_GRUPO)->map(fn ($h) => [
                'uuid' => $h->uuid,
                'titulo' => $h->titulo,
                'descricao_curta' => $h->descricao_curta,
                'tipo_historia' => $h->tipo?->nome,
                'data_historia' => $h->data_historia ? Carbon::parse($h->data_historia)->toDateString() : null,
            ])->values(),
            'fotos' => $fotos->map(fn ($f) => [
                'uuid' => $f->uuid,
                'titulo' => $f->titulo,
                'url' => route('api.fotos.show', $f->uuid),
            ])->values(),
            'videos' => $videos->map(fn ($v) => [
                'uuid' => $v->uuid,
                'titulo' => $v->titulo,
                'url' => route('api.videos.show', $v->uuid),
                'thumbnail' => $v->thumbnail ? asset('storage/' . $v->thumbnail) : null,
            ])->values(),
            'membros' => MembroPublicoResource::collection(
                $usuarios->listarMembros($termo, self::POR_GRUPO)
            )->resolve(),
        ]);
    }
}
