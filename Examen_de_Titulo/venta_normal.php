<?php
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

session_start();
if (!isset($_SESSION['id_usuario'])) {
    $_SESSION['id_usuario'] = 1; // Valor manual para pruebas
}

// Manejar las acciones del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'];

    // Buscar cliente por RUT
    if ($accion === 'buscarCliente') {
        $rutCliente = $_POST['rut_cliente'];
        $consultaCliente = "SELECT rut_cliente, nombre_cliente, correo_cliente, telefono_cliente FROM clientes WHERE rut_cliente = ?";
        $stmt = mysqli_prepare($enlace, $consultaCliente);
        mysqli_stmt_bind_param($stmt, 's', $rutCliente);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($resultado) > 0) {
            $cliente = mysqli_fetch_assoc($resultado);
            echo json_encode(['success' => true, 'cliente' => $cliente]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Cliente no encontrado. Ingrese los datos manualmente.']);
        }
        exit();
    }

    // Guardar cliente ingresado manualmente
    if ($accion === 'guardarCliente') {
        $rut = $_POST['rut_cliente'];
        $nombre = $_POST['nombre_cliente'];
        $correo = $_POST['correo_cliente'];
        $telefono = $_POST['telefono_cliente'];

        $verificarCliente = "SELECT COUNT(*) AS total FROM clientes WHERE rut_cliente = ?";
        $stmtVerificar = mysqli_prepare($enlace, $verificarCliente);
        mysqli_stmt_bind_param($stmtVerificar, 's', $rut);
        mysqli_stmt_execute($stmtVerificar);
        $resultado = mysqli_stmt_get_result($stmtVerificar);
        $existe = mysqli_fetch_assoc($resultado)['total'];

        if ($existe == 0) {
            $insertarCliente = "INSERT INTO clientes (rut_cliente, nombre_cliente, correo_cliente, telefono_cliente) VALUES (?, ?, ?, ?)";
            $stmtInsertar = mysqli_prepare($enlace, $insertarCliente);
            mysqli_stmt_bind_param($stmtInsertar, 'ssss', $rut, $nombre, $correo, $telefono);
            mysqli_stmt_execute($stmtInsertar);
            echo json_encode(['success' => true, 'message' => 'Cliente creado con éxito.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'El cliente ya existe.']);
        }
        exit();
    }

    // Agregar producto al carrito
    if ($accion === 'agregarProducto') {
        $codigoProducto = $_POST['codigo_producto'];
        $cantidad = (int)$_POST['cantidad'];

        $consultaProducto = "SELECT codigo, nombre_producto, valor_neto, cantidad AS stock FROM items_factura WHERE codigo = ?";
        $stmt = mysqli_prepare($enlace, $consultaProducto);
        mysqli_stmt_bind_param($stmt, 's', $codigoProducto);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($resultado) > 0) {
            $producto = mysqli_fetch_assoc($resultado);

            if ($cantidad <= $producto['stock']) {
                echo json_encode(['success' => true, 'producto' => $producto, 'cantidad' => $cantidad]);
            } else {
                echo json_encode(['success' => false, 'message' => "Stock insuficiente. Solo quedan {$producto['stock']} unidades."]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Código no encontrado.']);
        }
        exit();
    }

    // Mover producto al fiado
    if ($accion === 'agregarFiado') {
        $producto = json_decode($_POST['producto'], true);
        $cliente = json_decode($_POST['cliente'], true);
    
        try {
            // Verificar si el cliente existe y obtener su id_cliente
            $consultaCliente = "SELECT id_cliente FROM clientes WHERE rut_cliente = ?";
            $stmtCliente = mysqli_prepare($enlace, $consultaCliente);
            mysqli_stmt_bind_param($stmtCliente, 's', $cliente['rut_cliente']);
            mysqli_stmt_execute($stmtCliente);
            $resultadoCliente = mysqli_stmt_get_result($stmtCliente);
    
            if (mysqli_num_rows($resultadoCliente) > 0) {
                $clienteData = mysqli_fetch_assoc($resultadoCliente);
                $idCliente = $clienteData['id_cliente'];
    
                $codigo = $producto['codigo'];
                $cantidadVendida = $producto['cantidad'];
    
                // Validar si hay suficiente stock antes de reducirlo
                $consultaStock = "SELECT cantidad FROM items_factura WHERE codigo = ?";
                $stmtStock = mysqli_prepare($enlace, $consultaStock);
                mysqli_stmt_bind_param($stmtStock, 's', $codigo);
                mysqli_stmt_execute($stmtStock);
                $resultadoStock = mysqli_stmt_get_result($stmtStock);
    
                if ($resultadoStock && $stockData = mysqli_fetch_assoc($resultadoStock)) {
                    if ($stockData['cantidad'] < $cantidadVendida) {
                        throw new Exception("Stock insuficiente para el producto con código $codigo.");
                    }
                } else {
                    throw new Exception("El producto con código $codigo no existe.");
                }
    
                // Reducir el stock
                $actualizarStock = "UPDATE items_factura SET cantidad = cantidad - ? WHERE codigo = ?";
                $stmtActualizarStock = mysqli_prepare($enlace, $actualizarStock);
                mysqli_stmt_bind_param($stmtActualizarStock, 'is', $cantidadVendida, $codigo);
                mysqli_stmt_execute($stmtActualizarStock);
    
                // Registrar en la tabla de fiados
                $registrarFiado = "INSERT INTO fiados (id_cliente, id_usuario, codigo_producto, nombre_producto, cantidad, valor_neto, valor_total, fecha) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmtFiado = mysqli_prepare($enlace, $registrarFiado);
                $valorTotal = $producto['valor_neto'] * $cantidadVendida * 1.19;
                mysqli_stmt_bind_param($stmtFiado, 'iissidd',
                    $idCliente,
                    $_SESSION['id_usuario'],
                    $codigo,
                    $producto['nombre_producto'],
                    $cantidadVendida,
                    $producto['valor_neto'],
                    $valorTotal
                );
                mysqli_stmt_execute($stmtFiado);
    
                echo json_encode(['success' => true, 'message' => 'Producto agregado a fiados con éxito.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'El cliente no está registrado en el sistema.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // Realizar el pago
    if ($accion === 'pagar') {
        try {
            $productos = json_decode($_POST['productos'], true);
            $cliente = json_decode($_POST['cliente'], true);

            foreach ($productos as $producto) {
                $codigo = $producto['codigo'];
                $cantidadVendida = $producto['cantidad'];

                // Validar si hay suficiente stock antes de reducirlo
                $verificarStock = "SELECT cantidad FROM items_factura WHERE codigo = ?";
                $stmtVerificar = mysqli_prepare($enlace, $verificarStock);
                mysqli_stmt_bind_param($stmtVerificar, 's', $codigo);
                mysqli_stmt_execute($stmtVerificar);
                $resultado = mysqli_stmt_get_result($stmtVerificar);

                if ($resultado && $stockData = mysqli_fetch_assoc($resultado)) {
                    if ($stockData['cantidad'] < $cantidadVendida) {
                        throw new Exception("Stock insuficiente para el producto con código $codigo. Solo quedan {$stockData['cantidad']} unidades.");
                    }
                } else {
                    throw new Exception("No se encontró el producto con código $codigo.");
                }

                // Reducir el stock
                $actualizarStock = "UPDATE items_factura SET cantidad = cantidad - ? WHERE codigo = ?";
                $stmtStock = mysqli_prepare($enlace, $actualizarStock);
                mysqli_stmt_bind_param($stmtStock, 'is', $cantidadVendida, $codigo);
                mysqli_stmt_execute($stmtStock);

                // Registrar la venta
                $registrarVenta = "INSERT INTO ventas (id_usuario, rut_cliente, codigo_producto, cantidad, valor_neto, fecha) 
                                   VALUES (?, ?, ?, ?, ?, NOW())";
                $stmtVenta = mysqli_prepare($enlace, $registrarVenta);
                mysqli_stmt_bind_param($stmtVenta, 'issid',
                    $_SESSION['id_usuario'],
                    $cliente['rut_cliente'],
                    $codigo,
                    $cantidadVendida,
                    $producto['valor_neto']
                );
                mysqli_stmt_execute($stmtVenta);
            }

            echo json_encode(['success' => true, 'message' => 'Pago realizado con éxito.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
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
    <title>Módulo de Venta Normal</title>
    <link rel="stylesheet" href="css/estilos_venta_normal.css">
    <script>
        let productos = [];
        let cliente = {};

        function buscarCliente() {
            const rut = document.getElementById('rut_cliente').value;

            fetch('venta_normal.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ accion: 'buscarCliente', rut_cliente: rut })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('nombre_cliente').value = data.cliente.nombre_cliente;
                    document.getElementById('correo_cliente').value = data.cliente.correo_cliente;
                    document.getElementById('telefono_cliente').value = data.cliente.telefono_cliente;

                    cliente = data.cliente;
                } else {
                    alert(data.message);
                    document.getElementById('btnAgregarCliente').disabled = false;
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function agregarFiado(index) {
    const producto = productos[index];

    if (!cliente.rut_cliente) {
        alert("Debe seleccionar un cliente antes de agregar al fiado.");
        return;
    }

    fetch('venta_normal.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            accion: 'agregarFiado',
            producto: JSON.stringify(producto),
            cliente: JSON.stringify(cliente)
        })
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            productos.splice(index, 1);
            actualizarTabla();
        }
    })
    .catch(error => console.error('Error:', error));
}


        function agregarCliente() {
            const rut = document.getElementById('rut_cliente').value;
            const nombre = document.getElementById('nombre_cliente').value;
            const correo = document.getElementById('correo_cliente').value;
            const telefono = document.getElementById('telefono_cliente').value;

            if (!rut || !nombre || !correo || !telefono) {
                alert('Por favor, complete todos los campos para agregar al cliente.');
                return;
            }

            fetch('venta_normal.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    accion: 'guardarCliente',
                    rut_cliente: rut,
                    nombre_cliente: nombre,
                    correo_cliente: correo,
                    telefono_cliente: telefono
                })
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    document.getElementById('btnAgregarCliente').disabled = true;
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function agregarProducto() {
            const codigoProducto = document.getElementById('codigo_producto').value;
            const cantidad = document.getElementById('cantidad').value;

            fetch('venta_normal.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ accion: 'agregarProducto', codigo_producto: codigoProducto, cantidad })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const producto = { ...data.producto, cantidad: data.cantidad };
                    productos.push(producto);
                    actualizarTabla();
                } else {
                    alert(data.message);
                }
            });
        }

        function actualizarTabla() {
            const tablaBody = document.getElementById('productosLista');
            tablaBody.innerHTML = '';

            productos.forEach((producto, index) => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${producto.codigo}</td>
                    <td>${producto.nombre_producto}</td>
                    <td>${producto.cantidad}</td>
                    <td>$${(producto.valor_neto * 1.19).toFixed(2)}</td>
                    <td>
                        <button onclick="eliminarProducto(${index})">Eliminar</button>
                        <button onclick="agregarFiado(${index})">+ Fiado</button>
                    </td>
                `;
                tablaBody.appendChild(row);
            });
        }

        function eliminarProducto(index) {
            productos.splice(index, 1);
            actualizarTabla();
        }

        function pagar() {
            if (productos.length === 0) {
                alert("No hay productos en el carrito para pagar.");
                return;
            }

            fetch('venta_normal.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    accion: 'pagar',
                    productos: JSON.stringify(productos),
                    cliente: JSON.stringify(cliente)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    productos = [];
                    actualizarTabla();
                } else {
                    alert(data.message || "Hubo un problema al realizar el pago.");
                }
            });
        }
    </script>
</head>
<body>
    <div class="container">
        <header>
            <h1>Módulo de Venta Normal</h1>
        </header>
        <main>
            <div>
                <label for="rut_cliente">RUT Cliente:</label>
                <input type="text" id="rut_cliente" onblur="buscarCliente()">
                <label for="nombre_cliente">Nombre Cliente:</label>
                <input type="text" id="nombre_cliente">
                <label for="correo_cliente">Correo:</label>
                <input type="text" id="correo_cliente">
                <label for="telefono_cliente">Teléfono:</label>
                <input type="text" id="telefono_cliente">
                <button id="btnAgregarCliente" onclick="agregarCliente()" disabled>Agregar Cliente</button>
            </div>
            <div>
                <label for="codigo_producto">Código Producto:</label>
                <input type="text" id="codigo_producto">
                <label for="cantidad">Cantidad:</label>
                <input type="number" id="cantidad" min="1" value="1">
                <button onclick="agregarProducto()">Agregar Producto</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Total + IVA</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="productosLista"></tbody>
            </table>
            <div class="buttons">
                <button onclick="pagar()">Pagar</button>
                <button onclick="location.href='ventas_realizadas.php'">Visualizar Ventas</button>
                <button onclick="location.href='visualizacion_fiados.php'">Visualizar Fiado</button>
                <button onclick="location.href='pagina_principal.html'">Atrás</button>
            </div>
        </main>
    </div>
</body>
</html>
