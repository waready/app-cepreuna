<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBancoPreguntaLoteRequest;
use App\Models\BancoPreguntaLote;
use App\Models\BancoPreguntaRevision;
use App\Models\Periodo;
use App\Support\DocumentoWord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use RuntimeException;
use Throwable;

class PreguntasDemoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:docente');
    }

    public function index()
    {
        $cuenta = Auth::guard('docente')->user();
        $periodo = Periodo::actual();

        abort_unless($cuenta && $periodo, 404);

        $persistenciaDisponible = $this->persistenciaDisponible();
        $entregas = collect();

        if ($persistenciaDisponible) {
            $entregas = BancoPreguntaLote::query()
                ->with(['curso:id,denominacion', 'revision'])
                ->where('docentes_id', $cuenta->docentes_id)
                ->where('periodos_id', $periodo->id)
                ->orderByDesc('created_at')
                ->get()
                ->map(function (BancoPreguntaLote $lote) {
                    $revision = $lote->revision;

                    return [
                        'id' => $lote->id,
                        'curso' => optional($lote->curso)->denominacion,
                        'semana' => $lote->semana,
                        'nivel' => $lote->nivel,
                        'version' => $lote->version,
                        'archivo_nombre' => $lote->archivo_nombre,
                        'estado' => $lote->estado,
                        'comentario' => optional($revision)->comentario,
                        'archivo_revision' => $revision && $revision->archivo_path
                            ? [
                                'id' => $revision->id,
                                'nombre' => $revision->archivo_nombre,
                            ]
                            : null,
                        'enviado_at' => optional($lote->created_at)->format('d/m/Y H:i'),
                    ];
                });
        }

        return Inertia::render('Docente/Recurso/PreguntasDemo', [
            'cursos' => $this->cursosAsignados($cuenta->docentes_id, $periodo->id),
            'entregas' => $entregas,
            'persistenciaDisponible' => $persistenciaDisponible,
            'periodo' => [
                'id' => (int) $periodo->id,
                'nombre' => $periodo->nombre ?? $periodo->codigo ?? "Periodo {$periodo->id}",
            ],
        ]);
    }

    public function store(StoreBancoPreguntaLoteRequest $request)
    {
        abort_unless($this->persistenciaDisponible(), 503, 'El modulo aun no tiene sus tablas instaladas.');

        $cuenta = Auth::guard('docente')->user();
        $periodo = Periodo::actual();
        abort_unless($cuenta && $periodo, 404);

        $cursoId = (int) $request->input('curso_id');
        $semana = (int) $request->input('semana');

        $cursoAsignado = DB::table('carga_academicas')
            ->where('docentes_id', $cuenta->docentes_id)
            ->where('periodos_id', $periodo->id)
            ->where('cursos_id', $cursoId)
            ->where('estado', '1')
            ->exists();

        if (!$cursoAsignado) {
            throw ValidationException::withMessages([
                'curso_id' => 'El curso no pertenece a tus cargas activas del periodo actual.',
            ]);
        }

        $archivo = $request->file('archivo');
        $nombreGuardado = Str::uuid().'.docx';
        $directorio = sprintf('%d/%d/originales', $periodo->id, $cuenta->docentes_id);
        $nombreOriginal = Str::limit(
            basename(str_replace('\\', '/', $archivo->getClientOriginalName())),
            255,
            ''
        );
        $path = Storage::disk('banco_preguntas')->putFileAs(
            $directorio,
            $archivo,
            $nombreGuardado
        );

        if (!$path) {
            throw new RuntimeException('No se pudo almacenar el documento Word.');
        }

        try {
            DB::transaction(function () use (
                $cuenta,
                $cursoId,
                $nombreOriginal,
                $path,
                $periodo,
                $request,
                $semana
            ) {
                $ultimaEntrega = BancoPreguntaLote::query()
                    ->where('periodos_id', $periodo->id)
                    ->where('cursos_id', $cursoId)
                    ->where('docentes_id', $cuenta->docentes_id)
                    ->where('semana', $semana)
                    ->orderByDesc('version')
                    ->lockForUpdate()
                    ->first();

                if ($ultimaEntrega && $ultimaEntrega->estado !== BancoPreguntaLote::ESTADO_OBSERVADO) {
                    throw ValidationException::withMessages([
                        'semana' => $ultimaEntrega->estado === BancoPreguntaLote::ESTADO_EN_REVISION
                            ? 'Ya existe una entrega en revision para este curso y semana.'
                            : 'La entrega de este curso y semana ya tiene una decision final.',
                    ]);
                }

                BancoPreguntaLote::create([
                    'periodos_id' => $periodo->id,
                    'cursos_id' => $cursoId,
                    'docentes_id' => $cuenta->docentes_id,
                    'semana' => $semana,
                    'nivel' => $request->input('nivel'),
                    'version' => $ultimaEntrega ? $ultimaEntrega->version + 1 : 1,
                    'archivo_path' => $path,
                    'archivo_nombre' => $nombreOriginal,
                    'estado' => BancoPreguntaLote::ESTADO_EN_REVISION,
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('banco_preguntas')->delete($path);
            throw $exception;
        }

        return redirect()->back()->with('response', [
            'status' => true,
            'message' => 'El Word fue enviado correctamente para revision.',
        ]);
    }

    public function plantilla()
    {
        $path = resource_path('templates/modelo-preguntas.docx');
        abort_unless(is_file($path), 404);

        return response()->download($path, 'MODELO-DE-PREGUNTAS-CEPREUNA.docx');
    }

    public function download(BancoPreguntaLote $lote)
    {
        $cuenta = Auth::guard('docente')->user();
        $periodo = Periodo::actual();
        abort_unless(
            $cuenta
            && $periodo
            && (int) $lote->docentes_id === (int) $cuenta->docentes_id
            && (int) $lote->periodos_id === (int) $periodo->id,
            404
        );
        abort_unless(Storage::disk('banco_preguntas')->exists($lote->archivo_path), 404);

        return Storage::disk('banco_preguntas')->download(
            $lote->archivo_path,
            $lote->archivo_nombre,
            ['Content-Type' => DocumentoWord::MIME]
        );
    }

    public function downloadRevision(
        BancoPreguntaLote $lote,
        BancoPreguntaRevision $revision
    ) {
        $cuenta = Auth::guard('docente')->user();
        $periodo = Periodo::actual();
        abort_unless(
            $cuenta
            && $periodo
            && (int) $lote->docentes_id === (int) $cuenta->docentes_id
            && (int) $lote->periodos_id === (int) $periodo->id,
            404
        );
        abort_unless((int) $revision->banco_pregunta_lote_id === (int) $lote->id, 404);
        abort_unless($revision->archivo_path, 404);
        abort_unless(Storage::disk('banco_preguntas')->exists($revision->archivo_path), 404);

        return Storage::disk('banco_preguntas')->download(
            $revision->archivo_path,
            $revision->archivo_nombre,
            ['Content-Type' => DocumentoWord::MIME]
        );
    }

    private function persistenciaDisponible(): bool
    {
        return Schema::hasTable('banco_pregunta_lotes')
            && Schema::hasTable('banco_pregunta_revisiones');
    }

    private function cursosAsignados($docenteId, $periodoId)
    {
        $cargas = DB::table('carga_academicas as ca')
            ->select(
                'ca.cursos_id as curso_id',
                'c.denominacion as curso',
                'g.denominacion as grupo',
                's.id as sede_id',
                's.denominacion as sede'
            )
            ->join('cursos as c', 'c.id', 'ca.cursos_id')
            ->join('grupo_aulas as ga', 'ga.id', 'ca.grupo_aulas_id')
            ->join('grupos as g', 'g.id', 'ga.grupos_id')
            ->join('aulas as au', 'au.id', 'ga.aulas_id')
            ->join('locales as l', 'l.id', 'au.locales_id')
            ->join('sedes as s', 's.id', 'l.sedes_id')
            ->where('ca.docentes_id', $docenteId)
            ->where('ca.periodos_id', $periodoId)
            ->where('ca.estado', '1')
            ->orderBy('c.denominacion')
            ->orderBy('g.denominacion')
            ->get();

        return $cargas
            ->groupBy('curso_id')
            ->map(function ($cargasCurso) {
                $primeraCarga = $cargasCurso->first();
                $grupos = $cargasCurso->pluck('grupo')->filter()->unique()->values();
                $modalidades = $cargasCurso
                    ->map(function ($carga) {
                        $esVirtual = (int) $carga->sede_id === 1
                            || Str::contains(Str::lower((string) $carga->sede), 'virtual');

                        return $esVirtual ? 'Virtual' : 'Presencial';
                    })
                    ->unique()
                    ->values();

                return [
                    'id' => (int) $primeraCarga->curso_id,
                    'curso' => $primeraCarga->curso,
                    'grupos' => $grupos->all(),
                    'modalidades' => $modalidades->all(),
                    'label' => sprintf(
                        '%s - %d %s',
                        $primeraCarga->curso,
                        $grupos->count(),
                        $grupos->count() === 1 ? 'grupo' : 'grupos'
                    ),
                ];
            })
            ->values();
    }
}
