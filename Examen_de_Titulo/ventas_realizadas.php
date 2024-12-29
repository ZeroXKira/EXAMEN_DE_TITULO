<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['nombre_usuario'])) {
    header("Location: index.html");
    exit();
}

// Conexión a la base de datos
include('conexion.php');

// Consultar las ventas realizadas
$sql = "SELECT 
            v.fecha, 
            v.rut_cliente, 
            c.nombre_cliente AS cliente, 
            v.codigo_producto, 
            i.nombre_producto, 
            v.cantidad, 
            v.valor_neto
        FROM ventas v
        INNER JOIN clientes c ON v.rut_cliente = c.rut_cliente
        INNER JOIN items_factura i ON v.codigo_producto = i.codigo
        ORDER BY v.fecha DESC";

$resultado = mysqli_query($enlace, $sql);
if (!$resultado) {
    die("Error en la consulta: " . mysqli_error($enlace));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas Realizadas</title>
    <link rel="stylesheet" href="css/estilos_ventas_realizadas.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Ventas Realizadas</h1>
            <button class="cerrar-sesion" onclick="location.href='cerrar_sesion.php'">Cerrar Sesión</button>
        </header>
        <main>
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>RUT Cliente</th>
                        <th>Nombre Cliente</th>
                        <th>Código Producto</th>
                        <th>Nombre Producto</th>
                        <th>Cantidad</th>
                        <th>Valor Neto</th>
                        <th>IVA</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($resultado) > 0) {
                        while ($venta = mysqli_fetch_assoc($resultado)) {
                            $valorNeto = $venta['valor_neto'];
                            $iva = $valorNeto * 0.19;
                            $total = $valorNeto + $iva;
                            echo "<tr>
                                <td>" . htmlspecialchars($venta['fecha']) . "</td>
                                <td>" . htmlspecialchars($venta['rut_cliente']) . "</td>
                                <td>" . htmlspecialchars($venta['cliente']) . "</td>
                                <td>" . htmlspecialchars($venta['codigo_producto']) . "</td>
                                <td>" . htmlspecialchars($venta['nombre_producto']) . "</td>
                                <td>" . htmlspecialchars($venta['cantidad']) . "</td>
                                <td>$" . number_format($valorNeto, 0, ',', '.') . "</td>
                                <td>$" . number_format($iva, 0, ',', '.') . "</td>
                                <td>$" . number_format($total, 0, ',', '.') . "</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='9'>No se encontraron ventas realizadas.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
            <form action="exportar_excel_ventas.php" method="POST">
                <button type="submit" class="btn-excel">Descargar Excel</button>
            </form>
            <button onclick="location.href='pagina_principal.html'" class="btn-atras">ATRÁS</button>
        </main>
    </div>
</body>
</html>
