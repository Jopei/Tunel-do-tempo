<?php

namespace Database\Seeders;

use App\Enums\TipoHistoriaEnum;
use App\Enums\TipoImagemEnum;
use App\Enums\TipoUsuarioEnum;
use App\Mapping\FotoMapping;
use App\Mapping\HistoriaMapping;
use App\Mapping\TipoHistoriaMapping;
use App\Mapping\TipoImagemMapping;
use App\Mapping\TipoUsuarioMapping;
use App\Mapping\UsuarioMapping;
use App\Models\Foto;
use App\Models\Historia;
use App\Models\TipoHistoria;
use App\Models\TipoImagem;
use App\Models\TipoUsuario;
use App\Models\Usuario;
use App\Services\FotoService;
use App\Support\Sanitizador;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

/**
 * Dados de DEMONSTRACAO (apenas ambiente local).
 *
 *   php artisan db:seed --class=DemoRuaSeeder                 (cria/atualiza, idempotente)
 *   DEMO_REMOVER=1 php artisan db:seed --class=DemoRuaSeeder  (remove tudo que o seeder criou)
 *
 * Marcadores: membros com e-mail @demo.local; historias e fotos com UUID v5 deterministico
 * (namespace abaixo). Nunca altera usuarios reais.
 */
class DemoRuaSeeder extends Seeder
{
    private const NS = '6f1d2c3a-5b7e-4a90-9c11-d3a0de0a0001';
    private const DOMINIO = '@demo.local';
    private const SENHA = 'Local2026dev';

    // Os 13 membros reais da Rua: Joao Pedro (id 8, conta real) e Italo (id 2) ficam fora desta lista.
    private const MEMBROS = [
        'Gabriel', 'João Gabriel', 'João Vitor', 'Igor', 'Ruan', 'Moises', 'Daniel', 'Pedro', 'Breno', 'Vitor', 'Matheus',
    ];
    private const ID_ITALO = 2;          // antigo "Joao Pedro" sem e-mail (Membro ADM), renomeado so no local
    private const EMAIL_JOAO_PEDRO = 'joaomoura.269@gmail.com'; // conta real: so o slug e ajustado

    private const TEMAS = [
        'Tarde de futebol na rua', 'Churrasco na calcada', 'Noite de videogame', 'Campeonato de pipa',
        'Aniversario na garagem', 'Viagem para a praia', 'Festa junina da rua', 'Bicicleta ate a represa',
        'Pelada de fim de ano', 'Acampamento no quintal', 'Show na pracinha', 'Natal da turma',
        'Reencontro depois da escola', 'Ultimo verao na rua', 'Mudanca de bairro', 'Despedida da turma',
    ];

    public function run(): void
    {
        if (!app()->environment(['local', 'testing'])) {
            $this->command?->error('DemoRuaSeeder recusado: somente local/testing.');

            return;
        }

        DB::transaction(function () {
            filter_var(getenv('DEMO_REMOVER'), FILTER_VALIDATE_BOOL) ? $this->remover() : $this->criar();
        });
    }

    private function uuid(string $chave): string
    {
        return Uuid::uuid5(self::NS, $chave)->toString();
    }

    private function criar(): void
    {
        $tipoMembro = TipoUsuario::where(TipoUsuarioMapping::NOME, TipoUsuarioEnum::MEMBRO_RUA->value)->firstOrFail();
        $demo = [];

        foreach (self::MEMBROS as $i => $nome) {
            $email = Str::slug($nome, '.') . self::DOMINIO;
            // o UUID v5 deterministico identifica o registro demo mesmo apos renomear (migra em vez de duplicar)
            $usuario = Usuario::withTrashed()->where(UsuarioMapping::UUID, $this->uuid("usuario-$i"))->first()
                ?? Usuario::withTrashed()->where(UsuarioMapping::EMAIL, $email)->first()
                ?? Usuario::create([
                    UsuarioMapping::UUID => $this->uuid("usuario-$i"),
                    UsuarioMapping::NOME => $nome,
                    UsuarioMapping::EMAIL => $email,
                    UsuarioMapping::SENHA => self::SENHA,
                    UsuarioMapping::TIPO_USUARIO_ID => $tipoMembro->getKey(),
                    UsuarioMapping::ANIVERSARIO => now()->setDate(1995 + ($i % 4), ($i % 12) + 1, ($i * 2 % 27) + 1)->toDateString(),
                    UsuarioMapping::DESCRICAO => 'Membro de demonstracao.',
                ]);

            if ($usuario->trashed()) {
                $usuario->restore();
            }
            $this->sincronizarDemo($usuario, $nome, $email);
            $demo[] = $usuario;
        }

        $this->ajustarContasLocais();
        $this->removerAvataresDemo();

        $pool = collect($demo)->pluck('id')->all();
        $this->historias($pool, $demo[0]->id);
        $this->marcarPessoas($pool);

        $this->command?->info('Demo criada: ' . count($demo) . ' membros demo, ' . count($this->planoHistorias()) . ' historias.');
    }

