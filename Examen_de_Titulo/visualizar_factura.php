<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['nombre_usuario'])) {
    header("Location: index.html");
    exit();
}

// Evitar caché del navegador
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Conexión a la base de datos
include('conexion.php');

// Manejar la eliminación de facturas
if (isset($_GET['eliminar'])) {
    $numero_factura = mysqli_real_escape_string($enlace, $_GET['eliminar']);
    
    // Eliminar primero los ítems relacionados
    $sqlEliminarItems = "DELETE FROM items_factura WHERE numero_factura = '$numero_factura'";
    if (!mysqli_query($enlace, $sqlEliminarItems)) {
        echo "<script>alert('Error al eliminar los ítems: " . mysqli_error($enlace) . "');</script>";
    }

    // Luego eliminar la factura o boleta
    $sqlEliminarFactura = "DELETE FROM facturas_boleta WHERE numero_factura = '$numero_factura'";
    if (mysqli_query($enlace, $sqlEliminarFactura)) {
        echo "<script>alert('Factura/Boleta eliminada correctamente.');</script>";
        echo "<script>window.location.href='visualizar_factura.php';</script>";
    } else {
        echo "<script>alert('Error al eliminar la factura/boleta: " . mysqli_error($enlace) . "');</script>";
    }
}

// Consulta para obtener las facturas y los ingresos manuales
$sql = "
    SELECT DISTINCT
        numero_factura,
        fecha_ingreso,
        tipo
    FROM (
        SELECT 
            f.numero_factura, 
            f.fecha_ingreso, 
            f.tipo 
        FROM facturas_boleta AS f
        UNION ALL
        SELECT 
            i.numero_factura AS numero_factura, 
            NOW() AS fecha_ingreso, 
            'Ingreso Manual' AS tipo 
        FROM items_factura AS i
        WHERE i.numero_factura NOT IN (SELECT numero_factura FROM facturas_boleta)
    ) AS documentos
    ORDER BY fecha_ingreso DESC
";
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
    <title>Visualizar Factura / Boleta</title>
    <link rel="stylesheet" href="css/estilos_visualizar_factura.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Visualizar Factura / Boleta / Ingreso Manual</h1>
            <button class="cerrar-sesion" onclick="location.href='cerrar_sesion.php'">Cerrar Sesión</button>
        </header>
        <main>
            <table>
                <thead>
                    <tr>
                        <th>N° de Documento</th>
                        <th>Tipo</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($resultado) > 0) {
                        while ($fila = mysqli_fetch_assoc($resultado)) {
                            echo "<tr>
                                    <td>" . htmlspecialchars($fila['numero_factura']) . "</td>
                                    <td>" . htmlspecialchars($fila['tipo']) . "</td>
                                    <td>" . htmlspecialchars($fila['fecha_ingreso']) . "</td>
                                    <td>
                                        <button onclick=\"location.href='detalle_documento.php?numero_factura=" . urlencode($fila['numero_factura']) . "'\">Ver Detalle</button>
                                        <button onclick=\"if(confirm('¿Está seguro de que desea eliminar este registro?')) location.href='visualizar_factura.php?eliminar=" . urlencode($fila['numero_factura']) . "'\">Eliminar</button>
                                    </td>
                                </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4'>No hay facturas o ítems registrados.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
            <button class="boton-atras" onclick="location.href='pagina_principal.html'">ATRÁS</button>
        </main>
    </div>
</body>
</html>
