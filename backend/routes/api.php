<?php

use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\AtualizacaoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HistoriaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FotoController;
use App\Http\Controllers\MusicaController;
use App\Http\Controllers\NotificacaoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\BuscaController;
use App\Http\Controllers\FavoritoController;
use App\Http\Controllers\LembrancaController;
use App\Http\Controllers\PublicoController;
use App\Http\Controllers\SenhaController;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\VideoPastaController;
use App\Http\Controllers\MembroController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Todas as rotas aqui são stateless e protegidas por API Key
*/

Route::post('/api-keys/gerar', [ApiKeyController::class, 'gerar']);
// Rotas literais de login antes dos '/{uuid}' publicos (senao 'anos'/'neste-dia' casariam como uuid).
Route::middleware(['api.key.auth', 'auth:sanctum'])->group(function () {
    Route::get('/videos/anos', [VideoController::class, 'anos'])->name('api.videos.anos');
    Route::get('/fotos/neste-dia', [FotoController::class, 'nesteDia'])->name('api.fotos.neste-dia');
});
Route::prefix('fotos')->group(function () {
    Route::get('/', [FotoController::class, 'index'])->name('api.fotos.index');
    Route::get('/linha-do-tempo', [FotoController::class, 'linhaDoTempo'])->name('api.fotos.linha-do-tempo');
    Route::get('/anos', [FotoController::class, 'anos'])->name('api.fotos.anos');
    Route::get('/{uuid}', [FotoController::class, 'show'])->name('api.fotos.show');
});
Route::get('/videos/{uuid}', [VideoController::class, 'show'])->name('api.videos.show');
// Stream de musica via URL assinada temporaria (sem API key: <audio> nao envia headers).
Route::get('/musicas/{uuid}/stream', [MusicaController::class, 'stream'])
    ->middleware('signed')
    ->name('api.musicas.stream');
Route::get('/atualizacoes', [AtualizacaoController::class, 'index']);
Route::get('/public/membros', [PublicoController::class, 'membros'])->name('public.membros');
Route::get('/public/historias/recentes', [PublicoController::class, 'historiasRecentes'])->name('public.historias.recentes');


