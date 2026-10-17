<?php

namespace Tests\Feature;

use App\Enums\TipoUsuarioEnum;
use App\Models\Lembranca;
use Tests\Concerns\CriaCenarioApi;
use Tests\TestCase;

class LembrancaFavoritoApiTest extends TestCase
{
    use CriaCenarioApi;

    public function test_cria_e_lista_lembrancas_com_texto_puro(): void
    {
        $usuario = $this->logarComo();
        $historia = $this->criarHistoria();

        $this->postJson("/api/historias/{$historia->uuid}/lembrancas", [
            'texto' => 'Saudade <b>enorme</b><script>x</script>',
        ], $this->cabecalhos())
            ->assertCreated()
            ->assertJsonPath('usuario.uuid', $usuario->uuid)
            ->assertJsonPath('pode_excluir', true);

        $this->assertStringNotContainsString('<', Lembranca::first()->texto);
        $this->assertSame($historia->id, Lembranca::first()->historia_id);

        $this->getJson("/api/historias/{$historia->uuid}/lembrancas?page=1", $this->cabecalhos())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data', 'meta' => ['total']]);
    }

    public function test_valida_texto(): void
    {
        $this->logarComo();
        $historia = $this->criarHistoria();

        $this->postJson("/api/historias/{$historia->uuid}/lembrancas", ['texto' => ''], $this->cabecalhos())
            ->assertUnprocessable()->assertJsonValidationErrors('texto');
    }

    public function test_lembranca_da_homenagem_usa_contexto_proprio(): void
    {
        $this->logarComo();

        $this->postJson('/api/homenagem/lembrancas', ['texto' => 'Para sempre'], $this->cabecalhos())->assertCreated();
        $this->getJson('/api/homenagem/lembrancas', $this->cabecalhos())->assertOk()->assertJsonCount(1, 'data');

        $this->assertNull(Lembranca::first()->historia_id);
        $this->assertSame('homenagem', Lembranca::first()->contexto);
    }

    public function test_exclusao_apenas_autor_ou_adm(): void
    {
        $historia = $this->criarHistoria();
        $autor = $this->criarUsuario();
        $l = Lembranca::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(), 'historia_id' => $historia->id,
            'contexto' => 'historia', 'usuario_id' => $autor->id, 'texto' => 'oi',
        ]);

        $this->logarComo(TipoUsuarioEnum::MEMBRO_RUA);
        $this->deleteJson("/api/lembrancas/{$l->uuid}", [], $this->cabecalhos())->assertForbidden();

        $this->logarComo(TipoUsuarioEnum::MEMBRO_ADM);
        $this->deleteJson("/api/lembrancas/{$l->uuid}", [], $this->cabecalhos())->assertNoContent();
        $this->assertSoftDeleted($l);
    }

    public function test_favoritar_e_desfavoritar_e_idempotente(): void
    {
        $usuario = $this->logarComo();
        $historia = $this->criarHistoria();

        $this->postJson("/api/historias/{$historia->uuid}/favorito", [], $this->cabecalhos())
            ->assertOk()->assertJsonPath('favoritado', true);
        $this->postJson("/api/historias/{$historia->uuid}/favorito", [], $this->cabecalhos())->assertOk();
        $this->assertDatabaseCount('favoritos', 1);

        $this->getJson("/api/historias/{$historia->uuid}", $this->cabecalhos())->assertJsonPath('favoritado', true);

        $this->getJson('/api/me/favoritos', $this->cabecalhos())
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.uuid', $historia->uuid);

        $this->deleteJson("/api/historias/{$historia->uuid}/favorito", [], $this->cabecalhos())
            ->assertOk()->assertJsonPath('favoritado', false);
        $this->assertDatabaseCount('favoritos', 0);
    }
}
