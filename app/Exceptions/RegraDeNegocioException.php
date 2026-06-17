<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Exceção de regra de negócio com mensagem amigável ao usuário final.
 *
 * Lançada pelos services do domínio quando uma operação viola uma regra de
 * negócio (ex.: excluir almoxarifado com estoque, saída sem saldo). A mensagem
 * é segura para exibição direta na interface — sem stack trace nem detalhes
 * técnicos. Os controllers capturam esta exceção e a transformam em flash de erro.
 */
class RegraDeNegocioException extends RuntimeException
{
}
