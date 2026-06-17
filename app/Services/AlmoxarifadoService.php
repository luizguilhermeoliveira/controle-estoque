<?php

namespace App\Services;

use App\Exceptions\RegraDeNegocioException;
use App\Models\Almoxarifado;
use Illuminate\Support\Facades\DB;

/**
 * Regra de negócio dos almoxarifados.
 *
 * Concentra criação, atualização e exclusão, mantendo os controllers enxutos.
 * Cada operação é transacional e registra a trilha de auditoria via
 * {@see AuditLogService}. A exclusão aplica a regra do domínio: um almoxarifado
 * com estoque associado não pode ser removido.
 */
class AlmoxarifadoService
{
    public function __construct(private readonly AuditLogService $auditoria)
    {
    }

    /**
     * Cria um almoxarifado e audita a operação.
     *
     * @param  array<string, mixed>  $dados
     */
    public function criar(array $dados): Almoxarifado
    {
        return DB::transaction(function () use ($dados) {
            $almoxarifado = Almoxarifado::create($dados);

            $this->auditoria->registrar('almoxarifado.criado', [
                'id' => $almoxarifado->id,
                'nome' => $almoxarifado->nome,
                'localizacao' => $almoxarifado->localizacao,
            ]);

            return $almoxarifado;
        });
    }

    /**
     * Atualiza um almoxarifado e audita os valores anteriores e atuais.
     *
     * @param  array<string, mixed>  $dados
     */
    public function atualizar(Almoxarifado $almoxarifado, array $dados): Almoxarifado
    {
        return DB::transaction(function () use ($almoxarifado, $dados) {
            $anterior = $almoxarifado->only(['nome', 'localizacao']);

            $almoxarifado->update($dados);

            $this->auditoria->registrar('almoxarifado.atualizado', [
                'id' => $almoxarifado->id,
                'anterior' => $anterior,
                'atual' => $almoxarifado->only(['nome', 'localizacao']),
            ]);

            return $almoxarifado;
        });
    }

    /**
     * Exclui um almoxarifado, desde que não possua estoque associado.
     *
     * @throws RegraDeNegocioException quando há materiais em estoque.
     */
    public function excluir(Almoxarifado $almoxarifado): void
    {
        if ($this->possuiEstoque($almoxarifado)) {
            throw new RegraDeNegocioException(
                'Não é possível excluir um almoxarifado que possui materiais em estoque. '
                .'Transfira todo o estoque para outro almoxarifado antes de excluí-lo.'
            );
        }

        DB::transaction(function () use ($almoxarifado) {
            $dados = [
                'id' => $almoxarifado->id,
                'nome' => $almoxarifado->nome,
                'localizacao' => $almoxarifado->localizacao,
            ];

            // Remove eventuais vínculos de pivot zerados antes de excluir.
            $almoxarifado->materiais()->detach();
            $almoxarifado->delete();

            $this->auditoria->registrar('almoxarifado.excluido', $dados);
        });
    }

    /**
     * Indica se o almoxarifado possui algum material com quantidade em estoque.
     */
    private function possuiEstoque(Almoxarifado $almoxarifado): bool
    {
        return $almoxarifado->materiais()
            ->wherePivot('quantidade', '>', 0)
            ->exists();
    }
}
