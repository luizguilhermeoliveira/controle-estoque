<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AlmoxarifadoStoreRequest extends FormRequest
{
    /**
     * O acesso já é protegido pelo middleware `auth`.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação do cadastro de almoxarifado.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'localizacao' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Mensagens de validação em português.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome do almoxarifado.',
            'nome.max' => 'O nome pode ter no máximo 255 caracteres.',
            'localizacao.required' => 'Informe a localização do almoxarifado.',
            'localizacao.max' => 'A localização pode ter no máximo 255 caracteres.',
        ];
    }
}
