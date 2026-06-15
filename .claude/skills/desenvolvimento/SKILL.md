---
name: desenvolvimento
description: Implementa uma etapa do plano de desenvolvimento do projeto Controle de Estoque. Use SEMPRE que o usuário pedir para desenvolver, implementar ou codificar algo, por exemplo "desenvolva a etapa 1", "implemente o CRUD de almoxarifados", "codifique a transferência de material" ou "faça a próxima etapa" — mesmo sem citar "etapa". Segue docs/CONTEXTO.md e docs/PLANO.md, respeita a arquitetura, roda comandos no container Docker e verifica o resultado.
---

# Skill: Desenvolvimento

Implementa a etapa solicitada do projeto, seguindo as convenções definidas.

## Antes de codificar
1. Leia `docs/CONTEXTO.md` (arquitetura, domínio, regras de negócio).
2. Leia `docs/PLANO.md` e localize a etapa pedida. Se o usuário disser "próxima etapa",
   pegue a primeira com status `[ ]`.
3. Implemente APENAS a etapa solicitada, salvo instrução em contrário. Em dúvida de
   escopo, pergunte antes.

## Convenções a respeitar (do CONTEXTO)
- Controllers enxutos; regra de negócio em `app/Services`.
- Validação via FormRequest.
- Blade só apresentação — sem lógica, sem `@php`. Variáveis vêm do Controller.
- Transferências e escritas multi-passo dentro de `DB::transaction`.
- Registrar `audit_logs` nas operações relevantes.
- Tratar exceções; nunca expor stack trace ao usuário.
- SOLID e Clean Code; mudanças focadas e legíveis.

## Execução
- Rode tudo no container: `docker compose exec app php artisan ...` /
  `docker compose exec app composer ...`.
- Prefira os comandos `make:` (model, migration, controller, request, test) a criar
  arquivos na mão.

## Ao terminar
1. Verifique: rode migrations/testes pertinentes e confirme que sobe sem erro.
2. Atualize `docs/PLANO.md` marcando a etapa como `[x]`.
3. Sugira uma mensagem de commit descritiva e pergunte antes de commitar/enviar.
