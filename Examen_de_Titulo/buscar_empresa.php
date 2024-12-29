<?php
include('conexion.php');

if (isset($_GET['rut'])) {
    $rut = $_GET['rut'];

    $sql = "SELECT * FROM empresas WHERE rut_empresa = ?";
    $stmt = mysqli_prepare($enlace, $sql);
    mysqli_stmt_bind_param($stmt, 's', $rut);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    if ($fila = mysqli_fetch_assoc($resultado)) {
        echo json_encode(['success' => true, 'nombre_empresa' => $fila['nombre_empresa'], 'correo_empresa' => $fila['correo_empresa'], 'direccion_empresa' => $fila['direccion_empresa'], 'telefono_empresa' => $fila['telefono_empresa'], 'ciudad_empresa' => $fila['ciudad_empresa']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Empresa no encontrada.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'RUT no proporcionado.']);
}
?>
