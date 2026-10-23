<?php

namespace App\Console\Commands;

use App\Mapping\MusicaMapping;
use App\Models\Musica;
use App\Services\MusicaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MoverMusicasParaPrivado extends Command
{
    protected $signature = 'musicas:mover-para-privado';

    protected $description = 'Move os arquivos de musica do disco publico para o privado (idempotente; o path no banco nao muda).';

    public function handle(): int
    {
        $publico = Storage::disk('public');
        $privado = Storage::disk(MusicaService::DISCO);
        $movidos = 0;
        $ignorados = 0;

        Musica::withTrashed()->lazyById()->each(function (Musica $musica) use ($publico, $privado, &$movidos, &$ignorados) {
            $path = $musica->{MusicaMapping::PATH};

            if (!$path || !$publico->exists($path)) {
                $ignorados++;

                return;
            }

            // Copia, confere e so entao apaga do publico; reexecutar e seguro.
            if (!$privado->exists($path)) {
                $stream = $publico->readStream($path);
                $privado->writeStream($path, $stream);
                is_resource($stream) && fclose($stream);

                if ($privado->size($path) !== $publico->size($path)) {
                    $privado->delete($path);
                    $this->error("Falha ao copiar: musica {$musica->uuid}");

                    return;
                }
            }

            $publico->delete($path);
            $movidos++;
        });

        $this->info("Musicas movidas: {$movidos}. Ignoradas (sem arquivo publico): {$ignorados}.");

        return self::SUCCESS;
    }
}
