# Plano de Desenvolvimento — Controle de Estoque

> Plano incremental derivado de `docs/CONTEXTO.md`. Cada etapa é pequena o suficiente
> para ser desenvolvida isoladamente em uma sessão. Ordem por dependência e pelos pesos
> de avaliação: obrigatório (Alta) → Média → bônus.
>
> Convenções obrigatórias (valem para todas as etapas): controllers enxutos, regra de
> negócio em Services, Blade só apresentação (sem `@php`), schema só via migrations,
> transferências transacionais, auditoria nas operações relevantes. Comandos sempre via
> container `app`.

## Etapa 1 — Fundação de dados

**Status**: `[x]` concluída

**Objetivo**: Criar todo o schema do domínio via migrations, os models refletindo o schema com relacionamentos explícitos e `$fillable`, e seeders com dados iniciais (usuário admin + exemplos), deixando a base pronta para as funcionalidades.

**Camadas/arquivos**:
- *Migrations* (`database/migrations`): `create_almoxarifados_table`,
  `create_materiais_table`, `create_almoxarifado_material_table`,
  `create_movimentacoes_table`, `create_audit_logs_table`.
- *Models* (`app/Models`): `Almoxarifado`, `Material`, `Movimentacao`, `AuditLog`
  (+ ajuste no `User` se necessário para relacionamentos).
- *Seeders* (`database/seeders`): `DatabaseSeeder` (usuário admin), `AlmoxarifadoSeeder`,
  `MaterialSeeder` com estoque inicial no pivot.

**Passos**:
1. Migration `almoxarifados`: `nome`, `localizacao`, timestamps.
2. Migration `materiais`: `codigo_interno` (unique), `descricao`, `quantidade_total`
   (default 0), timestamps.
3. Migration `almoxarifado_material`: FKs `almoxarifado_id`/`material_id`, `quantidade`,
   timestamps, **índice único no par** + `onDelete` coerente.
4. Migration `movimentacoes`: `tipo` (enum entrada|saida|transferencia), `material_id`,
   `quantidade`, `almoxarifado_origem_id` (nullable), `almoxarifado_destino_id`
   (nullable), `user_id`, FKs, timestamps.
5. Migration `audit_logs`: `operacao`, `payload` (json), `user_id` (nullable), `ip`,
   `created_at`.
6. Models com `$table` explícito (evitar pluralização errada: *materials/movimentacaos*),
   `$fillable` e relacionamentos: `Almoxarifado belongsToMany Material
   withPivot('quantidade')`; inverso em `Material`; `Movimentacao belongsTo`
   Material/Almoxarifado(origem,destino)/User; `User hasMany Movimentacao`.
7. Seeders: 1 usuário admin (login da Etapa 2), 2–3 almoxarifados, alguns materiais com
   saldo no pivot e `quantidade_total` consistente.

**Critérios de aceite**:
- `migrate:fresh --seed` roda sem erros e cria as 5 tabelas do domínio.
- Índice único `(almoxarifado_id, material_id)` presente no pivot.
- Relacionamentos navegáveis no tinker (ex.: `Almoxarifado::first()->materiais` traz
  `pivot.quantidade`).
- `quantidade_total` de cada material = soma do pivot.

**Verificação**:
```bash
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan tinker --execute="echo App\Models\Almoxarifado::with('materiais')->first()->materiais->first()->pivot->quantidade;"
```

## Etapa 2 — Autenticação própria

**Status**: `[x]` concluída

**Objetivo**: Login/logout próprios em Blade + Bootstrap (sem Breeze/Tailwind),
protegendo todo o app via middleware `auth`. Usuário autenticado será o responsável
registrado nas movimentações e audit_logs.

**Camadas/arquivos**:
- *Controllers*: `Auth/LoginController` (formulário, autenticação, logout).
- *Requests*: `LoginRequest` (validação de credenciais).
- *Views* (`resources/views/auth`): `login.blade.php`.
- *Rotas* (`routes/web.php`): rotas de login/logout; grupo `auth` para o resto.
- *Config*: garantir `guard` web padrão.

**Passos**:
1. Rotas GET/POST de login e POST de logout.
2. `LoginRequest` validando `email`/`password`.
3. `LoginController` usando `Auth::attempt`, regenerando sessão; logout com invalidação.
4. View de login em Bootstrap (sem starter kit).
5. Proteger rotas do app com middleware `auth` e redirecionar guests para login.
6. Tela de erro amigável para credenciais inválidas (sem stack trace).

**Critérios de aceite**:
- Login com o usuário do seeder funciona; credenciais inválidas mostram mensagem clara.
- Acesso a rota protegida sem sessão redireciona para `/login`.
- Logout encerra a sessão e redireciona para login.

