<?php

namespace Tests\Feature;

use App\Enums\TipoUsuarioEnum;
use App\Models\ApiKey;
use App\Models\Chaves;
use App\Models\Foto;
use App\Models\Pivots\UsuarioFoto;
use App\Models\TipoUsuario;
use App\Models\Usuario;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\Concerns\CriaCenarioApi;
use Tests\TestCase;

class Rodada9SegurancaApiTest extends TestCase
{
    use CriaCenarioApi;

    private function cadastro(array $extra = [])
    {
        Storage::fake('public');
        $img = $this->pngFalso();
        $chave = Chaves::create(['nome' => 'Fulano', 'codigo' => 'abcdefghijklmnopqrst']);

        return $this->post('/api/cadastrar/usuarios', array_merge([
            'nome' => 'Novo', 'email' => 'novo@teste.com', 'senha' => 'senha-123456', 'aniversario' => '1990-01-01',
            'imagem' => $img, 'chave_nome' => $chave->nome, 'chave_codigo' => $chave->codigo,
        ], $extra), $this->cabecalhos());
    }

    public function test_cadastro_ignora_papel_do_cliente_e_cria_membro_rua(): void
    {
        $adm = TipoUsuario::where('nome', TipoUsuarioEnum::MEMBRO_ADM->value)->value('id');
        $this->cadastro(['tipo_usuario_id' => $adm])->assertCreated();

        $u = Usuario::where('email', 'novo@teste.com')->firstOrFail();
        $this->assertSame(TipoUsuarioEnum::MEMBRO_RUA->value, $u->tipoUsuario->nome);
        $this->assertTrue((bool) Chaves::first()->quebra);
    }

    public function test_cadastro_chave_invalida_mantem_mensagem_especifica(): void
    {
        $this->cadastro(['chave_codigo' => 'errado'])->assertStatus(422)
            ->assertJsonPath('message', 'Chave inválida ou já utilizada.');
    }

    public function test_cadastro_tem_rate_limit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/cadastrar/usuarios', [], $this->cabecalhos())->assertStatus(422);
        }
        $this->postJson('/api/cadastrar/usuarios', [], $this->cabecalhos())->assertStatus(429);
    }

    public function test_login_tem_rate_limit_por_ip_e_email(): void
    {
        $corpo = ['login' => 'x@teste.com', 'senha' => 'errada'];
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', $corpo, $this->cabecalhos())->assertStatus(401);
        }
        $this->postJson('/api/login', $corpo, $this->cabecalhos())->assertStatus(429);
        // outro e-mail nao e afetado
        $this->postJson('/api/login', ['login' => 'y@teste.com', 'senha' => 'errada'], $this->cabecalhos())->assertStatus(401);
    }

    public function test_logout_revoga_token_e_exige_autenticacao(): void
    {
        $u = $this->criarUsuario(TipoUsuarioEnum::MEMBRO_RUA, ['email' => 'a@teste.com', 'senha' => 'senha-123456']);
        $this->postJson('/api/logout', [], $this->cabecalhos())->assertUnauthorized();

        $token = $this->postJson('/api/login', ['login' => 'a@teste.com', 'senha' => 'senha-123456'], $this->cabecalhos())
            ->assertOk()->json('token');
        $this->assertSame(1, PersonalAccessToken::count());

        $this->app['auth']->forgetGuards();
        $this->postJson('/api/logout', [], $this->cabecalhos() + ['Authorization' => "Bearer $token"])->assertOk();
        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_sanctum_tem_expiracao_configurada(): void
    {
        $this->assertSame(43200, config('sanctum.expiration'));
    }

    public function test_api_key_gerar_nao_vaza_erro_e_exige_chave_antiga(): void
    {
        $this->postJson('/api/api-keys/gerar', ['origem' => 'API', 'api_key_antiga' => 'invalida'])
            ->assertForbidden()->assertJsonPath('message', 'Operação não permitida.');
    }

    public function test_api_key_primeira_chave_bloqueada_fora_de_local(): void
    {
        ApiKey::query()->delete();
        $this->app['env'] = 'production';
        $this->postJson('/api/api-keys/gerar', ['origem' => 'API', 'api_key_antiga' => 'x'])->assertForbidden();
        $this->assertSame(0, ApiKey::count());
    }

    public function test_comando_gera_api_key(): void
    {
        ApiKey::query()->delete();
        $this->artisan('api-keys:gerar', ['origem' => 'API'])->assertExitCode(0);
        $this->assertSame(1, ApiKey::where('ativo', true)->count());
    }

    public function test_foto_perfil_rejeita_webp_e_acima_de_2mb(): void
    {
        Storage::fake('public');
        $eu = $this->logarComo();
        $webp = \Illuminate\Http\UploadedFile::fake()->create('a.webp', 10, 'image/webp');
        $this->postJson("/api/usuarios/{$eu->uuid}/foto-perfil", ['foto' => $webp], $this->cabecalhos())->assertStatus(422);
        $grande = \Illuminate\Http\UploadedFile::fake()->create('a.png', 3000, 'image/png');
        $this->postJson("/api/usuarios/{$eu->uuid}/foto-perfil", ['foto' => $grande], $this->cabecalhos())->assertStatus(422);
    }

    public function test_trocar_foto_perfil_remove_antiga_e_preserva_galeria(): void
    {
        Storage::fake('public');
        $eu = $this->logarComo();
        $galeria = $this->criarFoto();
        UsuarioFoto::create(['usuario_id' => $eu->id, 'foto_id' => $galeria->id]);

        $a = $this->pngFalso();
        $r1 = $this->postJson("/api/usuarios/{$eu->uuid}/foto-perfil", ['foto' => $a], $this->cabecalhos())->assertOk();
        $antiga = Foto::where('uuid', $r1->json('foto.uuid'))->firstOrFail();
        Storage::disk('public')->assertExists($antiga->path);

        $b = $this->pngFalso('b.png');
        $this->postJson("/api/usuarios/{$eu->uuid}/foto-perfil", ['foto' => $b], $this->cabecalhos())->assertOk();

        $this->assertSoftDeleted('fotos', ['id' => $antiga->id]);
        Storage::disk('public')->assertMissing($antiga->path);
        $this->assertNull(Foto::find($galeria->id)->deleted_at);
    }

    public function test_demo_seeder_so_roda_em_local_ou_testing(): void
    {
        $this->app['env'] = 'staging';
        $this->artisan('db:seed', ['--class' => \Database\Seeders\DemoRuaSeeder::class])->assertExitCode(0);
        $this->assertSame(0, Usuario::where('email', 'like', '%@demo.local')->count());
    }
}
