<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Infraestrutura transversal de auditoria.
 *
 * Centraliza a gravação na tabela `audit_logs`, capturando o usuário autenticado
 * e o IP da requisição atual. É consumido pelos services de domínio (almoxarifados,
 * materiais, transferências, entradas/saídas) para registrar toda operação relevante.
 */
class AuditLogService
{
    /**
     * Registra uma operação na trilha de auditoria.
     *
     * @param  string  $operacao  Identificador da operação (ex.: "almoxarifado.criado").
     * @param  array<string, mixed>  $payload  Dados relevantes da operação.
     * @param  Request|null  $request  Requisição atual; quando omitida, usa a corrente.
     */
    public function registrar(string $operacao, array $payload = [], ?Request $request = null): AuditLog
    {
        $request ??= request();

        return AuditLog::create([
            'operacao' => $operacao,
            'payload' => $payload,
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
            'created_at' => now(),
        ]);
    }
}
