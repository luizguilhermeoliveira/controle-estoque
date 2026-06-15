# Controle de Estoque — Contexto do Projeto

Sistema de controle de estoque (entrada, saída e transferência de materiais entre
almoxarifados) em Laravel, com auditoria e observabilidade. Teste técnico para vaga
de desenvolvedor Laravel.

## Stack e execução
- Laravel 13 + PHP 8.4, MariaDB 11, Redis e Nginx — tudo via Docker.
- A aplicação roda em containers. SEMPRE execute artisan/composer/testes dentro do
  container `app`:
  - `docker compose exec app php artisan <comando>`
  - `docker compose exec app composer <comando>`
  - `docker compose exec app php artisan test`
- App em http://localhost:3000. Banco interno: host `db`, porta 3306
  (exposta no host como 3307).

## Arquitetura (exigida pelo teste)
- MVC do Laravel. Controllers enxutos (validação + orquestração); regra de negócio
  vai em Services (app/Services).
- Schema apenas via Migrations. Models refletem o schema, com relacionamentos
  explícitos (hasMany, belongsTo, belongsToMany) e $fillable.
- Blade é só apresentação: PROIBIDO lógica de negócio e diretivas @php/@endphp.
  As variáveis vêm exclusivamente do Controller.
- Frontend: Blade + Bootstrap 5 + DataTables + SweetAlert2.
- Aplicar SOLID e Clean Code. Tratar exceções e nunca expor stack trace ao usuário
  final em produção.

## Domínio
- almoxarifados (nome, localizacao)
- materiais (codigo_interno único, descricao, quantidade_total)
- almoxarifado_material (pivot/estoque: almoxarifado_id, material_id, quantidade;
  único por par)
- movimentacoes (tipo: entrada/saida/transferencia, material_id, quantidade,
  almoxarifado_origem_id, almoxarifado_destino_id, user_id)
- audit_logs (operacao, payload JSON, user_id, ip, created_at)

## Regras de negócio
- Transferência (parcial ou total) entre almoxarifados deve ser TRANSACIONAL
  (DB::transaction).
- Almoxarifado com material associado NÃO pode ser excluído; exigir a transferência
  de todo o estoque antes, com mensagem clara ao usuário.
- Toda operação relevante gera registro em audit_logs.

## Autenticação
- Login próprio, telas Blade em Bootstrap (sem Breeze/Tailwind).

## Convenções
- Nomes de tabelas/colunas em português conforme acima.
- Commits descritivos. Nunca commitar .env nem a pasta vendor.
