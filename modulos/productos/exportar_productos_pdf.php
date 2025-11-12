<?php
date_default_timezone_set('America/Asuncion');
$base_path = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/repuestos/';
$GLOBALS['base_path'] = $base_path;
require($base_path . 'includes/conexion.php');
require($base_path . 'fpdf/fpdf.php');

// Subclase FPDF para manejar tablas con MultiCell
class PDF extends FPDF
{
    function Header()
    {
        $this->Image($GLOBALS['base_path'] . 'img/logo3.png', 10, 2, 25);
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, utf8_decode('Listado de Productos - Repuestos Doble A'), 0, 1, 'C');
        $this->Ln(5);

        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(37, 99, 235);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(15, 8, 'Codigo', 1, 0, 'C', true);
        $this->Cell(35, 8, 'Nombre', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Categoria', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Marca', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Modelo', 1, 0, 'C', true);
        $this->Cell(20, 8, 'Cilindrada', 1, 0, 'C', true);
        $this->Cell(20, 8, 'Precio', 1, 0, 'C', true);
        $this->Cell(15, 8, 'Stock', 1, 0, 'C', true);
        $this->Cell(18, 8, 'Stock Min', 1, 1, 'C', true);
        $this->SetTextColor(0, 0, 0);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_decode('Repuestos Doble A - Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function Row($data)
    {
        $widths = [15, 35, 25, 25, 25, 20, 20, 15, 18];
        $nb = 0;
        for ($i = 0; $i < count($data); $i++) {
            $nb = max($nb, $this->NbLines($widths[$i], $data[$i]));
        }
        $h = 5 * $nb;
        $this->CheckPageBreak($h);
        for ($i = 0; $i < count($data); $i++) {
            $w = $widths[$i];
            $x = $this->GetX();
            $y = $this->GetY();
            $this->Rect($x, $y, $w, $h);
            $this->MultiCell($w, 5, utf8_decode($data[$i]), 0, 'L');
            $this->SetXY($x + $w, $y);
        }
        $this->Ln($h);
    }

    function CheckPageBreak($h)
    {
        if ($this->GetY() + $h > $this->PageBreakTrigger)
            $this->AddPage($this->CurOrientation);
    }

    function NbLines($w, $txt)
    {
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0)
            $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] == "\n")
            $nb--;
        $sep = -1;
        $i = 0; $j = 0; $l = 0; $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") { $i++; $sep = -1; $j = $i; $l = 0; $nl++; continue; }
            if ($c == ' ') $sep = $i;
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) { if ($i == $j) $i++; } else $i = $sep + 1;
                $sep = -1; $j = $i; $l = 0; $nl++;
            } else $i++;
        }
        return $nl;
    }
}

// Crear PDF
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 9);

// Consultar productos usando PDO
$stmt = $pdo->query("SELECT * FROM productos ORDER BY id ASC");
$productos = $stmt->fetchAll();

if ($productos) {
    foreach ($productos as $fila) {
        $pdf->Row([
            $fila['codigo'],
            $fila['nombre'],
            $fila['categoria'],
            $fila['marca'],
            $fila['modelo'],
            $fila['cilindrada'],
            number_format($fila['precio'], 0, ',', '.'),
            $fila['stock'],
            $fila['stock_min']
        ]);
    }
} else {
    $pdf->Cell(0, 10, 'No hay productos registrados.', 1, 1, 'C');
}

// Mostrar PDF
$pdf->Output('I', 'productos.pdf');
?>
