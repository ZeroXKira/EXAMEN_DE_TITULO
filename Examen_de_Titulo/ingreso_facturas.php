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
include('conexion.php');

// Manejar el envío del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rutEmpresa = $_POST['rut_empresa'];
    $nombreEmpresa = $_POST['nombre_empresa'];
    $correoEmpresa = $_POST['correo_empresa'];
    $direccionEmpresa = $_POST['direccion_empresa'];
    $telefonoEmpresa = $_POST['telefono_empresa'];
    $ciudadEmpresa = $_POST['ciudad_empresa'];
    $numeroFactura = $_POST['numero_factura'];

    $items = [];
    foreach ($_POST['codigo'] as $index => $codigo) {
        $items[] = [
            'codigo' => $codigo,
            'nombre_producto' => $_POST['nombre_producto'][$index],
            'valor_neto' => $_POST['valor_neto'][$index],
            'cantidad' => $_POST['cantidad'][$index]
        ];
    }

    // Iniciar transacción
    mysqli_begin_transaction($enlace);

    try {
        // Verificar si la factura ya existe
        $sqlVerificarFactura = "SELECT COUNT(*) AS total FROM facturas_boleta WHERE numero_factura = ?";
        $stmtVerificarFactura = mysqli_prepare($enlace, $sqlVerificarFactura);
        mysqli_stmt_bind_param($stmtVerificarFactura, 's', $numeroFactura);
        mysqli_stmt_execute($stmtVerificarFactura);
        $resultadoVerificarFactura = mysqli_stmt_get_result($stmtVerificarFactura);
        $existeFactura = mysqli_fetch_assoc($resultadoVerificarFactura)['total'];
        mysqli_stmt_close($stmtVerificarFactura);

        if ($existeFactura > 0) {
            throw new Exception('Factura o Boleta ya registrada.');
        }

        // Verificar si la empresa ya existe
        $sqlVerificarEmpresa = "SELECT COUNT(*) AS total FROM empresas WHERE rut_empresa = ?";
        $stmtVerificarEmpresa = mysqli_prepare($enlace, $sqlVerificarEmpresa);
        mysqli_stmt_bind_param($stmtVerificarEmpresa, 's', $rutEmpresa);
        mysqli_stmt_execute($stmtVerificarEmpresa);
        $resultadoVerificarEmpresa = mysqli_stmt_get_result($stmtVerificarEmpresa);
        $existeEmpresa = mysqli_fetch_assoc($resultadoVerificarEmpresa)['total'];
        mysqli_stmt_close($stmtVerificarEmpresa);

        // Insertar empresa solo si no existe
        if ($existeEmpresa == 0) {
            $sqlInsertarEmpresa = "INSERT INTO empresas (rut_empresa, nombre_empresa, correo_empresa, direccion_empresa, telefono_empresa, ciudad_empresa, fecha_registro)
                                   VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $stmtInsertarEmpresa = mysqli_prepare($enlace, $sqlInsertarEmpresa);
            mysqli_stmt_bind_param($stmtInsertarEmpresa, 'ssssss', $rutEmpresa, $nombreEmpresa, $correoEmpresa, $direccionEmpresa, $telefonoEmpresa, $ciudadEmpresa);
            mysqli_stmt_execute($stmtInsertarEmpresa);
            mysqli_stmt_close($stmtInsertarEmpresa);
        }

        // Insertar datos de la factura
        $sqlFactura = "INSERT INTO facturas_boleta (numero_factura, rut_empresa, fecha_ingreso)
                       VALUES (?, ?, NOW())";
        $stmtFactura = mysqli_prepare($enlace, $sqlFactura);
        mysqli_stmt_bind_param($stmtFactura, 'ss', $numeroFactura, $rutEmpresa);
        mysqli_stmt_execute($stmtFactura);

        // Insertar ítems en la tabla ingreso_facturas_boletas
        foreach ($items as $item) {
            $codigo = mysqli_real_escape_string($enlace, $item['codigo']);
            $nombreProducto = mysqli_real_escape_string($enlace, $item['nombre_producto']);
            $valorNeto = (int)$item['valor_neto'];
            $cantidad = (int)$item['cantidad'];

            $sqlInsertarBoletaItems = "INSERT INTO ingreso_facturas_boletas (numero_factura, codigo, nombre_producto, valor_neto, cantidad, fecha_ingreso) 
                                       VALUES ('$numeroFactura', '$codigo', '$nombreProducto', $valorNeto, $cantidad, NOW())";
            mysqli_query($enlace, $sqlInsertarBoletaItems);

            // Actualizar o insertar en items_factura
            $sqlVerificarProducto = "SELECT COUNT(*) AS total FROM items_factura WHERE codigo = '$codigo'";
            $resultadoProducto = mysqli_query($enlace, $sqlVerificarProducto);
            $existeProducto = mysqli_fetch_assoc($resultadoProducto)['total'];

            if ($existeProducto > 0) {
                // Actualizar cantidad y reemplazar el valor neto
                $sqlActualizarProducto = "UPDATE items_factura 
                                          SET cantidad = cantidad + $cantidad, 
                                              valor_neto = $valorNeto 
                                          WHERE codigo = '$codigo'";
                mysqli_query($enlace, $sqlActualizarProducto);
            } else {
                // Insertar nuevo producto
                $sqlInsertarProducto = "INSERT INTO items_factura (codigo, nombre_producto, valor_neto, cantidad, numero_factura) 
                                        VALUES ('$codigo', '$nombreProducto', $valorNeto, $cantidad, '$numeroFactura')";
                mysqli_query($enlace, $sqlInsertarProducto);
            }
        }

        // Confirmar transacción
        mysqli_commit($enlace);

        echo "<script>alert('Factura y sus ítems guardados correctamente.');</script>";
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        mysqli_rollback($enlace);
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
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
    <title>Ingreso de Facturas/Boletas</title>
    <link rel="stylesheet" href="css/estilos_facturas.css">
    <script>
        function verificarNumeroFactura() {
            const numeroFactura = document.getElementById('numero_factura').value;

            if (numeroFactura) {
                fetch(`verificar_factura.php?numero_factura=${numeroFactura}`)
                    .then(response => response.json())
                    .then(data => {
                        const mensajeError = document.getElementById('mensaje-error-factura');
                        if (data.existe) {
                            mensajeError.textContent = "Factura o Boleta ya registrada";
                            mensajeError.style.color = "red";
                        } else {
                            mensajeError.textContent = "Número de factura disponible";
                            mensajeError.style.color = "green";
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        }

        function cargarDatosEmpresa() {
            const rut = document.getElementById('rut_empresa').value;

            if (rut) {
                fetch(`buscar_empresa.php?rut=${rut}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('nombre_empresa').value = data.nombre_empresa;
                            document.getElementById('correo_empresa').value = data.correo_empresa;
                            document.getElementById('direccion_empresa').value = data.direccion_empresa;
                            document.getElementById('telefono_empresa').value = data.telefono_empresa;
                            document.getElementById('ciudad_empresa').value = data.ciudad_empresa;
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        }

        function agregarFila() {
            const tabla = document.getElementById('tabla-facturas-body');
            const fila = tabla.insertRow();
            fila.innerHTML = `
                <td><input type="text" name="codigo[]" required></td>
                <td><input type="text" name="nombre_producto[]" required></td>
                <td><input type="number" name="valor_neto[]" required></td>
                <td><input type="number" name="cantidad[]" required></td>
            `;
        }
    </script>
</head>
<body>
    <div class="container">
        <header>
            <h1>Ingreso de Facturas / Boletas</h1>
            <button class="cerrar-sesion" onclick="location.href='cerrar_sesion.php'">Cerrar Sesión</button>
        </header>
        <main>
            <form method="POST" action="ingreso_facturas.php">
                <div class="form-container">
                    <label for="numero_factura">N° de Factura/Boleta:</label>
                    <input type="text" id="numero_factura" name="numero_factura" onblur="verificarNumeroFactura()" required>
                    <div id="mensaje-error-factura" style="font-weight: bold; margin-bottom: 10px;"></div>

                    <h2>Datos de la Empresa</h2>
                    <label for="rut_empresa">Rut Empresa:</label>
                    <input type="text" id="rut_empresa" name="rut_empresa" onblur="cargarDatosEmpresa()" required>

                    <label for="nombre_empresa">Nombre Empresa:</label>
                    <input type="text" id="nombre_empresa" name="nombre_empresa" required>

                    <label for="correo_empresa">Correo Empresa:</label>
                    <input type="email" id="correo_empresa" name="correo_empresa" required>

                    <label for="direccion_empresa">Dirección Empresa:</label>
                    <input type="text" id="direccion_empresa" name="direccion_empresa" required>

                    <label for="telefono_empresa">Teléfono Empresa:</label>
                    <input type="tel" id="telefono_empresa" name="telefono_empresa" required>

                    <label for="ciudad_empresa">Ciudad Empresa:</label>
                    <input type="text" id="ciudad_empresa" name="ciudad_empresa" required>
                </div>

                <div class="tabla-facturas">
                    <h2>Ítems de la Factura</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre Producto</th>
                                <th>Valor Neto</th>
                                <th>Cantidad</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-facturas-body">
                            <tr>
                                <td><input type="text" name="codigo[]" required></td>
                                <td><input type="text" name="nombre_producto[]" required></td>
                                <td><input type="number" name="valor_neto[]" required></td>
                                <td><input type="number" name="cantidad[]" required></td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" onclick="agregarFila()">Agregar Ítem</button>
                </div>
                <button type="submit">Grabar Ingreso</button>
                <button type="button" onclick="location.href='pagina_principal.html'">Atrás</button>
            </form>
        </main>
    </div>
</body>
</html>
