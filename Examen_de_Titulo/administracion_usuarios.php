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

// Consultar los datos de la tabla usuarios
$sql = "SELECT id_usuario, nombres, apellido_paterno, apellido_materno, rut, correo, nombre_usuario, rol, fecha_creacion FROM usuarios";
$resultado = mysqli_query($enlace, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de Usuarios</title>
    <link rel="stylesheet" href="css/estilos_administracion_usuarios.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Administración de Usuarios</h1>
            <button class="cerrar-sesion" onclick="location.href='cerrar_sesion.php'">Cerrar Sesión</button>
        </header>
        <main>
            <table>
                <thead>
                    <tr>
                        <th>Nombres</th>
                        <th>Apellido Paterno</th>
                        <th>Apellido Materno</th>
                        <th>RUT</th>
                        <th>Correo</th>
                        <th>Nombre Usuario</th>
                        <th>Rol</th>
                        <th>Fecha Creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Mostrar los datos en la tabla
                    if (mysqli_num_rows($resultado) > 0) {
                        while ($fila = mysqli_fetch_assoc($resultado)) {
                            // Formatear fecha para mejor legibilidad
                            $fecha_creacion = date("d-m-Y H:i", strtotime($fila['fecha_creacion']));
                            echo "<tr>
                                <td>{$fila['nombres']}</td>
                                <td>{$fila['apellido_paterno']}</td>
                                <td>{$fila['apellido_materno']}</td>
                                <td>{$fila['rut']}</td>
                                <td>{$fila['correo']}</td>
                                <td>{$fila['nombre_usuario']}</td>
                                <td>{$fila['rol']}</td>
                                <td>{$fecha_creacion}</td>
                                <td>
                                    <a href='editar_usuario.php?id_usuario={$fila['id_usuario']}' class='btn editar'>Editar</a>
                                    <a href='eliminar_usuario.php?id_usuario={$fila['id_usuario']}' class='btn eliminar' onclick=\"return confirm('¿Está seguro de que desea eliminar este usuario?')\">Eliminar</a>
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='9'>No hay usuarios registrados.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
            <button class="boton-agregar" onclick="location.href='registro_usuario.php'">Agregar Usuario</button>
            <button class="boton-atras" onclick="location.href='pagina_administrador.html'">ATRÁS</button>
        </main>
    </div>
</body>
</html>

<?php
// Cerrar la conexión
mysqli_close($enlace);
?>
