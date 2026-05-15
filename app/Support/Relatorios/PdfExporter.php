<?php

namespace App\Support\Relatorios;

use App\Models\Empresa;
use Dompdf\Dompdf;
use Dompdf\Options;

class PdfExporter
{
    public function download(string $fileName, string $title, string $subtitle, array $summaryRows, array $sections, ?Empresa $empresa = null)
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->setDefaultFont('DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('relatorios.pdf', [
            'title' => $title,
            'subtitle' => $subtitle,
            'empresaNome' => $empresa?->nome,
            'empresaDocumento' => $empresa?->documento,
            'empresaContato' => collect([$empresa?->email, $empresa?->telefone])->filter()->implode(' | '),
            'logoDataUri' => $this->resolveLogoDataUri($empresa),
            'summaryRows' => $summaryRows,
            'sections' => $sections,
            'generatedAt' => now(),
        ])->render(), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $canvas->page_text(455, 812, 'Página {PAGE_NUM} de {PAGE_COUNT}', $font, 9, [0.42, 0.46, 0.50]);

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }

    private function resolveLogoDataUri(?Empresa $empresa): string
    {
        $logo = trim((string) ($empresa?->logo ?? ''));

        if ($logo !== '') {
            if (str_starts_with($logo, 'data:image/')) {
                return $logo;
            }

            foreach ($this->candidateLogoPaths($logo) as $path) {
                if (is_file($path)) {
                    $mime = mime_content_type($path) ?: 'image/png';

                    return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
                }
            }
        }

        return $this->fallbackLogoDataUri($empresa?->nome);
    }

    private function candidateLogoPaths(string $logo): array
    {
        $clean = ltrim(str_replace('\\', '/', $logo), '/');

        return array_values(array_unique([
            public_path($clean),
            public_path('storage/'.$clean),
            storage_path('app/public/'.$clean),
        ]));
    }

    private function fallbackLogoDataUri(?string $empresaNome): string
    {
        $nome = trim((string) $empresaNome) ?: 'Empresa';
        $partes = preg_split('/\s+/', $nome) ?: [];
        $iniciais = '';

        foreach ($partes as $parte) {
            if ($parte === '') {
                continue;
            }

            $iniciais .= mb_strtoupper(mb_substr($parte, 0, 1));
            if (mb_strlen($iniciais) >= 2) {
                break;
            }
        }

        $iniciais = $iniciais !== '' ? $iniciais : 'EM';
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120">
    <rect width="120" height="120" rx="24" fill="#111827" />
    <rect x="8" y="8" width="104" height="104" rx="18" fill="#f59e0b" opacity="0.18" />
    <text x="60" y="73" text-anchor="middle" font-size="42" font-family="Arial, sans-serif" fill="#f9fafb" font-weight="700">{$iniciais}</text>
</svg>
SVG;

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}