<?php
/**
 * lib/pdf_generator.php — Raw PDF generation without external libraries (A9)
 * Generates a valid single-page PDF receipt for an order.
 */

class FlowerPDFGenerator
{
    private array $objects   = [];
    private int   $objCount  = 0;
    private array $offsets   = [];
    private string $pdf      = '';

    // ── Public entry point ──────────────────────────────────────
    public function generate(array $order, array $items): void
    {
        // Build content stream
        $stream = $this->buildStream($order, $items);

        // Object 1 — Helvetica (regular)
        $this->addObj("<</Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding>>");
        // Object 2 — Helvetica-Bold
        $this->addObj("<</Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding>>");
        // Object 3 — Content stream
        $this->addObj("<</Length " . strlen($stream) . ">>\nstream\n" . $stream . "\nendstream");
        // Object 4 — Page
        $this->addObj(
            "<</Type /Page /Parent 5 0 R /MediaBox [0 0 595 842]\n" .
            "/Contents 3 0 R\n" .
            "/Resources <</Font <</F1 1 0 R /F2 2 0 R>>>>>>"
        );
        // Object 5 — Pages
        $this->addObj("<</Type /Pages /Kids [4 0 R] /Count 1>>");
        // Object 6 — Catalog
        $this->addObj("<</Type /Catalog /Pages 5 0 R>>");

        $this->output($order['order_code']);
    }

    // ── Build page content stream ────────────────────────────────
    private function buildStream(array $o, array $items): string
    {
        $W = 595;
        $s = '';

        // Header background band
        $s .= "q\n{$this->rgb(194, 24, 91)} rg\n0 780 $W 62 re f\nQ\n";

        // Logo text area
        $s .= "BT\n/F2 20 Tf\n{$this->rgb(255,255,255)} rg\n50 800 Td\n(Petals & Bloom) Tj\nET\n";
        $s .= "BT\n/F1 10 Tf\n{$this->rgb(255,255,255)} rg\n50 784 Td\n(Flower Shop - Order Receipt) Tj\nET\n";

        // Order code (top right)
        $code = $this->safeText($o['order_code']);
        $s .= "BT\n/F2 14 Tf\n{$this->rgb(255,255,255)} rg\n400 800 Td\n($code) Tj\nET\n";

        // Divider line
        $y = 760;
        $s .= $this->line(50, $y, $W - 50, $y, 0.5);

        // Customer section
        $y -= 20;
        $s .= $this->label('Customer:', 50, $y);
        $s .= $this->value($this->safeText($o['customer_name']), 160, $y);

        $y -= 16;
        $s .= $this->label('Email:', 50, $y);
        $s .= $this->value($this->safeText($o['customer_email']), 160, $y);

        $y -= 16;
        $s .= $this->label('Phone:', 50, $y);
        $s .= $this->value($this->safeText($o['customer_phone']), 160, $y);

        $y -= 16;
        $s .= $this->label('Delivery Address:', 50, $y);
        $s .= $this->value($this->safeText(mb_substr($o['delivery_address'], 0, 60)), 160, $y);

        $y -= 16;
        $s .= $this->label('Delivery Date:', 50, $y);
        $s .= $this->value(date('d M Y', strtotime($o['delivery_date'])), 160, $y);

        $y -= 16;
        $s .= $this->label('Occasion:', 50, $y);
        $s .= $this->value(ucfirst(str_replace('_', ' ', $o['occasion'] ?? '')), 160, $y);

        $y -= 16;
        $s .= $this->label('Payment:', 50, $y);
        $s .= $this->value(ucfirst($o['payment_method'] ?? '') . ' — ' . ucfirst($o['payment_status'] ?? ''), 160, $y);

        $y -= 16;
        $s .= $this->label('Status:', 50, $y);
        $s .= $this->value(ucfirst(str_replace('_', ' ', $o['status'])), 160, $y);

        if (!empty($o['card_message'])) {
            $y -= 16;
            $s .= $this->label('Card Message:', 50, $y);
            $s .= $this->value('"' . $this->safeText(mb_substr($o['card_message'], 0, 60)) . '"', 160, $y);
        }

        // Items header
        $y -= 24;
        $s .= "q\n{$this->rgb(244,244,248)} rg\n50 " . ($y - 4) . " " . ($W - 100) . " 18 re f\nQ\n";
        $s .= $this->bold('Product', 55, $y);
        $s .= $this->bold('Unit Price', 320, $y);
        $s .= $this->bold('Qty', 420, $y);
        $s .= $this->bold('Subtotal', 470, $y);

        $y -= 2;
        $s .= $this->line(50, $y, $W - 50, $y, 0.3);

        foreach ($items as $item) {
            $y -= 16;
            if ($y < 80) break; // Guard for very long orders
            $name = $this->safeText(mb_substr($item['product_name'] ?? 'Product', 0, 35));
            $s .= $this->text($name, 55, $y, 9);
            $s .= $this->text(number_format((float)$item['unit_price'], 2) . ' RON', 320, $y, 9);
            $s .= $this->text((string)$item['quantity'], 420, $y, 9);
            $s .= $this->text(number_format((float)$item['subtotal'], 2) . ' RON', 470, $y, 9);
        }

        // Total line
        $y -= 8;
        $s .= $this->line(50, $y, $W - 50, $y, 0.5);
        $y -= 18;
        $s .= "q\n{$this->rgb(252,228,236)} rg\n50 " . ($y - 4) . " " . ($W - 100) . " 20 re f\nQ\n";
        $s .= $this->bold('TOTAL', 55, $y, 11);
        $total = number_format((float)$o['total_price'], 2) . ' RON';
        $s .= "BT\n/F2 11 Tf\n{$this->rgb(194,24,91)} rg\n470 {$y} Td\n($total) Tj\nET\n";

        // Footer
        $s .= $this->line(50, 50, $W - 50, 50, 0.3);
        $s .= $this->text('Thank you for your order! | Petals & Bloom Flower Shop', 50, 36, 8);
        $s .= $this->text('Printed: ' . date('d M Y H:i'), 400, 36, 8);

        return $s;
    }

