<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Repuestos Doble A</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .container {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: calc(100vh - 60px);
      text-align: center;
      position: relative;
    }
    .container::before {
      content: "";
      position: absolute;
      top: 0; left: 0;
      width: 100%;
      height: 100%;
      background-image: url('img/logo3.png');
      background-repeat: no-repeat;
      background-position: center;
      background-size: 700px auto;
      opacity: 0.1;
      z-index: 0;
    }
    .welcome-text { position: relative; z-index: 1; color: #1e293b; }
    .welcome-text h1 { font-size: 36px; margin-bottom: 20px; }
    .welcome-text p { font-size: 18px; color: #475569; }
  </style>
</head>
<body>

  <?php include 'includes/header.php'; ?>

  <div class="container">
    <div class="welcome-text">
      <h1>Bienvenido a Repuestos Doble A</h1>
      <p>Seleccione una opcion en la barra de navegacion para comenzar</p>
    </div>
  </div>

</body>
</html>
