<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Resources\UsuarioAuthResource;
use App\Mapping\UsuarioMapping;
use Illuminate\Support\Facades\Auth;

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


    public function logout()
    {
        Auth::logout();

        return response()->json([
            'message' => 'Logout realizado com sucesso'
        ]);
    }
}
