<?php 
// Evitar incluir session y auth si ya están incluidos
if (!isset($base_url)) {
    $base_url = '/repuestos/';
}
if (!isset($base_path)) {
    $base_path = ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/repuestos/';
}
$current_page = $_SERVER['REQUEST_URI'] ?? '/repuestos/';
// No incluir session y auth aquí porque ya se incluyen en los archivos principales
// Las hojas de estilo ya están incluidas en el <head> de cada página
?>

<div class="navbar">
    <div class="logo"><i class="fas fa-cogs"></i> Repuestos Doble A</div>
    <a href="<?php echo $base_url; ?>index.php" <?php echo (strpos($current_page, 'index.php') !== false || ($current_page == $base_url || $current_page == $base_url . 'index.php')) ? 'class="active"' : ''; ?>><i class="fas fa-home"></i> Inicio</a>
    <?php if (tienePermiso('clientes', 'ver')): ?>
    <a href="<?php echo $base_url; ?>modulos/clientes/clientes.php" <?php echo (strpos($current_page, 'clientes') !== false) ? 'class="active"' : ''; ?>><i class="fas fa-user"></i> Clientes</a>
    <?php endif; ?>
    <?php if (tienePermiso('proveedores', 'ver')): ?>
    <a href="<?php echo $base_url; ?>modulos/proveedores/proveedores.php" <?php echo (strpos($current_page, 'proveedores') !== false) ? 'class="active"' : ''; ?>><i class="fas fa-truck"></i> Proveedores</a>
    <?php endif; ?>
    <?php if (tienePermiso('productos', 'ver')): ?>
    <a href="<?php echo $base_url; ?>modulos/productos/productos.php" <?php echo (strpos($current_page, 'productos') !== false) ? 'class="active"' : ''; ?>><i class="fas fa-box"></i> Productos</a>
    <?php endif; ?>
    <?php if (tienePermiso('caja', 'ver')): ?>
    <a href="<?php echo $base_url; ?>modulos/caja/caja.php" <?php echo (strpos($current_page, 'caja') !== false) ? 'class="active"' : ''; ?>><i class="fas fa-cash-register"></i> Caja</a>
    <?php endif; ?>
    <?php if (tienePermiso('ventas', 'ver')): ?>
    <a href="<?php echo $base_url; ?>modulos/ventas/ventas.php" <?php echo (strpos($current_page, 'ventas') !== false) ? 'class="active"' : ''; ?>><i class="fas fa-shopping-cart"></i> Ventas</a>
    <?php endif; ?>
    <?php if (tienePermiso('compras', 'ver')): ?>
    <a href="<?php echo $base_url; ?>modulos/compras/compras.php" <?php echo (strpos($current_page, 'compras') !== false) ? 'class="active"' : ''; ?>><i class="fas fa-hand-holding-dollar"></i> Compras</a>
    <?php endif; ?>
    <?php if (tienePermiso('stock', 'ver')): ?>
    <a href="<?php echo $base_url; ?>modulos/stock/stock.php" <?php echo (strpos($current_page, 'stock') !== false) ? 'class="active"' : ''; ?>><i class="fas fa-warehouse"></i> Stock</a>
    <?php endif; ?>
    <?php if (tienePermiso('auditoria', 'ver')): ?>
    <a href="<?php echo $base_url; ?>modulos/auditoria/auditoria.php" <?php echo (strpos($current_page, 'auditoria') !== false) ? 'class="active"' : ''; ?>><i class="fas fa-clipboard-list"></i> Auditoría</a>
    <?php endif; ?>
    <?php if (tienePermiso('reportes', 'ver')): ?>
    <a href="<?php echo $base_url; ?>modulos/reportes/reportes.php" <?php echo (strpos($current_page, 'reportes') !== false) ? 'class="active"' : ''; ?>><i class="fas fa-chart-bar"></i> Reportes</a>
    <?php endif; ?>
    <?php if (tienePermiso('ventas', 'ver')): ?>
    <a href="<?php echo $base_url; ?>modulos/credito/cuotas.php" <?php echo (strpos($current_page, 'credito') !== false || strpos($current_page, 'cuotas') !== false) ? 'class="active"' : ''; ?>><i class="fas fa-credit-card"></i> Crédito</a>
    <?php endif; ?>
    <?php if (tienePermiso('backup', 'ver')): ?>
    <a href="<?php echo $base_url; ?>modulos/backup/backup.php" <?php echo (strpos($current_page, 'backup') !== false) ? 'class="active"' : ''; ?>><i class="fas fa-database"></i> Backup</a>
    <?php endif; ?>
    <?php if (tienePermiso('facturacion', 'ver')): ?>
    <a href="<?php echo $base_url; ?>modulos/facturacion/facturacion.php" <?php echo (strpos($current_page, 'facturacion') !== false) ? 'class="active"' : ''; ?>><i class="fas fa-file-invoice"></i> Facturación</a>
    <?php endif; ?>
    <?php if (tienePermiso('usuarios', 'ver')): ?>
    <a href="<?php echo $base_url; ?>modulos/usuarios/usuarios.php" <?php echo (strpos($current_page, 'usuarios') !== false) ? 'class="active"' : ''; ?>><i class="fas fa-users-cog"></i> Usuarios</a>
    <?php endif; ?>
    <?php if (tienePermiso('usuarios', 'crear')): ?>
    <a href="<?php echo $base_url; ?>modulos/administracion/resetear_sistema.php" <?php echo (strpos($current_page, 'resetear_sistema') !== false) ? 'class="active"' : ''; ?> style="color: #dc2626;"><i class="fas fa-exclamation-triangle"></i> Resetear</a>
    <?php endif; ?>
    <a href="<?php echo $base_url; ?>logout.php" style="margin-left: auto;"><i class="fas fa-sign-out-alt"></i> Salir</a>
</div>
