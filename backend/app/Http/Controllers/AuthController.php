<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Resources\UsuarioAuthResource;
use App\Mapping\UsuarioMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        if (!Auth::attempt([
            UsuarioMapping::EMAIL => $request->login,
            'password' => $request->senha,
        ])) {
            return response()->json([
                'message' => 'Credenciais inválidas.'
            ], 401);
        }
        /** @var \App\Models\Usuario $usuario */
        $usuario = Auth::user();

        $usuario->load([
            'tipoUsuario',
            'fotos.tipo'
        ]);

        $usuario->tokens()->delete();

        $token = $usuario->createToken('web')->plainTextToken;

        return response()->json([
            'token' => $token,
            'usuario' => new UsuarioAuthResource($usuario),
        ]);
    }


    public function logout(Request $request)
    {
        $token = $request->user('sanctum')?->currentAccessToken();

        // Guard 'web' ja autenticado na mesma requisicao devolve TransientToken: revoga pelo bearer.
        if (!$token instanceof PersonalAccessToken && $request->bearerToken()) {
            $token = PersonalAccessToken::findToken($request->bearerToken());
        }

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Logout realizado com sucesso'
        ]);
    }
}
