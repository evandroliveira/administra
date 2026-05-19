# Checklist de ativacao do Asaas em producao

Este projeto ja possui a integracao do Asaas no codigo. Para ativar em producao, faltam apenas as configuracoes de ambiente e do painel.

## 1. Variaveis obrigatorias no .env da VPS

Use estes valores no arquivo .env de producao:

```env
BILLING_PROVIDER=asaas
BILLING_GRACE_DAYS=3
ASAAS_API_KEY=
ASAAS_BASE_URL=https://api.asaas.com/v3
ASAAS_BILLING_TYPE=UNDEFINED
ASAAS_SUBSCRIPTION_CYCLE=MONTHLY
ASAAS_WEBHOOK_TOKEN=
ASAAS_TIMEOUT=30
```

Preencha assim:

- `BILLING_PROVIDER` deve ser `asaas`.
- `ASAAS_API_KEY` deve ser a chave de API de producao da conta Asaas.
- `ASAAS_WEBHOOK_TOKEN` deve ser um segredo longo e aleatorio, igual no .env e no painel do Asaas.
- `ASAAS_BASE_URL` deve permanecer `https://api.asaas.com/v3` em producao.
- `ASAAS_BILLING_TYPE` esta em `UNDEFINED` por padrao para o Asaas definir a melhor forma de cobranca disponivel.
- `ASAAS_SUBSCRIPTION_CYCLE` esta em `MONTHLY`, que e o ciclo esperado hoje pelo projeto.
- `ASAAS_TIMEOUT` pode permanecer `30`.

## 2. Aplicar a configuracao na VPS

Depois de salvar o .env, rode:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:list --name=billing.webhooks.asaas
php artisan tinker --execute="dump(config('billing.provider')); dump(config('billing.asaas.base_url')); dump(config('billing.asaas.api_key') !== ''); dump(config('billing.asaas.webhook_token') !== '');"
```

Resultado esperado:

- a rota `billing.webhooks.asaas` deve aparecer apontando para `POST /webhooks/asaas`
- o primeiro `dump()` deve retornar `"asaas"`
- o segundo `dump()` deve retornar `"https://api.asaas.com/v3"`
- os dois ultimos `dump()` devem retornar `true`

## 3. Configurar o webhook no painel do Asaas

No painel do Asaas, configure:

- URL do webhook: `https://www.lojagerencia.com.br/webhooks/asaas`
- Token do webhook: o mesmo valor definido em `ASAAS_WEBHOOK_TOKEN`
- Se a conta estiver com restricao por IP, libere o IP publico da VPS

Observacoes importantes:

- a rota do webhook ja esta pronta no projeto
- o webhook ja esta liberado de CSRF
- se o token nao bater, a aplicacao responde `403` com a mensagem `Token de webhook invalido para Asaas.`

## 4. Dados obrigatorios antes da primeira cobranca

Antes de sincronizar a assinatura com o Asaas, confirme estes dados da empresa:

- telefone valido com DDD
- CPF ou CNPJ valido para planos pagos
- email da empresa ou do usuario administrador preenchido

Sem isso, a integracao pode falhar ao criar ou atualizar o cliente no gateway.

## 5. Validacao funcional

Depois da configuracao:

1. entre com um usuario admin
2. abra a tela `/assinatura`
3. gere a cobranca inicial ou altere para um plano pago
4. confirme que o sistema redireciona para a URL de checkout retornada pelo Asaas
5. no painel do Asaas, use o teste de webhook ou acompanhe um evento real de pagamento

Sinais de que ficou correto:

- a cobranca e criada no Asaas
- a assinatura local passa a guardar os ids do cliente e da assinatura remota
- quando o webhook chega, a fatura local e atualizada e a assinatura pode voltar para status `ativa`

## 6. Erros mais provaveis

### `Integracao Asaas nao configurada no ambiente.`

Causa comum:

- `BILLING_PROVIDER` vazio
- `BILLING_PROVIDER` diferente de `asaas`
- `ASAAS_API_KEY` vazio

### `A chave de API do Asaas configurada no ambiente esta invalida ou expirada.`

Causa comum:

- chave copiada errada
- chave de outro ambiente
- chave revogada no painel

### `O Asaas recusou a conexao porque este IP nao esta autorizado.`

Causa comum:

- a conta do Asaas exige whitelist de IP e a VPS ainda nao foi liberada

### `Informe um telefone valido com DDD na empresa antes de sincronizar a assinatura com o Asaas.`

Causa comum:

- telefone da empresa sem DDD
- telefone salvo com quantidade de digitos invalida

### `Informe um CPF ou CNPJ valido na empresa antes de sincronizar com o Asaas.`

Causa comum:

- documento ausente em plano pago
- documento invalido no cadastro da empresa
