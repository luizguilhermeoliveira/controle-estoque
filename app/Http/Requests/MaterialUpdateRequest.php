<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaterialUpdateRequest extends FormRequest
{
    /**
     * O acesso já é protegido pelo middleware `auth`.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação da edição de material.
     *
     * A edição altera apenas os dados cadastrais; o estoque é gerido pelas
     * movimentações. O `codigo_interno` permanece único, ignorando o próprio registro.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'codigo_interno' => [
                'required',
                'string',
                'max:255',
                Rule::unique('materiais', 'codigo_interno')->ignore($this->route('material')),
            ],
            'descricao' => ['required', 'string', 'max:255'],
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
            'codigo_interno.required' => 'Informe o código interno do material.',
            'codigo_interno.max' => 'O código interno pode ter no máximo 255 caracteres.',
            'codigo_interno.unique' => 'Já existe um material com este código interno.',
            'descricao.required' => 'Informe a descrição do material.',
            'descricao.max' => 'A descrição pode ter no máximo 255 caracteres.',
        ];
    }
}
