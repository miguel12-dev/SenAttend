document.addEventListener('DOMContentLoaded', function() {
    const btnProcesar = document.getElementById('btn-procesar-cierres');
    const btnExportar = document.getElementById('btn-exportar-excel-seguimiento');
    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

    if (btnProcesar) {
        btnProcesar.addEventListener('click', function() {
            Swal.fire({
                title: '¿Procesar Cierres Automáticamente?',
                text: "El sistema buscará registros de ingresos de fechas anteriores que nunca registraron su salida, y las marcará como 'Salida no registrada'.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#39A900',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, procesar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    
                    Swal.fire({
                        title: 'Procesando...',
                        html: 'Por favor espere mientras verificamos los registros.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch('/api/seguimiento-equipos/procesar', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            _token: csrfToken
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire(
                                '¡Completado!',
                                data.message,
                                'success'
                            ).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire(
                                'Error',
                                data.error || 'Ocurrió un error al procesar las infracciones.',
                                'error'
                            );
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire(
                            'Error',
                            'Fallo de conexión al procesar la solicitud.',
                            'error'
                        );
                    });
                }
            });
        });
    }

    if (btnExportar) {
        btnExportar.addEventListener('click', function() {
            const fechaInicio = document.getElementById('fecha_inicio').value;
            const fechaFin = document.getElementById('fecha_fin').value;
            
            if (!fechaInicio || !fechaFin) {
                Swal.fire('Atención', 'Debes tener ambas fechas seleccionadas.', 'warning');
                return;
            }

            const url = `/admin/seguimiento-equipos/exportar?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&_token=${csrfToken}`;
            window.location.href = url;
            
            Swal.fire({
                icon: 'success',
                title: 'Generando Reporte...',
                text: 'La descarga comenzará en breve.',
                timer: 3000,
                showConfirmButton: false
            });
        });
    }
});
