# Checklist de ativacao do Asaas em producao

Este projeto ja possui a integracao do Asaas no codigo. Para ativar em producao, faltam apenas as configuracoes de ambiente e do painel.

## 1. Variaveis obrigatorias no .env da VPS

Use estes valores no arquivo .env de producao:

```env
BILLING_PROVIDER=asaas
CUSTOMER_BOLETO_PROVIDER=asaas
BILLING_GRACE_DAYS=3
BILLING_TRIAL_DAYS=5
ASAAS_API_KEY=
ASAAS_BASE_URL=https://api.asaas.com/v3
ASAAS_BILLING_TYPE=UNDEFINED
ASAAS_SUBSCRIPTION_CYCLE=MONTHLY
ASAAS_WEBHOOK_TOKEN=
ASAAS_TIMEOUT=30
```

Preencha assim:

- `BILLING_PROVIDER` deve ser `asaas`.
- `CUSTOMER_BOLETO_PROVIDER` deve permanecer `asaas` para habilitar boletos dos clientes das lojas.
- `BILLING_TRIAL_DAYS` define quantos dias de acesso gratuito novos cadastros recebem antes de ativar a cobrança. Use `0` para cobrar logo após o cadastro.
- `ASAAS_API_KEY` deve ser a chave de API de producao da conta Asaas da plataforma. Ela e usada somente para cobrar a assinatura das lojas.
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

## 4. Boletos dos clientes de cada loja

Os boletos emitidos pelas lojas sao independentes da cobranca da assinatura do sistema:

- a chave `ASAAS_API_KEY` do `.env` nao e usada para criar cliente ou boleto de uma loja
- o admin da loja deve abrir `/empresa` e preencher **Chave de API Asaas para boletos dos clientes** com a chave da propria conta Asaas
- a chave da loja fica criptografada no banco e nunca e exibida novamente na tela
- sem essa chave, o sistema nao faz fallback para a conta Asaas da plataforma

Para gerar ou atualizar o boleto de uma conta a receber ja existente, o sistema disponibiliza a API JSON autenticada:

```http
POST /contas/receber/{contaReceber}/boleto
Accept: application/json
```

O usuario precisa estar autenticado e pertencer a mesma loja da conta. A resposta contem `boleto.url`, `boleto.gateway_payment_id` e o status local da conta.

Para a baixa automatica dos boletos, configure o webhook `https://www.lojagerencia.com.br/webhooks/asaas` tambem no painel Asaas de cada loja, usando o token definido em `ASAAS_WEBHOOK_TOKEN`.

### Recuperar boletos pendentes em lote

Quando uma venda em boleto foi registrada, mas a emissao falhou, execute o comando na VPS, a partir da pasta da aplicacao:

```bash
php artisan billing:generate-pending-boletos
```

O comando inicia em modo de simulacao: lista apenas contas abertas em boleto que nao possuem ID nem URL de cobranca, sem enviar requisicoes ao Asaas. Para efetivar a emissao, rode:

```bash
php artisan billing:generate-pending-boletos --execute
```

Para processar somente uma loja ou limitar o lote, use `--empresa=ID_DA_LOJA` e `--limit=100`. Contas que possuem uma URL, mas nao possuem ID do pagamento remoto, nao sao processadas automaticamente para evitar cobranca duplicada; revise esses casos manualmente antes de gerar outro boleto.

## 5. Dados obrigatorios antes da primeira cobranca

Antes de sincronizar a assinatura com o Asaas, confirme estes dados da empresa:

- telefone valido com DDD
- CPF ou CNPJ valido para planos pagos
- email da empresa ou do usuario administrador preenchido

Sem isso, a integracao pode falhar ao criar ou atualizar o cliente no gateway.

## 6. Validacao funcional

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

## 7. Erros mais provaveis

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
