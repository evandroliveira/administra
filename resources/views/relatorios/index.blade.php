<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Central de Relatórios</h2>
                <p class="text-sm text-gray-500 mt-1">{{ $empresa?->nome ?? 'Empresa atual' }}</p>
            </div>
            <div class="text-sm text-gray-500">Defina um período e siga para o relatório ou exportação desejada.</div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-1 lg:grid-cols-[1.6fr_1fr] gap-6 items-start">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Filtros compartilhados</h3>
                        <p class="mt-2 text-sm text-gray-500">Os filtros abaixo são reaproveitados pelas ações de faturamento e lucro. Para inadimplência, a central mantém o acesso direto porque o relatório é baseado na data atual.</p>

                        <form method="GET" action="{{ route('relatorios.index') }}" class="mt-5 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="data_inicio" class="block text-sm font-medium text-gray-700">Data inicial</label>
                                <input id="data_inicio" type="date" name="data_inicio" value="{{ $dataInicio }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            </div>
                            <div>
                                <label for="data_fim" class="block text-sm font-medium text-gray-700">Data final</label>
                                <input id="data_fim" type="date" name="data_fim" value="{{ $dataFim }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            </div>
                            <div class="flex items-end gap-3">
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-900 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Atualizar links</button>
                                <span class="text-sm text-gray-500">{{ $dataInicio }} até {{ $dataFim }}</span>
                            </div>
                        </form>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
                        <div class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Atalhos de período</div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($presets as $preset)
                                <a href="{{ route('relatorios.index', $preset['params']) }}" class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-full text-xs font-semibold text-gray-700 uppercase tracking-widest hover:bg-gray-100">{{ $preset['label'] }}</a>
                            @endforeach
                        </div>
                        <p class="mt-4 text-sm text-gray-500">Use estes atalhos para montar rapidamente o intervalo antes de abrir ou exportar cada relatório.</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
                @foreach ($reports as $report)
                    @php
                        $formatosDisponiveis = ['HTML', 'CSV'];
                        if ($report['xlsxUrl']) {
                            $formatosDisponiveis[] = 'XLSX';
                        }
                        if ($report['pdfUrl']) {
                            $formatosDisponiveis[] = 'PDF';
                        }
                    @endphp
                    <section class="rounded-3xl border {{ $report['accent'] }} p-6 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ $report['title'] }}</h3>
                                <p class="mt-2 text-sm text-gray-600 leading-6">{{ $report['description'] }}</p>
                            </div>
                            <div class="rounded-2xl bg-white/80 px-3 py-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-gray-500">{{ implode(' ', $formatosDisponiveis) }}</div>
                        </div>

                        <div class="mt-6 grid grid-cols-1 sm:grid-cols-4 gap-3">
                            <a href="{{ $report['openUrl'] }}" class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-gray-900 text-white text-sm font-semibold hover:bg-gray-700">Abrir</a>
                            <a href="{{ $report['csvUrl'] }}" class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-white text-gray-900 border border-gray-300 text-sm font-semibold hover:bg-gray-50">Exportar CSV</a>
                            @if ($report['xlsxUrl'])
                                <a href="{{ $report['xlsxUrl'] }}" class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-white text-gray-900 border border-gray-300 text-sm font-semibold hover:bg-gray-50">Exportar XLSX</a>
                            @endif
                            @if ($report['pdfUrl'])
                                <a href="{{ $report['pdfUrl'] }}" class="inline-flex items-center justify-center px-4 py-3 rounded-xl bg-white text-gray-900 border border-gray-300 text-sm font-semibold hover:bg-gray-50">Exportar PDF</a>
                            @endif
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>