<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\ApiKeyOrigemEnum;

class CreateApiKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'origem' => ['required', 'string'],
            'nome' => ['nullable', 'string'],
            'api_key_antiga' => ['required', 'string'],
        ];
    }

    public function getOrigem(): ApiKeyOrigemEnum
    {
        return ApiKeyOrigemEnum::from($this->origem);
    }

    public function getNome(): ?string
    {
        return $this->nome;
    }

    public function getApiKeyAntiga(): string
    {
        return $this->api_key_antiga;
    }
}
