<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-lg-between gap-3">
            <div>
                <span class="badge rounded-pill text-bg-primary px-3 py-2 mb-3">Catalogo</span>
                <h2 class="h1 fw-semibold text-dark mb-2">Produto</h2>
                <p class="text-body-secondary mb-0">Consulte os dados comerciais, categoria e estoque do item.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('produtos.edit', $produto) }}" class="btn btn-warning btn-lg rounded-pill px-4">Editar</a>
                <a href="{{ route('produtos.index') }}" class="btn btn-light btn-lg rounded-pill px-4">Voltar</a>
            </div>
        </div>
    </x-slot>
    <div class="container-xxl pb-5">
        <div class="row g-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4 p-lg-5">
                        <div class="row g-3 small">
                            <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Codigo</div><div class="text-dark">{{ $produto->codigo }}</div></div></div>
                            <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Nome</div><div class="text-dark">{{ $produto->nome }}</div></div></div>
                            <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Categoria</div><div class="text-dark">{{ $produto->categoria->nome ?? '-' }}</div></div></div>
                            <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Preco custo</div><div class="text-dark">R$ {{ number_format((float) $produto->preco_custo, 2, ',', '.') }}</div></div></div>
                            <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Preco venda</div><div class="text-dark">R$ {{ number_format((float) $produto->preco_venda, 2, ',', '.') }}</div></div></div>
                            <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Margem</div><div class="text-dark">{{ number_format((float) $produto->margem_lucro, 2, ',', '.') }}%</div></div></div>
                            <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Estoque atual</div><div class="text-dark">{{ $produto->estoque_atual }}</div></div></div>
                            <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Estoque minimo</div><div class="text-dark">{{ $produto->estoque_minimo }}</div></div></div>
                            <div class="col-md-4"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold text-body-secondary mb-1">Ativo</div><div class="text-dark">{{ $produto->ativo ? 'Sim' : 'Não' }}</div></div></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4 p-lg-5">
                        <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
                            <div>
                                <div class="text-uppercase text-body-secondary small fw-semibold mb-1">Galeria</div>
                                <h3 class="h4 mb-0">Imagens adicionais</h3>
                            </div>
                            <span class="badge text-bg-light border">{{ $produto->imagens->count() }} arquivo(s)</span>
                        </div>

                        @if ($produto->imagens->isEmpty())
                            <div class="border rounded-4 p-4 text-body-secondary">Nenhuma imagem adicional importada para este produto.</div>
                        @else
                            <div class="row row-cols-1 row-cols-sm-2 g-3">
                                @foreach ($produto->imagens as $imagem)
                                    <div class="col">
                                        <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                            @if ($imagem->preview_url)
                                                <img src="{{ $imagem->preview_url }}" alt="Imagem adicional {{ $imagem->ordem ?: $loop->iteration }}" class="img-fluid rounded-3 border mb-3" style="aspect-ratio: 4 / 3; object-fit: cover; width: 100%;">
                                            @else
                                                <div class="d-flex align-items-center justify-content-center rounded-3 border bg-white text-body-secondary mb-3" style="aspect-ratio: 4 / 3;">
                                                    <div class="text-center px-3">
                                                        <i class="bi bi-image fs-1 d-block mb-2"></i>
                                                        <div class="small">Arquivo importado sem preview local</div>
                                                    </div>
                                                </div>
                                            @endif
                                            <div class="small text-body-secondary">Ordem {{ $imagem->ordem }}</div>
                                            <div class="fw-semibold text-break">{{ $imagem->imagem }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-7">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4 p-lg-5">
                        <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
                            <div>
                                <div class="text-uppercase text-body-secondary small fw-semibold mb-1">Historico</div>
                                <h3 class="h4 mb-0">Movimentacoes de estoque</h3>
                            </div>
                            <span class="badge text-bg-light border">{{ $produto->movimentacoesEstoque->count() }} registro(s)</span>
                        </div>

                        @if ($produto->movimentacoesEstoque->isEmpty())
                            <div class="border rounded-4 p-4 text-body-secondary">Nenhuma movimentacao de estoque importada para este produto.</div>
                        @else
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light small text-uppercase">
                                        <tr>
                                            <th class="px-3 py-3">Data</th>
                                            <th class="px-3 py-3">Tipo</th>
                                            <th class="px-3 py-3 text-end">Qtd.</th>
                                            <th class="px-3 py-3 text-end">Estoque</th>
                                            <th class="px-3 py-3">Origem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($produto->movimentacoesEstoque as $movimentacao)
                                            <tr>
                                                <td class="px-3 py-3">
                                                    <div class="fw-semibold">{{ $movimentacao->created_at?->format('d/m/Y H:i') }}</div>
                                                    @if ($movimentacao->observacao)
                                                        <div class="small text-body-secondary">{{ $movimentacao->observacao }}</div>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-3">
                                                    <span class="badge rounded-pill {{ str_contains($movimentacao->tipo, 'saida') ? 'text-bg-danger' : 'text-bg-success' }}">{{ str_replace('_', ' ', ucfirst($movimentacao->tipo)) }}</span>
                                                </td>
                                                <td class="px-3 py-3 text-end fw-semibold">{{ $movimentacao->quantidade }}</td>
                                                <td class="px-3 py-3 text-end text-body-secondary">{{ $movimentacao->estoque_anterior }} -> {{ $movimentacao->estoque_posterior }}</td>
                                                <td class="px-3 py-3 text-body-secondary">{{ $movimentacao->origem_tipo ?: '-' }}{{ $movimentacao->origem_id ? ' #'.$movimentacao->origem_id : '' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
