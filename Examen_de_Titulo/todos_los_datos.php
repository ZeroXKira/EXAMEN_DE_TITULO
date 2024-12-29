<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['nombre_usuario'])) {
    // Redirigir al inicio de sesión si no hay sesión activa
    header("Location: index.html");
    exit();
}

// Evitar caché del navegador
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies
?>


<?php
// Conexión a la base de datos
include('conexion.php');

// Consulta para obtener todos los datos ingresados
$sql = "SELECT numero_factura, codigo, nombre_producto, valor_neto, cantidad FROM items_factura";
$resultado = mysqli_query($enlace, $sql);

// Verificar si la consulta tuvo éxito
if (!$resultado) {
    die("Error al obtener los datos: " . mysqli_error($enlace));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respaldo de Todos los Datos</title>
    <link rel="stylesheet" href="css/estilos_todos_los_datos.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Respaldo de Todos los Datos Ingresados</h1>
            <button class="cerrar-sesion" onclick="location.href='cerrar_sesion.php'">Cerrar Sesión</button>
        </header>
        <main>
            <table>
                <thead>
                    <tr>
                        <th>Número de Factura</th>
                        <th>Código</th>
                        <th>Nombre Producto</th>
                        <th>Valor Neto</th>
                        <th>Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Verificar si hay resultados
                    if (mysqli_num_rows($resultado) > 0) {
                        while ($fila = mysqli_fetch_assoc($resultado)) {
                            echo "<tr>
                                    <td>" . htmlspecialchars($fila['numero_factura']) . "</td>
                                    <td>" . htmlspecialchars($fila['codigo']) . "</td>
                                    <td>" . htmlspecialchars($fila['nombre_producto']) . "</td>
                                    <td>" . htmlspecialchars($fila['valor_neto']) . "</td>
                                    <td>" . htmlspecialchars($fila['cantidad']) . "</td>
                                </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5'>No hay datos registrados.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
            <div class="button-container">
                <button onclick="location.href='exportar_todos_los_datos.php'">Descargar Excel</button>
                <button onclick="location.href='pagina_principal.html'">ATRÁS</button>
            </div>
        </main>
    </div>
</body>
</html>
