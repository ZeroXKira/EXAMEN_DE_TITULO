<?php
// Conexión a la base de datos
include('conexion.php');

// Consulta para obtener todos los datos
$sql = "SELECT numero_factura, codigo, nombre_producto, valor_neto, cantidad FROM items_factura";
$resultado = mysqli_query($enlace, $sql);

if (!$resultado) {
    die("Error al consultar los datos: " . mysqli_error($enlace));
}

// Verificar si hay datos
if (mysqli_num_rows($resultado) === 0) {
    echo "<script>alert('No hay datos para exportar.'); window.location.href='visualizar_todos_los_datos.php';</script>";
    exit;
}

// Configuración de encabezados para descargar Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Todos_los_datos_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Encabezados de la tabla
echo "Número de Factura\tCódigo\tNombre Producto\tValor Neto\tCantidad\n";

// Agregar los datos al archivo Excel
while ($row = mysqli_fetch_assoc($resultado)) {
    echo $row['numero_factura'] . "\t" .
         $row['codigo'] . "\t" .
         $row['nombre_producto'] . "\t" .
         number_format($row['valor_neto'], 2, '.', '') . "\t" .
         $row['cantidad'] . "\n";
}

// Cerrar conexión
mysqli_close($enlace);
?>
