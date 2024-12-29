<?php
// Conexión a la base de datos
include('conexion.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigoProducto = $_POST['codigo_producto'];

    // Buscar el producto por código
    $consulta = "SELECT codigo, nombre_producto, valor_neto FROM items_factura WHERE codigo = ?";
    $stmt = mysqli_prepare($enlace, $consulta);
    mysqli_stmt_bind_param($stmt, 's', $codigoProducto);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($resultado) > 0) {
        $producto = mysqli_fetch_assoc($resultado);
        echo json_encode(['success' => true, 'producto' => $producto]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Código de producto no encontrado.']);
    }
}
