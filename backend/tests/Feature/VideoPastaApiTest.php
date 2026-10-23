<?php

namespace Tests\Feature;

use App\Enums\TipoUsuarioEnum;
use App\Models\Video;
use App\Models\VideoPasta;
use Illuminate\Support\Str;
use Tests\Concerns\CriaCenarioApi;
use Tests\TestCase;

class VideoPastaApiTest extends TestCase
{
    use CriaCenarioApi;

    private function pasta(string $nome, ?VideoPasta $pai = null): VideoPasta
    {
        return VideoPasta::create([
            'uuid' => (string) Str::uuid(), 'nome' => $nome, 'pai_id' => $pai?->id,
            'usuario_cadastrou_id' => $this->usuarioCadastrante()->id,
        ]);
    }

    private function video(?VideoPasta $pasta = null, string $titulo = 'V'): Video
    {
        return Video::create([
            'uuid' => (string) Str::uuid(), 'titulo' => $titulo, 'descricao' => 'd', 'path' => 'x.mp4',
            'thumbnail' => null, 'external_link' => 0, 'usuario_cadastrou_id' => $this->usuarioCadastrante()->id,
            'pasta_id' => $pasta?->id, 'duracao_segundos' => 90,
        ]);
    }

    public function test_autorizacao_por_papel(): void
    {
        $pasta = $this->pasta('A');

        $this->getJson('/api/video-pastas', $this->cabecalhos())->assertUnauthorized();
        $this->postJson('/api/video-pastas', ['nome' => 'X'], $this->cabecalhos())->assertUnauthorized();

        $this->logarComo(TipoUsuarioEnum::REVISOR);
        $this->getJson('/api/video-pastas', $this->cabecalhos())->assertOk();
        $this->postJson('/api/video-pastas', ['nome' => 'X'], $this->cabecalhos())->assertForbidden();
        $this->putJson("/api/video-pastas/{$pasta->uuid}", ['nome' => 'X'], $this->cabecalhos())->assertForbidden();
        $this->deleteJson("/api/video-pastas/{$pasta->uuid}", [], $this->cabecalhos())->assertForbidden();
        $this->patchJson('/api/videos/' . Str::uuid() . '/pasta', ['pasta_uuid' => null], $this->cabecalhos())->assertForbidden();
    }

    public function test_listagem_com_caminho_e_contagem(): void
    {
        $this->logarComo(TipoUsuarioEnum::MEMBRO_RUA);
        $a = $this->pasta('Familia');
        $b = $this->pasta('2020', $a);
        $this->pasta('Aniversarios', $b);
        $this->pasta('Outra');
        $this->video($b);
        $this->video($b)->delete();

        $this->getJson('/api/video-pastas', $this->cabecalhos())
            ->assertOk()
            ->assertJsonPath('pasta_atual', null)
            ->assertJsonPath('caminho', [])
            ->assertJsonCount(2, 'pastas')
            ->assertJsonPath('pastas.0.nome', 'Familia')
            ->assertJsonPath('pastas.0.videos_count', 0);

        $this->getJson("/api/video-pastas?pai_uuid={$b->uuid}", $this->cabecalhos())
            ->assertOk()
            ->assertJsonPath('pasta_atual.uuid', $b->uuid)
            ->assertJsonPath('caminho', [['uuid' => $a->uuid, 'nome' => 'Familia'], ['uuid' => $b->uuid, 'nome' => '2020']])
            ->assertJsonCount(1, 'pastas')
            ->assertJsonPath('pastas.0.nome', 'Aniversarios');

        $this->getJson("/api/video-pastas?pai_uuid={$a->uuid}", $this->cabecalhos())->assertJsonPath('pastas.0.videos_count', 1);
        $this->getJson('/api/video-pastas?pai_uuid=' . Str::uuid(), $this->cabecalhos())->assertNotFound();
        $this->getJson('/api/video-pastas?pai_uuid=abc', $this->cabecalhos())->assertStatus(422);
    }

    public function test_criar_pasta_e_nome_duplicado_inclusive_na_raiz(): void
    {
        $this->logarComo(TipoUsuarioEnum::MEMBRO_ADM);

        $r = $this->postJson('/api/video-pastas', ['nome' => '  Natal '], $this->cabecalhos())
            ->assertCreated()->assertJsonPath('data.nome', 'Natal')->assertJsonPath('data.pai_uuid', null);

        $this->postJson('/api/video-pastas', ['nome' => 'Natal'], $this->cabecalhos())
            ->assertStatus(422)->assertJsonValidationErrors('nome');

        $filha = $this->postJson('/api/video-pastas', ['nome' => 'Natal', 'pai_uuid' => $r->json('data.uuid')], $this->cabecalhos())
            ->assertCreated();
        $this->assertSame($r->json('data.uuid'), $filha->json('data.pai_uuid'));

        $this->postJson('/api/video-pastas', ['nome' => 'Natal', 'pai_uuid' => $r->json('data.uuid')], $this->cabecalhos())->assertStatus(422);
        $this->postJson('/api/video-pastas', [], $this->cabecalhos())->assertStatus(422)->assertJsonValidationErrors('nome');
        $this->postJson('/api/video-pastas', ['nome' => 'Y', 'pai_uuid' => (string) Str::uuid()], $this->cabecalhos())
            ->assertStatus(422)->assertJsonValidationErrors('pai_uuid');
    }

