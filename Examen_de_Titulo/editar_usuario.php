<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['nombre_usuario'])) {
    // Redirigir al inicio de sesión si no hay sesión activa
    header("Location: index.html");
    exit();
}

// Evitar caché del navegador
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies
?>


<?php
// Incluir el archivo de conexión
include('conexion.php');

// Verificar si se envió el parámetro 'id_usuario' en la URL
if (isset($_GET['id_usuario'])) {
    $id_usuario = $_GET['id_usuario'];

    // Preparar la consulta para obtener los datos del usuario
    $consulta = "SELECT * FROM usuarios WHERE id_usuario = ?";
    $stmt = $enlace->prepare($consulta);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado && $resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();
    } else {
        echo "<p>Usuario no encontrado. <a href='administracion_usuarios.php'>Volver</a></p>";
        exit();
    }
} else {
    echo "<p>ID de usuario no especificado. <a href='administracion_usuarios.php'>Volver</a></p>";
    exit();
}

// Procesar el formulario de actualización si se envió
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombres = $_POST['nombres'];
    $apellido_paterno = $_POST['apellido_paterno'];
    $apellido_materno = $_POST['apellido_materno'];
    $rut = $_POST['rut'];
    $correo = $_POST['correo'];
    $nombre_usuario = $_POST['nombre_usuario'];
    $contrasena = $_POST['contrasena'];
    $rol = $_POST['rol'];

    // Actualizar los datos en la base de datos
    $consulta_actualizar = "UPDATE usuarios SET nombres = ?, apellido_paterno = ?, apellido_materno = ?, rut = ?, correo = ?, nombre_usuario = ?, contrasena = ?, rol = ? WHERE id_usuario = ?";
    $stmt_actualizar = $enlace->prepare($consulta_actualizar);
    $stmt_actualizar->bind_param("ssssssssi", $nombres, $apellido_paterno, $apellido_materno, $rut, $correo, $nombre_usuario, $contrasena, $rol, $id_usuario);

    if ($stmt_actualizar->execute()) {
        header("Location: administracion_usuarios.php");
        exit();
    } else {
        echo "Error al actualizar el usuario: " . $stmt_actualizar->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario</title>
    <link rel="stylesheet" href="css/estilos_editar_usuario.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Editar Usuario</h1>
        </header>
        <main>
            <form action="" method="POST">
                <label for="nombres">Nombres:</label>
                <input type="text" id="nombres" name="nombres" value="<?php echo htmlspecialchars($usuario['nombres']); ?>" required>

                <label for="apellido_paterno">Apellido Paterno:</label>
                <input type="text" id="apellido_paterno" name="apellido_paterno" value="<?php echo htmlspecialchars($usuario['apellido_paterno']); ?>" required>

                <label for="apellido_materno">Apellido Materno:</label>
                <input type="text" id="apellido_materno" name="apellido_materno" value="<?php echo htmlspecialchars($usuario['apellido_materno']); ?>" required>

                <label for="rut">RUT:</label>
                <input type="text" id="rut" name="rut" value="<?php echo htmlspecialchars($usuario['rut']); ?>" required>

                <label for="correo">Correo:</label>
                <input type="email" id="correo" name="correo" value="<?php echo htmlspecialchars($usuario['correo']); ?>" required>

                <label for="nombre_usuario">Nombre de Usuario:</label>
                <input type="text" id="nombre_usuario" name="nombre_usuario" value="<?php echo htmlspecialchars($usuario['nombre_usuario']); ?>" required>

                <label for="contrasena">Contraseña:</label>
                <input type="password" id="contrasena" name="contrasena" value="<?php echo htmlspecialchars($usuario['contrasena']); ?>" required>

                <label for="rol">Rol:</label>
                <select id="rol" name="rol" required>
                    <option value="usuario" <?php echo $usuario['rol'] === 'usuario' ? 'selected' : ''; ?>>Usuario</option>
                    <option value="administrador" <?php echo $usuario['rol'] === 'administrador' ? 'selected' : ''; ?>>Administrador</option>
                </select>

                <button type="submit">Guardar Cambios</button>
                <button type="button" onclick="location.href='administracion_usuarios.php'">Cancelar</button>
            </form>
        </main>
    </div>
</body>
</html>
