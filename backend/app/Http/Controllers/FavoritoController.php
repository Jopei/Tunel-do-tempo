<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaginacaoRequest;
use App\Http\Resources\HistoriaResumoResource;
use App\Mapping\FavoritoMapping;
use App\Mapping\HistoriaMapping;
use App\Models\Favorito;
use App\Models\Historia;
use App\Services\HistoriaService;
use Illuminate\Http\JsonResponse;

class FavoritoController extends Controller
{
    public function store(string $uuid): JsonResponse
    {
        $historia = Historia::where(HistoriaMapping::UUID, $uuid)->firstOrFail();

        Favorito::firstOrCreate([
            FavoritoMapping::USUARIO_ID => auth()->user()->id,
            FavoritoMapping::HISTORIA_ID => $historia->id,
        ]);

        return response()->json(['favoritado' => true]);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $historia = Historia::where(HistoriaMapping::UUID, $uuid)->firstOrFail();

        Favorito::where(FavoritoMapping::USUARIO_ID, auth()->user()->id)
            ->where(FavoritoMapping::HISTORIA_ID, $historia->id)
            ->delete();

        return response()->json(['favoritado' => false]);
    }

    public function index(PaginacaoRequest $request, HistoriaService $historiaService)
    {
        return HistoriaResumoResource::collection(
            $historiaService->listarFavoritas(auth()->user()->id, (int) $request->input('per_page', 15))
        );
    }
}
