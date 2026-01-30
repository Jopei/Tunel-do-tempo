<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
class HistoriaPorLimiteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'limite' => 'sometimes|integer|min:1|max:100',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('limite')) {
            $this->merge([
                'limite' => (int) $this->limite,
            ]);
        }
    }
}