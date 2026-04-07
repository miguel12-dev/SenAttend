/**
 * reportes-equipos.js
 * Lógica del módulo de Reporte de Ingresos/Salidas de Equipos.
 *  - Validación del formulario de filtros antes de enviar.
 *  - Construcción de la URL de exportación con token CSRF.
 */

(function () {
    'use strict';

    // ── Referencias DOM ───────────────────────────────────────────────────────
    const formFiltros   = document.getElementById('form-filtros-reporte');
    const btnExportar   = document.getElementById('btn-exportar-excel');
    const inputInicio   = document.getElementById('fecha_inicio');
    const inputFin      = document.getElementById('fecha_fin');
    const csrfMeta      = document.querySelector('meta[name="csrf-token"]');

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Muestra un mensaje de validación junto a un campo.
     * @param {HTMLElement} el
     * @param {string} msg
     */
    function mostrarError(el, msg) {
        el.classList.add('input-error');
        let hint = el.nextElementSibling;
        if (!hint || !hint.classList.contains('validation-hint')) {
            hint = document.createElement('small');
            hint.className = 'validation-hint';
            el.parentNode.insertBefore(hint, el.nextSibling);
        }
        hint.textContent = msg;
    }

    function limpiarErrores() {
        document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
        document.querySelectorAll('.validation-hint').forEach(el => el.remove());
    }

    /**
     * Valida que las fechas sean correctas y el rango coherente.
     * @returns {boolean}
     */
    function validarFechas() {
        limpiarErrores();
        let ok = true;

        const inicio = inputInicio ? inputInicio.value : '';
        const fin    = inputFin    ? inputFin.value    : '';
        const hoy    = new Date().toISOString().split('T')[0];

        if (!inicio) {
            if (inputInicio) mostrarError(inputInicio, 'Selecciona una fecha de inicio.');
            ok = false;
        }

        if (!fin) {
            if (inputFin) mostrarError(inputFin, 'Selecciona una fecha fin.');
            ok = false;
        }

        if (ok && inicio > fin) {
            mostrarError(inputFin, 'La fecha fin debe ser igual o posterior a la fecha inicio.');
            ok = false;
        }

        if (ok && fin > hoy) {
            mostrarError(inputFin, 'La fecha fin no puede ser futura.');
            ok = false;
        }

        return ok;
    }

    // ── Formulario de filtros ─────────────────────────────────────────────────
    if (formFiltros) {
        formFiltros.addEventListener('submit', function (e) {
            if (!validarFechas()) {
                e.preventDefault();
            }
        });
    }

    // ── Botón exportar Excel ──────────────────────────────────────────────────
    if (btnExportar) {
        btnExportar.addEventListener('click', function (e) {
            e.preventDefault();

            if (!validarFechas()) return;

            const inicio = inputInicio ? inputInicio.value : '';
            const fin    = inputFin    ? inputFin.value    : '';
            const token  = csrfMeta    ? csrfMeta.content  : '';

            const url = new URL('/reportes-equipos/exportar', window.location.origin);
            url.searchParams.set('fecha_inicio', inicio);
            url.searchParams.set('fecha_fin',    fin);
            url.searchParams.set('_token',       token);

            // Iniciar descarga sin navegar
            const link = document.createElement('a');
            link.href  = url.toString();
            link.click();
        });
    }

    // ── Auto-ajuste de fecha mínima de "fin" ─────────────────────────────────
    if (inputInicio && inputFin) {
        inputInicio.addEventListener('change', function () {
            if (inputFin.value && inputFin.value < this.value) {
                inputFin.value = this.value;
            }
            inputFin.min = this.value;
        });
    }

})();
