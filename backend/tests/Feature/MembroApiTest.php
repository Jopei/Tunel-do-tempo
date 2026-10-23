<?php

namespace Tests\Feature;

use App\Enums\TipoUsuarioEnum;
use App\Models\TipoHistoria;
use Tests\Concerns\CriaCenarioApi;
use Tests\TestCase;

class MembroApiTest extends TestCase
{
    use CriaCenarioApi;

    public function test_public_membros_traz_slug(): void
    {
        $this->criarUsuario(TipoUsuarioEnum::MEMBRO_RUA, ['nome' => 'Ana', 'slug' => 'ana']);

        $this->getJson('/api/public/membros')->assertOk()->assertJsonPath('0.slug', 'ana');
    }

    public function test_pagina_do_membro_exige_login(): void
    {
        $this->criarUsuario(TipoUsuarioEnum::MEMBRO_RUA, ['slug' => 'ana']);

        $this->getJson('/api/membros/ana', $this->cabecalhos())->assertUnauthorized();
    }

    public function test_membro_sem_historia_pessoal(): void
    {
        $membro = $this->criarUsuario(TipoUsuarioEnum::MEMBRO_RUA, ['nome' => 'Ana', 'slug' => 'ana', 'descricao' => 'oi']);
        $this->logarComo(TipoUsuarioEnum::REVISOR);

        $this->getJson('/api/membros/ana', $this->cabecalhos())
            ->assertOk()
            ->assertJsonPath('uuid', $membro->uuid)
            ->assertJsonPath('slug', 'ana')
            ->assertJsonPath('nome', 'Ana')
            ->assertJsonPath('descricao', 'oi')
            ->assertJsonPath('foto_url', null)
            ->assertJsonPath('historia_pessoal', null)
            ->assertJsonPath('historias', []);
    }

    public function test_historias_e_historia_pessoal(): void
    {
        $membro = $this->criarUsuario(TipoUsuarioEnum::MEMBRO_RUA, ['slug' => 'ana']);
        $outro = $this->criarUsuario(TipoUsuarioEnum::MEMBRO_RUA, ['slug' => 'beto']);
        $pessoal = TipoHistoria::where('nome', 'Pessoal')->value('id');
        $principal = TipoHistoria::where('nome', 'Principal')->value('id');

        $minha = $this->criarHistoria(['titulo' => 'Viagem', 'tipo_historia_id' => $principal, 'data_historia' => '2016-01-01']);
        $minha->usuarios()->attach($membro->id);
        $minha->usuarios()->attach($outro->id);
        $this->criarHistoria(['titulo' => 'Alheia', 'tipo_historia_id' => $principal])->usuarios()->attach($outro->id);
        $perfil = $this->criarHistoria(['titulo' => 'Sobre a Ana', 'conteudo' => '<p>bio</p>', 'tipo_historia_id' => $pessoal]);
        $perfil->usuarios()->attach($membro->id);
        $this->criarHistoria(['titulo' => 'Sobre o Beto', 'tipo_historia_id' => $pessoal])->usuarios()->attach($outro->id);

        $this->logarComo(TipoUsuarioEnum::REVISOR);
        $this->getJson('/api/membros/ana', $this->cabecalhos())
            ->assertOk()
            ->assertJsonCount(1, 'historias')
            ->assertJsonPath('historias.0.uuid', $minha->uuid)
            ->assertJsonPath('historias.0.fotos_count', 0)
            ->assertJsonCount(2, 'historias.0.usuarios')
            ->assertJsonPath('historia_pessoal.uuid', $perfil->uuid)
            ->assertJsonPath('historia_pessoal.titulo', 'Sobre a Ana')
            ->assertJsonPath('historia_pessoal.conteudo', '<p>bio</p>');
    }

    public function test_uuid_serve_de_fallback_apenas_quando_slug_e_null(): void
    {
        $semSlug = $this->criarUsuario(TipoUsuarioEnum::MEMBRO_RUA, ['nome' => 'Sem Slug']);
        $comSlug = $this->criarUsuario(TipoUsuarioEnum::MEMBRO_RUA, ['nome' => 'Com Slug', 'slug' => 'com-slug']);
        $this->logarComo();

        $this->getJson('/api/membros/' . $semSlug->uuid, $this->cabecalhos())
            ->assertOk()->assertJsonPath('uuid', $semSlug->uuid)->assertJsonPath('slug', null)
            ->assertJsonStructure(['uuid', 'slug', 'nome', 'foto_url', 'descricao', 'historias', 'historia_pessoal']);
        $this->getJson('/api/membros/' . $comSlug->uuid, $this->cabecalhos())->assertNotFound();
        $this->getJson('/api/membros/com-slug', $this->cabecalhos())->assertOk();
    }

