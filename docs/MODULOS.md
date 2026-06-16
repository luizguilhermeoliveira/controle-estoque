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
