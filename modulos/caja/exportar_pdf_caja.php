<?php
require('../../includes/conexion.php');
require('../../includes/fpdf/fpdf.php');

$stmt = $pdo->query("SELECT * FROM caja WHERE estado='Cerrada' ORDER BY id DESC LIMIT 1");
$caja = $stmt->fetch();
if(!$caja){ die("No hay caja cerrada."); }

$stmt = $pdo->prepare("SELECT * FROM caja_movimientos WHERE caja_id=:caja_id");
$stmt->execute(['caja_id'=>$caja['id']]);
$movimientos = $stmt->fetchAll();

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,10,'Reporte de Caja - Repuestos Doble A',0,1,'C');

$pdf->SetFont('Arial','',12);
$pdf->Cell(0,8,'Fecha: '.$caja['fecha'],0,1);
$pdf->Cell(0,8,'Monto Inicial: '.number_format($caja['monto_inicial'],2),0,1);
$pdf->Ln(5);

$pdf->SetFont('Arial','B',12);
$pdf->Cell(40,8,'Fecha',1,0,'C');
$pdf->Cell(30,8,'Tipo',1,0,'C');
$pdf->Cell(80,8,'Concepto',1,0,'C');
$pdf->Cell(40,8,'Monto',1,1,'C');
$pdf->SetFont('Arial','',12);

$total_ingresos = 0;
$total_egresos = 0;
foreach($movimientos as $m){
    $pdf->Cell(40,8,$m['fecha'],1,0,'C');
    $pdf->Cell(30,8,$m['tipo'],1,0,'C');
    $pdf->Cell(80,8,$m['concepto'],1,0,'L');
    $pdf->Cell(40,8,number_format($m['monto'],2),1,1,'R');
    if($m['tipo']=='Ingreso') $total_ingresos+=$m['monto'];
    else $total_egresos+=$m['monto'];
}

$monto_final = $caja['monto_inicial'] + $total_ingresos - $total_egresos;
$pdf->Ln(5);
$pdf->Cell(0,8,'Total Ingresos: '.number_format($total_ingresos,2),0,1);
$pdf->Cell(0,8,'Total Egresos: '.number_format($total_egresos,2),0,1);
$pdf->Cell(0,8,'Monto Final: '.number_format($monto_final,2),0,1);

$pdf->Output('I','reporte_caja.pdf');
?>
