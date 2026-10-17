<?php

namespace Tests\Feature;

use App\Mail\RedefinirSenhaMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CriaCenarioApi;
use Tests\TestCase;

class SenhaApiTest extends TestCase
{
    use CriaCenarioApi;

    /** O envio roda no terminating (apos a resposta); o kernel de teste ja executa isso a cada requisicao. */
    private function tokenDoEmail(): string
    {

        $token = null;
        Mail::assertSent(RedefinirSenhaMail::class, function ($mail) use (&$token) {
            parse_str(parse_url($mail->link, PHP_URL_QUERY), $q);
            $token = $q['token'];

            return str_contains($mail->link, '/redefinir-senha?');
        });

        return $token;
    }

    public function test_resposta_generica_para_email_existente_e_inexistente(): void
    {
        Mail::fake();
        $usuario = $this->criarUsuario();

        $a = $this->postJson('/api/esqueci-senha', ['email' => $usuario->email], $this->cabecalhos());
        $b = $this->postJson('/api/esqueci-senha', ['email' => 'naoexiste@teste.com'], $this->cabecalhos());

        $a->assertOk();
        $b->assertOk();
        $this->assertSame($a->json(), $b->json());
        // (no ambiente de teste os callbacks de terminate acumulam entre requisicoes; por isso nao se conta envios)
        Mail::assertSent(RedefinirSenhaMail::class, fn ($m) => $m->hasTo($usuario->email));
        Mail::assertNotSent(RedefinirSenhaMail::class, fn ($m) => $m->hasTo('naoexiste@teste.com'));
    }

    public function test_fluxo_completo_com_token_de_uso_unico(): void
    {
        Mail::fake();
        $usuario = $this->criarUsuario();
        $this->postJson('/api/esqueci-senha', ['email' => $usuario->email], $this->cabecalhos());
        $token = $this->tokenDoEmail();

        $this->assertNotSame($token, DB::table('password_reset_tokens')->value('token'));

        $this->postJson('/api/redefinir-senha', [
            'token' => $token, 'email' => $usuario->email, 'senha' => 'nova-senha-123',
        ], $this->cabecalhos())->assertOk();

        $this->assertTrue(Hash::check('nova-senha-123', $usuario->fresh()->senha));

        $this->postJson('/api/redefinir-senha', [
            'token' => $token, 'email' => $usuario->email, 'senha' => 'outra-senha-123',
        ], $this->cabecalhos())->assertUnprocessable();
    }

    public function test_token_invalido_ou_expirado_e_rejeitado(): void
    {
        Mail::fake();
        $usuario = $this->criarUsuario();
        $this->postJson('/api/esqueci-senha', ['email' => $usuario->email], $this->cabecalhos());
        $token = $this->tokenDoEmail();

        $this->postJson('/api/redefinir-senha', [
            'token' => 'errado', 'email' => $usuario->email, 'senha' => 'nova-senha-123',
        ], $this->cabecalhos())->assertUnprocessable();

        DB::table('password_reset_tokens')->update(['created_at' => now()->subMinutes(61)]);

        $this->postJson('/api/redefinir-senha', [
            'token' => $token, 'email' => $usuario->email, 'senha' => 'nova-senha-123',
        ], $this->cabecalhos())->assertUnprocessable();
        $this->assertTrue(Hash::check('senha-antiga-123', $usuario->fresh()->senha));
    }

    public function test_senha_curta_e_rejeitada(): void
    {
        $this->postJson('/api/redefinir-senha', [
            'token' => 'x', 'email' => 'a@b.com', 'senha' => '123',
        ], $this->cabecalhos())->assertUnprocessable()->assertJsonValidationErrors('senha');
    }

    public function test_rate_limit(): void
    {
        Mail::fake();
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/esqueci-senha', ['email' => "u{$i}@teste.com"], $this->cabecalhos())->assertOk();
        }

        $this->postJson('/api/esqueci-senha', ['email' => 'u9@teste.com'], $this->cabecalhos())->assertStatus(429);
    }
}
