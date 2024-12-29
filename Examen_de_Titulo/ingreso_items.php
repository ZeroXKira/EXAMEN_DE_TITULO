<?php
$servidor = "localhost"; // Cambia si tu servidor es distinto
$usuario = "root"; // Cambia por tu usuario de base de datos
$clave = ""; // Cambia por la contraseña del usuario
$baseDeDatos = "base_de_datos"; // Cambia por el nombre de tu base de datos

$enlace = mysqli_connect($servidor, $usuario, $clave, $baseDeDatos);

if (!$enlace) {
    die("Error en la conexión a la base de datos: " . mysqli_connect_error());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso Manual de Ítems</title>
    <link rel="stylesheet" href="css/estilos_items.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Ingreso Manual de Ítems</h1>
            <button class="cerrar-sesion" onclick="location.href='cerrar_sesion.php'">Cerrar Sesión</button>
        </header>
        <main>
            <div class="form-container">
                <form id="form-items">
                    <div class="form-row">
                        <label for="codigo">Código:</label>
                        <input type="text" id="codigo" name="codigo" onblur="buscarProducto()" required>
                    </div>
                    <div class="form-row">
                        <label for="nombre_producto">Nombre Producto:</label>
                        <input type="text" id="nombre_producto" name="nombre_producto" readonly required>
                    </div>
                    <div class="form-row">
                        <label for="valor_neto">Valor Neto (CLP):</label>
                        <input type="number" id="valor_neto" name="valor_neto" readonly required>
                    </div>
                    <div class="form-row">
                        <label for="cantidad">Cantidad:</label>
                        <input type="number" id="cantidad" name="cantidad" readonly required>
                    </div>
                </form>
                <button class="boton-atras" onclick="location.href='pagina_principal.html'">ATRÁS</button>
            </div>

            <div class="aleatorio-container">
                <label>Número Aleatorio:</label>
                <div class="aleatorio" id="numeroAleatorio"></div>
            </div>
        </main>
        <button class="boton-grabar">Grabar Datos</button>
    </div>

    <script>
        // Generar un número aleatorio al cargar la página
        document.addEventListener("DOMContentLoaded", function() {
            document.getElementById("numeroAleatorio").textContent = Math.floor(Math.random() * 1000000);
        });

        // Buscar producto al ingresar el código
        function buscarProducto() {
            const codigo = document.getElementById('codigo').value;

            if (!codigo) {
                alert('Por favor, ingrese un código.');
                return;
            }

            fetch('buscar_producto.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ codigo_producto: codigo })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('nombre_producto').value = data.producto.nombre_producto;
                    document.getElementById('valor_neto').value = data.producto.valor_neto;
                    document.getElementById('nombre_producto').readOnly = true;
                    document.getElementById('valor_neto').readOnly = false;
                    document.getElementById('cantidad').readOnly = false;
                } else {
                    alert(data.message);
                    document.getElementById('nombre_producto').value = '';
                    document.getElementById('valor_neto').value = '';
                    document.getElementById('cantidad').value = '';
                    document.getElementById('nombre_producto').readOnly = false;
                    document.getElementById('valor_neto').readOnly = false;
                    document.getElementById('cantidad').readOnly = false;
                }
            })
            .catch(error => console.error('Error en la solicitud:', error));
        }

        // Enviar datos al servidor
        document.querySelector('.boton-grabar').addEventListener('click', () => {
            const datos = {
                codigo: document.getElementById('codigo').value,
                nombre_producto: document.getElementById('nombre_producto').value,
                valor_neto: document.getElementById('valor_neto').value,
                cantidad: document.getElementById('cantidad').value,
                numero_aleatorio: document.getElementById('numeroAleatorio').textContent
            };

            // Validar que todos los campos estén completos
            if (!datos.codigo || !datos.nombre_producto || !datos.valor_neto || !datos.cantidad || !datos.numero_aleatorio) {
                alert("Por favor, complete todos los campos.");
                return;
            }

            // Enviar datos al backend
            fetch('guardar_items_manual.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(datos)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    document.getElementById('form-items').reset();
                    document.getElementById('numeroAleatorio').textContent = Math.floor(Math.random() * 1000000);
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error("Error en la solicitud:", error);
                alert("Hubo un problema al enviar los datos.");
            });
        });
    </script>
</body>
</html>
