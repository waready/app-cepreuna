<?php

namespace App\Http\Controllers\Estudiante;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Constancia de no adeudo del estudiante autenticado.
 *
 * El sistema central (sistemas.cepreuna.edu.pe) entrega en GET /api/v1/{dni}
 * un enlace firmado y temporal a la constancia, solo si la matricula del ciclo
 * actual esta habilitada y sincronizada. Aqui se pide ese enlace del lado del
 * servidor (el token no llega al navegador) y se redirige al estudiante.
 */
class ConstanciaNoAdeudoController extends Controller
{
    public function __invoke()
    {
        $estudianteId = Auth::guard('estudiante')->id();
        abort_unless($estudianteId, 401);

        $dni = (string) DB::table('estudiantes')->where('id', $estudianteId)->value('nro_documento');
        abort_unless(preg_match('/^\d{8,12}$/', $dni) === 1, 404, 'No se encontro su documento de identidad.');

        $base = rtrim((string) config('services.constancia_no_adeudo.api_url'), '/');
        $token = (string) config('services.constancia_no_adeudo.token');
        abort_if($base === '' || $token === '', 503, 'El servicio de constancias no esta configurado.');

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
                'Accept' => 'application/json',
            ])->timeout(20)->get($base.'/api/v1/'.$dni);
        } catch (ConnectionException $exception) {
            abort(502, 'No se pudo conectar con el servicio de constancias. Intente nuevamente.');
        }

        abort_unless($response->successful(), 502, 'No se pudo obtener su constancia. Intente nuevamente.');

        $url = (string) data_get($response->json(), '0.constancia_no_adeudo', '');
        abort_if($url === '', 404, 'Su constancia aun no esta disponible: la habilitacion del ciclo actual esta pendiente de sincronizacion.');
        abort_unless(str_starts_with($url, $base.'/'), 502, 'El servicio de constancias devolvio un enlace no valido.');

        return redirect()->away($url);
    }
}
