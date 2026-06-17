<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaterialStoreRequest extends FormRequest
{
    /**
     * O acesso já é protegido pelo middleware `auth`.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação do cadastro de material.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'codigo_interno' => ['required', 'string', 'max:255', 'unique:materiais,codigo_interno'],
            'descricao' => ['required', 'string', 'max:255'],
            'quantidade_inicial' => ['required', 'integer', 'min:0'],
            'almoxarifado_id' => [
                Rule::requiredIf(fn (): bool => (int) $this->input('quantidade_inicial') > 0),
                'nullable',
                'integer',
                'exists:almoxarifados,id',
            ],
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
            'quantidade_inicial.required' => 'Informe a quantidade inicial em estoque.',
            'quantidade_inicial.integer' => 'A quantidade inicial deve ser um número inteiro.',
            'quantidade_inicial.min' => 'A quantidade inicial não pode ser negativa.',
            'almoxarifado_id.required' => 'Selecione o almoxarifado que receberá o estoque inicial.',
            'almoxarifado_id.exists' => 'Selecione um almoxarifado válido.',
        ];
    }
}
