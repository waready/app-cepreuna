<?php

namespace App\Http\Controllers\Estudiante;

use App\Http\Controllers\Controller;
use App\Models\Periodo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Inertia\Inertia;

class SimulacroController extends Controller
{
    public function index()
    {
        $periodo = Periodo::actual();
        abort_unless($periodo, 404, 'No hay un ciclo activo.');
        $inscripcion = $this->inscripcion((int) $periodo->id);

        if (! $inscripcion) {
            $url = trim((string) config('services.simulacro.formulario_url'));
            abort_if($url === '', 503, 'El formulario del simulacro no esta configurado.');

            return Inertia::location($url.(strpos($url, '?') === false ? '?' : '&').'periodo='.$periodo->id);
        }

        $config = DB::table('simulacro_configuraciones')->where('periodos_id', $periodo->id)->first();

        return Inertia::render('Estudiante/Simulacro', ['data' => [
            'ciclo' => $periodo->codigo ?: $periodo->nombre,
            'titulo' => $config->titulo ?? 'Simulacro de examen',
            'fecha_registro' => $inscripcion->created_at ? Carbon::parse($inscripcion->created_at)->format('d/m/Y H:i') : null,
            'monto' => number_format((float) $inscripcion->monto, 2, '.', ''),
            'fecha_examen' => ! empty($config->fecha_examen) ? Carbon::parse($config->fecha_examen)->format('d/m/Y H:i') : 'Por confirmar',
            'ingreso' => ! empty($config->hora_ingreso_inicio) && ! empty($config->hora_ingreso_fin)
                ? Carbon::parse($config->hora_ingreso_inicio)->format('H:i').' - '.Carbon::parse($config->hora_ingreso_fin)->format('H:i')
                : 'Por confirmar',
            'lugar' => $config->lugar ?? 'Por confirmar',
            'url_ficha' => route('estudiantes.simulacro.ficha'),
            'url_descarga' => route('estudiantes.simulacro.ficha', ['descargar' => 1]),
        ]]);
    }

    public function ficha(Request $request)
    {
        $periodo = Periodo::actual();
        abort_unless($periodo, 404, 'No hay un ciclo activo.');
        $inscripcion = $this->inscripcion((int) $periodo->id);
        abort_unless($inscripcion, 404, 'No tiene una inscripcion al simulacro en el ciclo actual.');
        $base = rtrim((string) config('services.simulacro.api_url'), '/');
        abort_if($base === '', 503, 'El servicio de fichas no esta configurado.');

        try {
            $response = Http::withHeaders(['Accept' => 'application/pdf'])->timeout(30)
                ->get($base.'/web/inscripciones/simulacro/'.rawurlencode($inscripcion->codigoqr).'/pdf');
        } catch (ConnectionException $exception) {
            abort(502, 'No se pudo conectar con el servicio de fichas. Intente nuevamente.');
        }
        abort_unless($response->successful() && strncmp($response->body(), '%PDF-', 5) === 0, 502, 'No se pudo generar su ficha. Intente nuevamente.');
        $nombre = 'ficha-simulacro-'.preg_replace('/[^A-Za-z0-9_-]/', '', (string) $periodo->codigo).'.pdf';

        return response($response->body(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($request->boolean('descargar') ? 'attachment' : 'inline').'; filename="'.$nombre.'"',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    private function inscripcion(int $periodoId): ?object
    {
        $estudianteId = Auth::guard('estudiante')->id();
        abort_unless($estudianteId, 401);

        return DB::table('inscripcion_simulacros')
            ->where('estudiantes_id', $estudianteId)
            ->where('periodos_id', $periodoId)
            ->latest('id')->first();
    }
}
