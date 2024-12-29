<?php
// Conexión a la base de datos
include('conexion.php');

// Consultar los fiados registrados
$sql = "SELECT 
            f.fecha, 
            c.rut_cliente, 
            c.nombre_cliente AS cliente, 
            f.codigo_producto, 
            f.nombre_producto, 
            f.cantidad, 
            f.valor_neto, 
            f.valor_total
        FROM fiados f
        INNER JOIN clientes c ON f.id_cliente = c.id_cliente
        ORDER BY f.fecha DESC";

$resultado = mysqli_query($enlace, $sql);
if (!$resultado) {
    die("Error en la consulta: " . mysqli_error($enlace));
}

// Configuración de encabezados para descargar Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=fiados_registrados_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Encabezado de la tabla
echo "Fecha\tRUT Cliente\tNombre Cliente\tCódigo Producto\tNombre Producto\tCantidad\tValor Neto\tIVA\tTotal\n";

// Datos de la tabla
while ($fiado = mysqli_fetch_assoc($resultado)) {
    $valorNeto = $fiado['valor_neto'];
    $iva = $valorNeto * 0.19;
    $total = $fiado['valor_total'];

    echo $fiado['fecha'] . "\t" .
         $fiado['rut_cliente'] . "\t" .
         $fiado['cliente'] . "\t" .
         $fiado['codigo_producto'] . "\t" .
         $fiado['nombre_producto'] . "\t" .
         $fiado['cantidad'] . "\t" .
         number_format($valorNeto, 2, '.', '') . "\t" .
         number_format($iva, 2, '.', '') . "\t" .
         number_format($total, 2, '.', '') . "\n";
}

// Cerrar conexión
mysqli_close($enlace);
?>
