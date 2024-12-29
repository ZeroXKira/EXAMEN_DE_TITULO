<?php
include('conexion.php');

$numeroFactura = $_GET['numero_factura'] ?? '';

if ($numeroFactura) {
    $sql = "SELECT COUNT(*) AS total FROM facturas_boleta WHERE numero_factura = ?";
    $stmt = mysqli_prepare($enlace, $sql);
    mysqli_stmt_bind_param($stmt, 's', $numeroFactura);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($resultado);

    echo json_encode(['existe' => $row['total'] > 0]);
} else {
    echo json_encode(['existe' => false]);
}

mysqli_close($enlace);
?>