    public function test_renomear_e_mover_com_validacao_de_ciclo(): void
    {
        $this->logarComo(TipoUsuarioEnum::MEMBRO_RUA);
        $a = $this->pasta('A');
        $b = $this->pasta('B', $a);
        $c = $this->pasta('C', $b);
        $raizB = $this->pasta('B');

        $this->putJson("/api/video-pastas/{$a->uuid}", ['pai_uuid' => $a->uuid], $this->cabecalhos())->assertStatus(422)->assertJsonValidationErrors('pai_uuid');
        $this->putJson("/api/video-pastas/{$a->uuid}", ['pai_uuid' => $c->uuid], $this->cabecalhos())->assertStatus(422)->assertJsonValidationErrors('pai_uuid');
        $this->putJson("/api/video-pastas/{$a->uuid}", ['nome' => 'B'], $this->cabecalhos())->assertStatus(422)->assertJsonValidationErrors('nome');
        // mover B (filha de A) para a raiz colide com o B da raiz
        $this->putJson("/api/video-pastas/{$b->uuid}", ['pai_uuid' => null], $this->cabecalhos())->assertStatus(422)->assertJsonValidationErrors('nome');

        $this->putJson("/api/video-pastas/{$a->uuid}", ['nome' => 'A2'], $this->cabecalhos())->assertOk()->assertJsonPath('data.nome', 'A2');
        $this->putJson("/api/video-pastas/{$c->uuid}", ['pai_uuid' => null], $this->cabecalhos())->assertOk()->assertJsonPath('data.pai_uuid', null);
        $this->putJson("/api/video-pastas/{$raizB->uuid}", ['pai_uuid' => $a->uuid], $this->cabecalhos())->assertStatus(422);
        $this->putJson('/api/video-pastas/' . Str::uuid(), ['nome' => 'Z'], $this->cabecalhos())->assertNotFound();
    }

    public function test_excluir_pasta_nao_vazia_retorna_409(): void
    {
        $this->logarComo(TipoUsuarioEnum::MEMBRO_RUA);
        $comVideo = $this->pasta('ComVideo');
        $comFilha = $this->pasta('ComFilha');
        $this->pasta('Filha', $comFilha);
        $vazia = $this->pasta('Vazia');
        $this->video($comVideo);

        $this->deleteJson("/api/video-pastas/{$comVideo->uuid}", [], $this->cabecalhos())->assertStatus(409);
        $this->deleteJson("/api/video-pastas/{$comFilha->uuid}", [], $this->cabecalhos())->assertStatus(409);
        $this->deleteJson("/api/video-pastas/{$vazia->uuid}", [], $this->cabecalhos())->assertOk();
        $this->deleteJson("/api/video-pastas/{$vazia->uuid}", [], $this->cabecalhos())->assertNotFound();

        // recriar o mesmo nome apos excluir nao viola o unique
        $this->postJson('/api/video-pastas', ['nome' => 'Vazia'], $this->cabecalhos())->assertCreated();
    }

    public function test_mover_video_e_filtros_da_listagem(): void
    {
        $this->logarComo(TipoUsuarioEnum::MEMBRO_RUA);
        $pasta = $this->pasta('P');
        $dentro = $this->video(null, 'Dentro');
        $fora = $this->video(null, 'Fora');

        $this->patchJson("/api/videos/{$dentro->uuid}/pasta", ['pasta_uuid' => $pasta->uuid], $this->cabecalhos())
            ->assertOk()->assertJsonPath('pasta_uuid', $pasta->uuid);
        $this->assertSame($pasta->id, $dentro->fresh()->pasta_id);

        $this->patchJson("/api/videos/{$dentro->uuid}/pasta", [], $this->cabecalhos())->assertStatus(422);
        $this->patchJson("/api/videos/{$dentro->uuid}/pasta", ['pasta_uuid' => (string) Str::uuid()], $this->cabecalhos())->assertStatus(422);
        $this->patchJson('/api/videos/' . Str::uuid() . '/pasta', ['pasta_uuid' => null], $this->cabecalhos())->assertNotFound();

        // sem filtro: todos (comportamento anterior), com campos novos
        $this->getJson('/api/videos', $this->cabecalhos())->assertOk()->assertJsonCount(2, 'data')->assertJsonStructure(['data' => [['pasta_uuid', 'duracao_segundos']]]);
        $this->getJson("/api/videos?pasta_uuid={$pasta->uuid}", $this->cabecalhos())
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.uuid', $dentro->uuid)->assertJsonPath('data.0.pasta_uuid', $pasta->uuid);
        $this->getJson('/api/videos?pasta_uuid=raiz', $this->cabecalhos())
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.uuid', $fora->uuid)->assertJsonPath('data.0.pasta_uuid', null);
        $this->getJson("/api/videos?pasta_uuid={$pasta->uuid}&todas=1", $this->cabecalhos())->assertJsonCount(2, 'data');
        $this->getJson('/api/videos?pasta_uuid=' . Str::uuid(), $this->cabecalhos())->assertStatus(422);

        $this->patchJson("/api/videos/{$dentro->uuid}/pasta", ['pasta_uuid' => null], $this->cabecalhos())->assertOk()->assertJsonPath('pasta_uuid', null);
        $this->assertNull($dentro->fresh()->pasta_id);
    }
}
