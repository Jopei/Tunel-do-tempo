<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Enums\TipoUsuarioEnum;
use Symfony\Component\HttpFoundation\Response;

class TipoUsuarioMiddleware
{
    public function handle(Request $request, Closure $next, ...$tipos)
    {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json([
                'message' => 'Não autenticado.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $tipoUsuario = $usuario->tipoUsuario?->nome;

        if (!$tipoUsuario) {
            return response()->json([
                'message' => 'Tipo de usuário não definido.'
            ], Response::HTTP_FORBIDDEN);
        }

        $tiposPermitidos = [];

        foreach ($tipos as $tipo) {
            if (!defined(TipoUsuarioEnum::class . "::$tipo")) {
                return response()->json([
                    'message' => "Tipo de usuário inválido na rota."
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $tiposPermitidos[] = constant(TipoUsuarioEnum::class . "::$tipo")->value;
        }

        if (!in_array($tipoUsuario, $tiposPermitidos, true)) {
            return response()->json([
                'message' => 'Acesso não autorizado.'
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
