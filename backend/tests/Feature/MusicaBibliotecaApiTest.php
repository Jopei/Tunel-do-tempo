<?php

namespace Tests\Feature;

use App\Enums\TipoUsuarioEnum;
use App\Models\Musica;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\CriaCenarioApi;
use Tests\TestCase;

class MusicaBibliotecaApiTest extends TestCase
{
    use CriaCenarioApi;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function musica(array $extra = []): Musica
    {
        Storage::disk('local')->put('uploads/musicas/a.mp3', '0123456789');

        return Musica::create(array_merge([
            'uuid' => (string) Str::uuid(), 'titulo' => 'Faixa', 'path' => 'uploads/musicas/a.mp3',
        ], $extra));
    }

    public function test_listagem_exige_login_e_traz_url_assinada(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $capa = $this->criarFoto();
        $this->musica(['artista' => 'Banda', 'album' => 'Disco', 'duracao_segundos' => 200, 'ano' => 1999, 'capa_foto_id' => $capa->id]);

        $this->getJson('/api/musicas', $this->cabecalhos())->assertUnauthorized();

        $this->logarComo(TipoUsuarioEnum::REVISOR);
        $r = $this->getJson('/api/musicas', $this->cabecalhos())
            ->assertOk()
            ->assertJsonPath('data.0.titulo', 'Faixa')
            ->assertJsonPath('data.0.artista', 'Banda')
            ->assertJsonPath('data.0.album', 'Disco')
            ->assertJsonPath('data.0.duracao_segundos', 200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonStructure(['data' => [['uuid', 'capa_url', 'url', 'url_expira_em']]]);

        $this->assertStringContainsString('signature=', $r->json('data.0.url'));
        $this->assertStringEndsWith("/api/fotos/{$capa->uuid}", $r->json('data.0.capa_url'));
    }

    public function test_busca_e_ordenacao(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->musica(['titulo' => 'Zeta', 'artista' => 'Ana']);
        $this->musica(['titulo' => 'Alfa', 'artista' => 'Zeca']);
        $this->logarComo();

        $this->getJson('/api/musicas?ordem=titulo', $this->cabecalhos())->assertJsonPath('data.0.titulo', 'Alfa');
        $this->getJson('/api/musicas?ordem=artista', $this->cabecalhos())->assertJsonPath('data.0.titulo', 'Zeta');
        $this->getJson('/api/musicas?q=zec', $this->cabecalhos())->assertJsonCount(1, 'data')->assertJsonPath('data.0.titulo', 'Alfa');
        $this->getJson('/api/musicas?ordem=xx', $this->cabecalhos())->assertStatus(422);
        $this->getJson('/api/musicas?per_page=1', $this->cabecalhos())->assertJsonPath('meta.per_page', 1)->assertJsonCount(1, 'data');
    }

    public function test_url_assinada_funciona_sem_api_key_nem_login_e_suporta_range(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->musica();
        $this->logarComo();
        $url = $this->getJson('/api/musicas', $this->cabecalhos())->json('data.0.url');

        // cliente sem credenciais (como um <audio src>)
        $this->app['auth']->forgetGuards();
        $completo = $this->get($url);
        $completo->assertOk();
        $completo->assertHeader('X-Musica-UUID');
        $this->assertSame('0123456789', file_get_contents($completo->baseResponse->getFile()->getPathname()));

        $parcial = $this->get($url, ['Range' => 'bytes=2-5']);
        $parcial->assertStatus(206);
        $parcial->assertHeader('Content-Range', 'bytes 2-5/10');
    }

    public function test_url_expirada_ou_adulterada_retorna_403(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $musica = $this->musica();
        $this->logarComo();
        $url = $this->getJson('/api/musicas', $this->cabecalhos())->json('data.0.url');

        $this->get($url . 'x')->assertForbidden();
        $this->get("/api/musicas/{$musica->uuid}/stream")->assertForbidden();

        Carbon::setTestNow(now()->addHours(2)->addMinute());
        $this->get($url)->assertForbidden();
    }

    public function test_stream_de_musica_inexistente_ou_sem_arquivo(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $semArquivo = Musica::create(['uuid' => (string) Str::uuid(), 'titulo' => 'S', 'path' => 'uploads/musicas/nao-existe.mp3']);
        $this->logarComo();
        $url = $this->getJson('/api/musicas', $this->cabecalhos())->json('data.0.url');

        $this->get($url)->assertNotFound();

        $fantasma = \Illuminate\Support\Facades\URL::temporarySignedRoute('api.musicas.stream', now()->addHour(), ['uuid' => (string) Str::uuid()]);
        $this->get($fantasma)->assertNotFound();
        $this->assertNotNull($semArquivo);
    }

    public function test_update_e_delete_apenas_para_membros(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $musica = $this->musica();
        $capa = $this->criarFoto();

        $this->putJson("/api/musicas/{$musica->uuid}", ['titulo' => 'N'], $this->cabecalhos())->assertUnauthorized();
        $this->deleteJson("/api/musicas/{$musica->uuid}", [], $this->cabecalhos())->assertUnauthorized();

        $this->logarComo(TipoUsuarioEnum::REVISOR);
        $this->putJson("/api/musicas/{$musica->uuid}", ['titulo' => 'N'], $this->cabecalhos())->assertForbidden();
        $this->deleteJson("/api/musicas/{$musica->uuid}", [], $this->cabecalhos())->assertForbidden();

        $this->logarComo(TipoUsuarioEnum::MEMBRO_RUA);
        $this->putJson("/api/musicas/{$musica->uuid}", [
            'titulo' => 'Nova', 'artista' => 'Eu', 'album' => 'Meu', 'ano' => 2001, 'duracao_segundos' => 120, 'capa_foto_uuid' => $capa->uuid,
        ], $this->cabecalhos())
            ->assertOk()->assertJsonPath('data.titulo', 'Nova')->assertJsonPath('data.artista', 'Eu')->assertJsonPath('data.ano', 2001);
        $this->assertSame($capa->id, $musica->fresh()->capa_foto_id);

        $this->putJson("/api/musicas/{$musica->uuid}", ['capa_foto_uuid' => null], $this->cabecalhos())->assertOk();
        $this->assertNull($musica->fresh()->capa_foto_id);
        $this->assertSame('Nova', $musica->fresh()->titulo);

        $this->putJson("/api/musicas/{$musica->uuid}", ['ano' => 1800], $this->cabecalhos())->assertStatus(422);
        $this->putJson('/api/musicas/' . Str::uuid(), ['titulo' => 'x'], $this->cabecalhos())->assertNotFound();

        $this->deleteJson("/api/musicas/{$musica->uuid}", [], $this->cabecalhos())->assertOk();
        $this->assertSoftDeleted($musica);
        $this->deleteJson('/api/musicas/' . Str::uuid(), [], $this->cabecalhos())->assertNotFound();
    }

    public function test_store_aceita_artista_album_e_capa(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->logarComo(TipoUsuarioEnum::MEMBRO_RUA);
        $audio = UploadedFile::fake()->createWithContent('a.mp3', "ID3\x03\x00\x00\x00\x00\x00\x00" . str_repeat("\xFF\xFB\x90\x00", 300));

        $this->postJson('/api/cadastrar/musicas', [
            'titulo' => 'Nova', 'musica' => $audio, 'artista' => 'Banda', 'album' => 'Disco', 'ano' => 1990,
            'capa' => $this->pngFalso('capa.png'),
        ], $this->cabecalhos())->assertCreated();

        $musica = Musica::firstOrFail();
        $this->assertSame('Banda', $musica->artista);
        $this->assertSame('Disco', $musica->album);
        $this->assertNotNull($musica->capa_foto_id);
        $this->assertNotNull($musica->capa);
    }
}
