<?php

declare(strict_types=1);

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

final class PdfRenderer {
    public function __construct(
        private readonly Environment $twig,
    ) {}

    /**
     * @param array<string, mixed> $context
     */
    public function render(string $template, array $context): string {
        $html = $this->twig->render($template, $context);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return (string) $dompdf->output();
    }
}
