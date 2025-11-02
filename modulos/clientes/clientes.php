<?php
$base_path = $_SERVER['DOCUMENT_ROOT'] . '/repuestos/';
include $base_path . 'includes/conexion.php';
include $base_path . 'includes/header.php';
?>

<div class="container">
    <h1>Clientes</h1>
    <a href="agregar_cliente.php" class="btn btn-edit">+ Agregar Cliente</a>
    <br><br>
    <table class="crud-table">
        <thead>
            <tr>
                <th style="width:50px;">ID</th>
                <th style="width:150px;">Nombre</th>
                <th style="width:150px;">Apellido</th>
                <th style="width:120px;">RUC</th>
                <th style="width:120px;">Telefono</th>
                <th style="width:200px;">Direccion</th>
                <th style="width:200px;">Email</th>
                <th style="width:180px;">Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $stmt = $pdo->query("SELECT * FROM clientes ORDER BY id ASC");
        $clientes = $stmt->fetchAll();
        if($clientes){
            foreach($clientes as $fila){
                echo "<tr>
                        <td>".htmlspecialchars($fila['id'])."</td>
                        <td>".htmlspecialchars($fila['nombre'])."</td>
                        <td>".htmlspecialchars($fila['apellido'])."</td>
                        <td>".htmlspecialchars($fila['ruc'])."</td>
                        <td>".htmlspecialchars($fila['telefono'])."</td>
                        <td>".htmlspecialchars($fila['direccion'])."</td>
                        <td>".htmlspecialchars($fila['email'])."</td>
                        <td class='acciones'>
                            <a href='editar_cliente.php?id={$fila['id']}' class='btn btn-edit'>Editar</a>
                            <a href='eliminar_cliente.php?id={$fila['id']}' class='btn btn-delete' onclick=\"return confirm('Seguro que deseas eliminar este cliente?')\">Eliminar</a>
                        </td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='8'>No hay clientes registrados.</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>

<style>
.container { 
    max-width:95%; 
    margin:80px auto; 
    background:#fff; 
    padding:20px; 
    border-radius:8px; 
    box-shadow:0 4px 10px rgba(0,0,0,0.1); 
    overflow-x:auto; /* Permite scroll horizontal si la tabla es más ancha */
}

h1 { 
    text-align:center; 
    margin-bottom:20px; 
    color:#1e293b; 
}

table.crud-table { 
    width:100%; 
    border-collapse:collapse; 
    min-width:800px; /* Evita que la tabla se haga demasiado pequeña */
    table-layout:auto; 
}

table.crud-table th, table.crud-table td { 
    border:1px solid #ccc; 
    padding:10px; 
    text-align:center; 
    word-wrap:break-word; 
}

table.crud-table th { 
    background:#2563eb; 
    color:white; 
}

table.crud-table tr:nth-child(even) { 
    background:#f9f9f9; 
}

table.crud-table tr:hover { 
    background:#e0f2fe; 
}

.acciones { 
    display:flex; 
    justify-content:center; 
    gap:5px; 
}

.btn { 
    padding:6px 12px; 
    border:none; 
    border-radius:4px; 
    text-decoration:none; 
    cursor:pointer; 
    font-size:14px; 
    white-space:nowrap; 
}

.btn-edit { 
    background:#facc15; 
    color:black; 
}

.btn-edit:hover { 
    background:#eab308; 
}

.btn-delete { 
    background:#ef4444; 
    color:white; 
}

.btn-delete:hover { 
    background:#dc2626; 
}

/* Responsivo para móviles */
@media (max-width:768px){
    .container { margin:20px auto; padding:10px; }
    table.crud-table { min-width:600px; font-size:12px; }
    .btn { font-size:12px; padding:4px 8px; }
}
</style>


<?php include $base_path . 'includes/footer.php'; ?>