    // ── Helper: add PDF object ───────────────────────────────────
    private function addObj(string $def): void
    {
        $this->objCount++;
        $this->objects[$this->objCount] = $def;
    }

    // ── Helper: RGB color operator ───────────────────────────────
    private function rgb(int $r, int $g, int $b): string
    {
        return round($r/255,3) . ' ' . round($g/255,3) . ' ' . round($b/255,3);
    }

    // ── Helpers: text ─────────────────────────────────────────────
    private function text(string $t, float $x, float $y, int $size = 9): string
    {
        return "BT\n/F1 $size Tf\n{$this->rgb(51,51,51)} rg\n$x $y Td\n($t) Tj\nET\n";
    }
    private function bold(string $t, float $x, float $y, int $size = 9): string
    {
        return "BT\n/F2 $size Tf\n{$this->rgb(51,51,51)} rg\n$x $y Td\n($t) Tj\nET\n";
    }
    private function label(string $t, float $x, float $y): string
    {
        return "BT\n/F2 9 Tf\n{$this->rgb(136,136,136)} rg\n$x $y Td\n($t) Tj\nET\n";
    }
    private function value(string $t, float $x, float $y): string
    {
        return "BT\n/F1 9 Tf\n{$this->rgb(51,51,51)} rg\n$x $y Td\n($t) Tj\nET\n";
    }

    // ── Helper: draw horizontal line ────────────────────────────
    private function line(float $x1, float $y1, float $x2, float $y2, float $w = 0.5): string
    {
        return "q {$w} w 0.8 0.8 0.8 RG $x1 $y1 m $x2 $y2 l S Q\n";
    }

    // ── Helper: PDF-safe text (ASCII only) ──────────────────────
    private function safeText(string $s): string
    {
        $s = iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $s) ?: '';
        return str_replace(['(', ')','\\'], ['\\(', '\\)','\\\\'], $s);
    }

    // ── Assemble and output PDF binary ──────────────────────────
    private function output(string $orderCode): void
    {
        $filename = 'Order-' . preg_replace('/[^a-zA-Z0-9\-]/', '', $orderCode) . '.pdf';

        $pdf  = "%PDF-1.4\n";
        foreach ($this->objects as $n => $def) {
            $this->offsets[$n] = strlen($pdf);
            $pdf .= "$n 0 obj\n$def\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $count      = $this->objCount + 1;
        $pdf .= "xref\n0 $count\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($this->offsets as $off) {
            $pdf .= str_pad($off, 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }
        $pdf .= "trailer\n<</Size $count /Root 6 0 R>>\n";
        $pdf .= "startxref\n$xrefOffset\n%%EOF";

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        header('Cache-Control: private, max-age=0, must-revalidate');
        echo $pdf;
    }
}
