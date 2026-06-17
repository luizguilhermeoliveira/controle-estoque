# Módulos do Projeto — Controle de Estoque

> Documentação viva da aplicação: registra **o que cada módulo/arquivo faz** e por quê.
> Atualizado de forma incremental a cada etapa do `docs/PLANO.md`. Serve como contexto
> de leitura para a skill de desenvolvimento e como referência rápida do que já existe
> (para reaproveitar em vez de recriar).
>
> Convenção: cada etapa concluída acrescenta/atualiza uma seção aqui, descrevendo os
> arquivos criados, suas responsabilidades e os pontos de extensão para as próximas etapas.

---

## Etapa 1 — Fundação de dados

Schema completo do domínio (migrations), models refletindo o schema com relacionamentos
explícitos, e seeders com dados iniciais.

### Migrations (`database/migrations`)

| Migration | Tabela | Responsabilidade |
|-----------|--------|------------------|
| `..._create_almoxarifados_table` | `almoxarifados` | `nome`, `localizacao`, timestamps. |
| `..._create_materiais_table` | `materiais` | `codigo_interno` (único), `descricao`, `quantidade_total` (default 0), timestamps. |
| `..._create_movimentacoes_table` | `movimentacoes` | Registro de movimentações: `tipo` (enum `entrada`/`saida`/`transferencia`), `material_id`, `quantidade`, `almoxarifado_origem_id`/`almoxarifado_destino_id` (nullable), `user_id`. |
| `..._create_audit_logs_table` | `audit_logs` | Trilha de auditoria: `operacao`, `payload` (JSON), `user_id` (nullable), `ip`, apenas `created_at`. |
| `..._create_almoxarifado_material_table` | `almoxarifado_material` | Pivot de estoque: `almoxarifado_id`, `material_id`, `quantidade`. **Índice único no par** `(almoxarifado_id, material_id)`. |

**Notas de implementação:**
- Ordem dos arquivos do pivot ajustada (timestamp `...003111`) para rodar **após**
  `almoxarifados` e `materiais`, já que referencia ambos via FK.
- FKs do pivot e de `movimentacoes.material_id` usam `cascadeOnDelete`; as FKs de
  almoxarifado/usuário em `movimentacoes` e `audit_logs` usam `nullOnDelete` para
  preservar o histórico.

### Models (`app/Models`)

Todos com `$table` explícito (evita pluralização errada do Eloquent —
`materials`/`movimentacaos`) e `#[Fillable]` no estilo de atributos do Laravel 13
(mesmo padrão do `User`).

| Model | Tabela | Relacionamentos |
|-------|--------|-----------------|
| `Almoxarifado` | `almoxarifados` | `materiais()` → `belongsToMany(Material)` com `withPivot('quantidade')`. |
| `Material` | `materiais` | `almoxarifados()` → `belongsToMany(Almoxarifado)` com `withPivot('quantidade')`; `movimentacoes()` → `hasMany(Movimentacao)`. |
| `Movimentacao` | `movimentacoes` | `material()`, `origem()`/`destino()` (`belongsTo(Almoxarifado)` em colunas distintas), `user()`. |
| `AuditLog` | `audit_logs` | `user()`. `payload` cast para `array`; `$timestamps = false` (só `created_at`). |
| `User` (ajuste) | `users` | `movimentacoes()` → `hasMany(Movimentacao)`. |

**Estoque** é modelado apenas como pivot com `withPivot('quantidade')` — sem model
dedicado. `quantidade_total` do material deve ser sempre igual à soma das quantidades
no pivot (mantido dentro de transações nas etapas de movimentação).

### Seeders (`database/seeders`)

| Seeder | Responsabilidade |
|--------|------------------|
| `DatabaseSeeder` | Cria o usuário admin e orquestra os demais seeders. |
| `AlmoxarifadoSeeder` | 3 almoxarifados de exemplo (`firstOrCreate` por nome). |
| `MaterialSeeder` | 4 materiais com saldo inicial distribuído no pivot via `sync`; `quantidade_total` = soma do pivot. |

