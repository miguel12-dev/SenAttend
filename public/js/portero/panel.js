/**
 * JavaScript para el panel del portero
 */

class PorteroPanel {
    constructor() {
        this.init();
    }

    init() {
        // Auto-refresh cada 30 segundos para actualizar lista de ingresos activos
        setInterval(() => {
            this.actualizarIngresos();
        }, 30000);

        // Configurar botón de actualizar para usar AJAX
        const btnActualizar = document.querySelector('.portero-ingresos-header button');
        if (btnActualizar) {
            btnActualizar.addEventListener('click', (e) => {
                e.preventDefault();
                this.actualizarIngresos();
                this.mostrarMensajeTemporal('Tabla actualizada', 'success');
            });
        }
    }

    async actualizarIngresos() {
        try {
            const response = await fetch('/api/portero/ingresos-activos?limit=50&page=1');
            const result = await response.json();

            if (result.success) {
                this.actualizarTablaIngresos(result.data, result.pagination?.total || 0);
                console.log('Ingresos actualizados:', result.data.length);
            }
        } catch (error) {
            console.error('Error al actualizar ingresos:', error);
        }
    }

    actualizarTablaIngresos(ingresos, total) {
        const tbody = document.getElementById('ingresosTableBody');
        const totalElement = document.getElementById('totalEquipos');

        if (!tbody) return;

        // Actualizar contador total
        if (totalElement) {
            totalElement.textContent = total;
        }

        if (ingresos.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="empty-state">No hay equipos dentro del CTA en este momento.</td>
                </tr>
            `;
            return;
        }

        // Actualizar filas de la tabla
        const filas = ingresos.map(ingreso => `
            <tr>
                <td data-label="Hora ingreso">
                    <strong>${this.escapeHtml(ingreso.fecha_ingreso || '')}</strong><br>
                    <small>${this.escapeHtml(ingreso.hora_ingreso || '')}</small>
                </td>
                <td data-label="Equipo">${this.escapeHtml(ingreso.marca || '')}</td>
                <td data-label="Serial"><code>${this.escapeHtml(ingreso.numero_serial || '')}</code></td>
                <td data-label="Aprendiz">
                    ${this.escapeHtml((ingreso.aprendiz_nombre || '') + ' ' + (ingreso.aprendiz_apellido || ''))}
                </td>
                <td data-label="Documento">${this.escapeHtml(ingreso.aprendiz_documento || '')}</td>
                <td data-label="Portero">${this.escapeHtml(ingreso.portero_nombre || '')}</td>
                <td data-label="Observaciones">
                    ${ingreso.observaciones
                        ? this.escapeHtml(ingreso.observaciones)
                        : '<span style="color:#999;">Sin observaciones</span>'}
                </td>
            </tr>
        `).join('');

        tbody.innerHTML = filas;
    }


    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    mostrarMensajeTemporal(mensaje, tipo) {
        // Crear elemento de mensaje temporal
        const mensajeDiv = document.createElement('div');
        mensajeDiv.className = `alert alert-${tipo}`;
        mensajeDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 1000; max-width: 300px;';
        mensajeDiv.innerHTML = `<i class="fas fa-check"></i> ${mensaje}`;

        document.body.appendChild(mensajeDiv);

        // Remover después de 3 segundos
        setTimeout(() => {
            if (mensajeDiv.parentNode) {
                mensajeDiv.parentNode.removeChild(mensajeDiv);
            }
        }, 3000);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new PorteroPanel();
});

