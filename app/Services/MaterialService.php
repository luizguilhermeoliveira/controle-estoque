<?php

namespace App\Services;

use App\Models\Material;
use Illuminate\Support\Facades\DB;

/**
 * Regra de negócio dos materiais.
 *
 * Concentra criação, atualização e exclusão, mantendo o controller enxuto.
 * Cada operação é transacional e registra a trilha de auditoria via
 * {@see AuditLogService}. No cadastro, o estoque inicial é lançado no pivot
 * e `quantidade_total` é mantido coerente com a soma das quantidades.
 */
class MaterialService
{
    public function __construct(private readonly AuditLogService $auditoria)
    {
    }

    /**
     * Cria um material com estoque inicial opcional e audita a operação.
     *
     * Espera `codigo_interno`, `descricao`, `almoxarifado_id` e
     * `quantidade_inicial`. Quando a quantidade inicial é maior que zero, o
     * saldo é lançado no pivot do almoxarifado informado; `quantidade_total`
     * reflete a soma do pivot.
     *
     * @param  array<string, mixed>  $dados
     */
    public function criar(array $dados): Material
    {
        return DB::transaction(function () use ($dados) {
            $quantidadeInicial = (int) ($dados['quantidade_inicial'] ?? 0);

            $material = Material::create([
                'codigo_interno' => $dados['codigo_interno'],
                'descricao' => $dados['descricao'],
                'quantidade_total' => $quantidadeInicial,
            ]);

            if ($quantidadeInicial > 0) {
                $material->almoxarifados()->attach($dados['almoxarifado_id'], [
                    'quantidade' => $quantidadeInicial,
                ]);
            }

            $this->auditoria->registrar('material.criado', [
                'id' => $material->id,
                'codigo_interno' => $material->codigo_interno,
                'descricao' => $material->descricao,
                'quantidade_total' => $material->quantidade_total,
                'almoxarifado_id' => $quantidadeInicial > 0 ? (int) $dados['almoxarifado_id'] : null,
            ]);

            return $material;
        });
    }

    /**
     * Atualiza os dados cadastrais do material (sem alterar estoque) e audita
     * os valores anteriores e atuais.
     *
     * A edição não toca o pivot nem `quantidade_total` para preservar a
     * consistência do estoque, que só é alterado pelas movimentações.
     *
     * @param  array<string, mixed>  $dados
     */
    public function atualizar(Material $material, array $dados): Material
    {
        return DB::transaction(function () use ($material, $dados) {
            $anterior = $material->only(['codigo_interno', 'descricao']);

            $material->update([
                'codigo_interno' => $dados['codigo_interno'],
                'descricao' => $dados['descricao'],
            ]);

            $this->auditoria->registrar('material.atualizado', [
                'id' => $material->id,
                'anterior' => $anterior,
                'atual' => $material->only(['codigo_interno', 'descricao']),
            ]);

            return $material;
        });
    }

    /**
     * Exclui um material, removendo seus vínculos de estoque, e audita a operação.
     */
    public function excluir(Material $material): void
    {
        DB::transaction(function () use ($material) {
            $dados = [
                'id' => $material->id,
                'codigo_interno' => $material->codigo_interno,
                'descricao' => $material->descricao,
                'quantidade_total' => $material->quantidade_total,
            ];

            $material->almoxarifados()->detach();
            $material->delete();

            $this->auditoria->registrar('material.excluido', $dados);
        });
    }
}
