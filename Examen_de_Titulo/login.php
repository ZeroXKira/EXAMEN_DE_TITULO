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

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_usuario = mysqli_real_escape_string($enlace, trim($_POST['nombre_usuario']));
    $contrasena = mysqli_real_escape_string($enlace, trim($_POST['contrasena']));

    // Validar campos vacíos
    if (empty($nombre_usuario) || empty($contrasena)) {
        echo "<script>alert('Por favor, complete todos los campos.'); window.location.href = 'index.html';</script>";
        exit();
    }

    // Consulta SQL preparada
    $sql = "SELECT * FROM usuarios WHERE nombre_usuario = ?";
    $stmt = mysqli_prepare($enlace, $sql);
    mysqli_stmt_bind_param($stmt, "s", $nombre_usuario);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    // Verificar resultado
    if ($resultado && mysqli_num_rows($resultado) === 1) {
        $usuario = mysqli_fetch_assoc($resultado);

        // Verificar contraseña (debería usarse hash en producción)
        if ($usuario['contrasena'] === $contrasena) {
            // Iniciar sesión
            session_start();
            $_SESSION['nombre_usuario'] = $usuario['nombre_usuario'];
            $_SESSION['rol'] = $usuario['rol'];

            // Redirigir según el rol
            if ($usuario['rol'] === 'administrador') {
                header("Location: http://localhost/Examen_de_Titulo/pagina_administrador.html");
            } elseif ($usuario['rol'] === 'usuario') {
                header("Location: http://localhost/Examen_de_Titulo/pagina_principal.html");
            }
            exit();
        } else {
            echo "<script>alert('Usuario o contraseña incorrectos.'); window.location.href = 'index.html';</script>";
        }
    } else {
        echo "<script>alert('Usuario o contraseña incorrectos.'); window.location.href = 'index.html';</script>";
    }

    // Cerrar la declaración preparada
    mysqli_stmt_close($stmt);
}

// Cerrar conexión
mysqli_close($enlace);
?>
