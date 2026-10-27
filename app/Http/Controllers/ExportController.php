<?php

namespace App\Http\Controllers;

use App\Models\Corrida;
use App\Models\Gestor;
use App\Models\Sesion;
use App\Models\Ticket;
use App\Services\CargaService;
use App\Services\OptimizadorService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportController extends Controller
{
    public function exportar(Request $r, string $formato)
    {
        $r->validate(['desde' => 'nullable|date', 'hasta' => 'nullable|date']);
        $q = Ticket::with('gestor');
        if (! $r->user()->hasAnyRole(['admin', 'mesa_entrada'])) {
            $q->whereIn('gestor_id', Gestor::where('user_id', $r->user()->id)->select('id'));
        }
        foreach (['estado' => 'estado', 'prioridad' => 'prioridad', 'modalidad' => 'modalidad', 'tipo' => 'tipo_docente'] as $key => $column) {
            if ($r->filled($key)) {
                $q->where($column, $r->input($key));
            }
        }
        if ($r->filled('desde')) {
            $q->whereDate('fecha_inicio', '>=', $r->desde);
        }if ($r->filled('hasta')) {
            $q->whereDate('fecha_inicio', '<=', $r->hasta);
        }
        if ($r->filled('busqueda')) {
            $q->where(fn ($x) => $x->where('codigo', 'like', '%'.$r->busqueda.'%')->orWhere('evento_formativo', 'like', '%'.$r->busqueda.'%'));
        }
        $tickets = $q->orderBy('codigo')->get();
        $c = app(CargaService::class);
        $run = Corrida::whereNotNull('aplicada_en')->latest('id')->first();
        $calendar = [];
        $stale = false;
        if ($run) {
            $stale = hash('sha256', json_encode(app(OptimizadorService::class)->entrada($run->parametros['fecha'], $run->parametros['solo_planificables'] ?? false))) !== ($run->resultado['huella_aplicada'] ?? $run->resultado['huella']);
            $sids = Sesion::whereIn('ticket_id', $tickets->modelKeys())->pluck('id')->all();
            foreach ($run->resultado['supervisiones'] as $s) {
                if (in_array($s['sesion_id'], $sids) && ($r->user()->hasAnyRole(['admin', 'mesa_entrada']) || Gestor::whereKey($s['gestor_id'])->where('user_id', $r->user()->id)->exists())) {
                    $session = Sesion::with('ticket')->find($s['sesion_id']);
                    $calendar[] = ['fecha' => $s['fecha'], 'ticket' => $session->ticket->codigo, 'gestor' => Gestor::find($s['gestor_id'])->nombre, 'tipo' => $s['tipo']];
                }
            }
        }
        if ($formato === 'pdf') {
            return Pdf::loadView('io.reporte', compact('tickets', 'calendar', 'run', 'stale', 'c'))->setPaper('a4', 'landscape')->download('resumen-docencia.pdf');
        }

        return response()->streamDownload(function () use ($tickets, $calendar, $c, $run, $stale) {
            $book = new Spreadsheet;
            $book->getProperties()->setCreator(config('academico.estudiante'))->setTitle(config('academico.titulo'))->setSubject(config('academico.curso').' · '.config('academico.universidad'));
            $sheet = $book->getActiveSheet();
            $sheet->setTitle('Tickets');
            $sheet->fromArray(['Ticket', 'Evento', 'Estado', 'Prioridad', 'Gestor', 'Modalidad', 'Ruta', 'Tipo docente', 'Inicio', 'Fin', 'Carga'], null, 'A1');
            $i = 2;
            foreach ($tickets as $t) {
                $row = [$t->codigo, $t->evento_formativo, $t->estado, $t->prioridad, $t->gestor?->nombre, $t->modalidad, $t->ruta, $t->tipo_docente, $t->fecha_inicio?->format('d/m/Y'), $t->fecha_fin?->format('d/m/Y'), $c->peso($t)];
                foreach ($row as $col => $value) {
                    $sheet->setCellValueExplicit([$col + 1, $i], $value ?? '', is_numeric($value) && $col === 10 ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING);
                }$i++;
            }
            $sheet->getStyle('K2:K'.max(2, $i))->getNumberFormat()->setFormatCode('0.00');
            $sheet = $book->createSheet();
            $sheet->setTitle('Calendario');
            $sheet->fromArray(['Fecha', 'Ticket', 'Gestor', 'Tipo'], null, 'A1');
            $i = 2;
            foreach ($calendar as $row) {
                foreach (array_values($row) as $col => $value) {
                    $sheet->setCellValueExplicit([$col + 1, $i], $value, DataType::TYPE_STRING);
                }$i++;
            }
            $sheet = $book->createSheet();
            $sheet->setTitle('Resumen');
            $sheet->fromArray([['Indicador', 'Valor'], ['Tickets exportados', $tickets->count()], ['Corrida aplicada', $run?->id ?? 'Sin corrida aplicada'], ['Calendario', $stale ? 'Desactualizado: vuelva a optimizar' : 'Según última corrida aplicada'], ['Generado', now()->format('d/m/Y H:i')]]);
            foreach ($book->getAllSheets() as $sheet) {
                $sheet->freezePane('A2');
                $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
                $sheet->getStyle('1:1')->getFont()->setBold(true);
                foreach (range('A', $sheet->getHighestColumn()) as $col) {
                    $sheet->getColumnDimension($col)->setWidth($col === 'B' ? 45 : 22);
                }
            }
            (new Xlsx($book))->save('php://output');
        }, 'docencia.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
