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

// Consulta para obtener el stock actual y el valor neto promedio
$sql = "SELECT 
            codigo, 
            nombre_producto, 
            SUM(cantidad) AS total_stock,
            ROUND(AVG(valor_neto), 2) AS valor_promedio
        FROM items_factura
        GROUP BY codigo, nombre_producto";
$resultado = mysqli_query($enlace, $sql);

// Verificar si la consulta tuvo éxito
if (!$resultado) {
    die("Error al obtener los datos de stock: " . mysqli_error($enlace));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock de Productos</title>
    <link rel="stylesheet" href="css/estilos_stock.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Stock de Productos</h1>
            <button class="cerrar-sesion" onclick="location.href='cerrar_sesion.php'">Cerrar Sesión</button>
        </header>
        <main>
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre Producto</th>
                        <th>Stock Total</th>
                        <th>Valor Neto Promedio</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Mostrar los datos en la tabla
                    if (mysqli_num_rows($resultado) > 0) {
                        while ($fila = mysqli_fetch_assoc($resultado)) {
                            echo "<tr>
                                    <td>" . htmlspecialchars($fila['codigo']) . "</td>
                                    <td>" . htmlspecialchars($fila['nombre_producto']) . "</td>
                                    <td>" . htmlspecialchars($fila['total_stock']) . "</td>
                                    <td>" . htmlspecialchars($fila['valor_promedio']) . "</td>
                                </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4'>No hay datos de stock registrados.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
            <div class="button-container">
                <button onclick="location.href='exportar_stock_productos.php'">Descargar Excel</button>
                <button onclick="location.href='pagina_principal.html'">ATRÁS</button>
            </div>
        </main>
    </div>
</body>
</html>
