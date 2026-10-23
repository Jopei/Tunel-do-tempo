<?php

namespace Tests\Feature;

use App\Enums\TipoUsuarioEnum;
use App\Models\Album;
use App\Models\Video;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\CriaCenarioApi;
use Tests\TestCase;

class Rodada8FotosVideosApiTest extends TestCase
{
    use CriaCenarioApi;

    private function video(array $extra = []): Video
    {
        return Video::create(array_merge([
            'uuid' => (string) Str::uuid(), 'titulo' => 'V', 'path' => 'x.mp4',
            'external_link' => 0, 'usuario_cadastrou_id' => $this->usuarioCadastrante()->id,
        ], $extra));
    }

    public function test_data_video_cadastro_edicao_item_ordem_e_anos(): void
    {
        $this->logarComo(TipoUsuarioEnum::MEMBRO_RUA);

        $arquivo = \Illuminate\Http\UploadedFile::fake()->create('v.mp4', 10, 'video/mp4');
        $this->postJson('/api/cadastrar/videos', ['titulo' => 'T', 'video' => $arquivo, 'data_video' => '2014-05-06'], $this->cabecalhos())
            ->assertStatus(201);
        $this->assertSame('2014-05-06', Video::firstWhere('titulo', 'T')->data_video->toDateString());

        $this->postJson('/api/cadastrar/videos', ['titulo' => 'X', 'video' => $arquivo, 'data_video' => 'ontem'], $this->cabecalhos())
            ->assertStatus(422);
    }

    public function test_listagem_ano_ordem_sem_data_e_anos(): void
    {
        $a = $this->video(['titulo' => 'A', 'data_video' => '2015-01-10']);
        $b = $this->video(['titulo' => 'B', 'data_video' => '2013-03-01']);
        $c = $this->video(['titulo' => 'C']);
        $d = $this->video(['titulo' => 'D', 'data_video' => '2015-07-07']);

        $this->getJson('/api/videos/anos', $this->cabecalhos())->assertUnauthorized();
        $this->logarComo();

        $this->getJson('/api/videos/anos', $this->cabecalhos())->assertOk()->assertExactJson([
            ['ano' => 2015, 'total' => 2], ['ano' => 2013, 'total' => 1], ['ano' => null, 'total' => 1],
        ]);

        $this->getJson('/api/videos?ano=2015', $this->cabecalhos())->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.data_video', fn ($v) => str_starts_with($v, '2015'));

        $this->getJson('/api/videos?ordem=data_video', $this->cabecalhos())
            ->assertJsonPath('data.0.uuid', $b->uuid)->assertJsonPath('data.3.uuid', $c->uuid);
        $this->getJson('/api/videos?ordem=data_video_desc', $this->cabecalhos())
            ->assertJsonPath('data.0.uuid', $d->uuid)->assertJsonPath('data.3.uuid', $c->uuid);
        $this->getJson('/api/videos?sem_data=1', $this->cabecalhos())->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.data_video', null);
    }

    public function test_editar_data_video_e_limpar(): void
    {
        $this->logarComo();
        $v = $this->video(['data_video' => '2015-01-10']);

        $this->putJson("/api/videos/{$v->uuid}", ['titulo' => 'Novo'], $this->cabecalhos())->assertOk();
        $this->assertSame('2015-01-10', $v->fresh()->data_video->toDateString());

        $this->putJson("/api/videos/{$v->uuid}", ['data_video' => '2016-02-03'], $this->cabecalhos())->assertOk();
        $this->assertSame('2016-02-03', $v->fresh()->data_video->toDateString());

        $this->putJson("/api/videos/{$v->uuid}", ['data_video' => null], $this->cabecalhos())->assertOk();
        $this->assertNull($v->fresh()->data_video);

        $this->putJson("/api/videos/{$v->uuid}", ['data_video' => '03/02/2016'], $this->cabecalhos())->assertStatus(422);
    }

