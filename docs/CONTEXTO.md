# Guia de Implementação — Controle de Estoque

> Documento-fonte do projeto: a referência detalhada usada como contexto pelo
> Claude Code e pelas skills. O `CLAUDE.md` na raiz é o resumo sempre carregado;
> este arquivo é a versão completa.

## 1. Visão geral
Módulo de controle de estoque com entrada, saída e transferência de materiais entre
almoxarifados. Toda operação é registrada de forma auditável. Interface web funcional
e objetiva. Prioriza observabilidade e boas práticas. Teste técnico para vaga de
desenvolvedor Laravel.

## 2. Ambiente e execução
- Laravel 13 + PHP 8.4, MariaDB 11, Redis, Nginx — 100% dockerizado.
- App em http://localhost:3000. Banco: host interno `db`, porta 3306 (host: 3307).
- Comandos SEMPRE dentro do container `app`:
  - `docker compose exec app php artisan <comando>`
  - `docker compose exec app composer <comando>`
  - `docker compose exec app php artisan test`

## 3. Arquitetura e padrões (obrigatório)
- MVC do Laravel, com camada de serviço.
- Controllers enxutos: recebem a request, validam (via FormRequest), chamam o Service
  e devolvem a resposta. Sem regra de negócio no controller.
- Regra de negócio em `app/Services` (ex.: TransferenciaService, EstoqueService).
- Schema apenas via Migrations. Models refletem o schema, com relacionamentos
  explícitos (hasMany, belongsTo, belongsToMany) e `$fillable`.
- Blade é só apresentação. PROIBIDO lógica de negócio e diretivas `@php`/`@endphp`.
  Variáveis vêm exclusivamente do Controller.
- SOLID e Clean Code de forma consistente.
- Tratamento de exceções: nunca expor stack trace ao usuário final em produção;
  mensagens claras e amigáveis.

## 4. Modelo de dados
Tabelas (nomes explícitos em português):
- `almoxarifados`: id, nome, localizacao, timestamps.
- `materiais`: id, codigo_interno (único), descricao, quantidade_total, timestamps.
- `almoxarifado_material` (pivot/estoque): id, almoxarifado_id (FK), material_id (FK),
  quantidade, timestamps. Índice ÚNICO no par (almoxarifado_id, material_id).
- `movimentacoes`: id, tipo (entrada|saida|transferencia), material_id (FK),
  quantidade, almoxarifado_origem_id (FK, nullable), almoxarifado_destino_id
  (FK, nullable), user_id (FK), timestamps.
- `audit_logs`: id, operacao, payload (JSON), user_id (FK, nullable), ip, created_at.

Relacionamentos:
- Almoxarifado belongsToMany Material (via pivot, withPivot('quantidade')).
- Material belongsToMany Almoxarifado (idem).
- Movimentacao belongsTo Material, Almoxarifado (origem/destino) e User.
- `quantidade_total` do material = soma das quantidades no pivot (manter consistente
  dentro de transações).

## 5. Regras de negócio
- Transferência (parcial ou total) deve ser TRANSACIONAL (`DB::transaction`): debita
  origem, credita destino, registra movimentação e audit_log atomicamente. Validar
  saldo suficiente na origem.
- Almoxarifado com material associado (quantidade > 0 no pivot) NÃO pode ser excluído.
  Exigir transferência de todo o estoque antes; exibir mensagem clara ao usuário.
- Toda operação relevante (criar/editar/excluir/transferir) gera registro em
  `audit_logs` com operacao, payload, user_id, ip e timestamp.

## 6. Funcionalidades (acessíveis via web)
1. Cadastrar almoxarifado (nome + localizacao).
2. Listar almoxarifados com paginação e busca.
3. Editar e excluir almoxarifado (respeitando a regra de exclusão).
4. Cadastrar material (codigo_interno + descricao + quantidade).
5. Transferir material entre almoxarifados (parcial ou total).
6. Excluir material.

## 7. Frontend
- Blade + Bootstrap 5 (estilo/responsividade).
- DataTables para listagens de almoxarifados e materiais.
- SweetAlert2 para confirmações das operações.
- Diferencial (bônus): DataTables com Server-Side Processing, consumindo endpoint
  JSON dedicado no Controller.

## 8. Autenticação
- Login próprio com telas Blade em Bootstrap (sem Breeze/Tailwind).
- Usuário autenticado é o responsável registrado nas movimentações e audit_logs.

## 9. Logging e observabilidade
- Logging estruturado na tabela dedicada `audit_logs` (operação, payload, usuário,
  IP, timestamp).
- Diferencial (bônus): OpenTelemetry — extensão ext-opentelemetry (PECL no Dockerfile)
  + open-telemetry/sdk + opentelemetry-auto-laravel, com spans manuais nas operações
  críticas (transferências, exclusões); exportar OTLP para Collector + Jaeger no
  docker-compose. Demonstrar logs e métricas no README.

## 10. Testes
- Diferencial (bônus): testes unitários e/ou de integração dos principais endpoints.
- PHPUnit ou Pest, via `docker compose exec app php artisan test`. SQLite em memória
  no ambiente de teste.

## 11. DevOps (já implementado)
- Dockerfile (PHP-FPM 8.4 + extensões), Nginx, docker-compose (app + nginx + mariadb
  + redis). App em localhost:3000 (porta configurável via env).

## 12. Entrega e critérios de avaliação
Entrega: repositório GitHub; README detalhado (instalação, variáveis de ambiente,
execução via Docker, comandos de migrations/seeders, e logs/métricas se houver OTel);
app em localhost:3000; SOLID e Clean Code; exceções tratadas.

Pesos (orientam a prioridade):
- Alta: qualidade/organização (SOLID, Clean Code, doc); aderência ao MVC; corretude
  das funcionalidades; tratamento de exceções e validações.
- Média: Docker; logging auditável.
- Bônus: testes; OpenTelemetry; DataTables server-side.

## 13. Decisões de projeto (documentar no README)
- Nomes de tabelas/colunas em português, com `$table` explícito nos models para evitar
  a pluralização do Eloquent (materials/movimentacaos).
- Estoque modelado como pivot com `withPivot('quantidade')`, sem model dedicado.
- Autenticação própria em Bootstrap em vez de starter kit (Breeze usa Tailwind).
- Porta 3306 do MariaDB exposta como 3307 no host para evitar conflito local.