**Usuário admin padrão (login da Etapa 2):** `admin@estoque.test` / senha `password`.

### Pontos de extensão para as próximas etapas
- Etapa 2 (auth) usa o usuário admin do `DatabaseSeeder`.
- Movimentações (Etapas 6–7) gravam em `movimentacoes` e ajustam o pivot + `quantidade_total`.
- Toda operação relevante deve gravar em `audit_logs` (infra criada na Etapa 3).

---

## Etapa 2 — Autenticação própria

Login/logout próprios em Blade + Bootstrap (sem Breeze/Tailwind), guard `web` padrão.
O usuário autenticado será o responsável registrado nas movimentações e audit_logs
(Etapas 6–7).

### Controller (`app/Http/Controllers/Auth`)

| Arquivo | Responsabilidade |
|---------|------------------|
| `LoginController` | Enxuto: `create()` exibe o formulário; `store(LoginRequest)` delega a autenticação ao request, regenera a sessão e redireciona para `intended('/')`; `destroy()` faz logout, invalida a sessão e regenera o token CSRF, voltando para `login`. |

### FormRequest (`app/Http/Requests/Auth`)

| Arquivo | Responsabilidade |
|---------|------------------|
| `LoginRequest` | Valida `email`/`password` (mensagens em PT-BR) e concentra a autenticação em `authenticate()`: `Auth::attempt` com suporte a "lembrar-me" e **rate limiting** (5 tentativas por e-mail+IP, evento `Lockout`). Credenciais inválidas e bloqueio por excesso de tentativas viram `ValidationException` com mensagem amigável — sem stack trace. |

### View (`resources/views/auth`)

| Arquivo | Responsabilidade |
|---------|------------------|
| `login.blade.php` | Tela de login **autônoma** (HTML completo + Bootstrap 5 via CDN), pois o layout master só nasce na Etapa 3. Só apresentação: erros vêm de `$errors`, status de `session('status')`; campos com `old()` e marcação `is-invalid`. Sem `@php` nem lógica. Inclui meta `csrf-token` e checkbox "Manter conectado". |

### Rotas (`routes/web.php`)

- Grupo `guest`: `GET /login` (`login`) e `POST /login` (`login.store`).
- Grupo `auth`: `POST /logout` (`logout`) e a rota raiz `GET /` (placeholder `welcome`,
  a ser substituída pelo dashboard/layout na Etapa 3).
- O middleware `auth` redireciona visitantes para a rota nomeada `login`
  automaticamente; o middleware `guest` redireciona usuários já logados para `/`.

### Notas de implementação
- **Permissões de arquivo:** `php artisan make:*` roda como `root` no container e gera
  arquivos com dono `root`. Use `docker compose exec --user appuser app ...` (UID 1000,
  igual ao host) para que os arquivos fiquem editáveis no host.
- Guard `web` (sessão) é o padrão do `config/auth.php` — nenhuma alteração necessária.

### Pontos de extensão
- Etapa 3 cria o layout master (`layouts/app`) com navbar exibindo o usuário logado e o
  botão de logout (form `POST /logout` + `@csrf`), e migra a `welcome` para um dashboard.
- `Auth::user()` / `auth()->id()` fornece o responsável para `movimentacoes.user_id` e
  `audit_logs.user_id` nas etapas seguintes.

---

## Etapa 3 — Layout base + Auditoria

Layout Bootstrap reaproveitável (navbar, flash messages, assets) e a infraestrutura
transversal de auditoria (`AuditLogService`) que as demais etapas consomem.

### Views de layout (`resources/views`)