Route::middleware(['api.key.auth'])->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('throttle:senha')->group(function () {
        Route::post('/esqueci-senha', [SenhaController::class, 'esqueci'])->name('senha.esqueci');
        Route::post('/redefinir-senha', [SenhaController::class, 'redefinir'])->name('senha.redefinir');
    });

    Route::get('/historias/limite/{limite?}', [HistoriaController::class, 'buscarHistoriasPorLimite'])->name('historias.por.limite');

    Route::prefix('cadastrar')->group(function () {
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('api.usuarios.store');
    });


    Route::middleware('auth:sanctum')->group(function () {

        Route::middleware(['tipoUsuario:MEMBRO_ADM,MEMBRO_RUA'])->group(function () {

            Route::prefix('cadastrar')->group(function () {
                Route::post('/videos', [VideoController::class, 'store'])->name('api.videos.store');
                Route::post('/musicas', [MusicaController::class, 'store'])->name('api.musicas.store');
                Route::post('/historias', [HistoriaController::class, 'store'])->name('api.historias.store');
            });

            Route::prefix('fotos')->group(function () {

                Route::post('/', [FotoController::class, 'store'])->name('api.fotos.store');
                Route::put('/{uuid}', [FotoController::class, 'update'])->name('api.fotos.update');
                Route::post('/lote-excluir', [FotoController::class, 'loteExcluir'])->name('api.fotos.lote-excluir');
                Route::delete('/{uuid}', [FotoController::class, 'destroy'])->name('api.fotos.destroy');
            });

            Route::prefix('albuns')->group(function () {
                Route::post('/', [AlbumController::class, 'store'])->name('api.albuns.store');
                Route::put('/{uuid}', [AlbumController::class, 'update'])->name('api.albuns.update');
                Route::delete('/{uuid}', [AlbumController::class, 'destroy'])->name('api.albuns.destroy');
                Route::post('/{uuid}/fotos', [AlbumController::class, 'adicionarFotos'])->name('api.albuns.fotos.adicionar');
                Route::delete('/{uuid}/fotos', [AlbumController::class, 'removerFotos'])->name('api.albuns.fotos.remover');
            });

            Route::prefix('video-pastas')->group(function () {
                Route::post('/', [VideoPastaController::class, 'store'])->name('api.video-pastas.store');
                Route::put('/{uuid}', [VideoPastaController::class, 'update'])->name('api.video-pastas.update');
                Route::delete('/{uuid}', [VideoPastaController::class, 'destroy'])->name('api.video-pastas.destroy');
            });

            Route::patch('/fotos/{uuid}/usuarios', [FotoController::class, 'syncUsuarios'])->name('api.fotos.usuarios');
            Route::put('/videos/{uuid}', [VideoController::class, 'update'])->name('api.videos.update');
            Route::delete('/videos/{uuid}', [VideoController::class, 'destroy'])->name('api.videos.destroy');

            Route::patch('/videos/{uuid}/pasta', [VideoPastaController::class, 'moverVideo'])->name('api.videos.pasta');

            Route::put('/musicas/{uuid}', [MusicaController::class, 'update'])->name('api.musicas.update');
            Route::delete('/musicas/{uuid}', [MusicaController::class, 'destroy'])->name('api.musicas.destroy');
        });

        Route::prefix('notificacoes')->group(function () {
            Route::post('/', [NotificacaoController::class, 'store'])
                ->middleware('tipoUsuario:MEMBRO_ADM')
                ->name('notificacoes.store');
            Route::get('/', [NotificacaoController::class, 'index'])->name('notificacoes.index');
            Route::get('/contagem', [NotificacaoController::class, 'contagem'])->name('notificacoes.contagem');
            Route::delete('/{id}', [NotificacaoController::class, 'destroy'])
                ->middleware('tipoUsuario:MEMBRO_ADM')
                ->whereNumber('id')
                ->name('notificacoes.destroy');
            Route::post('/{id}/marcar', [NotificacaoController::class, 'marcar'])->name('notificacoes.marcar');
            Route::post('/marcar-todas', [NotificacaoController::class, 'marcarTodas'])->name('notificacoes.marcar.todas');
        });

        Route::get('/admin/notificacoes', [NotificacaoController::class, 'enviadas'])
            ->middleware('tipoUsuario:MEMBRO_ADM')
            ->name('admin.notificacoes.index');

        Route::get('/me', [UsuarioController::class, 'me'])->name('me');
        Route::get('/me/favoritos', [FavoritoController::class, 'index'])->name('me.favoritos');

        Route::get('/busca', BuscaController::class)->middleware('throttle:60,1')->name('busca');

        Route::get('/homenagem/lembrancas', [LembrancaController::class, 'indexHomenagem'])->name('homenagem.lembrancas.index');
        Route::post('/homenagem/lembrancas', [LembrancaController::class, 'storeHomenagem'])
            ->middleware('throttle:20,1')->name('homenagem.lembrancas.store');
        Route::delete('/lembrancas/{uuid}', [LembrancaController::class, 'destroy'])->name('lembrancas.destroy');

        Route::prefix('usuarios')->group(function () {
            Route::get('/', [UsuarioController::class, 'index'])->name('usuarios.index');
            Route::put('/{uuid}', [UsuarioController::class, 'atualizar'])->name('api.usuarios.update');
            Route::get('/{uuid}', [UsuarioController::class, 'getUsuario'])->name('usuarios.get');
            Route::post('/{uuid}/foto-perfil', [UsuarioController::class, 'atualizarFotoPerfil'])->name('usuarios.foto.perfil.store');
            Route::post('/foto-perfil', [FotoController::class, 'storeFotoPerfil'])->name('usuarios.foto.perfil.atualizar');
        });

        Route::get('/fotos/{uuid}/details', [FotoController::class, 'details'])->name('api.fotos.details');

        Route::prefix('videos')->group(function () {
            Route::get('/', [VideoController::class, 'index']);
            Route::get('/{uuid}/detalhes', [VideoController::class, 'detalhes'])->name('api.videos.detalhes');
        });

        Route::prefix('albuns')->group(function () {
            Route::get('/', [AlbumController::class, 'index'])->name('api.albuns.index');
            Route::get('/{uuid}', [AlbumController::class, 'show'])->name('api.albuns.show');
        });

        Route::get('/video-pastas', [VideoPastaController::class, 'index'])->name('api.video-pastas.index');

        Route::get('/membros/{slug}', [MembroController::class, 'show'])->name('api.membros.show');

        Route::prefix('musicas')->group(function () {
            Route::get('/', [MusicaController::class, 'index'])->name('api.musicas.index');
            Route::get('/{uuid}/detalhes', [MusicaController::class, 'detalhes'])->name('api.musicas.detalhes');
            Route::get('/{uuid}', [MusicaController::class, 'show'])->name('api.musicas.show');
        });

        Route::prefix('historias')->group(function () {
            Route::get('/resumo', [HistoriaController::class, 'listarResumo']);
            Route::get('/{uuid}', [HistoriaController::class, 'show']);
            Route::put('/{uuid}', [HistoriaController::class, 'update']);
            Route::delete('/{uuid}', [HistoriaController::class, 'destroy']);
            Route::get('/{uuid}/resumo', [HistoriaController::class, 'resumo']);
            Route::get('/{uuid}/lembrancas', [LembrancaController::class, 'index'])->name('historias.lembrancas.index');
            Route::post('/{uuid}/lembrancas', [LembrancaController::class, 'store'])
                ->middleware('throttle:20,1')->name('historias.lembrancas.store');
            Route::post('/{uuid}/favorito', [FavoritoController::class, 'store'])->name('historias.favorito.store');
            Route::delete('/{uuid}/favorito', [FavoritoController::class, 'destroy'])->name('historias.favorito.destroy');
        });

        Route::prefix('atualizacoes')->group(function () {
            Route::post('/', [AtualizacaoController::class, 'store']);
            Route::put('/{uuid}', [AtualizacaoController::class, 'update']);
        });
    });
});
