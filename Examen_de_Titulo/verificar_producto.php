<?php
include('conexion.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = $_POST['codigo'];

    // Verificar si el producto existe
    $consulta = "SELECT nombre_producto, valor_neto FROM items_factura WHERE codigo = ?";
    $stmt = mysqli_prepare($enlace, $consulta);
    mysqli_stmt_bind_param($stmt, 's', $codigo);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($resultado) > 0) {
        $producto = mysqli_fetch_assoc($resultado);
        echo json_encode(['success' => true, 'producto' => $producto]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Código de producto no encontrado.']);
    }
}
?>
