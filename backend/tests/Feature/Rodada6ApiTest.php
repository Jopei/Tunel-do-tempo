<?php

namespace Tests\Feature;

use App\Enums\TipoUsuarioEnum;
use App\Models\Album;
use App\Models\Foto;
use App\Models\Musica;
use App\Models\TipoHistoria;
use App\Models\Video;
use App\Services\MiniaturaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\CriaCenarioApi;
use Tests\TestCase;

class Rodada6ApiTest extends TestCase
{
    use CriaCenarioApi;

    private function video(array $extra = []): Video
    {
        return Video::create(array_merge([
            'uuid' => (string) Str::uuid(), 'titulo' => 'V', 'descricao' => 'd', 'path' => 'x.mp4',
            'external_link' => 0, 'usuario_cadastrou_id' => $this->usuarioCadastrante()->id,
        ], $extra));
    }

    /** PNG valido no cabecalho, com dimensoes declaradas arbitrarias (so IHDR e lido por getimagesize). */
    private function pngGrande(int $l, int $a): UploadedFile
    {
        $ihdr = pack('NNCCCCC', $l, $a, 8, 2, 0, 0, 0);
        $chunk = pack('N', 13) . 'IHDR' . $ihdr . pack('N', crc32('IHDR' . $ihdr));
        $idat = gzcompress(str_repeat("\0", 4));
        $corpo = "\x89PNG\r\n\x1a\n" . $chunk
            . pack('N', strlen($idat)) . 'IDAT' . $idat . pack('N', crc32('IDAT' . $idat))
            . pack('N', 0) . 'IEND' . pack('N', crc32('IEND'));

        return UploadedFile::fake()->createWithContent('grande.png', $corpo);
    }

    // 1. IDOR foto de perfil
    public function test_foto_perfil_so_do_proprio_usuario_ou_admin(): void
    {
        Storage::fake('public');
        $eu = $this->logarComo(TipoUsuarioEnum::MEMBRO_RUA);
        $outro = $this->criarUsuario();

        $this->postJson('/api/usuarios/foto-perfil', [
            'imagem' => $this->pngFalso(), 'usuario_uuid' => $outro->uuid,
        ], $this->cabecalhos())->assertForbidden();
        $this->assertSame(0, DB::table('usuario_fotos')->count());

        $this->postJson('/api/usuarios/foto-perfil', [
            'imagem' => $this->pngFalso(), 'usuario_uuid' => $eu->uuid,
        ], $this->cabecalhos())->assertCreated();

        $this->logarComo(TipoUsuarioEnum::MEMBRO_ADM);
        $this->postJson('/api/usuarios/foto-perfil', [
            'imagem' => $this->pngFalso(), 'usuario_uuid' => $outro->uuid,
        ], $this->cabecalhos())->assertCreated();
    }

    // 2. Revisor nao altera videos nem vinculos de fotos
    public function test_revisor_nao_edita_video_nem_vincula_pessoas(): void
    {
        $video = $this->video();
        $foto = $this->criarFoto();
        $this->logarComo(TipoUsuarioEnum::REVISOR);

        $this->putJson("/api/videos/{$video->uuid}", ['titulo' => 'X'], $this->cabecalhos())->assertForbidden();
        $this->deleteJson("/api/videos/{$video->uuid}", [], $this->cabecalhos())->assertForbidden();
        $this->patchJson("/api/fotos/{$foto->uuid}/usuarios", ['usuarios_uuid' => []], $this->cabecalhos())->assertForbidden();

        $this->logarComo(TipoUsuarioEnum::MEMBRO_RUA);
        $this->putJson("/api/videos/{$video->uuid}", ['titulo' => 'X'], $this->cabecalhos())->assertOk();
        $this->patchJson("/api/fotos/{$foto->uuid}/usuarios", ['usuarios_uuid' => []], $this->cabecalhos())->assertOk();
        $this->deleteJson("/api/videos/{$video->uuid}", [], $this->cabecalhos())->assertOk();
    }

    // 3. Dados em rotas publicas
    public function test_details_exige_login_e_listagem_publica_nao_expoe_cadastrante(): void
    {
        $foto = $this->criarFoto();
        $publica = $this->criarHistoria([
            'titulo' => 'Publica',
            'tipo_historia_id' => TipoHistoria::where('nome', 'Principal')->value('id'),
        ]);
        $pessoal = $this->criarHistoria([
            'titulo' => 'Intima',
            'tipo_historia_id' => TipoHistoria::where('nome', 'Pessoal')->value('id'),
        ]);
        $publica->fotos()->attach($foto->id);
        $pessoal->fotos()->attach($foto->id);

        $this->getJson("/api/fotos/{$foto->uuid}/details", $this->cabecalhos())->assertUnauthorized();

        $anonimo = $this->getJson('/api/fotos', $this->cabecalhos())->assertOk();
        $anonimo->assertJsonMissingPath('data.0.cadastrado_por')->assertJsonMissingPath('data.0.usuario_cadastrou_id');

        $this->logarComo(TipoUsuarioEnum::REVISOR);
        $r = $this->getJson("/api/fotos/{$foto->uuid}/details", $this->cabecalhos())->assertOk()
            ->assertJsonMissingPath('data.usuario_cadastrou_id')
            ->assertJsonPath('data.cadastrado_por.uuid', $this->usuarioCadastrante()->uuid);
        $this->assertSame(['Publica'], collect($r->json('data.historias'))->pluck('titulo')->all());

        $this->getJson('/api/fotos', $this->cabecalhos())
            ->assertJsonPath('data.0.cadastrado_por.uuid', $this->usuarioCadastrante()->uuid)
            ->assertJsonMissingPath('data.0.usuario_cadastrou_id');
    }

