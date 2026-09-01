<?php

namespace App\Http\Controllers\Docente;

use App\Exceptions\BancoPreguntasApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBancoPreguntaLoteRequest;
use App\Models\BancoPreguntaLote;
use App\Models\BancoPreguntaRevision;
use App\Models\Periodo;
use App\Services\BancoPreguntasApi;
use App\Support\DocumentoWord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PreguntasDemoController extends Controller
{
    private $bancoPreguntasApi;

    public function __construct(BancoPreguntasApi $bancoPreguntasApi)
    {
        $this->bancoPreguntasApi = $bancoPreguntasApi;
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

        $response = [];

        try {
            $response = $this->bancoPreguntasApi->crearEntrega([
                'periodos_id' => (int) $periodo->id,
                'cursos_id' => $cursoId,
                'docentes_id' => (int) $cuenta->docentes_id,
                'semana' => $semana,
                'nivel' => $request->input('nivel'),
            ], $request->file('archivo'));
        } catch (BancoPreguntasApiException $exception) {
            $this->throwUploadException($exception);
        }

        return redirect()->back()->with('response', [
            'status' => true,
            'message' => $response['message'] ?? 'El Word fue enviado correctamente para revision.',
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
        $contents = $this->downloadFromApi(function () use ($cuenta, $lote, $periodo) {
            return $this->bancoPreguntasApi->descargarEntrega(
                $lote->id,
                $cuenta->docentes_id,
                $periodo->id
            );
        });

        return response()->streamDownload(
            function () use ($contents) {
                echo $contents;
            },
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

        $contents = $this->downloadFromApi(function () use ($cuenta, $lote, $periodo) {
            return $this->bancoPreguntasApi->descargarRevision(
                $lote->id,
                $cuenta->docentes_id,
                $periodo->id
            );
        });

        return response()->streamDownload(
            function () use ($contents) {
                echo $contents;
            },
            $revision->archivo_nombre,
            ['Content-Type' => DocumentoWord::MIME]
        );
    }

    public function destroy(BancoPreguntaLote $lote)
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

        try {
            $response = $this->bancoPreguntasApi->eliminarEntrega(
                $lote->id,
                $cuenta->docentes_id,
                $periodo->id
            );
        } catch (BancoPreguntasApiException $exception) {
            throw ValidationException::withMessages([
                'entrega' => $exception->getMessage(),
            ]);
        }

        return redirect()->back()->with('response', [
            'status' => true,
            'message' => $response['message'] ?? 'La entrega y sus archivos fueron eliminados.',
        ]);
    }

    private function throwUploadException(BancoPreguntasApiException $exception)
    {
        if ($exception->status() === 422 && $exception->errors()) {
            throw ValidationException::withMessages($exception->errors());
        }

        abort(503, $exception->getMessage());
    }

    private function downloadFromApi(callable $download)
    {
        try {
            return $download();
        } catch (BancoPreguntasApiException $exception) {
            abort($exception->status() === 404 ? 404 : 503, $exception->getMessage());
        }
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
