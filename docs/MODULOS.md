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
