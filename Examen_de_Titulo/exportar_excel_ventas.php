<?php
// Incluir la conexión a la base de datos
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

// Configurar encabezados para la descarga del archivo Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=ventas_realizadas_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Encabezado de la tabla en el archivo Excel
echo "Fecha\tRUT Cliente\tNombre Cliente\tCódigo Producto\tNombre Producto\tCantidad\tValor Neto\tIVA\tTotal\n";

// Agregar datos al archivo Excel
while ($venta = mysqli_fetch_assoc($resultado)) {
    $valorNeto = $venta['valor_neto'];
    $iva = $valorNeto * 0.19;
    $total = $valorNeto + $iva;

    echo $venta['fecha'] . "\t" .
         $venta['rut_cliente'] . "\t" .
         $venta['cliente'] . "\t" .
         $venta['codigo_producto'] . "\t" .
         $venta['nombre_producto'] . "\t" .
         $venta['cantidad'] . "\t" .
         number_format($valorNeto, 2, '.', '') . "\t" .
         number_format($iva, 2, '.', '') . "\t" .
         number_format($total, 2, '.', '') . "\n";
}

// Cerrar conexión a la base de datos
mysqli_close($enlace);
?>