    public function test_fotos_anos_publico_e_filtro_ano_coerente(): void
    {
        $this->criarFoto(['data_foto' => '2014-03-01']);
        $this->criarFoto(['data_foto' => '2014-09-01']);
        $this->criarFoto(['data_foto' => '2012-01-01']);
        $this->criarFoto(['tipo_imagem_id' => 1, 'data_foto' => '2012-01-01']); // perfil nao conta

        $r = $this->getJson('/api/fotos/anos', $this->cabecalhos())->assertOk();
        $r->assertJsonCount(2)->assertJsonPath('0.ano', 2014)->assertJsonPath('0.total', 2)
            ->assertJsonPath('1.ano', 2012)->assertJsonPath('1.total', 1);
        $r->assertJsonStructure([['ano', 'total', 'capa' => ['thumbnail_url', 'url']]]);
        $this->assertSame(['ano', 'total', 'capa'], array_keys($r->json('0')));
        $this->assertSame(['thumbnail_url', 'url'], array_keys($r->json('0.capa')));
        $this->getJson('/api/fotos?ano=2014')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_fotos_anos_capa_e_a_mais_recente_do_ano_sem_excluidas(): void
    {
        $this->criarFoto(['data_foto' => '2014-03-01', 'thumbnail_path' => 'fotos/t-antiga.jpg']);
        $nova = $this->criarFoto(['data_foto' => '2014-09-01', 'thumbnail_path' => 'fotos/t-nova.jpg']);
        $excluida = $this->criarFoto(['data_foto' => '2014-12-01']);
        $excluida->delete();
        $this->criarFoto(['tipo_imagem_id' => 1, 'data_foto' => '2014-12-30']); // perfil nao vira capa

        $r = $this->getJson('/api/fotos/anos')->assertOk()->assertJsonCount(1);
        $r->assertJsonPath('0.total', 2)
            ->assertJsonPath('0.capa.url', route('api.fotos.show', $nova->uuid))
            ->assertJsonPath('0.capa.thumbnail_url', asset('storage/fotos/t-nova.jpg'));
    }

    public function test_neste_dia(): void
    {
        $hoje = now();
        $this->criarFoto(['titulo' => 'ontem', 'data_foto' => $hoje->copy()->subYears(2)->subDay()->toDateString()]);
        $this->criarFoto(['titulo' => 'a', 'data_foto' => $hoje->copy()->subYears(2)->toDateString()]);
        $this->criarFoto(['titulo' => 'b', 'data_foto' => $hoje->copy()->subYears(5)->toDateString()]);
        $this->criarFoto(['titulo' => 'c', 'data_foto' => $hoje->copy()->subYears(2)->toDateString()]);
        $this->criarFoto(['titulo' => 'hoje', 'data_foto' => $hoje->toDateString()]); // ano atual fora

        $this->getJson('/api/fotos/neste-dia', $this->cabecalhos())->assertUnauthorized();
        $this->logarComo();

        $r = $this->getJson('/api/fotos/neste-dia', $this->cabecalhos())->assertOk();
        $r->assertJsonCount(2);
        $r->assertJsonPath('0.ano', $hoje->year - 2)->assertJsonCount(2, '0.fotos');
        $r->assertJsonPath('1.ano', $hoje->year - 5)->assertJsonCount(1, '1.fotos');
        $r->assertJsonStructure([['ano', 'fotos' => [['uuid', 'cadastrado_por' => ['slug', 'foto_url'], 'marcados']]]]);

        $this->getJson('/api/fotos/neste-dia?limite=1', $this->cabecalhos())->assertOk()->assertJsonCount(1);
    }

    public function test_autor_marcados_e_historias_no_details_sem_n_mais_1(): void
    {
        $this->logarComo();
        $pessoa = $this->criarUsuario(TipoUsuarioEnum::MEMBRO_RUA, ['slug' => 'ana']);
        $foto = $this->criarFoto(['data_foto' => '2014-03-01']);
        $foto->usuarios()->attach($pessoa->id);
        $h = $this->criarHistoria(['data_historia' => '2014-04-02', 'tipo_historia_id' => \App\Models\TipoHistoria::where('nome', '!=', 'Pessoal')->value('id')]);
        $h->fotos()->attach($foto->id);

        $this->getJson("/api/fotos/{$foto->uuid}/details", $this->cabecalhos())->assertOk()
            ->assertJsonPath('data.marcados.0.uuid', $pessoa->uuid)
            ->assertJsonPath('data.marcados.0.slug', 'ana')
            ->assertJsonPath('data.marcados.0.foto_url', null)
            ->assertJsonPath('data.cadastrado_por.slug', $this->usuarioCadastrante()->slug)
            ->assertJsonPath('data.historias.0.data_historia', '2014-04-02')
            ->assertJsonPath('data.historias.0.capa_url', route('api.fotos.show', $foto->uuid));
    }

    public function test_anonimo_nao_ve_marcados(): void
    {
        $foto = $this->criarFoto();
        $foto->usuarios()->attach($this->criarUsuario()->id);

        $r = $this->getJson('/api/fotos', $this->cabecalhos())->assertOk();
        $this->assertArrayNotHasKey('marcados', $r->json('data.0'));
        $this->assertArrayNotHasKey('cadastrado_por', $r->json('data.0'));
    }

    public function test_listagem_logado_marcados_sem_n_mais_1(): void
    {
        $this->logarComo();
        foreach (range(1, 3) as $i) {
            $f = $this->criarFoto();
            $f->usuarios()->attach($this->criarUsuario()->id);
        }
        $this->getJson('/api/fotos?per_page=1', $this->cabecalhos())->assertOk();

        DB::enableQueryLog();
        $this->getJson('/api/fotos?per_page=3', $this->cabecalhos())->assertOk()->assertJsonCount(3, 'data');
        $tres = count(DB::getQueryLog());
        DB::flushQueryLog();
        $this->getJson('/api/fotos?per_page=1', $this->cabecalhos())->assertOk();
        $this->assertSame(count(DB::getQueryLog()), $tres);
    }

    public function test_video_cadastrado_por_enriquecido(): void
    {
        $v = $this->video();
        $this->logarComo();
        $this->getJson('/api/videos', $this->cabecalhos())->assertOk()
            ->assertJsonStructure(['data' => [['data_video', 'cadastrado_por' => ['uuid', 'nome', 'slug', 'foto_url']]]]);
        $this->getJson("/api/videos/{$v->uuid}/detalhes", $this->cabecalhos())->assertOk()
            ->assertJsonStructure(['data' => ['cadastrado_por' => ['slug', 'foto_url']]]);
    }

    public function test_album_ano_inicio_fim(): void
    {
        $this->logarComo();
        $album = Album::create(['uuid' => (string) Str::uuid(), 'titulo' => 'A', 'usuario_cadastrou_id' => $this->usuarioCadastrante()->id]);
        $album->fotos()->attach([
            $this->criarFoto(['data_foto' => '2013-05-01'])->id => ['ordem' => 1],
            $this->criarFoto(['data_foto' => '2015-05-01'])->id => ['ordem' => 2],
            $this->criarFoto()->id => ['ordem' => 3],
        ]);
        $vazio = Album::create(['uuid' => (string) Str::uuid(), 'titulo' => 'V', 'usuario_cadastrou_id' => $this->usuarioCadastrante()->id]);

        $this->getJson("/api/albuns/{$album->uuid}", $this->cabecalhos())->assertOk()
            ->assertJsonPath('data.ano_inicio', 2013)->assertJsonPath('data.ano_fim', 2015);
        $r = $this->getJson('/api/albuns', $this->cabecalhos())->assertOk();
        $porUuid = collect($r->json('data'))->keyBy('uuid');
        $this->assertSame(2013, $porUuid[$album->uuid]['ano_inicio']);
        $this->assertSame(2015, $porUuid[$album->uuid]['ano_fim']);
        $this->assertNull($porUuid[$vazio->uuid]['ano_inicio']);
    }

    public function test_videos_anos_aceita_filtros_de_pasta_todas_e_q(): void
    {
        $this->logarComo();
        $pasta = \App\Models\VideoPasta::create(['uuid' => (string) Str::uuid(), 'nome' => 'P', 'usuario_cadastrou_id' => $this->usuarioCadastrante()->id]);
        $this->video(['titulo' => 'Praia', 'data_video' => '2015-01-01', 'pasta_id' => $pasta->id]);
        $this->video(['titulo' => 'Praia 2', 'data_video' => '2013-01-01']);
        $this->video(['titulo' => 'Festa', 'data_video' => '2013-05-05']);

        $anos = fn (string $qs) => $this->getJson('/api/videos/anos' . $qs, $this->cabecalhos())->assertOk()->json();

        $this->assertSame([['ano' => 2015, 'total' => 1]], $anos("?pasta_uuid={$pasta->uuid}"));
        $this->assertSame([['ano' => 2013, 'total' => 2]], $anos('?pasta_uuid=raiz'));
        $this->assertSame([['ano' => 2015, 'total' => 1], ['ano' => 2013, 'total' => 2]], $anos('?pasta_uuid=raiz&todas=1'));
        $this->assertSame([['ano' => 2015, 'total' => 1], ['ano' => 2013, 'total' => 1]], $anos('?q=Praia'));
        $this->assertSame([['ano' => 2013, 'total' => 1]], $anos('?q=Praia&pasta_uuid=raiz'));
        $this->assertCount(2, $anos(''));
        $this->getJson('/api/videos/anos?pasta_uuid=xx', $this->cabecalhos())->assertStatus(422);
    }
}
