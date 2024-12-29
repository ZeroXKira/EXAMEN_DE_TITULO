<?php
// Iniciar sesión
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no iniciada.']);
    exit();
}

// Conexión a la base de datos
include('conexion.php');

// Obtener los datos enviados desde el frontend
$datos = json_decode(file_get_contents("php://input"), true);

if (!$datos || !isset($datos['productos'])) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit();
}

$productos = $datos['productos'];
$idUsuario = $_SESSION['id_usuario'];

// Iniciar transacción
mysqli_begin_transaction($enlace);

try {
    // Registrar cada producto en la tabla de ventas y actualizar el stock
    foreach ($productos as $producto) {
        $codigoProducto = $producto['codigo'];
        $cantidad = $producto['cantidad'];

        // Verificar si el producto existe
        $sqlProducto = "SELECT cantidad, valor_neto FROM items_factura WHERE codigo = ?";
        $stmtProducto = mysqli_prepare($enlace, $sqlProducto);
        mysqli_stmt_bind_param($stmtProducto, 's', $codigoProducto);
        mysqli_stmt_execute($stmtProducto);
        $resultadoProducto = mysqli_stmt_get_result($stmtProducto);

        if ($filaProducto = mysqli_fetch_assoc($resultadoProducto)) {
            $stockActual = $filaProducto['cantidad'];
            $valorNeto = $filaProducto['valor_neto'];

            if ($cantidad > $stockActual) {
                throw new Exception("Stock insuficiente para el producto con código $codigoProducto.");
            }

            // Registrar la venta en la tabla 'ventas'
            $sqlVenta = "INSERT INTO ventas (id_usuario, codigo_producto, cantidad, valor_neto, fecha) 
                         VALUES (?, ?, ?, ?, NOW())";
            $stmtVenta = mysqli_prepare($enlace, $sqlVenta);
            mysqli_stmt_bind_param($stmtVenta, 'isid', $idUsuario, $codigoProducto, $cantidad, $valorNeto);
            mysqli_stmt_execute($stmtVenta);

            // Actualizar el stock en la tabla 'items_factura'
            $nuevoStock = $stockActual - $cantidad;
            $sqlActualizarStock = "UPDATE items_factura SET cantidad = ? WHERE codigo = ?";
            $stmtActualizarStock = mysqli_prepare($enlace, $sqlActualizarStock);
            mysqli_stmt_bind_param($stmtActualizarStock, 'is', $nuevoStock, $codigoProducto);
            mysqli_stmt_execute($stmtActualizarStock);
        } else {
            throw new Exception("Producto con código $codigoProducto no encontrado.");
        }
    }

    // Confirmar la transacción
    mysqli_commit($enlace);

    echo json_encode(['success' => true, 'message' => 'Venta registrada con éxito.']);
} catch (Exception $e) {
    // Revertir la transacción en caso de error
    mysqli_rollback($enlace);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Cerrar la conexión
mysqli_close($enlace);
?>