    /** Marca 1-2 membros demo em cada foto de galeria demo (determinístico; syncWithoutDetaching = idempotente). */
    private function marcarPessoas(array $pool): void
    {
        foreach ($this->planoHistorias() as $h) {
            for ($f = 0; $f < 4; $f++) {
                $foto = Foto::where(FotoMapping::UUID, $this->uuid("galeria-{$h['k']}-$f"))->first();
                if (!$foto) {
                    continue;
                }
                $ids = collect($pool)->sortBy(fn ($id) => crc32($foto->uuid . $id))->take(1 + ($h['k'] + $f) % 2)->values()->all();
                $foto->usuarios()->syncWithoutDetaching($ids);
            }
        }
    }

    /** Renomeia/ajusta slug e e-mail de um membro demo ja existente (idempotente). */
    private function sincronizarDemo(Usuario $usuario, string $nome, string $email): void
    {
        $slug = Str::slug($nome);
        $dados = [];
        if ($usuario->nome !== $nome) {
            $dados[UsuarioMapping::NOME] = $nome;
        }
        if ($usuario->email !== $email) {
            $dados[UsuarioMapping::EMAIL] = $email;
        }
        if ($usuario->{UsuarioMapping::SLUG} !== $slug) {
            $dados[UsuarioMapping::SLUG] = $this->slugLivre($nome, $usuario->id);
        }
        if ($dados) {
            $usuario->forceFill($dados)->save();
        }
    }

    /** Id 2 vira "Italo" (slug italo) e a conta real id 8 assume o slug joao-pedro. So local; nao toca nome/e-mail/senha do id 8. */
    private function ajustarContasLocais(): void
    {
        $italo = Usuario::find(self::ID_ITALO);
        if ($italo && !$italo->email) {
            $italo->forceFill([UsuarioMapping::NOME => 'Italo', UsuarioMapping::SLUG => 'italo'])->save();
        }

        $real = Usuario::where(UsuarioMapping::EMAIL, self::EMAIL_JOAO_PEDRO)->first();
        if ($real && $real->{UsuarioMapping::SLUG} !== 'joao-pedro'
            && !Usuario::withTrashed()->where(UsuarioMapping::SLUG, 'joao-pedro')->where('id', '!=', $real->id)->exists()) {
            $real->forceFill([UsuarioMapping::SLUG => 'joao-pedro'])->save();
        }
    }

