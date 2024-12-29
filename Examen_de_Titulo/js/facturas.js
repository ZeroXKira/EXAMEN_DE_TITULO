const items = [];

function agregarItem() {
    const codigo = document.getElementById('codigo').value;
    const nombreProducto = document.getElementById('nombre_producto').value;
    const valorNeto = document.getElementById('valor_neto').value;
    const cantidad = document.getElementById('cantidad').value;

    if (!codigo || !nombreProducto || !valorNeto || !cantidad) {
        alert("Por favor, complete todos los campos del ítem.");
        return;
    }

    items.push({ codigo, nombreProducto, valorNeto, cantidad });

    const tabla = document.getElementById('tabla-facturas-body');
    const fila = tabla.insertRow();
    fila.innerHTML = `
        <td>${codigo}</td>
        <td>${nombreProducto}</td>
        <td>${valorNeto}</td>
        <td>${cantidad}</td>
    `;

    // Limpiar campos
    document.getElementById('codigo').value = '';
    document.getElementById('nombre_producto').value = '';
    document.getElementById('valor_neto').value = '';
    document.getElementById('cantidad').value = '';
}

document.querySelector('.boton-grabar').addEventListener('click', () => {
    const rutEmpresa = document.getElementById('rut_empresa').value;
    const nombreEmpresa = document.getElementById('nombre_empresa').value;
    const correoEmpresa = document.getElementById('correo_empresa').value;
    const direccionEmpresa = document.getElementById('direccion_empresa').value;
    const telefonoEmpresa = document.getElementById('telefono_empresa').value;
    const ciudadEmpresa = document.getElementById('ciudad_empresa').value;

    if (!rutEmpresa || !nombreEmpresa || !correoEmpresa || !direccionEmpresa || !telefonoEmpresa || !ciudadEmpresa) {
        alert("Por favor, complete todos los datos de la empresa.");
        return;
    }

    if (items.length === 0) {
        alert("Debe agregar al menos un ítem a la factura.");
        return;
    }

    const datos = {
        rutEmpresa,
        nombreEmpresa,
        correoEmpresa,
        direccionEmpresa,
        telefonoEmpresa,
        ciudadEmpresa,
        items,
    };

    fetch('guardar_factura.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(datos),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Factura guardada correctamente');
            // Limpiar formulario y tabla
            document.querySelector('form').reset();
            document.getElementById('tabla-facturas-body').innerHTML = '';
            items.length = 0; // Vaciar array
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("Hubo un problema al intentar guardar la factura. Por favor, inténtelo de nuevo.");
    });
});
