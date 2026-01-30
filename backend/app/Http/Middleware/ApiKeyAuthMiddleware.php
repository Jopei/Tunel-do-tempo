<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiKey;
use App\Mapping\ApiKeyMapping;

class ApiKeyAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-KEY');

        if (!$apiKey) {
            return response()->json([
                'message' => 'API Key não informada'
            ], 401);
        }

        $hash = hash('sha256', $apiKey);

        $keyValida = ApiKey::query()
            ->where(ApiKeyMapping::HASH, $hash)
            ->where(ApiKeyMapping::ATIVO, true)
            ->exists();

        if (!$keyValida) {
            return response()->json([
                'message' => 'API Key inválida'
            ], 401);
        }

        return $next($request);
    }
}