    private function slugLivre(string $nome, ?int $ignorarId = null): string
    {
        $base = Str::limit(Str::slug($nome) ?: 'membro', 50, '');
        $slug = $base;
        $n = 2;
        while (Usuario::withTrashed()->where(UsuarioMapping::SLUG, $slug)->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))->exists()) {
            $slug = $base . '-' . $n++;
        }

        return $slug;
    }

    /** Avatares demo foram descontinuados (o frontend renderiza iniciais). Remove registros, vinculos e arquivos; nunca toca fotos nao-demo. */
    private function removerAvataresDemo(): void
    {
        $disco = Storage::disk('public');
        foreach (Foto::withTrashed()->where(FotoMapping::PATH, 'like', 'uploads/fotos/perfil/demo-%')->get() as $foto) {
            if (!$this->ehDemoFoto($foto)) {
                continue;
            }
            $foto->usuarios()->detach();
            $foto->historias()->detach();
            $disco->delete(array_filter([$foto->path, $foto->thumbnail_path]));
            $foto->forceDelete();
        }
    }

    private function tipoImagem(string $nome): int
    {
        return TipoImagem::where(TipoImagemMapping::NOME, $nome)->firstOrFail()->getKey();
    }

    private function planoHistorias(): array
    {
        $plano = [['k' => 0, 'ano' => 2009, 'tipo' => TipoHistoriaEnum::PARALELA, 'titulo' => 'Antes da Rua']];
        $n = 1;
        foreach (range(2011, 2018) as $ano) {
            foreach ([TipoHistoriaEnum::PRINCIPAL, TipoHistoriaEnum::PARALELA] as $j => $tipo) {
                $plano[] = ['k' => $n, 'ano' => $ano, 'tipo' => $tipo, 'titulo' => self::TEMAS[$n - 1] . " ($ano)"];
                $n++;
            }
        }
        $plano[] = ['k' => $n, 'ano' => 2020, 'tipo' => TipoHistoriaEnum::PARALELA, 'titulo' => 'Depois da Rua'];

        return $plano;
    }

    private function historias(array $pool, int $autorId): void
    {
        $tipos = TipoHistoria::pluck(TipoHistoriaMapping::ID, TipoHistoriaMapping::NOME);

        foreach ($this->planoHistorias() as $h) {
            $uuid = $this->uuid('historia-' . $h['k']);
            $existente = Historia::withTrashed()->where(HistoriaMapping::UUID, $uuid)->first();
            if ($existente) {
                $existente->trashed() && $existente->restore();
                continue;
            }

            $k = $h['k'];
            $mes = ($k * 5 % 12) + 1;
            $data = sprintf('%d-%02d-%02d', $h['ano'], $mes, ($k * 3 % 27) + 1);
            $historia = Historia::create([
                HistoriaMapping::UUID => $uuid,
                HistoriaMapping::TITULO => $h['titulo'],
                HistoriaMapping::DESCRICAO_CURTA => 'Lembranca de demonstracao: ' . Str::lower($h['titulo']) . '.',
                HistoriaMapping::CONTEUDO => Sanitizador::html(
                    "<p>Era <strong>{$h['ano']}</strong> e a turma da rua se juntou de novo.</p>"
                    . '<p>Historia ficticia criada para demonstracao da linha do tempo.</p>'
                    . '<ul><li>Risadas</li><li>Bola rolando</li><li>Muita conversa</li></ul>'
                ),
                HistoriaMapping::DATA_HISTORIA => $data,
                HistoriaMapping::TIPO_HISTORIA_ID => $tipos[$h['tipo']->value],
            ]);

            $qtd = 2 + ($k % 3);
            $membros = collect($pool)->sortBy(fn ($id) => crc32($uuid . $id))->take($qtd)->values()->all();
            $historia->usuarios()->attach($membros);

            $nFotos = $k % 4 === 0 ? 0 : ($k % 3) + 1;
            for ($f = 0; $f < $nFotos; $f++) {
                $foto = $this->fotoGaleria($h, $f, $data, $autorId, $k);
                $historia->fotos()->attach($foto->id);
            }
        }
    }

    private function fotoGaleria(array $h, int $f, string $data, int $autorId, int $k): Foto
    {
        $uuid = $this->uuid("galeria-{$h['k']}-$f");
        $path = "uploads/fotos/demo-$uuid.png";
        $this->salvarImagem($path, $this->paisagem($h['ano'], $h['titulo'], $k * 37 + $f * 90));

        $meta = app(FotoService::class)->processarImagem($path);

        return Foto::create([
            FotoMapping::UUID => $uuid,
            FotoMapping::TITULO => $h['titulo'] . ' #' . ($f + 1),
            FotoMapping::DESCRICAO => 'demo',
            FotoMapping::PATH => $path,
            FotoMapping::TIPO_IMAGEM_ID => $this->tipoImagem(TipoImagemEnum::GALERIA->value),
            FotoMapping::USUARIO_CADASTROU_ID => $autorId,
            FotoMapping::EXTERNAL_LINK => 0,
            FotoMapping::DATA_FOTO => $data,
            FotoMapping::LARGURA => $meta['largura'],
            FotoMapping::ALTURA => $meta['altura'],
            FotoMapping::THUMBNAIL_PATH => $meta['thumbnail_path'],
        ]);
    }

    private function salvarImagem(string $path, \GdImage $img): void
    {
        ob_start();
        imagepng($img);
        $bin = ob_get_clean();
        imagedestroy($img);
        Storage::disk('public')->put($path, $bin);
    }

    private function paisagem(int $ano, string $titulo, int $matiz): \GdImage
    {
        $w = 1200;
        $h = 800;
        $img = imagecreatetruecolor($w, $h);
        [$r, $g, $b] = $this->hsl($matiz % 360);
        for ($y = 0; $y < $h; $y++) {
            $t = $y / $h;
            $c = imagecolorallocate($img, (int) ($r * (1 - $t * .6)), (int) ($g * (1 - $t * .6)), (int) ($b * (1 - $t * .6)));
            imageline($img, 0, $y, $w, $y, $c);
        }
        imagefilledellipse($img, 900, 220, 160, 160, imagecolorallocate($img, 255, 236, 170));
        imagefilledpolygon($img, [0, $h, 300, 480, 620, $h], imagecolorallocate($img, 30, 60, 50));
        imagefilledpolygon($img, [350, $h, 800, 400, 1200, $h], imagecolorallocate($img, 20, 45, 40));
        $this->texto($img, (string) $ano, $w, $h, 10);

        return $img;
    }

    private function texto(\GdImage $img, string $txt, int $w, int $h, int $escala): void
    {
        // fonte bitmap embutida (5) ampliada por redimensionamento, sem depender de TTF
        $fw = imagefontwidth(5) * strlen($txt);
        $fh = imagefontheight(5);
        $tmp = imagecreatetruecolor($fw, $fh);
        imagesavealpha($tmp, true);
        imagealphablending($tmp, false);
        imagefill($tmp, 0, 0, imagecolorallocatealpha($tmp, 0, 0, 0, 127));
        imagestring($tmp, 5, 0, 0, $txt, imagecolorallocate($tmp, 255, 255, 255));
        $dw = $fw * $escala;
        $dh = $fh * $escala;
        imagealphablending($img, true);
        imagecopyresampled($img, $tmp, intdiv($w - $dw, 2), intdiv($h - $dh, 2), 0, 0, $dw, $dh, $fw, $fh);
        imagedestroy($tmp);
    }

    private function hsl(int $h): array
    {
        $f = fn ($n) => (int) (255 * (.5 + .35 * cos(deg2rad($h - $n * 120))));

        return [$f(0), $f(1), $f(2)];
    }

    private function remover(): void
    {
        $disco = Storage::disk('public');

        $historias = Historia::withTrashed()->get()->filter(fn ($x) => $this->ehDemo($x->uuid, 'historia-'));
        foreach ($historias as $hist) {
            $hist->usuarios()->detach();
            $hist->fotos()->detach();
            $hist->forceDelete();
        }

        foreach (Foto::withTrashed()->get() as $foto) {
            if (!$this->ehDemoFoto($foto)) {
                continue;
            }
            $foto->usuarios()->detach();
            $foto->historias()->detach();
            $disco->delete(array_filter([$foto->path, $foto->thumbnail_path]));
            $foto->forceDelete();
        }

        $usuarios = Usuario::withTrashed()->where(UsuarioMapping::EMAIL, 'like', '%' . self::DOMINIO)->get();
        foreach ($usuarios as $u) {
            $u->fotos()->detach();
            $u->forceDelete();
        }

        $this->command?->info('Demo removida.');
    }

    private function ehDemo(string $uuid, string $prefixo): bool
    {
        for ($k = 0; $k <= 40; $k++) {
            if ($uuid === $this->uuid($prefixo . $k)) {
                return true;
            }
        }

        return false;
    }

    private function ehDemoFoto(Foto $foto): bool
    {
        return str_contains($foto->path, 'demo-' . $foto->uuid . '.');
    }
}
