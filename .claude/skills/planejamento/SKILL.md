---
name: planejamento
description: Gera ou atualiza o plano de desenvolvimento do projeto a partir de docs/CONTEXTO.md. Use SEMPRE que o usuário pedir um plano, quiser planejar ou organizar as etapas do projeto, perguntar "quais as etapas" ou "por onde começar", ou disser algo como "crie o plano", "planeje o desenvolvimento" ou "atualize o plano" — mesmo que não use a palavra "plano" explicitamente. Não implementa código; apenas produz o plano em docs/PLANO.md.
---

# Skill: Planejamento de Desenvolvimento

Produz um plano de desenvolvimento incremental para o projeto Controle de Estoque,
gravado em `docs/PLANO.md`.

## Passos
1. Leia `docs/CONTEXTO.md` por inteiro (é a fonte da verdade). Se precisar, consulte
   também o PDF original em `docs/`.
2. Se já existir `docs/PLANO.md`, leia-o: preserve as etapas concluídas e ajuste o que
   for necessário, em vez de recomeçar do zero.
3. Quebre o trabalho em ETAPAS pequenas, ordenadas por dependência e pelos pesos de
   avaliação do CONTEXTO (primeiro o obrigatório de peso Alta, depois Média, por fim
   os bônus: testes, OpenTelemetry, DataTables server-side).
4. Cada etapa deve poder ser desenvolvida isoladamente em uma sessão.

## Formato de cada etapa em docs/PLANO.md
- Título: `## Etapa N — <nome>`
- **Status**: `[ ]` pendente / `[x]` concluída
- **Objetivo**: o que a etapa entrega.
- **Camadas/arquivos**: o que será criado ou alterado (migrations, models, services,
  controllers, requests, views, rotas, testes).
- **Passos**: lista concreta do que fazer.
- **Critérios de aceite**: como saber que ficou pronto.
- **Verificação**: comandos para validar (sempre via container, ex.:
  `docker compose exec app php artisan migrate`, `... php artisan test`).

## Regras
- NÃO escreva código de implementação aqui — só o plano.
- Respeite as convenções do CONTEXTO (Services para regra de negócio, controllers
  enxutos, Blade sem lógica, transações nas transferências, auditoria).
- Ao terminar, mostre um resumo das etapas e pergunte se pode salvar/atualizar o
  `docs/PLANO.md`.
