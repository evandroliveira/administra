<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class SolutionPageController extends Controller
{
    public function __invoke(string $slug): View
    {
        $solutions = [
            'gestao-financeira' => [
                'title' => 'Sistema de gestão financeira para pequenas empresas | Administrar',
                'description' => 'Organize contas a pagar e receber, acompanhe vencimentos e consulte relatórios de faturamento, lucro e inadimplência com o Administrar.',
                'eyebrow' => 'Gestão financeira',
                'heading' => 'Organize as finanças da empresa em um só lugar',
                'intro' => 'Registre compromissos financeiros, acompanhe recebimentos e use relatórios para entender melhor os resultados da sua empresa.',
                'icon' => 'bi-cash-stack',
                'benefits' => [
                    ['title' => 'Contas a pagar e a receber', 'description' => 'Registre valores, vencimentos e pagamentos para acompanhar os compromissos da empresa.'],
                    ['title' => 'Acompanhamento de recebimentos', 'description' => 'Consulte títulos em aberto, pagamentos registrados e valores pendentes por cliente.'],
                    ['title' => 'Relatórios para acompanhar resultados', 'description' => 'Analise faturamento, lucro e inadimplência por período, com opções de exportação conforme os recursos do plano.'],
                    ['title' => 'Visão geral no dashboard', 'description' => 'Acesse indicadores comerciais e financeiros para acompanhar a operação em um só lugar.'],
                ],
                'steps' => [
                    'Cadastre clientes, produtos e as informações financeiras da operação.',
                    'Registre vendas, despesas e recebimentos conforme acontecem.',
                    'Consulte o dashboard e os relatórios para acompanhar pendências e resultados.',
                ],
                'faqs' => [
                    ['question' => 'Quais relatórios financeiros estão disponíveis?', 'answer' => 'O sistema oferece relatórios de faturamento, lucro e inadimplência, com filtros por período. As opções de exportação dependem dos recursos habilitados no plano.'],
                    ['question' => 'Posso registrar pagamentos parciais?', 'answer' => 'O sistema permite registrar pagamentos associados às contas a pagar e a receber, mantendo o acompanhamento dos valores pagos e pendentes.'],
                    ['question' => 'O Administrar substitui a contabilidade?', 'answer' => 'Não. O Administrar apoia o controle operacional e financeiro da empresa, mas não substitui serviços contábeis ou orientação profissional.'],
                ],
            ],
            'controle-de-estoque' => [
                'title' => 'Sistema de controle de estoque e produtos | Administrar',
                'description' => 'Cadastre produtos e categorias, acompanhe quantidades e estoque mínimo e conecte o controle de estoque às vendas no Administrar.',
                'eyebrow' => 'Controle de estoque',
                'heading' => 'Saiba o que tem no estoque e acompanhe a movimentação',
                'intro' => 'Centralize o cadastro de produtos e categorias, acompanhe quantidades e mantenha as informações de estoque ligadas à rotina de vendas.',
                'icon' => 'bi-box-seam',
                'benefits' => [
                    ['title' => 'Produtos organizados por categoria', 'description' => 'Pesquise e filtre o catálogo para encontrar os itens com mais facilidade.'],
                    ['title' => 'Quantidade atual e estoque mínimo', 'description' => 'Acompanhe o saldo de cada produto e defina um nível mínimo para apoiar o planejamento de reposição.'],
                    ['title' => 'Informações de custo e venda', 'description' => 'Mantenha preços e custos dos produtos cadastrados para apoiar o acompanhamento comercial.'],
                    ['title' => 'Estoque integrado às vendas', 'description' => 'Ao registrar uma venda, o sistema verifica a disponibilidade dos produtos e atualiza a operação de estoque.'],
                ],
                'steps' => [
                    'Cadastre categorias e produtos com seus dados e quantidades.',
                    'Defina o estoque mínimo que faz sentido para a reposição do seu negócio.',
                    'Registre as vendas e consulte os saldos para acompanhar os produtos.',
                ],
                'faqs' => [
                    ['question' => 'O sistema avisa quando um produto está acabando?', 'answer' => 'O cadastro permite definir estoque mínimo e o dashboard ajuda a identificar produtos que precisam de atenção para reposição.'],
                    ['question' => 'Quantos produtos posso cadastrar?', 'answer' => 'O plano padrão prevê limite de até 1.000 produtos. O limite aplicável é o que estiver definido na assinatura da empresa.'],
                    ['question' => 'O estoque é atualizado quando faço uma venda?', 'answer' => 'As vendas são integradas ao controle dos produtos e o sistema valida se há quantidade disponível antes de concluir o registro.'],
                ],
            ],
            'vendas-e-clientes' => [
                'title' => 'Sistema para controlar vendas e clientes | Administrar',
                'description' => 'Organize o cadastro de clientes, registre vendas com produtos e formas de pagamento e acompanhe o andamento da operação no Administrar.',
                'eyebrow' => 'Vendas e clientes',
                'heading' => 'Organize seus clientes e acompanhe cada venda',
                'intro' => 'Registre clientes e vendas no mesmo sistema, consulte os dados da operação e acompanhe os pagamentos relacionados a cada negócio.',
                'icon' => 'bi-bag-check',
                'benefits' => [
                    ['title' => 'Cadastro centralizado de clientes', 'description' => 'Mantenha os dados dos clientes organizados para usar nas vendas e no acompanhamento financeiro.'],
                    ['title' => 'Registro detalhado de vendas', 'description' => 'Selecione produtos, quantidades e valores e informe condições como desconto, frete e data de entrega quando necessário.'],
                    ['title' => 'Acompanhamento do andamento', 'description' => 'Consulte as vendas registradas e acompanhe seus status pela área de vendas.'],
                    ['title' => 'Comprovantes da operação', 'description' => 'Acesse recibos de vendas e documentos relacionados conforme os recursos usados na operação.'],
                ],
                'steps' => [
                    'Cadastre seus clientes e mantenha os produtos atualizados.',
                    'Registre cada venda com os itens, valores e condições combinadas.',
                    'Consulte a venda e acompanhe os recebimentos e o status da operação.',
                ],
                'faqs' => [
                    ['question' => 'Posso registrar diferentes formas de pagamento?', 'answer' => 'O fluxo de vendas contempla modalidades como à vista, conta a receber, boleto e promissória, conforme a configuração e os recursos habilitados para a empresa.'],
                    ['question' => 'O sistema controla o estoque vendido?', 'answer' => 'Sim. A disponibilidade dos produtos é verificada durante o registro da venda e a operação é integrada ao controle de estoque.'],
                    ['question' => 'O sistema emite nota fiscal?', 'answer' => 'A emissão fiscal depende da configuração e habilitação do provedor fiscal da empresa. Verifique os requisitos antes de contratar ou utilizar esse recurso.'],
                ],
            ],
            'boletos-e-promissorias' => [
                'title' => 'Gestão de boletos e promissórias para empresas | Administrar',
                'description' => 'Acompanhe contas a receber, boletos e promissórias em conjunto com vendas e clientes no sistema Administrar.',
                'eyebrow' => 'Cobranças e recebimentos',
                'heading' => 'Acompanhe boletos, promissórias e valores a receber',
                'intro' => 'Organize cobranças e vencimentos vinculados à rotina de vendas, acompanhe os recebimentos e consulte pendências por cliente.',
                'icon' => 'bi-receipt',
                'benefits' => [
                    ['title' => 'Contas a receber em um só lugar', 'description' => 'Consulte valores, vencimentos, pagamentos registrados e saldo pendente dos títulos.'],
                    ['title' => 'Emissão de boleto integrada', 'description' => 'Quando a integração de cobrança da empresa estiver configurada, gere e acompanhe boletos a partir das contas a receber.'],
                    ['title' => 'Controle de promissórias', 'description' => 'Acompanhe promissórias, vencimentos e situação de pagamento conforme os recursos habilitados no plano.'],
                    ['title' => 'Visibilidade de atrasos', 'description' => 'Use as informações de contas e relatórios de inadimplência para identificar valores vencidos e organizar o acompanhamento.'],
                ],
                'steps' => [
                    'Registre a venda e a forma de recebimento acordada com o cliente.',
                    'Acompanhe vencimentos e pagamentos na área de contas a receber.',
                    'Consulte pendências e relatórios para organizar as próximas cobranças.',
                ],
                'faqs' => [
                    ['question' => 'Preciso configurar algo para emitir boletos?', 'answer' => 'Sim. A emissão depende da configuração da integração de cobrança da empresa e dos dados exigidos pelo provedor.'],
                    ['question' => 'Posso acompanhar pagamentos parciais?', 'answer' => 'É possível registrar pagamentos vinculados às contas a receber e acompanhar os valores pagos e o saldo restante.'],
                    ['question' => 'Promissórias estão disponíveis para todas as empresas?', 'answer' => 'A disponibilidade depende do recurso habilitado no plano da empresa. Consulte a assinatura antes de usar essa modalidade.'],
                ],
            ],
        ];

        abort_unless(isset($solutions[$slug]), 404);

        return view('solutions.show', [
            'slug' => $slug,
            'solution' => $solutions[$slug],
            'relatedSolutions' => array_diff_key($solutions, [$slug => true]),
        ]);
    }
}
