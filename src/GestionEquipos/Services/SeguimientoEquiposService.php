<?php

namespace App\GestionEquipos\Services;

use App\GestionEquipos\Repositories\SeguimientoEquiposRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SeguimientoEquiposService
{
    private SeguimientoEquiposRepository $repository;

    public function __construct(SeguimientoEquiposRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Procesa los cierres automáticos para equipos que quedaron sin salida en días anteriores.
     */
    public function procesarCierresAutomaticos(): int
    {
        return $this->repository->closePendingEntries();
    }

    /**
     * Obtiene los infractores frecuentes.
     */
    public function obtenerAprendicesInfractores(string $fechaInicio, string $fechaFin): array
    {
        return $this->repository->getRepeatOffenders($fechaInicio, $fechaFin);
    }

    /**
     * Genera un archivo Excel con el detalle de las infracciones para los infractores frecuentes.
     */
    public function generarExcelInfractores(string $fechaInicio, string $fechaFin): string
    {
        $detalles = $this->repository->getViolationsDetails($fechaInicio, $fechaFin);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Infracciones Equipos');

        // Estilos
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '39A900'] // Verde SENA
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ];

        // Encabezados
        $headers = ['Fecha Ingreso', 'Hora Ingreso', 'Documento Aprendiz', 'Nombre Completo', 'Ficha', 'Marca Equipo', 'Serial Equipo', 'Observaciones'];
        foreach (range('A', 'H') as $index => $column) {
            $sheet->setCellValue($column . '1', $headers[$index]);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

        // Llenado de datos
        $row = 2;
        foreach ($detalles as $detalle) {
            $sheet->setCellValue('A' . $row, $detalle['fecha_ingreso']);
            $sheet->setCellValue('B' . $row, $detalle['hora_ingreso']);
            $sheet->setCellValue('C' . $row, $detalle['documento_aprendiz']);
            $sheet->setCellValue('D' . $row, $detalle['nombre_aprendiz']);
            $sheet->setCellValue('E' . $row, $detalle['numero_ficha'] ?? 'N/A');
            $sheet->setCellValue('F' . $row, $detalle['marca_equipo']);
            $sheet->setCellValue('G' . $row, $detalle['numero_serial']);
            $sheet->setCellValue('H' . $row, $detalle['observaciones']);
            $row++;
        }

        // Generar archivo temporal
        $tempDir = __DIR__ . '/../../../storage/exports/';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $fileName = 'Reporte_Infracciones_Equipos_' . date('Ymd_His') . '.xlsx';
        $filePath = $tempDir . $fileName;

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return $filePath;
    }
}
