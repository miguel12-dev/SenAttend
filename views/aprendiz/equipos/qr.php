<?php
/** @var array $user */
/** @var array $qrInfo */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR del Equipo - SENAttend</title>
    <link rel="stylesheet" href="<?= asset('assets/vendor/fontawesome/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/common/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/dashboard/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/aprendiz/panel.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/aprendiz/equipos/qr.css') ?>">
</head>
<body>
    <div class="wrapper">
        <?php 
        $currentPage = 'aprendiz-equipo-qr';
        require __DIR__ . '/../../components/header.php'; 
        ?>

        <main class="main-content">
            <div class="container">
                <div class="aprendiz-dashboard">
                    <section class="aprendiz-dashboard-header">
                        <div>
                            <h1>Código QR de tu equipo</h1>
                            <p>Puedes descargar o capturar este código para presentarlo en el CTA.</p>
                        </div>
                        <div class="aprendiz-actions">
                            <?php 
                            $url = '/aprendiz/equipos';
                            $text = 'Volver a Mis Equipos';
                            require __DIR__ . '/../../components/back-button.php'; 
                            ?>
                        </div>
                    </section>

                    <section class="aprendiz-equipos-card qr-card">
                        <h2>QR del equipo</h2>
                        <div class="qr-image-container">
                            <img src="<?= htmlspecialchars($qrInfo['image_base64'] ?? '', ENT_QUOTES, 'UTF-8') ?>" alt="QR del equipo" class="qr-image" id="qrImage">
                        </div>
                        <p class="qr-details">
                            <strong>Generado:</strong> <?= htmlspecialchars($qrInfo['fecha_generacion'] ?? '', ENT_QUOTES, 'UTF-8') ?><br>
                            <?php if (!empty($qrInfo['fecha_expiracion'])): ?>
                                <strong>Expira:</strong> <?= htmlspecialchars($qrInfo['fecha_expiracion'], ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </p>
                        <button type="button" class="btn btn-primary btn-download" id="btnDownloadQR">
                            <i class="fas fa-download"></i> Descargar QR
                        </button>
                    </section>
                </div>
            </div>
        </main>
    </div>

    <script src="<?= asset('js/app.js') ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btnDownloadQR = document.getElementById('btnDownloadQR');
            
            if (btnDownloadQR) {
                btnDownloadQR.addEventListener('click', function() {
                    const img = document.getElementById('qrImage');
                    const serial = '<?= htmlspecialchars($qrInfo['numero_serial'] ?? 'equipo', ENT_QUOTES, 'UTF-8') ?>';
                    const link = document.createElement('a');
                    link.href = img.src;
                    link.download = 'qr-equipo-' + serial + '.png';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                });
            }
        });
    </script>
</body>
</html>