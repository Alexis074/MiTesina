<?php
date_default_timezone_set('America/Asuncion');
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
require($base_path . 'fpdf/fpdf.php');

// Obtener caja por ID o la última cerrada
$caja = null;
if(isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM caja WHERE id=:id");
    $stmt->execute(['id' => $_GET['id']]);
    $caja = $stmt->fetch();
} else {
    $stmt = $pdo->query("SELECT * FROM caja WHERE estado='Cerrada' ORDER BY id DESC LIMIT 1");
    $caja = $stmt->fetch();
}

if(!$caja){ die("No hay caja disponible."); }

// Obtener movimientos de la caja (manuales, ventas y compras)
$movimientos = [];

// Movimientos manuales
$stmtMov = $pdo->prepare("SELECT id, fecha, tipo, concepto as descripcion, monto, 'manual' as origen FROM caja_movimientos WHERE caja_id=? ORDER BY fecha DESC");
$stmtMov->execute([$caja['id']]);
$movimientos_manuales = $stmtMov->fetchAll();

// Ventas (ingresos) - excluyendo anuladas
$fecha_apertura = $caja['fecha'];
$stmtVentas = $pdo->prepare("SELECT id, fecha_hora as fecha, 'Ingreso' as tipo, CONCAT('Venta - Factura ', numero_factura) as descripcion, monto_total as monto, 'venta' as origen 
                             FROM cabecera_factura_ventas 
                             WHERE fecha_hora >= ? AND (anulada = 0 OR anulada IS NULL)
                             ORDER BY fecha_hora DESC");
$stmtVentas->execute([$fecha_apertura]);
$ventas = $stmtVentas->fetchAll();

// Compras (egresos) - de la tabla compras
$stmtCompras = $pdo->prepare("SELECT c.id, c.fecha, 'Egreso' as tipo, CONCAT('Compra - ID ', c.id, ' - Proveedor ID ', c.proveedor_id) as descripcion, c.total as monto, 'compra' as origen 
                              FROM compras c 
                              WHERE c.fecha >= ?
                              ORDER BY c.fecha DESC");
$stmtCompras->execute([$fecha_apertura]);
$compras = $stmtCompras->fetchAll();

// Facturas de compras (egresos) - si existen
$facturas_compras = [];
try {
    $stmtFacturasCompras = $pdo->prepare("SELECT fc.id, fc.fecha_hora as fecha, 'Egreso' as tipo, CONCAT('Factura Compra - ', fc.numero_factura) as descripcion, fc.monto_total as monto, 'factura_compra' as origen 
                                          FROM cabecera_factura_compras fc 
                                          WHERE fc.fecha_hora >= ?
                                          ORDER BY fc.fecha_hora DESC");
    $stmtFacturasCompras->execute([$fecha_apertura]);
    $facturas_compras = $stmtFacturasCompras->fetchAll();
} catch (Exception $e) {
    // Tabla no existe, continuar
}

// Combinar todos los movimientos
$movimientos = array_merge($movimientos_manuales, $ventas, $compras, $facturas_compras);

// Ordenar por fecha
usort($movimientos, function($a, $b) {
    return strtotime($b['fecha']) - strtotime($a['fecha']);
});

// Calcular totales correctamente
$total_ingresos = 0;
$total_egresos = 0;
foreach($movimientos as $m){
    if($m['tipo'] == 'Ingreso') {
        $total_ingresos += (float)$m['monto'];
    } else {
        $total_egresos += (float)$m['monto'];
    }
}

// Si la caja está cerrada, usar monto_final de la base de datos, sino calcular
if($caja['estado'] == 'Cerrada' && isset($caja['monto_final'])) {
    $monto_final = (float)$caja['monto_final'];
} else {
    $monto_final = (float)$caja['monto_inicial'] + $total_ingresos - $total_egresos;
}

$saldo = $monto_final - (float)$caja['monto_inicial'];

// Crear PDF con clase personalizada similar a generar_reporte_pdf.php
class PDF_Caja extends FPDF
{
    function Header()
    {
        global $caja;
        $this->Image($GLOBALS['base_path'] . 'img/logo3.png', 10, 8, 30);
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, utf8_decode('Reporte de Caja - Repuestos Doble A'), 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, utf8_decode('Fecha de Apertura: ' . date('d/m/Y H:i', strtotime($caja['fecha']))), 0, 1, 'C');
        if($caja['estado'] == 'Cerrada' && isset($caja['fecha_cierre'])) {
            $this->Cell(0, 5, utf8_decode('Fecha de Cierre: ' . date('d/m/Y H:i', strtotime($caja['fecha_cierre']))), 0, 1, 'C');
        }
        $this->Cell(0, 5, utf8_decode('Fecha de Generación: ' . date('d/m/Y H:i:s')), 0, 1, 'C');
        $this->Ln(5);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_decode('Repuestos Doble A - Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function SectionTitle($title)
    {
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor(37, 99, 235);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 8, utf8_decode($title), 0, 1, 'L', true);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(2);
    }
}

$pdf = new PDF_Caja();
$pdf->AliasNbPages();
$pdf->AddPage();

// Resumen de Caja
$pdf->SectionTitle('RESUMEN DE CAJA');
$pdf->SetFont('Arial', '', 10);

$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(95, 8, utf8_decode('Monto Inicial:'), 1, 0, 'L', true);
$pdf->Cell(95, 8, number_format($caja['monto_inicial'], 0, ',', '.') . ' Gs', 1, 1, 'R');
$pdf->Cell(95, 8, utf8_decode('Total Ingresos:'), 1, 0, 'L', true);
$pdf->Cell(95, 8, number_format($total_ingresos, 0, ',', '.') . ' Gs', 1, 1, 'R');
$pdf->Cell(95, 8, utf8_decode('Total Egresos:'), 1, 0, 'L', true);
$pdf->Cell(95, 8, number_format($total_egresos, 0, ',', '.') . ' Gs', 1, 1, 'R');
$pdf->Cell(95, 8, utf8_decode('Monto Final:'), 1, 0, 'L', true);
$pdf->Cell(95, 8, number_format($monto_final, 0, ',', '.') . ' Gs', 1, 1, 'R');

// Saldo con color según ganancia/pérdida
$pdf->SetFont('Arial', 'B', 11);
if($saldo < 0) {
    $pdf->SetFillColor(255, 220, 220);
    $pdf->SetTextColor(220, 38, 38);
} else {
    $pdf->SetFillColor(220, 255, 220);
    $pdf->SetTextColor(16, 185, 129);
}
$pdf->Cell(95, 8, utf8_decode('SALDO:'), 1, 0, 'L', true);
$pdf->Cell(95, 8, number_format($saldo, 0, ',', '.') . ' Gs ' . utf8_decode($saldo < 0 ? '(PÉRDIDA)' : '(GANANCIA)'), 1, 1, 'R');

$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(10);

// Detalle de Movimientos
$pdf->SectionTitle('DETALLE DE MOVIMIENTOS');
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(37, 99, 235);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(35, 7, 'Fecha', 1, 0, 'C', true);
$pdf->Cell(25, 7, 'Tipo', 1, 0, 'C', true);
$pdf->Cell(90, 7, 'Descripcion', 1, 0, 'C', true);
$pdf->Cell(40, 7, 'Monto', 1, 1, 'C', true);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 8);

if(empty($movimientos)) {
    $pdf->Cell(190, 8, utf8_decode('No hay movimientos registrados.'), 1, 1, 'C');
} else {
    foreach($movimientos as $m){
        $descripcion = isset($m['descripcion']) ? $m['descripcion'] : (isset($m['concepto']) ? $m['concepto'] : '');
        $fecha_formato = date('d/m/Y H:i', strtotime($m['fecha']));
        
        // Colorear fila según tipo
        if($m['tipo'] == 'Ingreso') {
            $pdf->SetFillColor(240, 255, 240);
        } else {
            $pdf->SetFillColor(255, 240, 240);
        }
        
        $pdf->Cell(35, 6, $fecha_formato, 1, 0, 'C', true);
        $pdf->Cell(25, 6, utf8_decode($m['tipo']), 1, 0, 'C', true);
        $pdf->Cell(90, 6, utf8_decode($descripcion), 1, 0, 'L', true);
        $pdf->Cell(40, 6, number_format($m['monto'], 0, ',', '.') . ' Gs', 1, 1, 'R', true);
    }
}

// Restaurar color normal
$pdf->SetFillColor(255, 255, 255);
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(5);

// Resumen por tipo
$pdf->SectionTitle('RESUMEN POR TIPO');
$pdf->SetFont('Arial', '', 10);

// Contar y sumar por origen
$resumen_ventas = 0;
$resumen_compras = 0;
$resumen_manuales_ingreso = 0;
$resumen_manuales_egreso = 0;

foreach($movimientos as $m){
    if($m['origen'] == 'venta') {
        $resumen_ventas += (float)$m['monto'];
    } elseif($m['origen'] == 'compra') {
        $resumen_compras += (float)$m['monto'];
    } elseif($m['origen'] == 'manual') {
        if($m['tipo'] == 'Ingreso') {
            $resumen_manuales_ingreso += (float)$m['monto'];
        } else {
            $resumen_manuales_egreso += (float)$m['monto'];
        }
    }
}

$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(95, 8, utf8_decode('Ventas (Ingresos):'), 1, 0, 'L', true);
$pdf->Cell(95, 8, number_format($resumen_ventas, 0, ',', '.') . ' Gs', 1, 1, 'R');
$pdf->Cell(95, 8, utf8_decode('Compras (Egresos):'), 1, 0, 'L', true);
$pdf->Cell(95, 8, number_format($resumen_compras, 0, ',', '.') . ' Gs', 1, 1, 'R');
if($resumen_manuales_ingreso > 0 || $resumen_manuales_egreso > 0) {
    $pdf->Cell(95, 8, utf8_decode('Movimientos Manuales (Ingresos):'), 1, 0, 'L', true);
    $pdf->Cell(95, 8, number_format($resumen_manuales_ingreso, 0, ',', '.') . ' Gs', 1, 1, 'R');
    $pdf->Cell(95, 8, utf8_decode('Movimientos Manuales (Egresos):'), 1, 0, 'L', true);
    $pdf->Cell(95, 8, number_format($resumen_manuales_egreso, 0, ',', '.') . ' Gs', 1, 1, 'R');
}

$pdf->Output('I','reporte_caja_' . date('Y-m-d') . '.pdf');
?>
