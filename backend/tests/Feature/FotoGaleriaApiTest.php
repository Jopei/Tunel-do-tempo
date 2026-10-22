<?php

namespace Tests\Feature;

use App\Enums\TipoUsuarioEnum;
use App\Models\Album;
use App\Models\Foto;
use App\Services\MiniaturaService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\CriaCenarioApi;
use Tests\TestCase;

class FotoGaleriaApiTest extends TestCase
{
    use CriaCenarioApi;

    public function test_filtros_de_ate_e_agrupar_usam_data_foto_com_fallback_para_created_at(): void
    {
        $a = $this->criarFoto(['titulo' => 'A', 'data_foto' => '2020-01-10', 'created_at' => '2024-06-01 10:00:00']);
        $b = $this->criarFoto(['titulo' => 'B', 'created_at' => '2022-03-05 10:00:00']);
        $c = $this->criarFoto(['titulo' => 'C', 'data_foto' => '2023-07-01', 'created_at' => '2021-01-01 10:00:00']);

        $this->getJson('/api/fotos?agrupar=data', $this->cabecalhos())
            ->assertOk()
            ->assertJsonPath('data.0.uuid', $c->uuid)
            ->assertJsonPath('data.1.uuid', $b->uuid)
            ->assertJsonPath('data.2.uuid', $a->uuid)
            ->assertJsonPath('data.0.data_foto', '2023-07-01')
            ->assertJsonPath('data.1.data_foto', null);

        $this->getJson('/api/fotos?agrupar=data&ordem=antigas', $this->cabecalhos())
            ->assertJsonPath('data.0.uuid', $a->uuid);

        $this->getJson('/api/fotos?de=2022-01-01&ate=2022-12-31', $this->cabecalhos())
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.uuid', $b->uuid);

        $this->getJson('/api/fotos?de=2025-01-01&ate=2024-01-01', $this->cabecalhos())->assertStatus(422);
        $this->getJson('/api/fotos?agrupar=mes', $this->cabecalhos())->assertStatus(422);
    }

    public function test_filtro_por_album(): void
    {
        $dentro = $this->criarFoto();
        $this->criarFoto();
        $album = Album::create(['uuid' => (string) Str::uuid(), 'titulo' => 'X', 'usuario_cadastrou_id' => $this->usuarioCadastrante()->id]);
        $album->fotos()->attach($dentro->id, ['ordem' => 1]);

        $this->getJson('/api/fotos?album_uuid=' . $album->uuid, $this->cabecalhos())
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.uuid', $dentro->uuid);
    }

    public function test_item_traz_campos_novos(): void
    {
        $this->criarFoto(['largura' => 800, 'altura' => 600, 'thumbnail_path' => 'uploads/fotos/thumbs/x.jpg']);

        $this->getJson('/api/fotos', $this->cabecalhos())
            ->assertOk()
            ->assertJsonPath('data.0.largura', 800)
            ->assertJsonPath('data.0.altura', 600)
            ->assertJsonStructure(['data' => [['data_foto', 'thumbnail_url']]]);
        $this->assertStringEndsWith('storage/uploads/fotos/thumbs/x.jpg', $this->getJson('/api/fotos')->json('data.0.thumbnail_url'));
    }

    public function test_linha_do_tempo_agrupa_por_mes(): void
    {
        $this->criarFoto(['data_foto' => '2024-05-02']);
        $this->criarFoto(['data_foto' => '2024-05-20']);
        $this->criarFoto(['created_at' => '2023-12-25 08:00:00']);

        $this->getJson('/api/fotos/linha-do-tempo')
            ->assertOk()
            ->assertExactJson([['data' => '2024-05', 'total' => 2], ['data' => '2023-12', 'total' => 1]]);

        $this->getJson('/api/fotos/linha-do-tempo?de=2024-01-01')->assertJsonCount(1);
    }

    public function test_update_data_foto_apenas_no_grupo_de_membros(): void
    {
        $foto = $this->criarFoto();

        $this->putJson("/api/fotos/{$foto->uuid}", ['data_foto' => '2019-08-09'], $this->cabecalhos())->assertUnauthorized();

        $this->logarComo(TipoUsuarioEnum::REVISOR);
        $this->putJson("/api/fotos/{$foto->uuid}", ['data_foto' => '2019-08-09'], $this->cabecalhos())->assertForbidden();
    }

    public function test_update_data_foto_e_limpeza(): void
    {
        $this->logarComo(TipoUsuarioEnum::MEMBRO_RUA);
        $foto = $this->criarFoto(['titulo' => 'Antigo']);

        $this->putJson("/api/fotos/{$foto->uuid}", ['data_foto' => '2019-08-09'], $this->cabecalhos())->assertOk();
        $this->assertSame('2019-08-09', $foto->fresh()->data_foto->toDateString());
        $this->assertSame('Antigo', $foto->fresh()->titulo);

        $this->putJson("/api/fotos/{$foto->uuid}", ['titulo' => 'Novo'], $this->cabecalhos())->assertOk();
        $this->assertSame('2019-08-09', $foto->fresh()->data_foto->toDateString());

        $this->putJson("/api/fotos/{$foto->uuid}", ['data_foto' => null], $this->cabecalhos())->assertOk();
        $this->assertNull($foto->fresh()->data_foto);

        $this->putJson("/api/fotos/{$foto->uuid}", ['data_foto' => '09/08/2019'], $this->cabecalhos())->assertStatus(422);
        $this->putJson('/api/fotos/' . Str::uuid(), ['titulo' => 'x'], $this->cabecalhos())->assertNotFound();
    }

