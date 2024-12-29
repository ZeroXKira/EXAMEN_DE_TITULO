let productos = [];
const tablaProductos = document.getElementById("productosLista");

function agregarProducto() {
    const codigo = document.getElementById("codigo_producto").value;
    const cantidad = parseInt(document.getElementById("cantidad").value);

    if (!codigo || cantidad <= 0) {
        alert("Por favor, ingrese un código válido y una cantidad mayor a 0.");
        return;
    }

    fetch(`verificar_producto.php?codigo=${codigo}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const producto = data.producto;
                producto.cantidad = cantidad;

                productos.push(producto);
                agregarProductoATabla(producto);
                actualizarTotales();
            } else {
                alert(data.message);
            }
        })
        .catch(error => console.error("Error:", error));
}

function agregarProductoATabla(producto) {
    const valorIVA = producto.valor_neto * 0.19;
    const total = (producto.valor_neto + valorIVA) * producto.cantidad;

    const fila = `
        <tr>
            <td>${producto.codigo}</td>
            <td>${producto.nombre_producto}</td>
            <td>$${producto.valor_neto.toFixed(2)}</td>
            <td>$${(producto.valor_neto + valorIVA).toFixed(2)}</td>
            <td>${producto.cantidad}</td>
            <td>$${total.toFixed(2)}</td>
            <td><button class="boton-eliminar" onclick="eliminarFila(this)">X</button></td>
        </tr>
    `;
    tablaProductos.insertAdjacentHTML("beforeend", fila);
}

function eliminarFila(boton) {
    const fila = boton.closest("tr");
    const codigo = fila.children[0].textContent;

    productos = productos.filter(producto => producto.codigo !== codigo);
    fila.remove();
    actualizarTotales();
}

function actualizarTotales() {
    let valorNetoTotal = 0;

    productos.forEach(producto => {
        valorNetoTotal += producto.valor_neto * producto.cantidad;
    });

    const ivaTotal = valorNetoTotal * 0.19;
    const total = valorNetoTotal + ivaTotal;

    document.getElementById("valorNetoTotal").textContent = `$${valorNetoTotal.toFixed(2)}`;
    document.getElementById("ivaTotal").textContent = `$${ivaTotal.toFixed(2)}`;
    document.getElementById("totalPagar").textContent = `$${total.toFixed(2)}`;
}

function pagar() {
    if (productos.length === 0) {
        alert("No hay productos en la lista para pagar.");
        return;
    }

    fetch("guardar_venta_rapida.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ productos }),
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Venta registrada con éxito.");
                productos = [];
                tablaProductos.innerHTML = "";
                actualizarTotales();
            } else {
                alert("Error al registrar la venta.");
            }
        })
        .catch(error => console.error("Error:", error));
}