**Verificação**:
```bash
docker compose exec app php artisan route:list
# Acessar http://localhost:3000 -> redireciona para /login; logar com o usuário admin.
```

## Etapa 3 — Layout base + Auditoria

**Status**: `[x]` concluída

**Objetivo**: Estabelecer o layout Bootstrap reaproveitável (navbar, container, flash
messages, assets) e a infraestrutura transversal de auditoria (`AuditLogService`) que as
demais etapas usarão.

**Camadas/arquivos**:
- *Views* (`resources/views/layouts`): `app.blade.php` (layout master), `partials`
  (navbar, alerts). Inclusão de Bootstrap 5, DataTables e SweetAlert2 (CDN ou Vite).
- *Services* (`app/Services`): `AuditLogService` (registrar operação, payload, user_id,
  ip, timestamp).
- *Support* (opcional): trait/helper para chamar auditoria a partir dos services.

**Passos**:
1. Layout master Bootstrap com `@yield`/`@section`, navbar com usuário logado e logout.
2. Partial de flash messages (success/error) lido de `session()` — sem lógica de negócio.
3. `AuditLogService::registrar(operacao, payload, request)` gravando em `audit_logs`
   com `user_id` e `ip` do request atual.
4. Incluir Bootstrap 5 + DataTables + SweetAlert2 nos assets do layout.

**Critérios de aceite**:
- Layout renderiza com navbar e área de mensagens; sem `@php` nas views.
- `AuditLogService` grava registro com todos os campos preenchidos.
- Assets de Bootstrap/DataTables/SweetAlert2 carregam na página.

**Verificação**:
```bash
docker compose exec app php artisan tinker --execute="app(App\Services\AuditLogService::class); echo 'ok';"
# Conferir o layout renderizado em uma página protegida.
```

## Etapa 4 — CRUD Almoxarifados

**Status**: `[ ]` pendente

**Objetivo**: Cadastro, listagem (com busca/paginação), edição e exclusão de
almoxarifados, respeitando a regra de que almoxarifado com estoque associado NÃO pode ser
excluído. Operações geram auditoria.

**Camadas/arquivos**:
- *Controller*: `AlmoxarifadoController` (resource).
- *Requests*: `AlmoxarifadoStoreRequest`, `AlmoxarifadoUpdateRequest`.
- *Service*: `AlmoxarifadoService` (criar/editar/excluir + chamada de auditoria + regra
  de exclusão).
- *Views* (`resources/views/almoxarifados`): `index`, `create`, `edit`.
- *Rotas*: resource `almoxarifados` no grupo `auth`.

**Passos**:
1. Listagem com tabela (DataTables client-side por enquanto) e busca.
2. Formulários de criação/edição validados por FormRequest.
3. `AlmoxarifadoService` para persistência e auditoria de cada operação.
4. Regra de exclusão: bloquear se houver pivot com `quantidade > 0`; mensagem clara
   pedindo transferência prévia do estoque.
5. Confirmação de exclusão com SweetAlert2.

**Critérios de aceite**:
- CRUD completo funcionando via web.
- Excluir almoxarifado com estoque é bloqueado com mensagem amigável; sem estoque, exclui.
- Cada criar/editar/excluir gera `audit_log`.

**Verificação**:
```bash
docker compose exec app php artisan route:list --name=almoxarifados
# Testar criação, edição e tentativa de exclusão com/sem estoque pela interface.
```

## Etapa 5 — CRUD Materiais

**Status**: `[ ]` pendente

**Objetivo**: Cadastro (codigo_interno único + descricao + quantidade), listagem e
exclusão de materiais. Operações geram auditoria.

**Camadas/arquivos**:
- *Controller*: `MaterialController`.
- *Requests*: `MaterialStoreRequest`, `MaterialUpdateRequest`.
- *Service*: `MaterialService` (criar/editar/excluir + auditoria; manter
  `quantidade_total` consistente).
- *Views* (`resources/views/materiais`): `index`, `create`, `edit`.
- *Rotas*: resource `materiais` no grupo `auth`.

**Passos**:
1. Listagem com DataTables e busca.
2. Cadastro validando `codigo_interno` único; definir estoque inicial (em qual
   almoxarifado) e `quantidade_total` coerente.
3. Edição de descrição (sem quebrar consistência de estoque).
4. Exclusão com confirmação (SweetAlert2) e auditoria.

**Critérios de aceite**:
- CRUD funcionando; `codigo_interno` duplicado é rejeitado com mensagem clara.
- `quantidade_total` reflete o saldo no pivot após cadastro.
- Operações auditadas.

**Verificação**:
```bash
docker compose exec app php artisan route:list --name=materiais
# Testar cadastro com codigo_interno repetido (deve falhar) e exclusão pela interface.
```

