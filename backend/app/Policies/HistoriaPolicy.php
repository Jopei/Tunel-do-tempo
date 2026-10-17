<?php

namespace App\Policies;

use App\Enums\TipoUsuarioEnum;
use App\Models\Historia;
use App\Models\Usuario;

class HistoriaPolicy
{
    public function update(Usuario $usuario, Historia $historia): bool
    {
        return $this->podeGerenciar($usuario, $historia);
    }

    public function delete(Usuario $usuario, Historia $historia): bool
    {
        return $this->podeGerenciar($usuario, $historia);
    }

    /**
     * ADM/RUA podem tudo; os demais apenas se forem autor (usuario vinculado a historia).
     */
    private function podeGerenciar(Usuario $usuario, Historia $historia): bool
    {
        $tipo = $usuario->tipoUsuario?->nome;

        if (in_array($tipo, [TipoUsuarioEnum::MEMBRO_ADM->value, TipoUsuarioEnum::MEMBRO_RUA->value], true)) {
            return true;
        }

        return $historia->usuarios()->where('usuarios.id', $usuario->id)->exists();
    }
}
