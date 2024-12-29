<?php
include('conexion.php');

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['productos'])) {
    $productos = $data['productos'];
    $fecha = date("Y-m-d H:i:s");

    foreach ($productos as $producto) {
        $codigo = $producto['codigo'];
        $cantidad = $producto['cantidad'];
        $valorNeto = $producto['valor_neto'];

        // Insertar en la tabla ventas
        $queryVenta = "INSERT INTO ventas (codigo_producto, cantidad, valor_neto, fecha) VALUES (?, ?, ?, ?)";
        $stmtVenta = $enlace->prepare($queryVenta);
        $stmtVenta->bind_param("sids", $codigo, $cantidad, $valorNeto, $fecha);
        $stmtVenta->execute();

        // Actualizar el stock en items_factura
        $queryStock = "UPDATE items_factura SET cantidad = cantidad - ? WHERE codigo = ?";
        $stmtStock = $enlace->prepare($queryStock);
        $stmtStock->bind_param("is", $cantidad, $codigo);
        $stmtStock->execute();
    }

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>
