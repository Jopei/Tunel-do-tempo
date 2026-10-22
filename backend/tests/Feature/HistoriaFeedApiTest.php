<?php

namespace Tests\Feature;

use App\Models\Favorito;
use App\Models\Lembranca;
use App\Models\TipoHistoria;
use Illuminate\Support\Str;
use Tests\Concerns\CriaCenarioApi;
use Tests\TestCase;

class HistoriaFeedApiTest extends TestCase
{
    use CriaCenarioApi;

    public function test_resumo_traz_capa_contagens_favoritado_e_usuarios_com_slug_e_foto(): void
    {
        $eu = $this->logarComo();
        $historia = $this->criarHistoria();
        $semThumb = $this->criarFoto();
        $comThumb = $this->criarFoto(['thumbnail_path' => 'miniaturas/a.jpg']);
        $apagada = $this->criarFoto(['thumbnail_path' => 'miniaturas/b.jpg']);
        $historia->fotos()->attach([$apagada->id, $semThumb->id, $comThumb->id]);
        $apagada->delete();

        $autor = $this->criarUsuario(extra: ['slug' => 'fulano']);
        $perfil = $this->criarFoto(['tipo_imagem_id' => \App\Models\TipoImagem::where('nome', 'Perfil')->value('id')]);
        $autor->fotos()->attach($perfil->id);
        $historia->usuarios()->attach($autor->id);

        Favorito::create(['usuario_id' => $eu->id, 'historia_id' => $historia->id]);
        Favorito::create(['usuario_id' => $autor->id, 'historia_id' => $historia->id]);
        Lembranca::create(['uuid' => (string) Str::uuid(), 'historia_id' => $historia->id, 'usuario_id' => $eu->id, 'texto' => 'oi']);

        $semFoto = $this->criarHistoria(['data_historia' => '2010-01-01']);

        $r = $this->getJson('/api/historias/resumo', $this->cabecalhos())->assertOk();
        $item = collect($r->json())->firstWhere('uuid', $historia->uuid);

        $this->assertStringContainsString('miniaturas/a.jpg', $item['capa_url']);
        $this->assertSame(2, $item['favoritos_count']);
        $this->assertSame(1, $item['lembrancas_count']);
        $this->assertTrue($item['favoritado']);
        $this->assertSame('fulano', $item['usuarios'][0]['slug']);
        $this->assertStringContainsString("/api/fotos/{$perfil->uuid}", $item['usuarios'][0]['foto_url']);

        $outro = collect($r->json())->firstWhere('uuid', $semFoto->uuid);
        $this->assertNull($outro['capa_url']);
        $this->assertFalse($outro['favoritado']);
        $this->assertSame(0, $outro['favoritos_count']);
    }

    public function test_resumo_individual_e_paginado_tem_os_novos_campos(): void
    {
        $this->logarComo();
        $historia = $this->criarHistoria();
        $historia->fotos()->attach($this->criarFoto()->id);

        $this->getJson("/api/historias/{$historia->uuid}/resumo", $this->cabecalhos())
            ->assertOk()
            ->assertJsonStructure(['capa_url', 'favoritos_count', 'lembrancas_count', 'favoritado', 'usuarios']);

        $this->getJson('/api/historias/resumo?page=1', $this->cabecalhos())
            ->assertOk()
            ->assertJsonPath('data.0.favoritado', false)
            ->assertJsonStructure(['data' => [['capa_url', 'favoritos_count']]]);
    }

    public function test_ordem_asc_e_desc_com_datas_nulas_por_ultimo(): void
    {
        $this->logarComo();
        $a = $this->criarHistoria(['data_historia' => '2012-01-01']);
        $b = $this->criarHistoria(['data_historia' => '2016-01-01']);

        $asc = $this->getJson('/api/historias/resumo?ordem=asc', $this->cabecalhos())->assertOk();
        $this->assertSame([$a->uuid, $b->uuid], array_column($asc->json(), 'uuid'));

        $desc = $this->getJson('/api/historias/resumo', $this->cabecalhos())->assertOk();
        $this->assertSame($b->uuid, $desc->json('0.uuid'));
        $this->assertSame($a->uuid, $desc->json('1.uuid'));

        $this->getJson('/api/historias/resumo?ordem=xx', $this->cabecalhos())->assertStatus(422);
    }

