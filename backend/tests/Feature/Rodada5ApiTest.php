<?php

namespace Tests\Feature;

use App\Enums\TipoUsuarioEnum;
use App\Models\Album;
use App\Models\Musica;
use App\Models\Video;
use App\Models\VideoPasta;
use Illuminate\Support\Str;
use Tests\Concerns\CriaCenarioApi;
use Tests\TestCase;

class Rodada5ApiTest extends TestCase
{
    use CriaCenarioApi;

    private function video(array $extra = []): Video
    {
        return Video::create(array_merge([
            'uuid' => (string) Str::uuid(), 'titulo' => 'V', 'descricao' => 'desc', 'path' => 'x.mp4',
            'external_link' => 0, 'usuario_cadastrou_id' => $this->usuarioCadastrante()->id,
        ], $extra));
    }

    private function musica(array $extra = []): Musica
    {
        return Musica::create(array_merge([
            'uuid' => (string) Str::uuid(), 'titulo' => 'M', 'path' => 'musicas/x.mp3',
            'usuario_cadastrou_id' => $this->usuarioCadastrante()->id,
        ], $extra));
    }

    public function test_mover_pasta_sem_nome_e_editar_video_so_titulo(): void
    {
        $this->logarComo(TipoUsuarioEnum::MEMBRO_RUA);
        $pai = VideoPasta::create(['uuid' => (string) Str::uuid(), 'nome' => 'Pai', 'usuario_cadastrou_id' => $this->usuarioCadastrante()->id]);
        $pasta = VideoPasta::create(['uuid' => (string) Str::uuid(), 'nome' => 'Filha', 'usuario_cadastrou_id' => $this->usuarioCadastrante()->id]);

        $this->putJson("/api/video-pastas/{$pasta->uuid}", ['pai_uuid' => $pai->uuid], $this->cabecalhos())
            ->assertOk()->assertJsonPath('data.pai_uuid', $pai->uuid)->assertJsonPath('data.nome', 'Filha');

        $v = $this->video();
        $this->putJson("/api/videos/{$v->uuid}", ['titulo' => 'Novo'], $this->cabecalhos())->assertOk();
        $v->refresh();
        $this->assertSame('Novo', $v->titulo);
        $this->assertSame('desc', $v->descricao);
    }

    public function test_video_detalhes_e_cadastrado_por(): void
    {
        $v = $this->video();
        $this->getJson("/api/videos/{$v->uuid}/detalhes", $this->cabecalhos())->assertUnauthorized();

        $this->logarComo();
        $this->getJson("/api/videos/{$v->uuid}/detalhes", $this->cabecalhos())
            ->assertOk()
            ->assertJsonPath('data.uuid', $v->uuid)
            ->assertJsonPath('data.cadastrado_por.uuid', $this->usuarioCadastrante()->uuid)
            ->assertJsonPath('data.cadastrado_por.nome', $this->usuarioCadastrante()->nome)
            ->assertJsonStructure(['data' => ['url', 'poster_url', 'created_at']]);
        $this->getJson('/api/videos/' . Str::uuid() . '/detalhes', $this->cabecalhos())->assertNotFound();

        $this->getJson('/api/videos', $this->cabecalhos())
            ->assertJsonPath('data.0.cadastrado_por.uuid', $this->usuarioCadastrante()->uuid)
            ->assertJsonStructure(['data' => [['created_at']]]);
    }

    public function test_musica_detalhes_e_ordem_album(): void
    {
        $m = $this->musica(['album' => 'B']);
        $this->musica(['album' => 'A', 'titulo' => 'Z']);
        $this->getJson("/api/musicas/{$m->uuid}/detalhes", $this->cabecalhos())->assertUnauthorized();

        $this->logarComo();
        $this->getJson("/api/musicas/{$m->uuid}/detalhes", $this->cabecalhos())
            ->assertOk()
            ->assertJsonPath('data.uuid', $m->uuid)
            ->assertJsonStructure(['data' => ['url', 'url_expira_em', 'capa_url', 'album']]);
        $this->getJson('/api/musicas/' . Str::uuid() . '/detalhes', $this->cabecalhos())->assertNotFound();

        $this->getJson('/api/musicas?ordem=album', $this->cabecalhos())
            ->assertOk()->assertJsonPath('data.0.album', 'A')->assertJsonPath('data.1.album', 'B');
    }

    public function test_foto_detalhes_com_albuns_historias_e_cadastrado_por(): void
    {
        $foto = $this->criarFoto();
        $album = Album::create(['uuid' => (string) Str::uuid(), 'titulo' => 'Alb', 'usuario_cadastrou_id' => $this->usuarioCadastrante()->id]);
        $album->fotos()->attach($foto->id, ['ordem' => 1]);
        $h = $this->criarHistoria(['titulo' => 'Hist', 'tipo_historia_id' => \App\Models\TipoHistoria::where('nome', 'Principal')->value('id')]);
        $h->fotos()->attach($foto->id);

        $this->logarComo(TipoUsuarioEnum::REVISOR);
        $this->getJson("/api/fotos/{$foto->uuid}/details", $this->cabecalhos())
            ->assertOk()
            ->assertJsonPath('data.albuns.0.titulo', 'Alb')
            ->assertJsonPath('data.albuns.0.uuid', $album->uuid)
            ->assertJsonPath('data.historias.0.uuid', $h->uuid)
            ->assertJsonPath('data.cadastrado_por.uuid', $this->usuarioCadastrante()->uuid);

        $this->getJson('/api/fotos', $this->cabecalhos())
            ->assertJsonPath('data.0.cadastrado_por.nome', $this->usuarioCadastrante()->nome)
            ->assertJsonMissingPath('data.0.albuns');
    }
}
