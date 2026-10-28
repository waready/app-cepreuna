<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Services\TramiteApi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Throwable;

/**
 * Panel de tramite del docente.
 *
 * Muestra sus informes del ciclo con la lista de verificacion de cada uno y
 * deja descargar los documentos. Todo el contenido viene del backend de
 * sistemas, que es donde vive el modulo; aqui no se calcula nada.
 *
 * El documento del docente sale SIEMPRE de la sesion, nunca de la peticion:
 * asi nadie puede consultar el expediente de otro cambiando la URL.
 */
class TramiteController extends Controller
{
    /** Los unicos documentos que el docente puede pedir. */
    private const DESCARGABLES = ['informe', 'cci', 'dj_proveedor', 'dj_docente', 'completo'];

    /** Los unicos que puede reemplazar el: los que son escaneos suyos. */
    private const SUBIBLES = ['copia_dni', 'suspension_4ta', 'rnp'];

    /** Los que puede declarar que no le corresponden. */
    private const DECLARABLES = ['suspension_4ta', 'rnp'];

    public function __construct(private readonly TramiteApi $api) {}

    /** El documento del docente con la sesion abierta. */
    private function documentoDelDocente(): string
    {
        $cuenta = Auth::guard('docente')->user();

        return (string) optional($cuenta->docente)->nro_documento;
    }

    public function index(): InertiaResponse
    {
        $documento = $this->documentoDelDocente();

        $datos = ['docente' => null, 'informes' => [], 'error' => null];

        try {
            $datos = $this->api->expediente($documento) + ['error' => null];
        } catch (Throwable $e) {
            // Si el backend no responde, la pantalla lo dice en vez de
            // romperse: el docente no tiene por que ver una traza.
            Log::warning('No se pudo consultar el tramite del docente', [
                'documento' => $documento,
                'error' => $e->getMessage(),
            ]);
            $datos['error'] = 'No se pudo consultar el estado de tu trámite en este momento. Inténtalo más tarde.';
        }

        return Inertia::render('Docente/Tramite', ['data' => $datos]);
    }

    public function armar(int $periodo): RedirectResponse
    {
        $documento = $this->documentoDelDocente();

        try {
            $this->api->armar($documento, $periodo);
        } catch (Throwable $e) {
            Log::warning('No se pudo generar el informe', [
                'documento' => $documento,
                'periodo' => $periodo,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'No se pudo generar el informe. Inténtalo más tarde.');
        }

        return back()->with('success', 'Tu informe se generó correctamente.');
    }

    /**
     * Recibe un anexo del docente y lo reenvia al backend.
     *
     * Aqui no se guarda nada: el archivo acaba en la ficha del docente, que es
     * de donde el tramite los lee. El backend comprueba ademas que el
     * expediente sea suyo y que siga en preparacion.
     */
    public function subir(Request $request, string $codigo, string $tipo): RedirectResponse
    {
        abort_unless(in_array($tipo, self::SUBIBLES, true), 404);

        $request->validate([
            'archivo' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
        ], [], ['archivo' => 'documento']);

        $archivo = $request->file('archivo');
        $documento = $this->documentoDelDocente();

        try {
            $this->api->subir(
                $documento,
                $codigo,
                $tipo,
                (string) file_get_contents($archivo->getRealPath()),
                (string) $archivo->getClientOriginalName(),
            );
        } catch (Throwable $e) {
            Log::warning('No se pudo subir un anexo del tramite', [
                'documento' => $documento,
                'codigo' => $codigo,
                'tipo' => $tipo,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'No se pudo subir el documento. Inténtalo más tarde.');
        }

        return back()->with('success', 'Tu documento se actualizó correctamente.');
    }

    /**
     * El docente marca que un documento no le corresponde.
     *
     * El backend decide si puede: el RNP esta bloqueado para quien figura en
     * la lista de obligados que lleva administracion.
     */
    public function declarar(Request $request, string $codigo, string $tipo): RedirectResponse
    {
        abort_unless(in_array($tipo, self::DECLARABLES, true), 404);

        $validado = $request->validate(['no_aplica' => ['required', 'boolean']]);
        $documento = $this->documentoDelDocente();

        try {
            $this->api->declarar($documento, $codigo, $tipo, (bool) $validado['no_aplica']);
        } catch (Throwable $e) {
            Log::warning('No se pudo marcar un documento del tramite', [
                'documento' => $documento,
                'codigo' => $codigo,
                'tipo' => $tipo,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'No se pudo cambiar la marca de ese documento.');
        }

        return back()->with('success', $validado['no_aplica']
            ? 'Marcado como que no te corresponde.'
            : 'Vuelve a pedirse ese documento.');
    }

    public function documento(string $codigo, string $tipo): Response
    {
        abort_unless(in_array($tipo, self::DESCARGABLES, true), 404);

        $documento = $this->documentoDelDocente();

        try {
            $pdf = $this->api->documento($documento, $codigo, $tipo);
        } catch (Throwable $e) {
            Log::warning('No se pudo descargar un documento del tramite', [
                'documento' => $documento,
                'codigo' => $codigo,
                'tipo' => $tipo,
                'error' => $e->getMessage(),
            ]);

            abort(404, 'No se pudo obtener el documento.');
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$tipo.'-'.$codigo.'.pdf"',
        ]);
    }
}
