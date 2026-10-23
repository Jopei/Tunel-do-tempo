<?php

namespace App\Console\Commands;

use App\Mapping\FotoMapping;
use App\Models\Foto;
use App\Services\MiniaturaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GerarMiniaturasFotos extends Command
{
    protected $signature = 'fotos:gerar-miniaturas {--force : Regera mesmo as fotos que ja possuem miniatura} {--limpar-exif : Apenas regrava os JPEG originais sem EXIF/GPS (preserva data_foto e dimensoes)}';

    protected $description = 'Gera miniaturas e preenche largura/altura/data_foto (EXIF) das fotos existentes.';

    public function handle(MiniaturaService $miniaturas): int
    {
        if (!$miniaturas->gdDisponivel()) {
            $this->error('A extensao PHP GD nao esta disponivel; instale-a para gerar miniaturas.');

            return self::FAILURE;
        }

        $disco = Storage::disk('public');

        if ($this->option('limpar-exif')) {
            return $this->limparExif($miniaturas, $disco);
        }

        $geradas = 0;
        $ignoradas = 0;

        Foto::query()
            ->where(fn ($q) => $q->where(FotoMapping::EXTERNAL_LINK, 0)->orWhereNull(FotoMapping::EXTERNAL_LINK))
            ->when(!$this->option('force'), fn ($q) => $q->whereNull(FotoMapping::THUMBNAIL_PATH))
            ->lazyById()
            ->each(function (Foto $foto) use ($miniaturas, $disco, &$geradas, &$ignoradas) {
                $path = $foto->{FotoMapping::PATH};

                if (!$path || !$disco->exists($path)) {
                    $ignoradas++;
                    $this->warn("Arquivo ausente: foto {$foto->uuid}");

                    return;
                }

                $abs = $disco->path($path);
                $meta = $miniaturas->analisar($abs);
                $thumb = $miniaturas->gerar($abs, basename($path));

                if (!$thumb) {
                    $ignoradas++;
                    $this->warn("Nao foi possivel gerar miniatura: foto {$foto->uuid}");

                    return;
                }

                $foto->forceFill(array_filter([
                    FotoMapping::THUMBNAIL_PATH => $thumb,
                    FotoMapping::LARGURA => $meta['largura'],
                    FotoMapping::ALTURA => $meta['altura'],
                    FotoMapping::DATA_FOTO => $foto->{FotoMapping::DATA_FOTO} ? null : $meta['data_foto'],
                ], fn ($v) => $v !== null))->save();

                $geradas++;
            });

        $this->info("Miniaturas geradas: {$geradas}. Ignoradas: {$ignoradas}.");

        return self::SUCCESS;
    }

    private function limparExif(MiniaturaService $miniaturas, $disco): int
    {
        $limpas = 0;
        $ignoradas = 0;

        Foto::query()
            ->where(fn ($q) => $q->where(FotoMapping::EXTERNAL_LINK, 0)->orWhereNull(FotoMapping::EXTERNAL_LINK))
            ->lazyById()
            ->each(function (Foto $foto) use ($miniaturas, $disco, &$limpas, &$ignoradas) {
                $path = $foto->{FotoMapping::PATH};

                if (!$path || !$disco->exists($path)) {
                    $ignoradas++;

                    return;
                }

                $abs = $disco->path($path);
                $meta = $miniaturas->analisar($abs);

                if (!$miniaturas->removerMetadados($abs)) {
                    $ignoradas++;

                    return;
                }

                $foto->forceFill(array_filter([
                    FotoMapping::LARGURA => $meta['largura'],
                    FotoMapping::ALTURA => $meta['altura'],
                    FotoMapping::DATA_FOTO => $foto->{FotoMapping::DATA_FOTO} ? null : $meta['data_foto'],
                ], fn ($v) => $v !== null))->save();

                $limpas++;
            });

        $this->info("Originais limpos: {$limpas}. Ignorados (nao JPEG, ausentes ou ja limpos/sem GD): {$ignoradas}.");

        return self::SUCCESS;
    }
}