## Etapa 6 — Transferência de material (transacional)

**Status**: `[ ]` pendente

**Objetivo**: Transferir material (parcial ou total) entre almoxarifados de forma
ATÔMICA: debita origem, credita destino, registra `movimentacao` (tipo transferencia) e
`audit_log` dentro de `DB::transaction`, validando saldo na origem.

**Camadas/arquivos**:
- *Controller*: `TransferenciaController` (form + store).
- *Requests*: `TransferenciaRequest` (origem ≠ destino, quantidade > 0, material/saldo).
- *Service*: `TransferenciaService` (regra transacional + auditoria + movimentação).
- *Views* (`resources/views/transferencias`): `create`.
- *Rotas*: rotas de transferência no grupo `auth`.

**Passos**:
1. Formulário: material, almoxarifado origem, destino, quantidade.
2. Validação: origem ≠ destino, quantidade ≤ saldo na origem, quantidade > 0.
3. `TransferenciaService` em `DB::transaction`: decrementa pivot origem (remove par se
   zerar), incrementa/cria pivot destino, cria `movimentacao`, chama auditoria.
4. Mensagens de sucesso/erro; confirmação com SweetAlert2.
5. Garantir rollback em qualquer falha intermediária.

**Critérios de aceite**:
- Transferência parcial e total funcionam e mantêm saldos corretos.
- Saldo insuficiente é bloqueado com mensagem clara, sem alterar dados.
- Cada transferência gera `movimentacao` e `audit_log`; falha causa rollback total.

**Verificação**:
```bash
docker compose exec app php artisan tinker --execute="echo App\Models\Movimentacao::where('tipo','transferencia')->count();"
# Testar transferência parcial, total e com saldo insuficiente pela interface.
```

## Etapa 7 — Entrada e saída de estoque

**Status**: `[ ]` pendente

**Objetivo**: Movimentações de entrada (crédito) e saída (débito) de material em um
almoxarifado, atualizando pivot e `quantidade_total`, com auditoria — completando os três
tipos de movimentação do domínio.

**Camadas/arquivos**:
- *Controller*: `MovimentacaoController` (entrada/saída).
- *Requests*: `EntradaRequest`, `SaidaRequest`.
- *Service*: `EstoqueService` (creditar/debitar transacional + movimentacao + auditoria).
- *Views* (`resources/views/movimentacoes`): formulários de entrada e saída; histórico.
- *Rotas*: rotas no grupo `auth`.

**Passos**:
1. Formulário de entrada: material, almoxarifado, quantidade → credita e registra
   `movimentacao` (entrada).
2. Formulário de saída: valida saldo, debita e registra `movimentacao` (saida).
3. `EstoqueService` transacional, mantendo `quantidade_total` consistente + auditoria.
4. Listagem/histórico de movimentações (opcional, com filtro por tipo).

**Critérios de aceite**:
- Entrada e saída atualizam pivot e `quantidade_total` corretamente.
- Saída maior que o saldo é bloqueada com mensagem clara.
- Movimentações e audit_logs gerados; falhas revertidas.

**Verificação**:
```bash
docker compose exec app php artisan tinker --execute="echo App\Models\Movimentacao::whereIn('tipo',['entrada','saida'])->count();"
# Testar entrada e saída (inclusive saída sem saldo) pela interface.
```

## Etapa 8 — Frontend (DataTables + SweetAlert2)

**Status**: `[ ]` pendente

**Objetivo**: Refinar listagens com DataTables (busca, ordenação, paginação) e
padronizar confirmações de operações destrutivas/críticas com SweetAlert2 em todo o app.

**Camadas/arquivos**:
- *Views*: ajustes nas listagens de almoxarifados, materiais e movimentações.
- *Assets* (`resources/js`/`resources/css` ou CDN): inicialização de DataTables e
  helpers de SweetAlert2.

**Passos**:
1. Inicializar DataTables nas tabelas com idioma pt-BR.
2. Padronizar confirmação de exclusão/transferência com SweetAlert2.
3. Feedback de sucesso/erro consistente (toast/alert).
4. Garantir responsividade Bootstrap.

**Critérios de aceite**:
- Listagens com busca/ordenação/paginação funcionando.
- Operações críticas pedem confirmação via SweetAlert2.
- Sem lógica de negócio nas views.

**Verificação**:
```bash
# Conferir as listagens e confirmações pela interface em http://localhost:3000.
```

## Etapa 9 — Testes (bônus)

**Status**: `[ ]` pendente

**Objetivo**: Testes de feature/integração dos principais endpoints e testes unitários
das regras críticas (transferência, regra de exclusão, saldo insuficiente), com SQLite em
memória.

