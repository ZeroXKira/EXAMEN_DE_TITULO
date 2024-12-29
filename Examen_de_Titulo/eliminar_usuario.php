<?php
// Incluir el archivo de conexión
include('conexion.php');

// Verificar si se envió el parámetro 'id_usuario' en la URL
if (isset($_GET['id_usuario'])) {
    $id_usuario = intval($_GET['id_usuario']); // Asegurarse de que el ID sea un número

    // Verificar que el ID exista en la base de datos antes de eliminar
    $consultaVerificar = "SELECT * FROM usuarios WHERE id_usuario = ?";
    $stmtVerificar = $enlace->prepare($consultaVerificar);
    $stmtVerificar->bind_param("i", $id_usuario);
    $stmtVerificar->execute();
    $resultado = $stmtVerificar->get_result();

    if ($resultado->num_rows > 0) {
        // Preparar la consulta para eliminar al usuario
        $consultaEliminar = "DELETE FROM usuarios WHERE id_usuario = ?";
        $stmtEliminar = $enlace->prepare($consultaEliminar);
        $stmtEliminar->bind_param("i", $id_usuario);

        if ($stmtEliminar->execute()) {
            // Redirigir a la página de administración de usuarios con éxito
            header("Location: administracion_usuarios.php?mensaje=Usuario eliminado correctamente.");
            exit();
        } else {
            echo "<p>Error al eliminar el usuario: " . $stmtEliminar->error . "</p>";
        }

        $stmtEliminar->close();
    } else {
        echo "<p>El usuario no existe o ya fue eliminado. <a href='administracion_usuarios.php'>Volver</a></p>";
    }

    $stmtVerificar->close();
} else {
    echo "<p>ID de usuario no especificado. <a href='administracion_usuarios.php'>Volver</a></p>";
}

// Cerrar la conexión
$enlace->close();
?>