| Arquivo | Responsabilidade |
|---------|------------------|
| `layouts/app.blade.php` | Layout master. `@yield('titulo')` e `@yield('conteudo')`; `@stack('styles')`/`@stack('scripts')` para assets por página. Inclui via CDN: Bootstrap 5 (+ Icons), DataTables (integração Bootstrap 5), jQuery, SweetAlert2. Estrutura navbar + `main.container` + footer (layout sticky com flexbox). Inclui `partials.navbar` e `partials.alerts`. Meta `csrf-token` disponível para chamadas AJAX. |
| `partials/navbar.blade.php` | Navbar escura responsiva. Marca/links à esquerda; à direita, dropdown com o nome do usuário (`@auth` + `auth()->user()->name`) e botão **Sair** (form `POST {{ route('logout') }}` + `@csrf`). Só apresentação. |
| `partials/alerts.blade.php` | Flash messages lidas de `session('success')` / `session('error')` e a lista de `$errors` de validação, como alertas Bootstrap dismissíveis. Sem lógica de negócio. |
| `dashboard.blade.php` | Página inicial (rota `/`, nome `dashboard`) que `@extends('layouts.app')`. Recebe `$usuario` do controller/closure (Blade sem lógica). Cards de atalho para Almoxarifados/Materiais/Movimentações — placeholders a serem ligados nas próximas etapas. Substitui a antiga `welcome.blade.php` (removida). |

### Service (`app/Services`)

| Arquivo | Responsabilidade |
|---------|------------------|
| `AuditLogService` | `registrar(string $operacao, array $payload = [], ?Request $request = null): AuditLog`. Grava em `audit_logs` capturando `user_id` (de `Auth::id()`), `ip` (do request atual — usa `request()` quando não informado) e `created_at` (`now()`, pois o model tem `$timestamps = false`). Ponto único de auditoria consumido pelos services de domínio. |

### Rotas (`routes/web.php`)

- `GET /` passou a renderizar `dashboard` (nome de rota `dashboard`), passando `usuario`
  pela closure; o placeholder `welcome` foi removido.

### Notas de implementação
- Assets carregados por CDN (sem build Vite) para simplificar a entrega dockerizada.
  jQuery vem **antes** do DataTables (dependência); Bootstrap bundle inclui o Popper.
- Convenção de nomes de seção/yield em PT-BR: `@section('titulo')` e `@section('conteudo')`.

### Pontos de extensão
- Telas das próximas etapas devem `@extends('layouts.app')` e preencher `@section('conteudo')`;
  scripts específicos (init de DataTables, SweetAlert2) vão em `@push('scripts')`.
- Services de domínio (Almoxarifado/Material/Transferência/Estoque) injetam
  `AuditLogService` e chamam `registrar()` em cada operação relevante, dentro da mesma
  transação quando aplicável.
- Flash de sucesso/erro: redirecionar com `->with('success', ...)` / `->with('error', ...)`;
  o `partials/alerts` já os exibe.

---

## Etapa 4 — CRUD Almoxarifados

CRUD completo de almoxarifados (listar/criar/editar/excluir) com busca/paginação via
DataTables, confirmação de exclusão com SweetAlert2 e auditoria em cada operação. Aplica
a regra de domínio: almoxarifado com estoque associado não pode ser excluído.

### Exceção de domínio (`app/Exceptions`)

| Arquivo | Responsabilidade |
|---------|------------------|
| `RegraDeNegocioException` | `RuntimeException` com **mensagem amigável** ao usuário final (sem stack trace). Lançada pelos services quando uma operação viola uma regra de negócio; capturada pelos controllers e convertida em flash de erro. Reaproveitável nas próximas etapas (saída sem saldo, transferência inválida etc.). |

### Service (`app/Services`)

| Arquivo | Responsabilidade |
|---------|------------------|
| `AlmoxarifadoService` | Regra de negócio dos almoxarifados, mantendo o controller enxuto. Injeta `AuditLogService`. `criar`/`atualizar`/`excluir` rodam em `DB::transaction` e auditam (`almoxarifado.criado`/`atualizado`/`excluido`); a atualização registra valores anterior/atual no payload. `excluir` bloqueia (lança `RegraDeNegocioException`) se `possuiEstoque()` — pivot com `quantidade > 0`; caso contrário faz `detach()` de pivôs zerados e exclui. |

