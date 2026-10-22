<?php

namespace App\Console\Commands;

use App\Enums\TipoUsuarioEnum;
use App\Mapping\TipoUsuarioMapping;
use App\Mapping\UsuarioMapping;
use App\Models\Usuario;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GerarSlugsMembros extends Command
{
    protected $signature = 'membros:gerar-slugs';

    protected $description = 'Gera o slug (URL /membros/{slug}) dos membros sem slug, a partir do nome. Nunca sobrescreve slugs existentes.';

    public function handle(): int
    {
        $gerados = 0;

        Usuario::query()
            ->whereNull(UsuarioMapping::SLUG)
            ->whereHas('tipoUsuario', fn ($q) => $q->whereIn(TipoUsuarioMapping::NOME, [
                TipoUsuarioEnum::MEMBRO_ADM->value,
                TipoUsuarioEnum::MEMBRO_RUA->value,
            ]))
            ->orderBy('id')
            ->get()
            ->each(function (Usuario $usuario) use (&$gerados) {
                $slug = $this->slugLivre($usuario->nome);
                $usuario->forceFill([UsuarioMapping::SLUG => $slug])->save();
                $this->line("{$usuario->nome} -> {$slug}");
                $gerados++;
            });

        $this->info("Slugs gerados: {$gerados}.");

        return self::SUCCESS;
    }

    private function slugLivre(string $nome): string
    {
        $base = Str::limit(Str::slug($nome) ?: 'membro', 50, '');
        $slug = $base;
        $n = 2;

        // withTrashed: o unique do banco considera tambem usuarios excluidos
        while (Usuario::withTrashed()->where(UsuarioMapping::SLUG, $slug)->exists()) {
            $slug = $base . '-' . $n++;
        }

        return $slug;
    }
}
