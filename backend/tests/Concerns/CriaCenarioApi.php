<?php

namespace Tests\Concerns;

use App\Enums\TipoUsuarioEnum;
use App\Models\ApiKey;
use App\Models\Foto;
use App\Models\Historia;
use App\Models\TipoHistoria;
use App\Models\TipoUsuario;
use App\Models\Usuario;
use Database\Seeders\TipoHistoriaSeeder;
use Database\Seeders\TipoImagemSeeder;
use Database\Seeders\TipoUsuarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

trait CriaCenarioApi
{
    use RefreshDatabase;

    protected string $apiKeyBruta = 'chave-de-teste';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([TipoImagemSeeder::class, TipoUsuarioSeeder::class, TipoHistoriaSeeder::class]);

        ApiKey::create([
            'nome' => 'teste',
            'hash' => hash('sha256', $this->apiKeyBruta),
            'ativo' => true,
            'origem' => 'TESTER',
        ]);
    }

    protected function cabecalhos(): array
    {
        return ['X-API-KEY' => $this->apiKeyBruta, 'Accept' => 'application/json'];
    }

    protected function criarUsuario(TipoUsuarioEnum $tipo = TipoUsuarioEnum::MEMBRO_RUA, array $extra = []): Usuario
    {
        return Usuario::create(array_merge([
            'uuid' => (string) Str::uuid(),
            'nome' => 'Usuario ' . Str::random(5),
            'email' => Str::random(8) . '@teste.com',
            'senha' => 'senha-antiga-123',
            'tipo_usuario_id' => TipoUsuario::where('nome', $tipo->value)->value('id'),
            'aniversario' => '1990-01-01',
        ], $extra));
    }

    protected function logarComo(TipoUsuarioEnum $tipo = TipoUsuarioEnum::MEMBRO_RUA): Usuario
    {
        $usuario = $this->criarUsuario($tipo);
        Sanctum::actingAs($usuario);

        return $usuario;
    }

    protected function criarHistoria(array $extra = []): Historia
    {
        return Historia::create(array_merge([
            'uuid' => (string) Str::uuid(),
            'titulo' => 'Historia ' . Str::random(4),
            'descricao_curta' => 'curta',
            'conteudo' => '<p>conteudo</p>',
            'data_historia' => '2015-06-01',
            'tipo_historia_id' => TipoHistoria::first()->id,
        ], $extra));
    }

    private ?Usuario $cadastrante = null;

    protected function usuarioCadastrante(): Usuario
    {
        return $this->cadastrante ??= $this->criarUsuario(TipoUsuarioEnum::MEMBRO_ADM);
    }

    protected function criarFoto(array $extra = []): Foto
    {
        $criadoEm = $extra['created_at'] ?? null;
        unset($extra['created_at']);

        $foto = Foto::create(array_merge([
            'uuid' => (string) Str::uuid(),
            'titulo' => 'Foto ' . Str::random(4),
            'path' => 'fotos/x.jpg',
            'tipo_imagem_id' => 3,
            'usuario_cadastrou_id' => $this->usuarioCadastrante()->id,
        ], $extra));

        if ($criadoEm) {
            $foto->forceFill(['created_at' => $criadoEm])->save();
        }

        return $foto;
    }

    /** PNG 1x1 valido (nao depende da extensao GD). */
    protected function pngFalso(string $nome = 'foto.png'): \Illuminate\Http\UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        return \Illuminate\Http\UploadedFile::fake()->createWithContent($nome, $png);
    }
}
