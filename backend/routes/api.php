<?php

use App\Http\Controllers\ApiKeyController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HistoriaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FotoController;
use App\Http\Controllers\MusicaController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VideoController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Todas as rotas aqui são stateless e protegidas por API Key
*/

Route::post('/api-keys/gerar', [ApiKeyController::class, 'gerar']);

Route::middleware(['api.key.auth'])->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/historias/limite/{limite?}', [HistoriaController::class, 'buscarHistoriasPorLimite'])->name('historias.por.limite');


    Route::middleware('auth:sanctum')->group(function () {

        Route::middleware(['tipoUsuario:MEMBRO_ADM,MEMBRO_RUA'])->group(function () {

        Route::prefix('cadastrar')->group(function () {
            Route::post('/usuarios', [UsuarioController::class, 'store'])->name('api.usuarios.store');
            Route::post('/videos', [VideoController::class, 'store'])->name('api.videos.store');
            Route::post('/fotos', [FotoController::class, 'store'])->name('api.fotos.store');
            Route::post('/musicas', [MusicaController::class, 'store'])->name('api.musicas.store');
            Route::post('/historias', [HistoriaController::class, 'store'])->name('api.historias.store');
        });
        });

        Route::prefix('usuarios')->group(function () {
            Route::get('/', [UsuarioController::class, 'index'])->name('usuarios.index');
            Route::get('/{uuid}', [UsuarioController::class, 'getUsuario'])->name('usuarios.get');
            Route::post('/foto-perfil', [FotoController::class, 'storeFotoPerfil'])->name('usuarios.foto.perfil.store');
        });

        Route::prefix('fotos')->group(function () {
            Route::get('/{uuid}', [FotoController::class, 'show'])->name('api.fotos.show');
            Route::put('/{uuid}', [FotoController::class, 'update'])->name('api.fotos.update');
            Route::patch('/{uuid}/usuarios', [FotoController::class, 'syncUsuarios']);
        });

        Route::prefix('historias')->group(function () {
            Route::get('/resumo', [HistoriaController::class, 'listarResumo']);
            Route::get('/{uuid}', [HistoriaController::class, 'show']);
            Route::put('/{uuid}', [HistoriaController::class, 'update']);
            Route::delete('/{uuid}', [HistoriaController::class, 'destroy']);
            Route::get('/{uuid}/resumo', [HistoriaController::class, 'resumo']);
        });
    });
});
