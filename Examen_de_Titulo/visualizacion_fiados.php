<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['nombre_usuario'])) {
    header("Location: index.html");
    exit();
}

// Conexión a la base de datos
include('conexion.php');

// Consultar los fiados
$sql = "SELECT 
            c.nombre_cliente,
            c.rut_cliente,
            c.correo_cliente,
            c.telefono_cliente,
            f.valor_neto,
            f.valor_total,
            f.cantidad,
            f.fecha
        FROM fiados f
        INNER JOIN clientes c ON f.id_cliente = c.id_cliente
        ORDER BY f.fecha DESC";

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
    <title>Visualización de Fiados</title>
    <link rel="stylesheet" href="css/estilos_visualizacion_fiados.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Visualización de Fiados</h1>
            <button class="cerrar-sesion" onclick="location.href='cerrar_sesion.php'">Cerrar Sesión</button>
        </header>
        <main>
            <table>
                <thead>
                    <tr>
                        <th>Nombre Cliente</th>
                        <th>RUT</th>
                        <th>Correo</th>
                        <th>Teléfono</th>
                        <th>Valor Neto</th>
                        <th>IVA</th>
                        <th>Total</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($resultado) > 0) {
                        while ($fiado = mysqli_fetch_assoc($resultado)) {
                            $iva = $fiado['valor_neto'] * 0.19;
                            echo "<tr>
                                <td>" . htmlspecialchars($fiado['nombre_cliente']) . "</td>
                                <td>" . htmlspecialchars($fiado['rut_cliente']) . "</td>
                                <td>" . htmlspecialchars($fiado['correo_cliente']) . "</td>
                                <td>" . htmlspecialchars($fiado['telefono_cliente']) . "</td>
                                <td>$" . number_format($fiado['valor_neto'], 0, ',', '.') . "</td>
                                <td>$" . number_format($iva, 0, ',', '.') . "</td>
                                <td>$" . number_format($fiado['valor_total'], 0, ',', '.') . "</td>
                                <td>" . htmlspecialchars($fiado['fecha']) . "</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8'>No se encontraron fiados registrados.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
            <form action="exportar_excel_fiados.php" method="POST">
                <button type="submit" class="boton-descargar">Descargar Excel</button>
            </form>
            <button class="boton-atras" onclick="location.href='venta_normal.php'">ATRÁS</button>
        </main>
    </div>
</body>
</html>
