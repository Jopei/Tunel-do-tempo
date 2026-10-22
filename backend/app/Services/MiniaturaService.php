<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Metadados (dimensoes, data EXIF) e miniaturas de imagens, via GD nativo.
 * Sem a extensao GD, as miniaturas sao ignoradas (thumbnail_url fica null);
 * dimensoes e data EXIF nao dependem de GD.
 */
class MiniaturaService
{
    public const LADO_MAXIMO = 480;
    public const QUALIDADE = 80;
    public const PASTA = 'uploads/fotos/thumbs';
    /** Acima disso (largura x altura) a imagem nao e decodificada pelo GD (decompression bomb). */
    public const MAX_PIXELS = 64_000_000;
    public const QUALIDADE_ORIGINAL = 90;

    public function gdDisponivel(): bool
    {
        return extension_loaded('gd') && function_exists('imagecreatefromstring') && function_exists('imagejpeg');
    }

    /**
     * @return array{largura:?int, altura:?int, data_foto:?string}
     */
    public function analisar(string $caminhoAbsoluto): array
    {
        $resultado = ['largura' => null, 'altura' => null, 'data_foto' => null];

        $info = @getimagesize($caminhoAbsoluto);
        if ($info) {
            [$largura, $altura] = $info;
            $exif = $this->lerExif($caminhoAbsoluto);
            if (in_array((int) ($exif['Orientation'] ?? 1), [5, 6, 7, 8], true)) {
                [$largura, $altura] = [$altura, $largura];
            }
            $resultado['largura'] = (int) $largura;
            $resultado['altura'] = (int) $altura;
        }

        $resultado['data_foto'] = $this->dataExif($caminhoAbsoluto);

        return $resultado;
    }

    /** Data (Y-m-d) em que a foto foi tirada, segundo o EXIF, ou null. */
    public function dataExif(string $caminhoAbsoluto): ?string
    {
        $exif = $this->lerExif($caminhoAbsoluto);
        $bruta = $exif['DateTimeOriginal'] ?? $exif['DateTimeDigitized'] ?? $exif['DateTime'] ?? null;

        if (!is_string($bruta) || !preg_match('/^(\d{4}):(\d{2}):(\d{2})/', $bruta, $m)) {
            return null;
        }

        if (!checkdate((int) $m[2], (int) $m[3], (int) $m[1]) || (int) $m[1] < 1900) {
            return null;
        }

        return "{$m[1]}-{$m[2]}-{$m[3]}";
    }

    /**
     * Gera a miniatura (JPEG, lado maior ate LADO_MAXIMO) no disco "public".
     * Devolve o path relativo ou null se nao foi possivel (sem GD, arquivo invalido...).
     */
    public function gerar(string $caminhoAbsoluto, string $nomeBase): ?string
    {
        if (!$this->gdDisponivel() || !is_file($caminhoAbsoluto) || !$this->dentroDoLimite($caminhoAbsoluto)) {
            return null;
        }

        try {
            $origem = @imagecreatefromstring((string) file_get_contents($caminhoAbsoluto));
            if (!$origem) {
                return null;
            }

            $origem = $this->corrigirOrientacao($origem, $caminhoAbsoluto);
            $largura = imagesx($origem);
            $altura = imagesy($origem);
            $escala = min(1, self::LADO_MAXIMO / max($largura, $altura));
            $novaLargura = max(1, (int) round($largura * $escala));
            $novaAltura = max(1, (int) round($altura * $escala));

            $destino = imagecreatetruecolor($novaLargura, $novaAltura);
            imagefill($destino, 0, 0, imagecolorallocate($destino, 255, 255, 255));
            imagecopyresampled($destino, $origem, 0, 0, 0, 0, $novaLargura, $novaAltura, $largura, $altura);

            ob_start();
            imagejpeg($destino, null, self::QUALIDADE);
            $binario = (string) ob_get_clean();

            $path = self::PASTA . '/' . pathinfo($nomeBase, PATHINFO_FILENAME) . '.jpg';
            Storage::disk('public')->put($path, $binario);

            return $path;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /** false se as dimensoes (via getimagesize, sem decodificar) excedem MAX_PIXELS ou o arquivo nao e imagem. */
    public function dentroDoLimite(string $caminhoAbsoluto): bool
    {
        $info = @getimagesize($caminhoAbsoluto);

        return $info !== false && ((int) $info[0] * (int) $info[1]) <= self::MAX_PIXELS;
    }

    /**
     * Regrava um JPEG sem metadados (EXIF/GPS), aplicando a orientacao nos pixels.
     * Nao mexe em outros formatos, sem GD ou em imagens acima do limite. Devolve true se regravou.
     */
    public function removerMetadados(string $caminhoAbsoluto): bool
    {
        if (!$this->gdDisponivel() || !is_file($caminhoAbsoluto)
            || @exif_imagetype($caminhoAbsoluto) !== IMAGETYPE_JPEG
            || !$this->dentroDoLimite($caminhoAbsoluto)) {
            return false;
        }

        try {
            $imagem = @imagecreatefromjpeg($caminhoAbsoluto);
            if (!$imagem) {
                return false;
            }

            $imagem = $this->corrigirOrientacao($imagem, $caminhoAbsoluto);

            ob_start();
            imagejpeg($imagem, null, self::QUALIDADE_ORIGINAL);
            $binario = (string) ob_get_clean();

            return $binario !== '' && file_put_contents($caminhoAbsoluto, $binario) !== false;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    private function lerExif(string $caminhoAbsoluto): array
    {
        if (!function_exists('exif_read_data')) {
            return [];
        }

        $tipo = @exif_imagetype($caminhoAbsoluto);
        if ($tipo !== IMAGETYPE_JPEG) {
            return [];
        }

        $dados = @exif_read_data($caminhoAbsoluto, 'EXIF,IFD0');

        return is_array($dados) ? $dados : [];
    }

    private function corrigirOrientacao(\GdImage $imagem, string $caminhoAbsoluto): \GdImage
    {
        $orientacao = (int) ($this->lerExif($caminhoAbsoluto)['Orientation'] ?? 1);
        $graus = match ($orientacao) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($graus === 0) {
            return $imagem;
        }

        return imagerotate($imagem, $graus, 0) ?: $imagem;
    }
}
