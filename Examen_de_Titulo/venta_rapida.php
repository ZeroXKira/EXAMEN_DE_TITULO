<?php
session_start();

// Conexión a la base de datos
$servidor = "localhost";
$usuario = "root";
$clave = "";
$baseDeDatos = "base_de_datos";

$enlace = mysqli_connect($servidor, $usuario, $clave, $baseDeDatos);

// Verificar conexión
if (!$enlace) {
    die("Error en la conexión a la base de datos: " . mysqli_connect_error());
}

// Verificar si el usuario está autenticado
if (!isset($_SESSION['id_usuario'])) {
    $_SESSION['id_usuario'] = 1; // Simulación de ID de usuario para pruebas
}

// Lógica del backend
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'];

    if ($accion === 'agregarProducto') {
        $codigoProducto = $_POST['codigo_producto'];
        $cantidad = (int)$_POST['cantidad'];

        // Verificar si el producto existe
        $consultaProducto = "SELECT codigo, nombre_producto, valor_neto, cantidad AS stock FROM items_factura WHERE codigo = ?";
        $stmt = mysqli_prepare($enlace, $consultaProducto);
        mysqli_stmt_bind_param($stmt, 's', $codigoProducto);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($resultado) > 0) {
            $producto = mysqli_fetch_assoc($resultado);

            // Verificar stock
            if ($cantidad <= $producto['stock']) {
                echo json_encode([
                    'success' => true,
                    'producto' => $producto,
                    'cantidad' => $cantidad
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => "Stock insuficiente. Solo quedan {$producto['stock']} unidades disponibles."
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Código no encontrado.'
            ]);
        }
        exit();
    } elseif ($accion === 'pagar') {
        $productos = json_decode($_POST['productos'], true); // Lista de productos en formato JSON
        $idUsuario = $_SESSION['id_usuario']; // ID del usuario en sesión

        // Iniciar una transacción
        mysqli_begin_transaction($enlace);

        try {
            foreach ($productos as $producto) {
                $codigo = $producto['codigo'];
                $cantidadVendida = $producto['cantidad'];
                $valorNeto = $producto['valor_neto'];

                // Actualizar stock
                $consultaActualizarStock = "UPDATE items_factura SET cantidad = cantidad - ? WHERE codigo = ?";
                $stmtStock = mysqli_prepare($enlace, $consultaActualizarStock);
                mysqli_stmt_bind_param($stmtStock, 'is', $cantidadVendida, $codigo);
                mysqli_stmt_execute($stmtStock);

                // Registrar venta
                $consultaRegistrarVenta = "INSERT INTO ventas (id_usuario, codigo_producto, cantidad, valor_neto, fecha) VALUES (?, ?, ?, ?, NOW())";
                $stmtVenta = mysqli_prepare($enlace, $consultaRegistrarVenta);
                mysqli_stmt_bind_param($stmtVenta, 'isis', $idUsuario, $codigo, $cantidadVendida, $valorNeto);
                mysqli_stmt_execute($stmtVenta);
            }

            // Confirmar la transacción
            mysqli_commit($enlace);

            echo json_encode(['success' => true, 'message' => 'Pago realizado con éxito.']);
        } catch (Exception $e) {
            // Revertir cambios en caso de error
            mysqli_rollback($enlace);
            echo json_encode(['success' => false, 'message' => 'Error al procesar el pago: ' . $e->getMessage()]);
        }
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Venta Rápida</title>
    <link rel="stylesheet" href="css/estilos_venta_rapida.css">
    <script>
        let productos = [];

        function agregarProducto() {
            const codigoProducto = document.getElementById('codigo_producto').value;
            const cantidad = document.getElementById('cantidad').value;

            if (!codigoProducto || !cantidad) {
                alert('Por favor, ingrese el código y la cantidad.');
                return;
            }

            fetch('venta_rapida.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    accion: 'agregarProducto',
                    codigo_producto: codigoProducto,
                    cantidad: cantidad
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const producto = data.producto;
                    producto.cantidad = cantidad;

                    productos.push(producto);

                    actualizarTabla();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function actualizarTabla() {
            const tablaBody = document.getElementById('productosLista');
            tablaBody.innerHTML = '';

            let valorNetoTotal = 0;
            let ivaTotal = 0;

            productos.forEach((producto, index) => {
                const total = producto.valor_neto * producto.cantidad;
                valorNetoTotal += total;
                ivaTotal += total * 0.19;

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${producto.codigo}</td>
                    <td>${producto.nombre_producto}</td>
                    <td>${producto.valor_neto}</td>
                    <td>${(producto.valor_neto * 1.19).toFixed(2)}</td>
                    <td>${producto.cantidad}</td>
                    <td>${(total * 1.19).toFixed(2)}</td>
                    <td><button onclick="eliminarProducto(${index})">X</button></td>
                `;
                tablaBody.appendChild(row);
            });

            document.getElementById('valorNetoTotal').textContent = `$${valorNetoTotal.toFixed(2)}`;
            document.getElementById('ivaTotal').textContent = `$${ivaTotal.toFixed(2)}`;
            document.getElementById('totalPagar').textContent = `$${(valorNetoTotal + ivaTotal).toFixed(2)}`;
        }

        function eliminarProducto(index) {
            productos.splice(index, 1);
            actualizarTabla();
        }

        function pagar() {
            fetch('venta_rapida.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    accion: 'pagar',
                    productos: JSON.stringify(productos)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    productos = [];
                    actualizarTabla();
                } else {
                    alert('Error al procesar el pago: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
</head>
<body>
    <div class="container">
        <header>
            <h1>Venta Rápida</h1>
            <button class="cerrar-sesion" onclick="location.href='cerrar_sesion.php'">Cerrar Sesión</button>
        </header>
        <main>
            <div class="form-container">
                <label for="codigo_producto">Código del Producto:</label>
                <input type="text" id="codigo_producto" name="codigo_producto">

                <label for="cantidad">Cantidad:</label>
                <input type="number" id="cantidad" name="cantidad" value="1">

                <button type="button" onclick="agregarProducto()">Agregar Producto</button>
            </div>

            <div class="tabla-container">
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre Producto</th>
                            <th>Valor Neto</th>
                            <th>Valor + IVA</th>
                            <th>Cantidad</th>
                            <th>Total</th>
                            <th>Eliminar</th>
                        </tr>
                    </thead>
                    <tbody id="productosLista"></tbody>
                </table>
                <div class="totales">
                    <p>Valor Neto Total: <span id="valorNetoTotal">$0</span></p>
                    <p>IVA Total (19%): <span id="ivaTotal">$0</span></p>
                    <p>Total a Pagar: <span id="totalPagar">$0</span></p>
                </div>
                <button onclick="pagar()">PAGAR</button>
                <button onclick="location.href='modulo_ventas.html'">Atrás</button>
            </div>
        </main>
    </div>
</body>
</html>
