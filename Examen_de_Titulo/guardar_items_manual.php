<?php
include('conexion.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = $_POST['codigo'];
    $nombre_producto = $_POST['nombre_producto'];
    $valor_neto = $_POST['valor_neto'];
    $cantidad = $_POST['cantidad'];
    $numero_factura = $_POST['numero_aleatorio']; // Usamos numero_factura para almacenar el número aleatorio

    // Verificar si el producto ya existe
    $consultaExiste = "SELECT * FROM items_factura WHERE codigo = ?";
    $stmtExiste = mysqli_prepare($enlace, $consultaExiste);
    mysqli_stmt_bind_param($stmtExiste, 's', $codigo);
    mysqli_stmt_execute($stmtExiste);
    $resultado = mysqli_stmt_get_result($stmtExiste);

    if (mysqli_num_rows($resultado) > 0) {
        // Actualizar el valor neto, cantidad y número aleatorio si ya existe
        $consultaActualizar = "UPDATE items_factura 
                               SET valor_neto = ?, cantidad = cantidad + ?, nombre_producto = ?, numero_factura = ? 
                               WHERE codigo = ?";
        $stmtActualizar = mysqli_prepare($enlace, $consultaActualizar);
        mysqli_stmt_bind_param($stmtActualizar, 'disss', $valor_neto, $cantidad, $nombre_producto, $numero_factura, $codigo);
        mysqli_stmt_execute($stmtActualizar);

        echo json_encode(['success' => true, 'message' => 'Producto actualizado correctamente.']);
    } else {
        // Insertar un nuevo producto si no existe
        $consultaInsertar = "INSERT INTO items_factura (codigo, nombre_producto, valor_neto, cantidad, numero_factura) 
                             VALUES (?, ?, ?, ?, ?)";
        $stmtInsertar = mysqli_prepare($enlace, $consultaInsertar);
        mysqli_stmt_bind_param($stmtInsertar, 'ssdii', $codigo, $nombre_producto, $valor_neto, $cantidad, $numero_factura);
        mysqli_stmt_execute($stmtInsertar);

        echo json_encode(['success' => true, 'message' => 'Producto ingresado correctamente.']);
    }
    exit();
}
?>
