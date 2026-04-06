<?php

namespace App\Services;

use App\Repositories\ReporteEquiposRepository;
use App\Repositories\TurnoConfigRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Servicio para generación del reporte de ingresos/salidas de equipos.
 * Organiza los datos por turno y exporta a Excel con una hoja por turno.
 *
 * @version 1.0
 */
class ReporteEquiposService
{
    private const HEADERS = [
        'Fecha Ingreso', 'Hora Ingreso', 'Fecha Salida', 'Hora Salida',
        'Nombre Aprendiz', 'Documento', 'Marca Equipo', 'Número Serial',
        'Portero', 'Observaciones',
    ];

    private const HEADER_COLOR  = 'FF39A900'; // Verde SENA
    private const FONT_COLOR    = 'FFFFFFFF'; // Blanco

    private ReporteEquiposRepository $repo;
    private TurnoConfigRepository    $turnoRepo;

    public function __construct(
        ReporteEquiposRepository $repo,
        TurnoConfigRepository $turnoRepo
    ) {
        $this->repo      = $repo;
        $this->turnoRepo = $turnoRepo;
    }

    /**
     * Datos paginados para visualización en pantalla.
     */
    public function getDatosPaginados(
        string $fechaInicio,
        string $fechaFin,
        int $pagina = 1,
        int $porPagina = 20
    ): array {
        $offset = ($pagina - 1) * $porPagina;
        $datos  = $this->repo->getReportePaginated($fechaInicio, $fechaFin, $porPagina, $offset);
        $total  = $this->repo->getTotalRegistros($fechaInicio, $fechaFin);

        return [
            'datos'       => $datos,
            'total'       => $total,
            'pagina'      => $pagina,
            'por_pagina'  => $porPagina,
            'total_paginas' => (int) ceil($total / $porPagina),
        ];
    }

    /**
     * Genera el archivo Excel en disco y retorna la ruta absoluta.
     * Cada turno activo ocupa una hoja separada; los registros sin turno
     * van a una hoja "Sin Turno Definido".
     */
    public function generarExcel(string $fechaInicio, string $fechaFin): string
    {
        $registros = $this->repo->getAllParaExportar($fechaInicio, $fechaFin);
        $turnos    = $this->turnoRepo->findAllActive();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);
        $idx = 0;

        // Agrupar por turno según hora_ingreso
        $grupos = $this->agruparPorTurno($registros, $turnos);

        foreach ($grupos as $nombreTurno => $filas) {
            $sheet = new Worksheet($spreadsheet, $this->sanitizarNombreHoja($nombreTurno));
            $spreadsheet->addSheet($sheet, $idx++);
            $this->poblarHoja($sheet, $filas);
        }

        // Si no hay ninguna hoja, crear una vacía
        if ($idx === 0) {
            $sheet = new Worksheet($spreadsheet, 'Sin datos');
            $spreadsheet->addSheet($sheet, 0);
            $this->poblarHoja($sheet, []);
        }

        $exportDir = __DIR__ . '/../../public/exports';
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        $fileName = sprintf(
            'reporte_equipos_%s_%s_%s.xlsx',
            $fechaInicio,
            $fechaFin,
            date('His')
        );
        $filePath = $exportDir . '/' . $fileName;

        (new Xlsx($spreadsheet))->save($filePath);

        return $filePath;
    }

    // ─── Privados ────────────────────────────────────────────────────────────

    /** Agrupa registros en un mapa [nombre_turno => [...filas]] */
    private function agruparPorTurno(array $registros, array $turnos): array
    {
        $grupos = [];

        // Inicializar con orden de turnos activos
        foreach ($turnos as $turno) {
            $grupos[$turno['nombre_turno']] = [];
        }
        $grupos['Sin Turno Definido'] = [];

        foreach ($registros as $fila) {
            $hora  = $fila['hora_ingreso'] ?? '00:00:00';
            $asign = $this->detectarTurno($hora, $turnos);
            $grupos[$asign][] = $fila;
        }

        // Eliminar grupos vacíos
        return array_filter($grupos, fn($g) => count($g) > 0);
    }

    /** Determina a qué turno pertenece una hora dada */
    private function detectarTurno(string $hora, array $turnos): string
    {
        foreach ($turnos as $turno) {
            if ($hora >= $turno['hora_inicio'] && $hora < $turno['hora_fin']) {
                return $turno['nombre_turno'];
            }
        }
        return 'Sin Turno Definido';
    }

    /** Escribe encabezados y filas en una hoja de spreadsheet */
    private function poblarHoja(Worksheet $sheet, array $filas): void
    {
        $cols = range('A', 'J'); // 10 columnas
        foreach (self::HEADERS as $i => $header) {
            $cell = $cols[$i] . '1';
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFont()->getColor()->setARGB(self::FONT_COLOR);
            $sheet->getStyle($cell)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB(self::HEADER_COLOR);
        }

        $row = 2;
        foreach ($filas as $fila) {
            $valores = [
                $fila['fecha_ingreso']   ?? '',
                $fila['hora_ingreso']    ?? '',
                $fila['fecha_salida']    ?? '',
                $fila['hora_salida']     ?? '',
                $fila['nombre_aprendiz'] ?? '',
                $fila['documento_aprendiz'] ?? '',
                $fila['marca_equipo']    ?? '',
                $fila['numero_serial']   ?? '',
                $fila['nombre_portero']  ?? '',
                $fila['observaciones']   ?? '',
            ];
            foreach ($valores as $j => $val) {
                $sheet->setCellValue($cols[$j] . $row, $val);
            }
            $row++;
        }

        foreach ($cols as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /** Trunca nombres de hoja a 31 caracteres (límite Excel) */
    private function sanitizarNombreHoja(string $name): string
    {
        return substr(preg_replace('/[\/\\\?\*\[\]:]/', '', $name), 0, 31);
    }
}