### Controller (`app/Http/Controllers`)

| Arquivo | Responsabilidade |
|---------|------------------|
| `AlmoxarifadoController` | Resource **sem `show`**. Injeta `AlmoxarifadoService`. `index` lista com `withCount('materiais')` ordenado por nome; `create`/`edit` exibem formulários; `store`/`update` validam via FormRequest, delegam ao service e redirecionam com flash de sucesso; `destroy` captura `RegraDeNegocioException` e redireciona com flash de erro. |

### FormRequests (`app/Http/Requests`)

| Arquivo | Responsabilidade |
|---------|------------------|
| `AlmoxarifadoStoreRequest` / `AlmoxarifadoUpdateRequest` | `authorize()` = `true` (acesso já protegido pelo middleware `auth`). Regras idênticas: `nome` e `localizacao` obrigatórios, string, `max:255`. Mensagens em PT-BR. |

### Views (`resources/views/almoxarifados`)

| Arquivo | Responsabilidade |
|---------|------------------|
| `index.blade.php` | Tabela `#tabela-almoxarifados` (nome, localização, contagem de materiais, ações). Inicializa **DataTables** (idioma pt-BR via CDN, coluna de ações não ordenável) só quando há linhas. Botão excluir dispara confirmação **SweetAlert2** que então submete o form `DELETE` (`@method('DELETE')`). Estado vazio tratado com `@forelse`. |
| `create.blade.php` / `edit.blade.php` | Formulários Bootstrap; `edit` usa `@method('PUT')`. Ambos incluem o partial de campos. |
| `_form.blade.php` | Partial com os campos `nome`/`localizacao`, reaproveitado por create/edit. Repovoa com `old(..., $almoxarifado->campo ?? '')` e marca `is-invalid` via `@error`. Sem `@php`. |

### Rotas (`routes/web.php`)

- `Route::resource('almoxarifados', AlmoxarifadoController::class)->except('show')` no grupo
  `auth`, com `->parameters(['almoxarifados' => 'almoxarifado'])` para route-model binding
  no singular (`{almoxarifado}`).

### Integração com o layout

- Navbar (`partials/navbar`) ganhou link **Almoxarifados**; o card de Almoxarifados do
  `dashboard` agora linka para `almoxarifados.index`.

### Notas de implementação
- Route-model binding implícito (`Almoxarifado $almoxarifado`) — 404 automático para id inexistente.
- A confirmação destrutiva fica na view (SweetAlert2 intercepta o `submit`); a regra de
  bloqueio por estoque é do **server** (`AlmoxarifadoService`), nunca confiando só no front.

### Pontos de extensão
- `RegraDeNegocioException` é o padrão para erros de negócio amigáveis nas Etapas 5–7.
- O par Service + FormRequests + resource controller (sem `show`) + partial `_form` é o
  molde para o **CRUD de Materiais (Etapa 5)**.
- A Etapa 8 pode evoluir a init de DataTables para um helper compartilhado; a Etapa 10
  pode trocar o modo client-side por server-side (endpoint `data()`).

---

## Etapa 5 — CRUD Materiais

CRUD completo de materiais (listar/criar/editar/excluir) com busca/paginação via
DataTables, confirmação de exclusão com SweetAlert2 e auditoria em cada operação.
Reaproveita o molde da Etapa 4 (Service + FormRequests + resource controller sem `show`
+ partial `_form`). O cadastro lança o estoque inicial no pivot e mantém
`quantidade_total` coerente com a soma das quantidades.

### Service (`app/Services`)

| Arquivo | Responsabilidade |
|---------|------------------|
| `MaterialService` | Regra de negócio dos materiais, mantendo o controller enxuto. Injeta `AuditLogService`. `criar`/`atualizar`/`excluir` rodam em `DB::transaction` e auditam (`material.criado`/`atualizado`/`excluido`). `criar` grava `quantidade_total = quantidade_inicial` e, quando essa quantidade é `> 0`, faz `attach` do saldo no pivot do almoxarifado informado. `atualizar` altera **apenas** `codigo_interno`/`descricao` (registra anterior/atual no payload) — não toca pivot nem `quantidade_total`, preservando a consistência do estoque (que só muda nas movimentações das Etapas 6–7). `excluir` faz `detach()` dos vínculos de estoque e remove o material. |

