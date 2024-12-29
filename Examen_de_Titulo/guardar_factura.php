<?php
header('Content-Type: application/json');
session_start();

// Conexión a la base de datos
include('conexion.php');

// Verificar si el usuario está autenticado
if (!isset($_SESSION['nombre_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado.']);
    exit();
}

// Obtener datos del POST
$data = json_decode(file_get_contents('php://input'), true);

// Validar datos obligatorios
if (
    empty($data['tipoDocumento']) || empty($data['rutEmpresa']) || empty($data['nombreEmpresa']) ||
    empty($data['correoEmpresa']) || empty($data['direccionEmpresa']) || empty($data['telefonoEmpresa']) ||
    empty($data['ciudadEmpresa']) || empty($data['numeroFactura']) || empty($data['items'])
) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios.']);
    exit();
}

$tipoDocumento = $data['tipoDocumento'];
$fecha = date('Y-m-d'); // Fecha actual
$usuario = $_SESSION['nombre_usuario'];
$rutEmpresa = $data['rutEmpresa'];
$nombreEmpresa = $data['nombreEmpresa'];
$correoEmpresa = $data['correoEmpresa'];
$direccionEmpresa = $data['direccionEmpresa'];
$telefonoEmpresa = $data['telefonoEmpresa'];
$ciudadEmpresa = $data['ciudadEmpresa'];
$numeroFactura = $data['numeroFactura'];
$items = $data['items'];

// Iniciar transacción
mysqli_begin_transaction($enlace);

try {
    // Insertar datos de la factura/boleta
    $sqlEmpresa = "INSERT INTO facturas_boleta (numero_factura, tipo_documento, fecha, usuario, rut_empresa, nombre_empresa, correo_empresa, direccion_empresa, telefono_empresa, ciudad_empresa) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($enlace, $sqlEmpresa);
    mysqli_stmt_bind_param($stmt, 'ssssssssss', $numeroFactura, $tipoDocumento, $fecha, $usuario, $rutEmpresa, $nombreEmpresa, $correoEmpresa, $direccionEmpresa, $telefonoEmpresa, $ciudadEmpresa);
    mysqli_stmt_execute($stmt);

    $idFactura = mysqli_insert_id($enlace); // Obtener el ID de la factura

    // Insertar cada ítem
    $sqlItem = "INSERT INTO items_factura (id_factura, codigo, nombre_producto, valor_neto, cantidad) VALUES (?, ?, ?, ?, ?)";
    $stmtItem = mysqli_prepare($enlace, $sqlItem);

    foreach ($items as $item) {
        $codigo = $item['codigo'];
        $nombreProducto = $item['nombreProducto'];
        $valorNeto = $item['valorNeto'];
        $cantidad = $item['cantidad'];

        mysqli_stmt_bind_param($stmtItem, 'issdi', $idFactura, $codigo, $nombreProducto, $valorNeto, $cantidad);
        mysqli_stmt_execute($stmtItem);
    }

    // Confirmar transacción
    mysqli_commit($enlace);

    echo json_encode(['success' => true, 'message' => 'Factura y sus ítems guardados correctamente.']);
} catch (Exception $e) {
    // Revertir transacción en caso de error
    mysqli_rollback($enlace);
    echo json_encode(['success' => false, 'message' => 'Error al guardar la factura o sus ítems. ' . $e->getMessage()]);
}

// Cerrar la conexión
mysqli_close($enlace);
?>