**Camadas/arquivos**:
- *Tests* (`tests/Feature`, `tests/Unit`): casos para auth, CRUDs, transferência,
  entrada/saída e regra de exclusão.
- *Config*: `phpunit.xml` com SQLite em memória no ambiente de teste.

**Passos**:
1. Configurar ambiente de teste (SQLite memória, `RefreshDatabase`).
2. Testes de auth (login obrigatório).
3. Testes de transferência (parcial, total, saldo insuficiente, atomicidade).
4. Testes da regra de exclusão de almoxarifado com estoque.
5. Testes de entrada/saída e auditoria.

**Critérios de aceite**:
- `php artisan test` verde.
- Cobertura das regras de negócio críticas.

**Verificação**:
```bash
docker compose exec app php artisan test
```

## Etapa 10 — DataTables server-side (bônus)

**Status**: `[ ]` pendente

**Objetivo**: Processamento server-side das listagens, consumindo endpoints JSON
dedicados nos controllers (paginação/ordenação/busca no banco).

**Camadas/arquivos**:
- *Controllers*: métodos `data()` retornando JSON no formato DataTables.
- *Views*: DataTables apontando para o endpoint (`serverSide: true`).
- *Rotas*: rotas JSON dedicadas.

**Passos**:
1. Endpoint JSON para almoxarifados e materiais (draw, recordsTotal, recordsFiltered,
   data).
2. Aplicar busca/ordenação/paginação no query builder.
3. Ajustar views para server-side.

**Critérios de aceite**:
- Listagens grandes paginam no servidor; busca/ordenação respondem via JSON.

**Verificação**:
```bash
docker compose exec app php artisan route:list | grep -i data
# Conferir requisições XHR do DataTables na interface.
```

## Etapa 11 — OpenTelemetry (bônus)

**Status**: `[ ]` pendente

**Objetivo**: Observabilidade com OpenTelemetry: spans manuais nas operações críticas
(transferências, exclusões), exportando OTLP para Collector + Jaeger via docker-compose.

**Camadas/arquivos**:
- *Docker*: `ext-opentelemetry` (PECL) no Dockerfile; serviços Collector + Jaeger no
  `docker-compose.yml`.
- *Composer*: `open-telemetry/sdk`, `open-telemetry/opentelemetry-auto-laravel`.
- *Services*: spans manuais em `TransferenciaService`, `EstoqueService`,
  `AlmoxarifadoService`.
- *Docs*: seção no README com logs/métricas.

**Passos**:
1. Adicionar extensão e pacotes; configurar exportador OTLP.
2. Subir Collector + Jaeger no compose.
3. Instrumentar operações críticas com spans manuais.
4. Documentar como visualizar traces/métricas no README.

**Critérios de aceite**:
- Traces das operações críticas visíveis no Jaeger.
- README com instruções de observabilidade.

**Verificação**:
```bash
docker compose up -d
# Executar uma transferência e conferir o trace no Jaeger.
```

## Etapa 12 — Documentação e entrega (README)

**Status**: `[ ]` pendente

**Objetivo**: Entregar o README detalhado exigido pelo enunciado (peso Alta —
Documentação) e fechar os requisitos de entrega: instalação, execução via Docker,
variáveis de ambiente, comandos de migrations/seeders, decisões de projeto documentadas
e demonstração de logs/métricas (se OTel implementado).

**Camadas/arquivos**:
- *Docs* (`README.md` na raiz): seções de instalação, configuração e uso.
- Ajustes finais de `.env.example`, mensagens de erro amigáveis e revisão geral.

**Passos**:
1. README com: visão geral, stack, pré-requisitos, subir via Docker, app em
   `localhost:3000` (porta configurável via `.env`).
2. Variáveis de ambiente documentadas + `.env.example` coerente.
3. Comandos de migrations/seeders e usuário admin padrão para login.
4. Seção "Decisões de projeto": nomes em português + `$table` explícito; estoque como
   pivot com `withPivot`; auth própria em Bootstrap (em vez de Breeze/Tailwind, e por que
   há login mesmo não sendo exigido explicitamente); porta 3307 do MariaDB no host.
5. Se OTel implementado: como visualizar logs e métricas (Collector/Jaeger).
6. Conferir tratamento de exceções: nenhuma stack trace exposta ao usuário final.

**Critérios de aceite**:
- README permite clonar e subir o projeto do zero seguindo só as instruções.
- Decisões de projeto documentadas (atende à orientação do enunciado).
- App acessível em `localhost:3000`; sem stack trace exposta em erros.

**Verificação**:
```bash
# Seguir o README do zero em ambiente limpo:
docker compose up -d --build
docker compose exec app php artisan migrate:fresh --seed
# Acessar http://localhost:3000 e validar login + funcionalidades.
```
