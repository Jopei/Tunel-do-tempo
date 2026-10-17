<?php

namespace App\Services;

use App\Mail\RedefinirSenhaMail;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class RedefinicaoSenhaService
{
    public const EXPIRA_EM_MINUTOS = 60;

    private const TABELA = 'password_reset_tokens';

    /**
     * Gera o token e envia o e-mail. Nao revela se o e-mail existe: nunca lanca
     * e nao tem retorno diferenciado.
     */
    public function solicitar(string $email): void
    {
        $email = strtolower(trim($email));

        // Tudo (consulta, token e e-mail) roda apos a resposta, para o tempo de
        // resposta nao revelar se o e-mail existe.
        dispatch(function () use ($email) {
            $this->processarSolicitacao($email);
        })->afterResponse();
    }

    private function processarSolicitacao(string $email): void
    {
        $usuario = Usuario::whereRaw('lower(email) = ?', [$email])->first();

        if (!$usuario) {
            return;
        }

        $token = Str::random(64);

        DB::table(self::TABELA)->updateOrInsert(
            ['email' => strtolower($usuario->email)],
            ['token' => hash('sha256', $token), 'created_at' => now()]
        );

        $link = rtrim(config('app.frontend_url'), '/')
            . '/redefinir-senha?' . http_build_query(['token' => $token, 'email' => $usuario->email]);

        try {
            Mail::to($usuario->email)->send(
                new RedefinirSenhaMail($usuario->nome, $link, self::EXPIRA_EM_MINUTOS)
            );
        } catch (Throwable $e) {
            Log::error('Falha ao enviar e-mail de redefinicao de senha', ['erro' => $e->getMessage()]);
        }
    }

    /**
     * Valida o token (hash, expiracao de 60 min, uso unico) e troca a senha.
     */
    public function redefinir(string $email, string $token, string $novaSenha): bool
    {
        $email = strtolower(trim($email));

        $registro = DB::table(self::TABELA)->where('email', $email)->first();

        if (!$registro || !$registro->created_at) {
            return false;
        }

        $expirou = Carbon::parse($registro->created_at)->lt(now()->subMinutes(self::EXPIRA_EM_MINUTOS));

        if ($expirou || !hash_equals((string) $registro->token, hash('sha256', $token))) {
            return false;
        }

        $usuario = Usuario::whereRaw('lower(email) = ?', [$email])->first();

        if (!$usuario) {
            return false;
        }

        DB::transaction(function () use ($usuario, $novaSenha, $email) {
            $usuario->senha = $novaSenha;
            $usuario->save();
            $usuario->tokens()->delete();
            DB::table(self::TABELA)->where('email', $email)->delete();
        });

        return true;
    }
}
