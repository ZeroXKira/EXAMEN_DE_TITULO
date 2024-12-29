<?php
// Conexión a la base de datos
include('conexion.php');

// Consulta para obtener el stock de productos
$sql = "SELECT 
            codigo, 
            nombre_producto, 
            SUM(cantidad) AS total_stock,
            ROUND(AVG(valor_neto), 2) AS valor_promedio
        FROM items_factura
        GROUP BY codigo, nombre_producto";

$resultado = mysqli_query($enlace, $sql);

if (!$resultado) {
    die("Error al consultar los datos: " . mysqli_error($enlace));
}

// Verificar si hay datos
if (mysqli_num_rows($resultado) === 0) {
    echo "<script>alert('No hay datos para exportar.'); window.location.href='stock_de_productos.php';</script>";
    exit;
}

// Configuración de encabezados para descargar Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Stock_de_productos_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Encabezados de la tabla
echo "Código\tNombre Producto\tStock Total\tValor Neto Promedio\n";

// Agregar los datos
while ($row = mysqli_fetch_assoc($resultado)) {
    echo $row['codigo'] . "\t" .
         $row['nombre_producto'] . "\t" .
         $row['total_stock'] . "\t" .
         number_format($row['valor_promedio'], 2, '.', '') . "\n";
}

// Cerrar conexión
mysqli_close($enlace);
?>