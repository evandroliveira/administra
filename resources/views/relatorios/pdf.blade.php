<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 132px 28px 46px 28px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
            line-height: 1.35;
        }

        header {
            position: fixed;
            top: -108px;
            left: 0;
            right: 0;
            height: 92px;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 10px;
        }

        footer {
            position: fixed;
            bottom: -30px;
            left: 0;
            right: 0;
            font-size: 9px;
            color: #6b7280;
        }

        h1 {
            margin: 0;
            font-size: 21px;
        }

        h2 {
            margin: 0 0 8px 0;
            font-size: 14px;
        }

        .subtitle {
            margin-top: 5px;
            color: #4b5563;
        }

        .empresa {
            margin-top: 5px;
            font-weight: bold;
        }

        .header-table,
        .footer-table,
        .summary,
        .section-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .header-table td,
        .footer-table td {
            vertical-align: top;
        }

        .logo-cell {
            width: 86px;
        }

        .logo-box {
            width: 72px;
            height: 72px;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            background: #ffffff;
            overflow: hidden;
            text-align: center;
        }

        .logo-box img {
            width: 72px;
            height: 72px;
            object-fit: contain;
        }

        .meta-cell {
            width: 170px;
            text-align: right;
        }

        .meta-box {
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            border-radius: 10px;
            padding: 8px 10px;
            font-size: 9px;
            line-height: 1.45;
        }


        .summary td,
        .section-table th,
        .section-table td {
            border: 1px solid #d1d5db;
            padding: 6px 7px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: anywhere;
        }

        .summary td:first-child {
            width: 38%;
            font-weight: bold;
            background: #f9fafb;
        }

        .section-table th {
            background: #f3f4f6;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .section {
            margin-top: 18px;
        }

        .num {
            text-align: right;
        }

        .muted {
            color: #6b7280;
        }

        .footer-table td:last-child {
            text-align: right;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    <header>
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    <div class="logo-box">
                        <img src="{{ $logoDataUri }}" alt="Logo da empresa">
                    </div>
                </td>
                <td>
                    <h1>{{ $title }}</h1>
                    <div class="subtitle">{{ $subtitle }}</div>
                    @if ($empresaNome)
                        <div class="empresa">{{ $empresaNome }}</div>
                    @endif
                    @if ($empresaDocumento || $empresaContato)
                        <div class="subtitle">
                            {{ $empresaDocumento ?: '' }}@if ($empresaDocumento && $empresaContato) | @endif{{ $empresaContato ?: '' }}
                        </div>
                    @endif
                </td>
                <td class="meta-cell">
                    <div class="meta-box">
                        <strong>Documento interno</strong><br>
                        Emitido em {{ $generatedAt->format('d/m/Y H:i') }}<br>
                        Formato PDF para impressão e compartilhamento
                    </div>
                </td>
            </tr>
        </table>
    </header>

    <footer>
        <table class="footer-table">
            <tr>
                <td>{{ $empresaNome ?: 'Administrar' }} | {{ $title }}</td>
                <td>Gerado em {{ $generatedAt->format('d/m/Y H:i') }}</td>
            </tr>
        </table>
    </footer>

    <main>
        <table class="summary">
            <tbody>
                @foreach ($summaryRows as $row)
                    <tr>
                        <td>{{ $row[0] }}</td>
                        <td>{{ $row[1] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @foreach ($sections as $section)
            <div class="section">
                <h2>{{ $section['title'] }}</h2>
                <table class="section-table">
                    <thead>
                        <tr>
                            @foreach ($section['columns'] as $column)
                                <th class="{{ $column['class'] ?? '' }}" @if (! empty($column['width'])) style="width: {{ $column['width'] }}" @endif>{{ $column['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($section['rows'] as $row)
                            <tr>
                                @foreach ($row as $index => $cell)
                                    <td class="{{ $section['columns'][$index]['class'] ?? '' }}">{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($section['columns']) }}" class="muted">Sem dados para este bloco.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endforeach
    </main>
</body>
</html>