<?php

namespace Tests\Feature;

use App\Enums\TipoUsuarioEnum;
use Tests\Concerns\CriaCenarioApi;
use Tests\TestCase;

class HistoriaApiTest extends TestCase
{
    use CriaCenarioApi;

    public function test_resumo_inclui_data_e_contagens_mantendo_formato_de_lista(): void
    {
        $this->logarComo();
        $historia = $this->criarHistoria(['data_historia' => '2015-06-01']);
        $foto = $this->criarFoto();
        $historia->fotos()->attach($foto->id);

        $resposta = $this->getJson('/api/historias/resumo', $this->cabecalhos());

        $resposta->assertOk()
            ->assertJsonPath('0.uuid', $historia->uuid)
            ->assertJsonPath('0.data_historia', '2015-06-01')
            ->assertJsonPath('0.fotos_count', 1)
            ->assertJsonPath('0.videos_count', 0)
            ->assertJsonPath('0.musicas_count', 0);
    }

    public function test_resumo_paginado_e_filtros(): void
    {
        $this->logarComo();
        $this->criarHistoria(['titulo' => 'Viagem a praia']);
        $this->criarHistoria(['titulo' => 'Formatura']);

        $resposta = $this->getJson('/api/historias/resumo?page=1&per_page=10&q=praia', $this->cabecalhos());

        $resposta->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.titulo', 'Viagem a praia')
            ->assertJsonStructure(['data', 'links', 'meta' => ['current_page', 'per_page', 'total']]);
    }

    public function test_show_traz_midia_com_url_e_mantem_arrays_de_uuid(): void
    {
        $this->logarComo();
        $historia = $this->criarHistoria();
        $foto = $this->criarFoto(['titulo' => 'Praia']);
        $apagada = $this->criarFoto();
        $historia->fotos()->attach([$foto->id, $apagada->id]);
        $apagada->delete();

        $resposta = $this->getJson("/api/historias/{$historia->uuid}", $this->cabecalhos());

        $resposta->assertOk()
            ->assertJsonPath('fotos', [$foto->uuid])
            ->assertJsonCount(1, 'fotos_midia')
            ->assertJsonPath('fotos_midia.0.uuid', $foto->uuid)
            ->assertJsonPath('fotos_midia.0.titulo', 'Praia')
            ->assertJsonPath('favoritado', false);
        $this->assertStringContainsString("/api/fotos/{$foto->uuid}", $resposta->json('fotos_midia.0.url'));
    }

    public function test_revisor_nao_autor_nao_edita_nem_exclui(): void
    {
        $this->logarComo(TipoUsuarioEnum::REVISOR);
        $historia = $this->criarHistoria();

        $this->putJson("/api/historias/{$historia->uuid}", [
            'titulo' => 'Novo', 'tipo_historia' => 'Pessoal',
        ], $this->cabecalhos())->assertForbidden();

        $this->deleteJson("/api/historias/{$historia->uuid}", [], $this->cabecalhos())->assertForbidden();
        $this->assertNotSoftDeleted($historia);
    }

    public function test_revisor_autor_pode_editar_e_excluir(): void
    {
        $revisor = $this->logarComo(TipoUsuarioEnum::REVISOR);
        $historia = $this->criarHistoria();
        $historia->usuarios()->attach($revisor->id);

        $this->putJson("/api/historias/{$historia->uuid}", [
            'titulo' => 'Novo titulo', 'tipo_historia' => 'Pessoal',
        ], $this->cabecalhos())->assertOk()->assertJsonPath('titulo', 'Novo titulo');

        $this->deleteJson("/api/historias/{$historia->uuid}", [], $this->cabecalhos())->assertNoContent();
        $this->assertSoftDeleted($historia);
    }

    public function test_membro_rua_pode_editar_qualquer_historia(): void
    {
        $this->logarComo(TipoUsuarioEnum::MEMBRO_RUA);
        $historia = $this->criarHistoria();

        $this->putJson("/api/historias/{$historia->uuid}", [
            'titulo' => 'Outro', 'tipo_historia' => 'Pessoal',
        ], $this->cabecalhos())->assertOk();
    }

    public function test_historia_inexistente_retorna_404(): void
    {
        $this->logarComo();

        $this->deleteJson('/api/historias/' . \Illuminate\Support\Str::uuid(), [], $this->cabecalhos())->assertNotFound();
    }
}