    public function test_show_traz_capa_usuarios_detalhe_midia_e_vizinhas(): void
    {
        $this->logarComo();
        $pessoal = TipoHistoria::where('nome', 'Pessoal')->first();
        $principal = TipoHistoria::where('nome', 'Principal')->value('id');
        $h1 = $this->criarHistoria(['tipo_historia_id' => $principal, 'titulo' => 'Um', 'data_historia' => '2012-01-01']);
        $oculta = $this->criarHistoria(['titulo' => 'Pessoal', 'data_historia' => '2013-01-01', 'tipo_historia_id' => $pessoal->id]);
        $apagada = $this->criarHistoria(['tipo_historia_id' => $principal, 'titulo' => 'Apagada', 'data_historia' => '2013-06-01']);
        $h2 = $this->criarHistoria(['tipo_historia_id' => $principal, 'titulo' => 'Dois', 'data_historia' => '2014-01-01']);
        $h3 = $this->criarHistoria(['tipo_historia_id' => $principal, 'titulo' => 'Tres', 'data_historia' => '2016-01-01']);
        $apagada->delete();

        $foto = $this->criarFoto(['thumbnail_path' => 'miniaturas/c.jpg', 'data_foto' => '2014-02-03']);
        $h2->fotos()->attach($foto->id);

        $r = $this->getJson("/api/historias/{$h2->uuid}", $this->cabecalhos())->assertOk();
        $r->assertJsonPath('anterior.uuid', $h1->uuid)
            ->assertJsonPath('anterior.titulo', 'Um')
            ->assertJsonPath('anterior.data_historia', '2012-01-01')
            ->assertJsonPath('proxima.uuid', $h3->uuid)
            ->assertJsonPath('fotos_midia.0.data_foto', '2014-02-03');
        $this->assertStringContainsString('miniaturas/c.jpg', $r->json('fotos_midia.0.thumbnail_url'));
        $this->assertStringContainsString('miniaturas/c.jpg', $r->json('capa_url'));

        $this->getJson("/api/historias/{$h1->uuid}", $this->cabecalhos())->assertJsonPath('anterior', null);
        $this->getJson("/api/historias/{$h3->uuid}", $this->cabecalhos())->assertJsonPath('proxima', null);
    }

    public function test_show_usuarios_tem_slug_e_foto_url(): void
    {
        $this->logarComo();
        $h = $this->criarHistoria();
        $u = $this->criarUsuario(extra: ['slug' => 'beltrano']);
        $h->usuarios()->attach($u->id);

        $this->getJson("/api/historias/{$h->uuid}", $this->cabecalhos())
            ->assertJsonPath('usuarios.0.slug', 'beltrano')
            ->assertJsonPath('usuarios.0.foto_url', null)
            ->assertJsonPath('capa_url', null);
    }

    public function test_vizinhas_de_historia_sem_data_seguem_ordem_do_feed(): void
    {
        $this->logarComo();
        $principal = TipoHistoria::where('nome', 'Principal')->value('id');
        $nova = fn ($t, $d) => $this->criarHistoria(['tipo_historia_id' => $principal, 'titulo' => $t, 'data_historia' => $d]);
        // A coluna e NOT NULL na migration; so no teste ela vira nullable para exercitar o ramo defensivo.
        \Illuminate\Support\Facades\Schema::table('historias', fn ($t) => $t->date('data_historia')->nullable()->change());
        $a = $nova('A', '2012-01-01');
        $b = $nova('B', '2016-01-01');
        $n1 = $nova('N1', null);
        $n2 = $nova('N2', null);
        $n3 = $nova('N3', null);

        // feed asc: A, B, N1, N2, N3
        $r = $this->getJson("/api/historias/{$n1->uuid}", $this->cabecalhos())->assertOk();
        $r->assertJsonPath('anterior.uuid', $b->uuid)->assertJsonPath('proxima.uuid', $n2->uuid);
        $this->getJson("/api/historias/{$n2->uuid}", $this->cabecalhos())
            ->assertJsonPath('anterior.uuid', $n1->uuid)->assertJsonPath('proxima.uuid', $n3->uuid);
        $this->getJson("/api/historias/{$n3->uuid}", $this->cabecalhos())
            ->assertJsonPath('anterior.uuid', $n2->uuid)->assertJsonPath('proxima', null);
        $this->getJson("/api/historias/{$b->uuid}", $this->cabecalhos())
            ->assertJsonPath('anterior.uuid', $a->uuid)->assertJsonPath('proxima.uuid', $n1->uuid);
    }
}
