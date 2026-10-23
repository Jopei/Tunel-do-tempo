<?php

namespace Tests\Feature;

use App\Enums\TipoUsuarioEnum;
use App\Models\Album;
use Illuminate\Support\Str;
use Tests\Concerns\CriaCenarioApi;
use Tests\TestCase;

class AlbumApiTest extends TestCase
{
    use CriaCenarioApi;

    public function test_autorizacao_por_papel(): void
    {
        $this->getJson('/api/albuns', $this->cabecalhos())->assertUnauthorized();
        $this->postJson('/api/albuns', ['titulo' => 'X'], $this->cabecalhos())->assertUnauthorized();

        $this->logarComo(TipoUsuarioEnum::REVISOR);
        $this->getJson('/api/albuns', $this->cabecalhos())->assertOk();
        $this->postJson('/api/albuns', ['titulo' => 'X'], $this->cabecalhos())->assertForbidden();
    }

    public function test_crud_completo(): void
    {
        $this->logarComo(TipoUsuarioEnum::MEMBRO_RUA);
        $f1 = $this->criarFoto();
        $f2 = $this->criarFoto();

        $criado = $this->postJson('/api/albuns', [
            'titulo' => 'Viagem', 'descricao' => 'Praia', 'capa_foto_uuid' => $f1->uuid, 'fotos' => [$f1->uuid, $f2->uuid],
        ], $this->cabecalhos())
            ->assertCreated()
            ->assertJsonPath('data.titulo', 'Viagem')
            ->assertJsonPath('data.fotos_count', 2)
            ->assertJsonPath('data.capa.uuid', $f1->uuid);
        $uuid = $criado->json('data.uuid');

        $this->getJson("/api/albuns/{$uuid}", $this->cabecalhos())->assertOk()->assertJsonPath('data.descricao', 'Praia');
        $this->getJson('/api/albuns?q=Viag', $this->cabecalhos())->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('meta.total', 1);

        $this->putJson("/api/albuns/{$uuid}", ['titulo' => 'Férias', 'capa_foto_uuid' => null], $this->cabecalhos())
            ->assertOk()->assertJsonPath('data.titulo', 'Férias');
        $this->assertNull(Album::first()->capa_foto_id);
        $this->assertSame('Praia', Album::first()->descricao);

        $this->deleteJson("/api/albuns/{$uuid}", [], $this->cabecalhos())->assertOk();
        $this->assertSoftDeleted('albuns', ['uuid' => $uuid]);
        $this->getJson("/api/albuns/{$uuid}", $this->cabecalhos())->assertNotFound();
    }

    public function test_validacao_e_404(): void
    {
        $this->logarComo(TipoUsuarioEnum::MEMBRO_ADM);

        $this->postJson('/api/albuns', [], $this->cabecalhos())->assertStatus(422)->assertJsonValidationErrors('titulo');
        $this->postJson('/api/albuns', ['titulo' => 'X', 'fotos' => [(string) Str::uuid()]], $this->cabecalhos())
            ->assertStatus(422)->assertJsonValidationErrors('fotos.0');
        $this->putJson('/api/albuns/' . Str::uuid(), ['titulo' => 'X'], $this->cabecalhos())->assertNotFound();
        $this->deleteJson('/api/albuns/' . Str::uuid(), [], $this->cabecalhos())->assertNotFound();
        $this->getJson('/api/albuns/' . Str::uuid(), $this->cabecalhos())->assertNotFound();
    }

    public function test_adicionar_e_remover_fotos_sao_idempotentes(): void
    {
        $this->logarComo(TipoUsuarioEnum::MEMBRO_RUA);
        $f1 = $this->criarFoto();
        $f2 = $this->criarFoto();
        $uuid = $this->postJson('/api/albuns', ['titulo' => 'A', 'capa_foto_uuid' => $f1->uuid], $this->cabecalhos())->json('data.uuid');

        $this->postJson("/api/albuns/{$uuid}/fotos", ['fotos' => [$f1->uuid, $f2->uuid]], $this->cabecalhos())
            ->assertOk()->assertJsonPath('data.fotos_count', 2);
        $this->postJson("/api/albuns/{$uuid}/fotos", ['fotos' => [$f1->uuid]], $this->cabecalhos())
            ->assertOk()->assertJsonPath('data.fotos_count', 2);

        $this->assertSame([1, 2], Album::first()->fotos()->pluck('album_fotos.ordem')->all());

        $this->deleteJson("/api/albuns/{$uuid}/fotos", ['fotos' => [$f1->uuid]], $this->cabecalhos())
            ->assertOk()->assertJsonPath('data.fotos_count', 1)->assertJsonPath('data.capa.uuid', $f2->uuid);
        $this->assertNull(Album::first()->capa_foto_id);

        $this->postJson("/api/albuns/{$uuid}/fotos", ['fotos' => []], $this->cabecalhos())->assertStatus(422);
        $this->postJson('/api/albuns/' . Str::uuid() . '/fotos', ['fotos' => [$f1->uuid]], $this->cabecalhos())->assertNotFound();
    }

    public function test_fotos_excluidas_saem_da_contagem(): void
    {
        $this->logarComo(TipoUsuarioEnum::MEMBRO_RUA);
        $f1 = $this->criarFoto();
        $uuid = $this->postJson('/api/albuns', ['titulo' => 'A', 'fotos' => [$f1->uuid]], $this->cabecalhos())->json('data.uuid');

        $f1->delete();

        $this->getJson("/api/albuns/{$uuid}", $this->cabecalhos())->assertJsonPath('data.fotos_count', 0)->assertJsonPath('data.capa', null);
    }
}