    public function test_slug_inexistente_ou_de_nao_membro_retorna_404(): void
    {
        $this->criarUsuario(TipoUsuarioEnum::REVISOR, ['slug' => 'revisor']);
        $this->logarComo();

        $this->getJson('/api/membros/nao-existe', $this->cabecalhos())->assertNotFound();
        $this->getJson('/api/membros/revisor', $this->cabecalhos())->assertNotFound();
    }

    public function test_comando_gera_slugs_sem_sobrescrever_e_resolve_colisao(): void
    {
        $existente = $this->criarUsuario(TipoUsuarioEnum::MEMBRO_RUA, ['nome' => 'Zé Paulo', 'slug' => 'meu-slug']);
        $a = $this->criarUsuario(TipoUsuarioEnum::MEMBRO_RUA, ['nome' => 'João Pedro']);
        $b = $this->criarUsuario(TipoUsuarioEnum::MEMBRO_ADM, ['nome' => 'Joao Pedro']);
        $c = $this->criarUsuario(TipoUsuarioEnum::MEMBRO_RUA, ['nome' => 'Joao Pedro']);
        $revisor = $this->criarUsuario(TipoUsuarioEnum::REVISOR, ['nome' => 'Fulano']);

        $this->artisan('membros:gerar-slugs')->assertExitCode(0);

        $this->assertSame('meu-slug', $existente->fresh()->slug);
        $this->assertSame(['joao-pedro', 'joao-pedro-2', 'joao-pedro-3'], collect([$a, $b, $c])->map(fn ($u) => $u->fresh()->slug)->all());
        $this->assertNull($revisor->fresh()->slug);

        $this->artisan('membros:gerar-slugs')->assertExitCode(0);
        $this->assertSame('joao-pedro', $a->fresh()->slug);
    }

    public function test_historias_do_membro_trazem_capa_contagens_e_favoritado(): void
    {
        $membro = $this->criarUsuario(TipoUsuarioEnum::MEMBRO_RUA, ['slug' => 'ana']);
        $eu = $this->logarComo();
        $principal = TipoHistoria::where('nome', 'Principal')->value('id');
        $h = $this->criarHistoria(['tipo_historia_id' => $principal]);
        $h->usuarios()->attach($membro->id);
        $h->fotos()->attach($this->criarFoto(['thumbnail_path' => 'miniaturas/m.jpg'])->id);
        \App\Models\Favorito::create(['usuario_id' => $eu->id, 'historia_id' => $h->id]);
        \App\Models\Favorito::create(['usuario_id' => $membro->id, 'historia_id' => $h->id]);

        $r = $this->getJson('/api/membros/ana', $this->cabecalhos())->assertOk();
        $this->assertStringContainsString('miniaturas/m.jpg', $r->json('historias.0.capa_url'));
        $this->assertSame(2, $r->json('historias.0.favoritos_count'));
        $this->assertTrue($r->json('historias.0.favoritado'));
        $this->assertSame(1, $r->json('historias.0.fotos_count'));

        $pessoal = $this->criarHistoria(['tipo_historia_id' => TipoHistoria::where('nome', 'Pessoal')->value('id')]);
        $pessoal->usuarios()->attach($membro->id);
        $pessoal->fotos()->attach($this->criarFoto(['thumbnail_path' => 'miniaturas/p.jpg'])->id);
        \App\Models\Favorito::create(['usuario_id' => $eu->id, 'historia_id' => $pessoal->id]);

        $r = $this->getJson('/api/membros/ana', $this->cabecalhos())->assertOk();
        $this->assertStringContainsString('miniaturas/p.jpg', $r->json('historia_pessoal.capa_url'));
        $this->assertTrue($r->json('historia_pessoal.favoritado'));
        $this->assertSame(1, $r->json('historia_pessoal.favoritos_count'));
    }
}
