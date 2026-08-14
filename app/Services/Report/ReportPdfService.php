<?php

namespace App\Services\Report;

use Mpdf\Mpdf;

class ReportPdfService
{
    public function generate(string $title, array $summaryCards, array $tableHeaders, array $tableRows, array $chartData = [], string $companyName = ''): string
    {
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavusans', // بيدعم العربي built-in في mpdf
            'directionality' => 'rtl',
        ]);

        $html = $this->buildHtml($title, $summaryCards, $tableHeaders, $tableRows, $chartData, $companyName);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S'); // يرجع محتوى الملف كـ string
    }

    private function buildHtml(string $title, array $summaryCards, array $tableHeaders, array $tableRows, array $chartData, string $companyName): string
    {
        $cardsHtml = '';
        foreach ($summaryCards as $label => $value) {
            $cardsHtml .= "<td style='border:1px solid #ddd; padding:10px; text-align:center; width:{$this->cardWidth($summaryCards)}%;'>
                <div style='font-size:11px; color:#666;'>{$label}</div>
                <div style='font-size:16px; font-weight:bold; color:#1a3a5c;'>{$value}</div>
            </td>";
        }

        $chartHtml = $chartData ? $this->buildChartSvg($chartData) : '';

        $headHtml = '<tr>' . implode('', array_map(fn($h) => "<th style='background:#1a3a5c; color:#fff; padding:6px; font-size:10px;'>{$h}</th>", $tableHeaders)) . '</tr>';

        $rowsHtml = '';
        foreach ($tableRows as $i => $row) {
            $bg = $i % 2 === 0 ? '#f9f9f9' : '#ffffff';
            $rowsHtml .= "<tr style='background:{$bg};'>" . implode('', array_map(fn($cell) => "<td style='border-bottom:1px solid #eee; padding:6px; font-size:9px;'>{$cell}</td>", $row)) . '</tr>';
        }

        return "
        <html dir='rtl'>
        <head><style>body { font-family: dejavusans; }</style></head>
        <body>
            <h2 style='color:#1a3a5c; text-align:center;'>{$companyName}</h2>
            <h3 style='text-align:center; color:#444;'>{$title}</h3>
            <p style='text-align:left; font-size:9px; color:#999;'>تاريخ التصدير: " . now()->format('Y-m-d H:i') . "</p>

            <table style='width:100%; border-collapse:collapse; margin-bottom:15px;'>
                <tr>{$cardsHtml}</tr>
            </table>

            {$chartHtml}

            <table style='width:100%; border-collapse:collapse;'>
                {$headHtml}
                {$rowsHtml}
            </table>
        </body>
        </html>";
    }

    private function cardWidth(array $cards): int
    {
        $count = count($cards);
        return $count > 0 ? intval(100 / $count) : 100;
    }

    /**
     * يرسم بار شارت بسيط كـ SVG من بيانات [label => value]
     */
    private function buildChartSvg(array $chartData): string
    {
        $max = max($chartData) ?: 1;
        $barWidth = 60;
        $gap = 20;
        $height = 120;
        $width = count($chartData) * ($barWidth + $gap) + $gap;

        $bars = '';
        $x = $gap;
        foreach ($chartData as $label => $value) {
            $barHeight = $max > 0 ? ($value / $max) * $height : 0;
            $y = $height - $barHeight;
            $bars .= "<rect x='{$x}' y='{$y}' width='{$barWidth}' height='{$barHeight}' fill='#3b82f6' rx='3'/>";
            $bars .= "<text x='" . ($x + $barWidth / 2) . "' y='" . ($height + 15) . "' font-size='9' text-anchor='middle' fill='#333'>{$label}</text>";
            $bars .= "<text x='" . ($x + $barWidth / 2) . "' y='" . ($y - 4) . "' font-size='9' text-anchor='middle' fill='#1a3a5c'>{$value}</text>";
            $x += $barWidth + $gap;
        }

        return "<div style='text-align:center; margin-bottom:15px;'>
            <svg width='{$width}' height='" . ($height + 30) . "' xmlns='http://www.w3.org/2000/svg'>{$bars}</svg>
        </div>";
    }
}