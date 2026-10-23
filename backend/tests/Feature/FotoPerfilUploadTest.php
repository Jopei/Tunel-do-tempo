<?php

namespace Tests\Feature;

use App\Enums\TipoUsuarioEnum;
use App\Models\Foto;
use App\Models\Pivots\UsuarioFoto;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CriaCenarioApi;
use Tests\TestCase;

class FotoPerfilUploadTest extends TestCase
{
    use CriaCenarioApi;

    public function test_rota_com_uuid_grava_foto_perfil_completa_e_preserva_galeria(): void
    {
        Storage::fake('public');
        $eu = $this->logarComo(TipoUsuarioEnum::MEMBRO_RUA);
        $galeria = $this->criarFoto();
        UsuarioFoto::create(['usuario_id' => $eu->id, 'foto_id' => $galeria->id]);

        $arq = $this->pngFalso();
        $r1 = $this->postJson("/api/usuarios/{$eu->uuid}/foto-perfil", ['foto' => $arq], $this->cabecalhos());
        $r1->assertOk()->assertJsonPath('mensagem', 'Foto atualizada com sucesso.')
            ->assertJsonMissingPath('foto.id')->assertJsonMissingPath('foto.path')
            ->assertJsonMissingPath('foto.usuario_cadastrou_id')->assertJsonStructure(['foto' => ['uuid', 'url', 'thumbnail_url']]);

        $arq2 = $this->pngFalso('outra.png');
        $r2 = $this->postJson("/api/usuarios/{$eu->uuid}/foto-perfil", ['foto' => $arq2], $this->cabecalhos());
        $r2->assertOk();

        $foto = Foto::where('uuid', $r2->json('foto.uuid'))->firstOrFail();
        $this->assertSame(1, $foto->tipo_imagem_id);
        $this->assertSame($eu->id, $foto->usuario_cadastrou_id);
        $this->assertStringStartsWith('uploads/fotos/perfil/', $foto->path);

        $ativos = UsuarioFoto::where('usuario_id', $eu->id)->pluck('foto_id')->all();
        $this->assertEqualsCanonicalizing([$galeria->id, $foto->id], $ativos);
        $this->assertNotNull(Foto::find($galeria->id));
    }

    public function test_rota_com_uuid_bloqueia_outro_usuario_e_libera_admin(): void
    {
        Storage::fake('public');
        $eu = $this->logarComo(TipoUsuarioEnum::MEMBRO_RUA);
        $outro = $this->criarUsuario();

        $a = $this->pngFalso();
        $this->postJson("/api/usuarios/{$outro->uuid}/foto-perfil", ['foto' => $a], $this->cabecalhos())->assertForbidden();

        $adm = $this->logarComo(TipoUsuarioEnum::MEMBRO_ADM);
        $b = $this->pngFalso();
        $this->postJson("/api/usuarios/{$outro->uuid}/foto-perfil", ['foto' => $b], $this->cabecalhos())->assertOk();

        $this->assertSame($adm->id, Foto::latest('id')->first()->usuario_cadastrou_id);
    }

    public function test_rota_sem_uuid_grava_mesmos_campos(): void
    {
        Storage::fake('public');
        $eu = $this->logarComo();
        $arq = $this->pngFalso();

        $r = $this->postJson('/api/usuarios/foto-perfil', ['imagem' => $arq, 'usuario_uuid' => $eu->uuid], $this->cabecalhos());
        $r->assertCreated();

        $foto = Foto::where('uuid', $r->json('uuid'))->firstOrFail();
        $this->assertSame(1, $foto->tipo_imagem_id);
        $this->assertSame($eu->id, $foto->usuario_cadastrou_id);
    }

    public function test_foto_sem_tipo_imagem_ou_cadastrante_e_rejeitada_pelo_banco(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        \Illuminate\Support\Facades\DB::table('fotos')->insert([
            'uuid' => (string) \Illuminate\Support\Str::uuid(), 'path' => 'x.jpg',
        ]);
    }

    public function test_trocar_perfil_atualiza_me_e_membros_com_a_foto_nova(): void
    {
        Storage::fake('public');
        $eu = $this->logarComo(TipoUsuarioEnum::MEMBRO_RUA);

        $a = $this->pngFalso();
        $uuidA = $this->postJson("/api/usuarios/{$eu->uuid}/foto-perfil", ['foto' => $a], $this->cabecalhos())->json('foto.uuid');
        $b = $this->pngFalso('b.png');
        $uuidB = $this->postJson("/api/usuarios/{$eu->uuid}/foto-perfil", ['foto' => $b], $this->cabecalhos())->json('foto.uuid');

        $me = $this->getJson('/api/me', $this->cabecalhos());
        $me->assertOk();
        $this->assertStringContainsString($uuidB, $me->json('foto_url'));
        $this->assertStringContainsString('?v=', $me->json('foto_url'));
        $this->assertStringStartsWith('uploads/fotos/perfil/', $me->json('foto_perfil'));

        $membro = collect($this->getJson('/api/public/membros', $this->cabecalhos())->json())->firstWhere('uuid', $eu->uuid);
        $this->assertStringContainsString($uuidB, $membro['foto_url']);
        $this->assertStringNotContainsString($uuidA, $membro['foto_url']);
    }
}