    // 4. EXIF
    public function test_original_jpeg_e_regravado_sem_exif(): void
    {
        $servico = app(MiniaturaService::class);
        if (!$servico->gdDisponivel()) {
            $this->markTestSkipped('Extensao GD indisponivel neste ambiente.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'jpg');
        $img = imagecreatetruecolor(4, 2);
        imagejpeg($img, $tmp);
        $this->assertTrue($servico->removerMetadados($tmp));
        $this->assertSame([4, 2], array_slice(getimagesize($tmp), 0, 2));
        unlink($tmp);
    }

    public function test_remover_metadados_ignora_png_e_sem_gd(): void
    {
        $png = $this->pngFalso();
        $antes = file_get_contents($png->getPathname());

        $this->assertFalse(app(MiniaturaService::class)->removerMetadados($png->getPathname()));
        $this->assertSame($antes, file_get_contents($png->getPathname()));
        $this->artisan('fotos:gerar-miniaturas', ['--limpar-exif' => true])
            ->assertExitCode(app(MiniaturaService::class)->gdDisponivel() ? 0 : 1);
    }

    // 5. Imagem gigante
    public function test_imagem_acima_de_12000px_e_rejeitada_sem_arquivo_orfao(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->logarComo(TipoUsuarioEnum::MEMBRO_RUA);

        $this->postJson('/api/fotos', ['imagem' => $this->pngGrande(13000, 1), 'tipo_imagem_id' => 3], $this->cabecalhos())
            ->assertUnprocessable()->assertJsonValidationErrors('imagem');
        $this->postJson('/api/cadastrar/musicas', [
            'titulo' => 'M', 'musica' => UploadedFile::fake()->create('a.mp3', 10, 'audio/mpeg'),
            'capa' => $this->pngGrande(1, 13000),
        ], $this->cabecalhos())->assertUnprocessable()->assertJsonValidationErrors('capa');

        $this->assertSame(0, Foto::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_miniatura_pula_imagem_acima_de_64mp(): void
    {
        $servico = app(MiniaturaService::class);
        $grande = $this->pngGrande(10000, 10000); // 100 MP, dentro do limite de lado
        $normal = $this->pngFalso();

        $this->assertFalse($servico->dentroDoLimite($grande->getPathname()));
        $this->assertTrue($servico->dentroDoLimite($normal->getPathname()));
        $this->assertNull($servico->gerar($grande->getPathname(), 'grande.png'));
    }

    public function test_falha_no_processamento_nao_deixa_arquivo_orfao(): void
    {
        Storage::fake('public');
        $this->logarComo(TipoUsuarioEnum::MEMBRO_RUA);

        $this->mock(MiniaturaService::class, function ($m) {
            $m->shouldReceive('analisar')->andThrow(new \RuntimeException('boom'));
        });

        $this->postJson('/api/fotos', ['imagem' => $this->pngFalso(), 'tipo_imagem_id' => 3], $this->cabecalhos())
            ->assertStatus(500);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame(0, Foto::count());
    }

    // 6. Album: capa
    public function test_album_capa_trashed_usa_fallback_sem_n_mais_1(): void
    {
        $this->logarComo(TipoUsuarioEnum::REVISOR);
        $primeira = null;
        $trashed = $this->criarFoto();

        foreach (range(1, 4) as $i) {
            $album = Album::create(['uuid' => (string) Str::uuid(), 'titulo' => "A$i", 'usuario_cadastrou_id' => $this->usuarioCadastrante()->id]);
            $f = $this->criarFoto();
            $album->fotos()->attach($f->id, ['ordem' => 1]);
            if ($i === 1) {
                $primeira = $f;
                $album->update(['capa_foto_id' => $trashed->id]);
            }
        }
        $trashed->delete();

        DB::enableQueryLog();
        $r = $this->getJson('/api/albuns', $this->cabecalhos())->assertOk();
        $consultas = count(DB::getQueryLog());

        $capas = collect($r->json('data'))->keyBy('titulo');
        $this->assertSame($primeira->uuid, $capas['A1']['capa']['uuid']);
        $this->assertNotNull($capas['A4']['capa']);
        $this->assertLessThanOrEqual(8, $consultas, 'consultas: ' . $consultas);
    }

    // 7. Musica privada
    public function test_musica_nova_vai_para_disco_privado_e_stream_funciona(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->logarComo(TipoUsuarioEnum::MEMBRO_RUA);

        $this->postJson('/api/cadastrar/musicas', [
            'titulo' => 'M', 'musica' => UploadedFile::fake()->createWithContent('a.mp3', '0123456789'),
        ], $this->cabecalhos())->assertCreated();

        $musica = Musica::firstOrFail();
        Storage::disk('local')->assertExists($musica->path);
        Storage::disk('public')->assertMissing($musica->path);

        $url = $this->getJson('/api/musicas', $this->cabecalhos())->json('data.0.url');
        $this->withHeaders(['Range' => 'bytes=0-3'])->get($url)->assertStatus(206);
    }

    public function test_comando_move_musicas_publicas_para_privado_idempotente(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Storage::disk('public')->put('uploads/musicas/a.mp3', '0123456789');
        $musica = Musica::create(['uuid' => (string) Str::uuid(), 'titulo' => 'L', 'path' => 'uploads/musicas/a.mp3']);

        $this->artisan('musicas:mover-para-privado')->assertSuccessful();
        Storage::disk('public')->assertMissing($musica->path);
        $this->assertSame('0123456789', Storage::disk('local')->get($musica->path));

        $this->artisan('musicas:mover-para-privado')->assertSuccessful();
        $this->assertSame('0123456789', Storage::disk('local')->get($musica->path));

        $this->logarComo(TipoUsuarioEnum::REVISOR);
        $url = $this->getJson('/api/musicas', $this->cabecalhos())->json('data.0.url');
        $this->get($url)->assertOk();
    }

    // 8. Ordenacao de videos
    public function test_videos_ordenacao_por_titulo_desc_duracao_e_tamanho(): void
    {
        $a = $this->video(['titulo' => 'A', 'duracao_segundos' => 30, 'tamanho_bytes' => 300]);
        $b = $this->video(['titulo' => 'B', 'duracao_segundos' => 10, 'tamanho_bytes' => 900]);
        $c = $this->video(['titulo' => 'C', 'duracao_segundos' => null, 'tamanho_bytes' => null]);
        $this->logarComo(TipoUsuarioEnum::REVISOR);

        $ordem = fn (string $o) => collect($this->getJson("/api/videos?ordem=$o", $this->cabecalhos())->assertOk()->json('data'))
            ->pluck('titulo')->all();

        $this->assertSame(['C', 'B', 'A'], $ordem('titulo_desc'));
        $this->assertSame(['B', 'A', 'C'], $ordem('duracao'));
        $this->assertSame(['A', 'B', 'C'], $ordem('duracao_desc'));
        $this->assertSame(['A', 'B', 'C'], $ordem('tamanho'));
        $this->assertSame(['B', 'A', 'C'], $ordem('tamanho_desc'));
        $this->getJson('/api/videos?ordem=xyz', $this->cabecalhos())->assertUnprocessable();
    }

    // 9. Baixos
    public function test_limites_de_video_e_foto(): void
    {
        $this->logarComo(TipoUsuarioEnum::MEMBRO_RUA);

        $this->postJson('/api/cadastrar/videos', ['titulo' => 'V', 'external_link' => 'javascript://x'], $this->cabecalhos())
            ->assertUnprocessable()->assertJsonValidationErrors('external_link');
        $this->postJson('/api/cadastrar/videos', ['titulo' => 'V', 'external_link' => 'ftp://x.com/v'], $this->cabecalhos())
            ->assertUnprocessable()->assertJsonValidationErrors('external_link');
        $this->postJson('/api/cadastrar/videos', [
            'titulo' => 'V', 'external_link' => 'https://youtu.be/x',
            'thumbnail' => UploadedFile::fake()->create('t.png', 5000, 'image/png'),
        ], $this->cabecalhos())->assertUnprocessable()->assertJsonValidationErrors('thumbnail');
        Storage::fake('public');
        $this->postJson('/api/cadastrar/videos', [
            'titulo' => 'V', 'external_link' => 'https://youtu.be/x',
            'video' => UploadedFile::fake()->create('v.mp4', 10, 'video/mp4'),
        ], $this->cabecalhos())->assertCreated();

        $foto = $this->criarFoto();
        $this->putJson("/api/fotos/{$foto->uuid}", ['descricao' => str_repeat('a', 2001)], $this->cabecalhos())
            ->assertUnprocessable()->assertJsonValidationErrors('descricao');
    }

    public function test_update_foto_nao_vaza_mensagem_da_excecao(): void
    {
        $this->logarComo(TipoUsuarioEnum::MEMBRO_RUA);
        $foto = $this->criarFoto();
        $this->mock(\App\Services\FotoService::class, fn ($m) => $m->shouldReceive('editarFoto')->andThrow(new \RuntimeException('segredo-interno')));

        $r = $this->putJson("/api/fotos/{$foto->uuid}", ['titulo' => 'x'], $this->cabecalhos())->assertStatus(500);
        $this->assertStringNotContainsString('segredo-interno', $r->getContent());
    }
}
