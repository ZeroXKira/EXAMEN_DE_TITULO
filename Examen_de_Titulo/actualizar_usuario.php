<?php
// Incluir el archivo de conexión
include('conexion.php');

// Verificar si el formulario fue enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_POST['id_usuario'];
    $nombres = $_POST['nombres'];
    $apellido_paterno = $_POST['apellido_paterno'];
    $apellido_materno = $_POST['apellido_materno'];
    $rut = $_POST['rut'];
    $correo = $_POST['correo'];
    $nombre_usuario = $_POST['nombre_usuario'];
    $contrasena = $_POST['contrasena'];
    $rol = $_POST['rol'];

    // Validar los datos
    if (empty($id_usuario) || empty($nombres) || empty($apellido_paterno) || empty($rut) || empty($correo) || empty($nombre_usuario) || empty($rol)) {
        die("Todos los campos son obligatorios.");
    }

    // Si la contraseña fue actualizada, aplicar un hash
    if (!empty($contrasena)) {
        $contrasena = password_hash($contrasena, PASSWORD_DEFAULT);
    }

    // Preparar la consulta SQL para actualizar
    $sql = "UPDATE usuarios SET nombres = ?, apellido_paterno = ?, apellido_materno = ?, rut = ?, correo = ?, nombre_usuario = ?, contrasena = ?, rol = ? WHERE id_usuario = ?";
    $stmt = $enlace->prepare($sql); // Cambiar $conn por $enlace si el archivo de conexión lo utiliza
    $stmt->bind_param("ssssssssi", $nombres, $apellido_paterno, $apellido_materno, $rut, $correo, $nombre_usuario, $contrasena, $rol, $id_usuario);

    if ($stmt->execute()) {
        // Redirigir a la página de administración
        header("Location: administracion_usuarios.php");
        exit();
    } else {
        echo "Error al actualizar el usuario: " . $stmt->error;
    }

    $stmt->close();
}

$enlace->close(); // Cambiar $conn por $enlace si es necesario
?>
