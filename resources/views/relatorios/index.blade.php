<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-lg-between gap-3">
            <div>
                <span class="badge rounded-pill text-bg-primary px-3 py-2 mb-3">Relatórios</span>
                <h2 class="h1 fw-semibold text-dark mb-2">Central de Relatórios</h2>
                <p class="text-body-secondary mb-0">{{ $empresa?->nome ?? 'Empresa atual' }}</p>
            </div>
            <div class="text-body-secondary">Defina um período e siga para o relatório ou exportação desejada.</div>
        </div>
    </x-slot>

    <div class="container-xxl pb-5">
        <div class="d-flex flex-column gap-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-lg-5">
                <div class="row g-4 align-items-start">
                    <div class="col-lg-7">
                        <h3 class="h4 fw-semibold text-dark mb-2">Filtros compartilhados</h3>
                        <p class="text-body-secondary mb-0">Os filtros abaixo são reaproveitados pelas ações de faturamento e lucro. Para inadimplência, a central mantém o acesso direto porque o relatório é baseado na data atual.</p>

                        <form method="GET" action="{{ route('relatorios.index') }}" class="row g-3 mt-1">
                            <div class="col-md-4">
                                <label for="data_inicio" class="form-label fw-semibold">Data inicial</label>
                                <input id="data_inicio" type="date" name="data_inicio" value="{{ $dataInicio }}" class="form-control form-control-lg">
                            </div>
                            <div class="col-md-4">
                                <label for="data_fim" class="form-label fw-semibold">Data final</label>
                                <input id="data_fim" type="date" name="data_fim" value="{{ $dataFim }}" class="form-control form-control-lg">
                            </div>
                            <div class="col-md-4 d-flex flex-column flex-sm-row align-items-sm-end gap-3">
                                <button type="submit" class="btn btn-dark btn-lg rounded-pill px-4">Atualizar links</button>
                                <span class="text-body-secondary mb-sm-2">{{ $dataInicio }} até {{ $dataFim }}</span>
                            </div>
                        </form>
                    </div>

                    <div class="col-lg-5">
                        <div class="border rounded-4 bg-light-subtle p-4 h-100">
                        <div class="small fw-semibold text-uppercase text-body-secondary">Atalhos de período</div>
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            @foreach ($presets as $preset)
                                <a href="{{ route('relatorios.index', $preset['params']) }}" class="btn btn-outline-secondary rounded-pill btn-sm px-3">{{ $preset['label'] }}</a>
                            @endforeach
                        </div>
                        <p class="text-body-secondary mt-3 mb-0">Use estes atalhos para montar rapidamente o intervalo antes de abrir ou exportar cada relatório.</p>
                        </div>
                    </div>
                </div>
                </div>
            </div>

            <div class="row g-4">
                @foreach ($reports as $report)
                    @php
                        $formatosDisponiveis = ['HTML', 'CSV'];
                        if ($report['xlsxUrl']) {
                            $formatosDisponiveis[] = 'XLSX';
                        }
                        if ($report['pdfUrl']) {
                            $formatosDisponiveis[] = 'PDF';
                        }
                        $accentClass = match ($report['accent']) {
                            'border-emerald-200 bg-emerald-50/70' => 'border-success-subtle bg-success-subtle',
                            'border-amber-200 bg-amber-50/70' => 'border-warning-subtle bg-warning-subtle',
                            'border-sky-200 bg-sky-50/70' => 'border-info-subtle bg-info-subtle',
                            default => 'border-light bg-light',
                        };
                    @endphp
                    <section class="col-xl-4">
                        <div class="card border-1 {{ $accentClass }} shadow-sm rounded-4 h-100">
                            <div class="card-body p-4 d-flex flex-column gap-4">
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div>
                                <h3 class="h4 fw-semibold text-dark mb-2">{{ $report['title'] }}</h3>
                                <p class="text-body-secondary mb-0">{{ $report['description'] }}</p>
                            </div>
                            <div class="badge text-bg-light border px-3 py-2 text-uppercase text-wrap">{{ implode(' ', $formatosDisponiveis) }}</div>
                        </div>

                        <div class="row g-2 mt-auto">
                            <div class="col-sm-6 col-lg-12 col-xxl-6 d-grid">
                            <a href="{{ $report['openUrl'] }}" class="btn btn-dark btn-lg rounded-pill">Abrir</a>
                            </div>
                            <div class="col-sm-6 col-lg-12 col-xxl-6 d-grid">
                            <a href="{{ $report['csvUrl'] }}" class="btn btn-light btn-lg rounded-pill border">Exportar CSV</a>
                            </div>
                            @if ($report['xlsxUrl'])
                                <div class="col-sm-6 col-lg-12 col-xxl-6 d-grid"><a href="{{ $report['xlsxUrl'] }}" class="btn btn-outline-secondary btn-lg rounded-pill">Exportar XLSX</a></div>
                            @endif
                            @if ($report['pdfUrl'])
                                <div class="col-sm-6 col-lg-12 col-xxl-6 d-grid"><a href="{{ $report['pdfUrl'] }}" class="btn btn-outline-secondary btn-lg rounded-pill">Exportar PDF</a></div>
                            @endif
                        </div>
                            </div>
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>