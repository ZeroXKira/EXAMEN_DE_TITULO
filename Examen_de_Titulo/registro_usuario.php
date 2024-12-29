<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['nombre_usuario'])) {
    header("Location: index.html");
    exit();
}

// Evitar caché del navegador
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies
?>

<?php
// Conexión a la base de datos
$servidor = "localhost";
$usuario = "root";
$clave = "";
$baseDeDatos = "base_de_datos";

$enlace = mysqli_connect($servidor, $usuario, $clave, $baseDeDatos);

// Verificar conexión
if (!$enlace) {
    die("Conexión fallida: " . mysqli_connect_error());
}

// Comprobar si el formulario fue enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["registro"])) {
    $nombres = mysqli_real_escape_string($enlace, trim($_POST['nombres']));
    $apellido_paterno = mysqli_real_escape_string($enlace, trim($_POST['apellido_paterno']));
    $apellido_materno = mysqli_real_escape_string($enlace, trim($_POST['apellido_materno']));
    $rut = mysqli_real_escape_string($enlace, trim($_POST['rut']));
    $correo = mysqli_real_escape_string($enlace, trim($_POST['correo']));
    $nombre_usuario = mysqli_real_escape_string($enlace, trim($_POST['nombre_usuario']));
    $contrasena = mysqli_real_escape_string($enlace, trim($_POST['contrasena']));
    $rol = mysqli_real_escape_string($enlace, $_POST['rol']);

    // Encriptar la contraseña con sha1
    $contrasena_encriptada = sha1($contrasena);

    // Verificar si el RUT ya existe
    $consultaRut = "SELECT COUNT(*) AS total FROM usuarios WHERE rut = '$rut'";
    $resultadoRut = mysqli_query($enlace, $consultaRut);
    $filaRut = mysqli_fetch_assoc($resultadoRut);

    if ($filaRut['total'] > 0) {
        echo "<script>alert('El RUT ingresado ya está registrado. Por favor, use uno diferente.');</script>";
    } else {
        // Insertar datos en la base de datos
        $insertarDatos = "INSERT INTO usuarios (nombres, apellido_paterno, apellido_materno, rut, correo, nombre_usuario, contrasena, rol, fecha_creacion) 
                          VALUES ('$nombres', '$apellido_paterno', '$apellido_materno', '$rut', '$correo', '$nombre_usuario', '$contrasena_encriptada', '$rol', NOW())";

        if (mysqli_query($enlace, $insertarDatos)) {
            echo "<script>alert('Usuario registrado con éxito.'); window.location.href = 'administracion_usuarios.php';</script>";
        } else {
            echo "<script>alert('Error en el registro: " . mysqli_error($enlace) . "');</script>";
        }
    }
}

// Cerrar conexión
mysqli_close($enlace);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario</title>
    <link rel="stylesheet" href="css/estilos_registro_usuario.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Registro de Usuario</h1>
            <button class="cerrar-sesion" onclick="location.href='cerrar_sesion.php'">Cerrar Sesión</button>
        </header>
        <main>
            <form action="registro_usuario.php" method="POST">
                <label for="nombres">Nombres:</label>
                <input type="text" id="nombres" name="nombres" required>

                <label for="apellido_paterno">Apellido Paterno:</label>
                <input type="text" id="apellido_paterno" name="apellido_paterno" required>

                <label for="apellido_materno">Apellido Materno:</label>
                <input type="text" id="apellido_materno" name="apellido_materno" required>

                <label for="rut">RUT:</label>
                <input type="text" id="rut" name="rut" required>

                <label for="correo">Correo:</label>
                <input type="email" id="correo" name="correo" required>

                <label for="nombre_usuario">Nombre de Usuario:</label>
                <input type="text" id="nombre_usuario" name="nombre_usuario" required>

                <label for="contrasena">Contraseña:</label>
                <input type="password" id="contrasena" name="contrasena" required>

                <label for="rol">Rol:</label>
                <select id="rol" name="rol" required>
                    <option value="usuario">Usuario</option>
                    <option value="administrador">Administrador</option>
                </select>

                <button type="submit" name="registro">Registrar</button>
                <button type="button" class="boton-atras" onclick="location.href='administracion_usuarios.php'">ATRÁS</button>
            </form>
        </main>
    </div>
</body>
</html>
