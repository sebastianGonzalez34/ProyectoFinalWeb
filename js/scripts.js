// Funciones JavaScript para el sistema HelpDesk

// Confirmación antes de eliminar
function confirmarEliminacion(mensaje = "¿Está seguro de que desea eliminar este registro?") {
    return confirm(mensaje);
}

// Validación de formularios
function validarFormulario(form) {
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let valido = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = '#dc3545';
            valido = false;
        } else {
            input.style.borderColor = '#ddd';
        }
    });
    
    return valido;
}

// Mostrar/ocultar elementos
function toggleElement(id) {
    const element = document.getElementById(id);
    if (element) {
        element.style.display = element.style.display === 'none' ? 'block' : 'none';
    }
}

// Formatear fecha
function formatearFecha(fecha) {
    const opciones = { year: 'numeric', month: '2-digit', day: '2-digit' };
    return new Date(fecha).toLocaleDateString('es-ES', opciones);
}

// Cargar datos via AJAX
function cargarDatos(url, callback) {
    fetch(url)
        .then(response => response.json())
        .then(data => callback(data))
        .catch(error => console.error('Error:', error));
}

// Mensajes temporales
function mostrarMensaje(mensaje, tipo = 'success', duracion = 5000) {
    const div = document.createElement('div');
    div.className = `alert alert-${tipo} temporary-message`;
    div.textContent = mensaje;
    div.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 1000;';
    
    document.body.appendChild(div);
    
    setTimeout(() => {
        div.remove();
    }, duracion);
}

// Inicializar tooltips
function inicializarTooltips() {
    const tooltips = document.querySelectorAll('[data-toggle="tooltip"]');
    tooltips.forEach(tooltip => {
        tooltip.addEventListener('mouseenter', function() {
            // Implementar lógica de tooltip si es necesario
        });
    });
}

// Document ready
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    inicializarTooltips();
    
    // Validación de contraseñas coincidentes
    const formCambioPassword = document.querySelector('form[action*="cambiar_password"]');
    if (formCambioPassword) {
        formCambioPassword.addEventListener('submit', function(e) {
            const password = document.getElementById('nueva_password');
            const confirmar = document.getElementById('confirmar_password');
            
            if (password && confirmar && password.value !== confirmar.value) {
                e.preventDefault();
                mostrarMensaje('Las contraseñas no coinciden', 'error');
                confirmar.focus();
            }
        });
    }
    
    // Auto-ocultar mensajes de alerta después de 5 segundos
    const alertas = document.querySelectorAll('.alert');
    alertas.forEach(alerta => {
        if (!alerta.classList.contains('temporary-message')) {
            setTimeout(() => {
                alerta.style.opacity = '0';
                setTimeout(() => alerta.remove(), 300);
            }, 5000);
        }
    });
});