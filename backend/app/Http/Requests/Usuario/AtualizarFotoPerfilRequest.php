<?php

namespace App\Http\Requests\Usuario;

use Illuminate\Foundation\Http\FormRequest;
use App\Mapping\UsuarioMapping;

class AtualizarFotoPerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'uuid' => $this->route('uuid')
        ]);
    }

    public function rules(): array
    {
        return [
            'uuid' => 'required|exists:' 
                . UsuarioMapping::MODEL_TABLE_NAME . ',' 
                . UsuarioMapping::UUID . ',' 
                . UsuarioMapping::DELETED_AT . ',NULL',

            'foto' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ];
    }
}