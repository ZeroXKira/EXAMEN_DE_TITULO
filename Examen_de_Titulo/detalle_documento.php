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

// Verificar si se ha proporcionado un número de factura
if (!isset($_GET['numero_factura'])) {
    echo "<script>alert('Número de documento no especificado.'); window.location.href='visualizar_factura.php';</script>";
    exit();
}

$numeroFactura = mysqli_real_escape_string($enlace, $_GET['numero_factura']);

// Verificar si el documento es una factura/boleta o un ingreso manual
$sqlFactura = "SELECT fb.numero_factura, fb.fecha_ingreso, e.rut_empresa, e.nombre_empresa, e.correo_empresa, 
                       e.direccion_empresa, e.telefono_empresa, e.ciudad_empresa 
                FROM facturas_boleta AS fb
                LEFT JOIN empresas AS e ON fb.rut_empresa = e.rut_empresa
                WHERE fb.numero_factura = '$numeroFactura'";
$resultadoFactura = mysqli_query($enlace, $sqlFactura);

if ($resultadoFactura && mysqli_num_rows($resultadoFactura) > 0) {
    $factura = mysqli_fetch_assoc($resultadoFactura);
} else {
    echo "<script>alert('Número de documento no encontrado.'); window.location.href='visualizar_factura.php';</script>";
    exit();
}

// Obtener los ítems relacionados de la tabla `ingreso_facturas_boletas`
$sqlItems = "SELECT codigo, nombre_producto, valor_neto, cantidad 
             FROM ingreso_facturas_boletas 
             WHERE numero_factura = '$numeroFactura'";
$resultadoItems = mysqli_query($enlace, $sqlItems);

if (!$resultadoItems) {
    die("Error en la consulta de ítems: " . mysqli_error($enlace));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Documento</title>
    <link rel="stylesheet" href="css/estilos_detalle.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Detalle del Documento</h1>
        </header>
        <main>
            <section class="detalle-documento">
                <h2>Información del Documento</h2>
                <p><strong>Número de Documento:</strong> <?php echo htmlspecialchars($factura['numero_factura']); ?></p>
                <p><strong>Fecha:</strong> <?php echo htmlspecialchars($factura['fecha_ingreso']); ?></p>
            </section>
            <section class="detalle-empresa">
                <h2>Información de la Empresa</h2>
                <p><strong>RUT Empresa:</strong> <?php echo htmlspecialchars($factura['rut_empresa']); ?></p>
                <p><strong>Nombre:</strong> <?php echo htmlspecialchars($factura['nombre_empresa']); ?></p>
                <p><strong>Correo:</strong> <?php echo htmlspecialchars($factura['correo_empresa']); ?></p>
                <p><strong>Dirección:</strong> <?php echo htmlspecialchars($factura['direccion_empresa']); ?></p>
                <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($factura['telefono_empresa']); ?></p>
                <p><strong>Ciudad:</strong> <?php echo htmlspecialchars($factura['ciudad_empresa']); ?></p>
            </section>
            <section class="items-relacionados">
                <h2>Ítems Relacionados</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre Producto</th>
                            <th>Valor Neto (CLP)</th>
                            <th>Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($resultadoItems) > 0) {
                            while ($item = mysqli_fetch_assoc($resultadoItems)) {
                                echo "<tr>
                                    <td>" . htmlspecialchars($item['codigo']) . "</td>
                                    <td>" . htmlspecialchars($item['nombre_producto']) . "</td>
                                    <td>" . number_format($item['valor_neto'], 0, ',', '.') . "</td>
                                    <td>" . htmlspecialchars($item['cantidad']) . "</td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4'>No hay ítems relacionados con este documento.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </section>
            <button onclick="location.href='visualizar_factura.php'" class="boton-atras">ATRÁS</button>
        </main>
    </div>
</body>
</html>
