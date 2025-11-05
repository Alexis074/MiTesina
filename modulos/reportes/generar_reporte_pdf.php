<?php
date_default_timezone_set('America/Asuncion');
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
require($base_path . 'fpdf/fpdf.php');

// Obtener fechas
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : date('Y-m-d', strtotime('-30 days'));
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : date('Y-m-d');

// Obtener estadísticas
$stmt_ventas = $pdo->prepare("SELECT COUNT(*) as total, SUM(monto_total) as monto_total 
                              FROM cabecera_factura_ventas 
                              WHERE fecha_hora >= ? AND fecha_hora <= ? AND (anulada = 0 OR anulada IS NULL)");
$stmt_ventas->execute([$fecha_desde, $fecha_hasta . ' 23:59:59']);
$stats_ventas = $stmt_ventas->fetch();

$stmt_compras = $pdo->prepare("SELECT COUNT(*) as total, SUM(total) as monto_total 
                               FROM compras 
                               WHERE fecha >= ? AND fecha <= ?");
$stmt_compras->execute([$fecha_desde, $fecha_hasta . ' 23:59:59']);
$stats_compras = $stmt_compras->fetch();

// Obtener cajas cerradas
$stmt_cajas = $pdo->prepare("SELECT * FROM caja 
                             WHERE estado='Cerrada' AND fecha >= ? AND fecha <= ?
                             ORDER BY fecha DESC");
$stmt_cajas->execute([$fecha_desde, $fecha_hasta . ' 23:59:59']);
$cajas_cerradas = $stmt_cajas->fetchAll();

// Obtener productos
$stmt_productos = $pdo->query("SELECT * FROM productos ORDER BY nombre ASC");
$productos = $stmt_productos->fetchAll();

// Obtener proveedores
$stmt_proveedores = $pdo->query("SELECT * FROM proveedores ORDER BY empresa ASC");
$proveedores = $stmt_proveedores->fetchAll();

$total_ventas = $stats_ventas['monto_total'] ? (float)$stats_ventas['monto_total'] : 0;
$total_compras = $stats_compras['monto_total'] ? (float)$stats_compras['monto_total'] : 0;
$ganancia_neta = $total_ventas - $total_compras;

// Calcular saldo de cajas RECALCULANDO desde ventas y compras reales (sin duplicar por caja)
$monto_inicial_total = 0;
foreach($cajas_cerradas as $caja) {
    $monto_inicial_total += (float)$caja['monto_inicial'];
}

// Calcular ingresos y egresos TOTALES en el rango de fechas (sin duplicar por caja)
// Total de ingresos (ventas) en el rango de fechas
$stmt_ingresos_total = $pdo->prepare("SELECT SUM(monto_total) as total 
                                     FROM cabecera_factura_ventas 
                                     WHERE fecha_hora >= ? AND fecha_hora <= ? AND (anulada = 0 OR anulada IS NULL)");
$stmt_ingresos_total->execute([$fecha_desde, $fecha_hasta . ' 23:59:59']);
$ingresos_total_data = $stmt_ingresos_total->fetch();
$ingresos_totales = $ingresos_total_data['total'] ? (float)$ingresos_total_data['total'] : 0;

// Total de egresos (compras) en el rango de fechas
$stmt_egresos_total = $pdo->prepare("SELECT SUM(total) as total 
                                     FROM compras 
                                     WHERE fecha >= ? AND fecha <= ?");
$stmt_egresos_total->execute([$fecha_desde, $fecha_hasta . ' 23:59:59']);
$egresos_total_data = $stmt_egresos_total->fetch();
$egresos_totales = $egresos_total_data['total'] ? (float)$egresos_total_data['total'] : 0;

// Facturas de compras (egresos) - si existen
try {
    $stmt_facturas_compras_total = $pdo->prepare("SELECT SUM(monto_total) as total 
                                                   FROM cabecera_factura_compras 
                                                   WHERE fecha_hora >= ? AND fecha_hora <= ?");
    $stmt_facturas_compras_total->execute([$fecha_desde, $fecha_hasta . ' 23:59:59']);
    $facturas_compras_total_data = $stmt_facturas_compras_total->fetch();
    $egresos_totales += $facturas_compras_total_data['total'] ? (float)$facturas_compras_total_data['total'] : 0;
} catch (Exception $e) {
    // Tabla no existe, continuar
}

// Movimientos manuales de todas las cajas en el rango
$movimientos_manuales_totales = 0;
$egresos_manuales_totales = 0;
foreach($cajas_cerradas as $caja) {
    $stmt_mov_manual = $pdo->prepare("SELECT * FROM caja_movimientos WHERE caja_id=?");
    $stmt_mov_manual->execute([$caja['id']]);
    $movimientos_manual = $stmt_mov_manual->fetchAll();
    foreach($movimientos_manual as $m){
        if($m['tipo']=='Ingreso') {
            $movimientos_manuales_totales += (float)$m['monto'];
        } else {
            $egresos_manuales_totales += (float)$m['monto'];
        }
    }
}
$ingresos_totales += $movimientos_manuales_totales;
$egresos_totales += $egresos_manuales_totales;

// Calcular monto final: monto_inicial_total + ingresos_totales - egresos_totales
$monto_final_total = $monto_inicial_total + $ingresos_totales - $egresos_totales;
$saldo_cajas = $monto_final_total - $monto_inicial_total;

// Crear PDF
class PDF extends FPDF
{
    function Header()
    {
        global $fecha_desde, $fecha_hasta;
        $this->Image($GLOBALS['base_path'] . 'img/logo3.png', 10, 8, 30);
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, utf8_decode('Reporte General - Repuestos Doble A'), 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 5, utf8_decode('Período: ' . date('d/m/Y', strtotime($fecha_desde)) . ' - ' . date('d/m/Y', strtotime($fecha_hasta))), 0, 1, 'C');
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

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// Estadísticas Generales
$pdf->SectionTitle('RESUMEN GENERAL');
$pdf->SetFont('Arial', '', 10);

$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(95, 8, utf8_decode('Total Ventas:'), 1, 0, 'L', true);
$pdf->Cell(95, 8, number_format($total_ventas, 0, ',', '.') . ' Gs', 1, 1, 'R');
$pdf->Cell(95, 8, utf8_decode('Total Compras:'), 1, 0, 'L', true);
$pdf->Cell(95, 8, number_format($total_compras, 0, ',', '.') . ' Gs', 1, 1, 'R');
$pdf->SetFillColor($ganancia_neta >= 0 ? 220 : 255, $ganancia_neta >= 0 ? 255 : 220, $ganancia_neta >= 0 ? 220 : 220);
$pdf->Cell(95, 8, utf8_decode('Ganancia Neta:'), 1, 0, 'L', true);
$pdf->Cell(95, 8, number_format($ganancia_neta, 0, ',', '.') . ' Gs', 1, 1, 'R');

if(!empty($cajas_cerradas)) {
    $pdf->Ln(3);
    $pdf->Cell(95, 8, utf8_decode('Monto Inicial Caja:'), 1, 0, 'L', true);
    $pdf->Cell(95, 8, number_format($monto_inicial_total, 0, ',', '.') . ' Gs', 1, 1, 'R');
    $pdf->Cell(95, 8, utf8_decode('Monto Final Caja:'), 1, 0, 'L', true);
    $pdf->Cell(95, 8, number_format($monto_final_total, 0, ',', '.') . ' Gs', 1, 1, 'R');
    $pdf->SetFillColor($saldo_cajas >= 0 ? 220 : 255, $saldo_cajas >= 0 ? 255 : 220, $saldo_cajas >= 0 ? 220 : 220);
    $pdf->Cell(95, 8, utf8_decode('Saldo Cajas:'), 1, 0, 'L', true);
    $pdf->Cell(95, 8, number_format($saldo_cajas, 0, ',', '.') . ' Gs ' . ($saldo_cajas < 0 ? '(PERDIDA)' : ''), 1, 1, 'R');
}

$pdf->Ln(10);

// Resumen de Cajas
if(!empty($cajas_cerradas)) {
    $pdf->SectionTitle('RESUMEN DE CAJAS CERRADAS');
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(37, 99, 235);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(40, 7, 'Fecha', 1, 0, 'C', true);
    $pdf->Cell(38, 7, 'Monto Inicial', 1, 0, 'C', true);
    $pdf->Cell(38, 7, 'Monto Final', 1, 0, 'C', true);
    $pdf->Cell(38, 7, 'Saldo', 1, 0, 'C', true);
    $pdf->Cell(36, 7, 'Estado', 1, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', '', 8);
    
    foreach($cajas_cerradas as $caja) {
        // Recalcular saldo para esta caja desde ventas y compras reales
        $fecha_apertura = $caja['fecha'];
        $fecha_cierre = isset($caja['fecha_cierre']) ? $caja['fecha_cierre'] : $fecha_hasta . ' 23:59:59';
        
        // Movimientos manuales
        $stmt_mov = $pdo->prepare("SELECT * FROM caja_movimientos WHERE caja_id=?");
        $stmt_mov->execute([$caja['id']]);
        $movimientos = $stmt_mov->fetchAll();
        
        $ingresos_caja = 0;
        $egresos_caja = 0;
        foreach($movimientos as $m){
            if($m['tipo']=='Ingreso') {
                $ingresos_caja += (float)$m['monto'];
            } else {
                $egresos_caja += (float)$m['monto'];
            }
        }
        
        // Ventas
        $stmt_ventas = $pdo->prepare("SELECT SUM(monto_total) as total 
                                     FROM cabecera_factura_ventas 
                                     WHERE fecha_hora >= ? AND fecha_hora <= ? AND (anulada = 0 OR anulada IS NULL)");
        $stmt_ventas->execute([$fecha_apertura, $fecha_cierre]);
        $ventas_data = $stmt_ventas->fetch();
        $ingresos_caja += $ventas_data['total'] ? (float)$ventas_data['total'] : 0;
        
        // Compras
        $stmt_compras = $pdo->prepare("SELECT SUM(total) as total 
                                       FROM compras 
                                       WHERE fecha >= ? AND fecha <= ?");
        $stmt_compras->execute([$fecha_apertura, $fecha_cierre]);
        $compras_data = $stmt_compras->fetch();
        $egresos_caja += $compras_data['total'] ? (float)$compras_data['total'] : 0;
        
        // Facturas de compras - si existen
        try {
            $stmt_facturas_compras = $pdo->prepare("SELECT SUM(monto_total) as total 
                                                    FROM cabecera_factura_compras 
                                                    WHERE fecha_hora >= ? AND fecha_hora <= ?");
            $stmt_facturas_compras->execute([$fecha_apertura, $fecha_cierre]);
            $facturas_compras_data = $stmt_facturas_compras->fetch();
            $egresos_caja += $facturas_compras_data['total'] ? (float)$facturas_compras_data['total'] : 0;
        } catch (Exception $e) {
            // Tabla no existe, continuar
        }
        
        // Calcular monto final correcto
        $monto_final_calculado = (float)$caja['monto_inicial'] + $ingresos_caja - $egresos_caja;
        $saldo_caja = $monto_final_calculado - (float)$caja['monto_inicial'];
        
        $pdf->SetFillColor($saldo_caja < 0 ? 255 : 240, $saldo_caja < 0 ? 220 : 240, $saldo_caja < 0 ? 220 : 240);
        $pdf->Cell(40, 6, date('d/m/Y H:i', strtotime($caja['fecha'])), 1, 0, 'C', true);
        $pdf->Cell(38, 6, number_format($caja['monto_inicial'], 0, ',', '.'), 1, 0, 'R', true);
        $pdf->Cell(38, 6, number_format($monto_final_calculado, 0, ',', '.'), 1, 0, 'R', true);
        $pdf->Cell(38, 6, number_format($saldo_caja, 0, ',', '.'), 1, 0, 'R', true);
        $pdf->Cell(36, 6, utf8_decode($saldo_caja < 0 ? 'PERDIDA' : 'GANANCIA'), 1, 1, 'C', true);
    }
    $pdf->Ln(5);
}

// Productos
if(!empty($productos)) {
    $pdf->AddPage();
    $pdf->SectionTitle('LISTADO DE PRODUCTOS');
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetFillColor(37, 99, 235);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(20, 7, 'Código', 1, 0, 'C', true);
    $pdf->Cell(60, 7, 'Nombre', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Categoría', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'Precio', 1, 0, 'C', true);
    $pdf->Cell(20, 7, 'Stock', 1, 0, 'C', true);
    $pdf->Cell(35, 7, 'Stock Mín.', 1, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', '', 8);
    
    foreach($productos as $prod) {
        $pdf->Cell(20, 6, utf8_decode($prod['codigo']), 1, 0, 'C');
        $pdf->Cell(60, 6, utf8_decode($prod['nombre']), 1, 0, 'L');
        $pdf->Cell(30, 6, utf8_decode($prod['categoria']), 1, 0, 'L');
        $pdf->Cell(25, 6, number_format($prod['precio'], 0, ',', '.'), 1, 0, 'R');
        $pdf->Cell(20, 6, $prod['stock'], 1, 0, 'C');
        $pdf->Cell(35, 6, $prod['stock_min'], 1, 1, 'C');
    }
    $pdf->Ln(5);
}

// Proveedores
if(!empty($proveedores)) {
    $pdf->AddPage();
    $pdf->SectionTitle('LISTADO DE PROVEEDORES');
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetFillColor(37, 99, 235);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(20, 7, 'ID', 1, 0, 'C', true);
    $pdf->Cell(60, 7, 'Empresa', 1, 0, 'C', true);
    $pdf->Cell(40, 7, 'Contacto', 1, 0, 'C', true);
    $pdf->Cell(35, 7, 'Teléfono', 1, 0, 'C', true);
    $pdf->Cell(35, 7, 'Email', 1, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', '', 8);
    
    foreach($proveedores as $prov) {
        $pdf->Cell(20, 6, $prov['id'], 1, 0, 'C');
        $pdf->Cell(60, 6, utf8_decode($prov['empresa']), 1, 0, 'L');
        $pdf->Cell(40, 6, utf8_decode($prov['contacto']), 1, 0, 'L');
        $pdf->Cell(35, 6, utf8_decode($prov['telefono']), 1, 0, 'L');
        $pdf->Cell(35, 6, utf8_decode($prov['email']), 1, 1, 'L');
    }
}

$pdf->Output('I', 'reporte_general_' . date('Y-m-d') . '.pdf');
?>

