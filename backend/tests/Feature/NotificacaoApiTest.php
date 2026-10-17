<?php

namespace Tests\Feature;

use App\Enums\TipoUsuarioEnum;
use App\Models\Notificacao;
use Illuminate\Support\Str;
use Tests\Concerns\CriaCenarioApi;
use Tests\TestCase;

class NotificacaoApiTest extends TestCase
{
    use CriaCenarioApi;

    private function notificacao(string $titulo = 'Aviso'): Notificacao
    {
        return Notificacao::create(['uuid' => (string) Str::uuid(), 'titulo' => $titulo]);
    }

    public function test_apenas_adm_cria_notificacao(): void
    {
        $this->logarComo(TipoUsuarioEnum::MEMBRO_RUA);
        $this->postJson('/api/notificacoes', ['titulo' => 'X'], $this->cabecalhos())->assertForbidden();
        $this->assertDatabaseCount('notificacoes', 0);
    }

    public function test_adm_cria_notificacao_com_descricao_sanitizada(): void
    {
        $this->logarComo(TipoUsuarioEnum::MEMBRO_ADM);

        $this->postJson('/api/notificacoes', [
            'titulo' => 'Novidade',
            'descricao' => '<p>Oi</p><script>alert(1)</script>',
        ], $this->cabecalhos())->assertCreated();

        $this->assertStringNotContainsString('script', Notificacao::first()->descricao);
    }

    public function test_index_sem_parametros_mantem_formato_de_nao_lidas(): void
    {
        $usuario = $this->logarComo();
        $lida = $this->notificacao('Lida');
        $this->notificacao('Nova');
        $lida->usuarios()->attach($usuario->id, ['lida_em' => now()]);

        $this->getJson('/api/notificacoes', $this->cabecalhos())
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.titulo', 'Nova');
    }

    public function test_index_paginado_com_status_e_flag_lida(): void
    {
        $usuario = $this->logarComo();
        $lida = $this->notificacao('Lida');
        $this->notificacao('Nova');
        $lida->usuarios()->attach($usuario->id, ['lida_em' => now()]);

        $this->getJson('/api/notificacoes?status=todas', $this->cabecalhos())
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data', 'meta' => ['total']]);

        $this->getJson('/api/notificacoes?status=nao_lidas', $this->cabecalhos())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.lida', false);
    }

    public function test_contagem(): void
    {
        $usuario = $this->logarComo();
        $a = $this->notificacao();
        $this->notificacao();
        $a->usuarios()->attach($usuario->id, ['lida_em' => now()]);

        $this->getJson('/api/notificacoes/contagem', $this->cabecalhos())
            ->assertOk()->assertExactJson(['nao_lidas' => 1]);
    }

    public function test_exclusao_so_adm_e_listagem_admin(): void
    {
        $n = $this->notificacao();

        $this->logarComo(TipoUsuarioEnum::MEMBRO_RUA);
        $this->deleteJson("/api/notificacoes/{$n->id}", [], $this->cabecalhos())->assertForbidden();
        $this->getJson('/api/admin/notificacoes', $this->cabecalhos())->assertForbidden();

        $this->logarComo(TipoUsuarioEnum::MEMBRO_ADM);
        $this->getJson('/api/admin/notificacoes', $this->cabecalhos())
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.lidas_count', 0);
        $this->deleteJson("/api/notificacoes/{$n->id}", [], $this->cabecalhos())->assertOk();
        $this->assertSoftDeleted($n);
    }
}