    public function test_lote_excluir(): void
    {
        $f1 = $this->criarFoto();
        $f2 = $this->criarFoto();
        $outra = $this->criarFoto();
        $fantasma = (string) Str::uuid();

        $this->postJson('/api/fotos/lote-excluir', ['uuids' => [$f1->uuid]], $this->cabecalhos())->assertUnauthorized();

        $this->logarComo(TipoUsuarioEnum::REVISOR);
        $this->postJson('/api/fotos/lote-excluir', ['uuids' => [$f1->uuid]], $this->cabecalhos())->assertForbidden();

        $this->logarComo(TipoUsuarioEnum::MEMBRO_RUA);
        $this->postJson('/api/fotos/lote-excluir', ['uuids' => []], $this->cabecalhos())->assertStatus(422);
        $this->postJson('/api/fotos/lote-excluir', ['uuids' => ['nao-uuid']], $this->cabecalhos())->assertStatus(422);
        $this->postJson('/api/fotos/lote-excluir', ['uuids' => array_map(fn () => (string) Str::uuid(), range(1, 101))], $this->cabecalhos())->assertStatus(422);

        $this->postJson('/api/fotos/lote-excluir', ['uuids' => [$f1->uuid, $f2->uuid, $fantasma]], $this->cabecalhos())
            ->assertOk()
            ->assertJsonPath('excluidas', 2)
            ->assertJsonPath('nao_encontradas', [$fantasma]);

        $this->assertSoftDeleted($f1);
        $this->assertSoftDeleted($f2);
        $this->assertNotSoftDeleted($outra);
    }

    public function test_rota_de_foto_de_perfil_foi_renomeada_sem_duplicidade(): void
    {
        $nomes = collect(Route::getRoutes()->getRoutes())->map->getName()->filter();

        $this->assertSame($nomes->count(), $nomes->unique()->count(), 'Ha nomes de rota duplicados.');
        $this->assertNotNull(Route::getRoutes()->getByName('usuarios.foto.perfil.atualizar'));
        $this->assertNotNull(Route::getRoutes()->getByName('usuarios.foto.perfil.store'));
    }

    public function test_upload_preenche_dimensoes_e_data_informada(): void
    {
        Storage::fake('public');
        $this->logarComo(TipoUsuarioEnum::MEMBRO_RUA);

        $this->postJson('/api/fotos', [
            'imagem' => $this->pngFalso(),
            'tipo_imagem_id' => 3,
            'data_foto' => '2018-04-03',
        ], $this->cabecalhos())->assertCreated();

        $foto = Foto::firstOrFail();
        $this->assertSame('2018-04-03', $foto->data_foto->toDateString());
        $this->assertSame(1, $foto->largura);
        $this->assertSame(1, $foto->altura);
        Storage::disk('public')->assertExists($foto->path);
    }

    public function test_upload_gera_miniatura_quando_gd_disponivel(): void
    {
        if (!app(MiniaturaService::class)->gdDisponivel()) {
            $this->markTestSkipped('Extensao GD indisponivel neste ambiente.');
        }

        Storage::fake('public');
        $this->logarComo(TipoUsuarioEnum::MEMBRO_RUA);
        $this->postJson('/api/fotos', ['imagem' => $this->pngFalso(), 'tipo_imagem_id' => 3], $this->cabecalhos())->assertCreated();

        $foto = Foto::firstOrFail();
        $this->assertNotNull($foto->thumbnail_path);
        Storage::disk('public')->assertExists($foto->thumbnail_path);
    }

    public function test_comando_miniaturas_falha_sem_gd_e_gera_com_gd(): void
    {
        Storage::fake('public');
        $temGd = app(MiniaturaService::class)->gdDisponivel();

        $foto = $this->criarFoto(['path' => 'uploads/fotos/a.png']);
        $png = $this->pngFalso();
        Storage::disk('public')->put('uploads/fotos/a.png', file_get_contents($png->getPathname()));

        $this->artisan('fotos:gerar-miniaturas')->assertExitCode($temGd ? 0 : 1);

        if ($temGd) {
            $foto->refresh();
            $this->assertNotNull($foto->thumbnail_path);
            $this->assertSame(1, $foto->largura);
        }
    }

    public function test_data_exif_ignora_arquivo_sem_exif(): void
    {
        $servico = app(MiniaturaService::class);
        $png = $this->pngFalso();
        $arquivo = $png->getPathname();

        $this->assertNull($servico->dataExif($arquivo));
        $this->assertSame(['largura' => 1, 'altura' => 1, 'data_foto' => null], $servico->analisar($arquivo));
    }
}