### Controller (`app/Http/Controllers`)

| Arquivo | Responsabilidade |
|---------|------------------|
| `MaterialController` | Resource **sem `show`**. Injeta `MaterialService`. `index` lista por `codigo_interno`; `create` envia os almoxarifados (`orderBy('nome')`) para o select de estoque inicial; `store`/`update` validam via FormRequest, delegam ao service e redirecionam com flash de sucesso; `destroy` exclui e redireciona com flash (exclusão de material é livre — sem regra de bloqueio). |

### FormRequests (`app/Http/Requests`)

| Arquivo | Responsabilidade |
|---------|------------------|
| `MaterialStoreRequest` | `authorize()` = `true`. `codigo_interno` obrigatório, `max:255`, **`unique:materiais`**; `descricao` obrigatória, `max:255`; `quantidade_inicial` obrigatória, `integer`, `min:0`; `almoxarifado_id` obrigatório **apenas quando `quantidade_inicial > 0`** (via `Rule::requiredIf`), `nullable`, `exists:almoxarifados,id`. Mensagens em PT-BR. |
| `MaterialUpdateRequest` | Valida só os dados cadastrais: `codigo_interno` (`unique` ignorando o próprio via `Rule::unique(...)->ignore($this->route('material'))`) e `descricao`. Não recebe campos de estoque. Mensagens em PT-BR. |

### Views (`resources/views/materiais`)

| Arquivo | Responsabilidade |
|---------|------------------|
| `index.blade.php` | Tabela `#tabela-materiais` (código interno, descrição, quantidade total, ações). Inicializa **DataTables** (pt-BR, coluna de ações não ordenável) só quando há linhas; exclusão dispara confirmação **SweetAlert2** que submete o form `DELETE`. Estado vazio via `@forelse`. |
| `create.blade.php` | Inclui o `_form` (campos cadastrais) e acrescenta a seção **Estoque inicial**: `select` de almoxarifado (`@selected` repovoado com `old`) + `quantidade_inicial` (`number`, `min=0`, default 0, com `form-text` explicando que 0 cadastra sem estoque). |
| `edit.blade.php` | Inclui o `_form` com `@method('PUT')`. Edita só os dados cadastrais (sem campos de estoque). |
| `_form.blade.php` | Partial com `codigo_interno`/`descricao`, reaproveitado por create/edit. Repovoa com `old(..., $material->campo ?? '')` e marca `is-invalid` via `@error`. Sem `@php`. |

### Rotas (`routes/web.php`)

- `Route::resource('materiais', MaterialController::class)->except('show')` no grupo `auth`,
  com `->parameters(['materiais' => 'material'])` para route-model binding no singular
  (`{material}`).

### Integração com o layout

- Navbar (`partials/navbar`) ganhou link **Materiais**; o card de Materiais do `dashboard`
  agora linka para `materiais.index`.

### Notas de implementação
- Exclusão de material é livre (diferente de almoxarifado): o `detach()` dos vínculos de
  estoque ocorre dentro da transação antes do `delete()`, e a operação é auditada.
- `quantidade_total` no cadastro reflete o saldo lançado no pivot; alterações de saldo
  ficam reservadas às movimentações (Etapas 6–7), que devem manter os dois sincronizados.

### Pontos de extensão
- As Etapas 6 (transferência) e 7 (entrada/saída) consomem o material e o pivot já
  populados; ao mover saldo, atualizam pivot **e** `quantidade_total` dentro da mesma
  transação, reusando o `AuditLogService`.
- A Etapa 10 pode adicionar endpoint `data()` server-side à listagem de materiais.
