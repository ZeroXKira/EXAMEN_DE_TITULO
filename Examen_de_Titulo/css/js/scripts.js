document.getElementById('formLogin').addEventListener('submit', function(e) {
    e.preventDefault(); // Evita el envío del formulario

    const usuario = document.getElementById('usuario').value.trim();
    const contrasena = document.getElementById('contrasena').value.trim();
    const mensajeError = document.getElementById('mensaje-error');

    // Validación simple
    if (usuario === '' || contrasena === '') {
        mensajeError.textContent = 'Todos los campos son obligatorios.';
        return;
    }

    // Si todo está bien, se puede proceder con el envío al backend (cuando agreguemos PHP)
    mensajeError.textContent = ''; // Limpiamos errores
    alert('Formulario enviado correctamente. (Aquí se procesará con PHP)');
});
