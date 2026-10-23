<?php

namespace Tests\Feature;

use App\Enums\TipoUsuarioEnum;
use Tests\Concerns\CriaCenarioApi;
use Tests\TestCase;

class UsuarioPublicoBuscaApiTest extends TestCase
{
    use CriaCenarioApi;

    public function test_me_retorna_usuario_completo(): void
    {
        $usuario = $this->criarUsuario(TipoUsuarioEnum::MEMBRO_RUA, [
            'telefone' => '11999999999', 'descricao' => 'bio',
        ]);
        \Laravel\Sanctum\Sanctum::actingAs($usuario);

        $this->getJson('/api/me', $this->cabecalhos())
            ->assertOk()
            ->assertJsonPath('uuid', $usuario->uuid)
            ->assertJsonPath('telefone', '11999999999')
            ->assertJsonPath('descricao', 'bio')
            ->assertJsonPath('tipo_usuario', 'Membro Rua')
            ->assertJsonStructure(['aniversario', 'email', 'foto_perfil']);
    }

    public function test_me_exige_login(): void
    {
        $this->getJson('/api/me', $this->cabecalhos())->assertUnauthorized();
    }

    public function test_membros_publicos_sem_dados_sensiveis_e_sem_login(): void
    {
        $this->criarUsuario(TipoUsuarioEnum::MEMBRO_RUA, ['nome' => 'Ana']);
        $this->criarUsuario(TipoUsuarioEnum::REVISOR, ['nome' => 'Revisor']);

        $resposta = $this->getJson('/api/public/membros');

        $resposta->assertOk()->assertJsonCount(1)->assertJsonPath('0.nome', 'Ana');
        $this->assertSame(['foto_url', 'nome', 'slug', 'uuid'], collect($resposta->json('0'))->keys()->sort()->values()->all());
    }

    public function test_busca_global_agrupa_resultados(): void
    {
        $this->logarComo();
        $this->criarHistoria(['titulo' => 'Viagem especial']);
        $this->criarFoto(['titulo' => 'Foto da viagem']);
        $this->criarUsuario(TipoUsuarioEnum::MEMBRO_RUA, ['nome' => 'Maria Viagens']);

        $this->getJson('/api/busca?q=viag', $this->cabecalhos())
            ->assertOk()
            ->assertJsonCount(1, 'historias')
            ->assertJsonCount(1, 'fotos')
            ->assertJsonCount(0, 'videos')
            ->assertJsonCount(1, 'membros');

        $this->getJson('/api/busca?q=a', $this->cabecalhos())->assertUnprocessable();
    }

    public function test_filtros_de_fotos(): void
    {
        $this->criarFoto(['titulo' => 'Natal em familia', 'created_at' => '2019-12-25 10:00:00']);
        $this->criarFoto(['titulo' => 'Praia', 'created_at' => '2021-01-10 10:00:00']);

        $this->getJson('/api/fotos?q=natal')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/fotos?ano=2021')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.titulo', 'Praia');
        $this->getJson('/api/fotos?ordem=antigas')->assertOk()->assertJsonPath('data.0.titulo', 'Natal em familia');
        $this->getJson('/api/fotos?ordem=invalida')->assertUnprocessable();
    }

    public function test_videos_continuam_exigindo_login(): void
    {
        $this->getJson('/api/videos', $this->cabecalhos())->assertUnauthorized();

        $this->logarComo();
        $this->getJson('/api/videos?q=x', $this->cabecalhos())->assertOk();
    }
}
