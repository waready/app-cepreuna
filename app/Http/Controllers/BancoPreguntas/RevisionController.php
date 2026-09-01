<?php

namespace App\Http\Controllers\BancoPreguntas;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewBancoPreguntaLoteRequest;
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

class RevisionController extends Controller
{
    public function index()
    {
        $periodo = Periodo::actual();
        abort_unless($periodo, 404);

        $persistenciaDisponible = $this->persistenciaDisponible();
        $entregas = collect();

        if ($persistenciaDisponible) {
            $entregas = BancoPreguntaLote::query()
                ->with([
                    'curso:id,denominacion',
                    'docente:id,nombres,paterno,materno',
                    'revision.usuario:id,name',
                ])
                ->where('periodos_id', $periodo->id)
                ->orderByRaw("CASE WHEN estado = 'en_revision' THEN 0 ELSE 1 END")
                ->orderByDesc('created_at')
                ->limit(300)
                ->get()
                ->map(function (BancoPreguntaLote $lote) {
                    $docente = $lote->docente;
                    $revision = $lote->revision;

                    return [
                        'id' => $lote->id,
                        'docente' => $docente
                            ? trim("{$docente->nombres} {$docente->paterno} {$docente->materno}")
                            : null,
                        'curso' => optional($lote->curso)->denominacion,
                        'semana' => $lote->semana,
                        'nivel' => $lote->nivel,
                        'version' => $lote->version,
                        'archivo_nombre' => $lote->archivo_nombre,
                        'estado' => $lote->estado,
                        'comentario' => optional($revision)->comentario,
                        'revisor' => $revision
                            ? optional($revision->usuario)->name
                            : null,
                        'enviado_at' => optional($lote->created_at)->format('d/m/Y H:i'),
                    ];
                });
        }

        return Inertia::render('BancoPreguntas/Revision', [
            'entregas' => $entregas,
            'persistenciaDisponible' => $persistenciaDisponible,
            'periodo' => [
                'id' => (int) $periodo->id,
                'nombre' => $periodo->nombre ?? $periodo->codigo ?? "Periodo {$periodo->id}",
            ],
        ]);
    }

    public function decidir(
        ReviewBancoPreguntaLoteRequest $request,
        BancoPreguntaLote $lote
    ) {
        abort_unless($this->persistenciaDisponible(), 503, 'El modulo aun no tiene sus tablas instaladas.');

        $periodo = Periodo::actual();
        abort_unless($periodo && (int) $lote->periodos_id === (int) $periodo->id, 404);

        $estados = [
            BancoPreguntaRevision::ACCION_APROBAR => BancoPreguntaLote::ESTADO_APROBADO,
            BancoPreguntaRevision::ACCION_OBSERVAR => BancoPreguntaLote::ESTADO_OBSERVADO,
            BancoPreguntaRevision::ACCION_RECHAZAR => BancoPreguntaLote::ESTADO_RECHAZADO,
        ];
        $accion = $request->input('accion');
        $archivo = $request->file('archivo_revision');
        $archivoPath = null;
        $archivoNombre = null;

        if ($archivo) {
            $archivoNombre = Str::limit(
                basename(str_replace('\\', '/', $archivo->getClientOriginalName())),
                255,
                ''
            );
            $archivoPath = Storage::disk('banco_preguntas')->putFileAs(
                sprintf('%d/%d/revisiones', $periodo->id, $lote->docentes_id),
                $archivo,
                Str::uuid().'.docx'
            );

            if (!$archivoPath) {
                throw new RuntimeException('No se pudo almacenar la version revisada.');
            }
        }

        try {
            DB::transaction(function () use (
                $accion,
                $archivoNombre,
                $archivoPath,
                $estados,
                $lote,
                $request
            ) {
                $entrega = BancoPreguntaLote::query()
                    ->lockForUpdate()
                    ->findOrFail($lote->id);

                if ($entrega->estado !== BancoPreguntaLote::ESTADO_EN_REVISION) {
                    throw ValidationException::withMessages([
                        'accion' => 'Esta entrega ya fue revisada y no admite otra decision.',
                    ]);
                }

                BancoPreguntaRevision::create([
                    'banco_pregunta_lote_id' => $entrega->id,
                    'users_id' => Auth::id(),
                    'accion' => $accion,
                    'comentario' => $request->input('comentario'),
                    'archivo_path' => $archivoPath,
                    'archivo_nombre' => $archivoNombre,
                ]);

                $entrega->update([
                    'estado' => $estados[$accion],
                ]);
            });
        } catch (Throwable $exception) {
            if ($archivoPath) {
                Storage::disk('banco_preguntas')->delete($archivoPath);
            }
            throw $exception;
        }

        return redirect()->back()->with('response', [
            'status' => true,
            'message' => 'La decision fue registrada correctamente.',
        ]);
    }

    public function download(BancoPreguntaLote $lote)
    {
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
}
