<?php

namespace App\Http\Controllers\Estudiante;

use App\Http\Controllers\Controller;
use App\Models\Inscripciones;
use App\Models\Periodo;
use App\Services\PagoArchivosApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Throwable;

class PagoRegistroController extends Controller
{
    private $pagos;

    public function __construct(PagoArchivosApi $pagos)
    {
        $this->pagos = $pagos;
    }

    public function validar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'secuencia' => ['required', 'string', 'max:50'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'fecha' => ['required', 'date_format:Y-m-d', 'after:2020-12-14'],
            'pagarEnPagalo' => ['nullable', 'boolean'],
            'file' => ['required', 'mimes:pdf,jpg,jpeg,png', 'max:6144'],
        ], [
            'required' => '* El campo es obligatorio.',
            'monto.numeric' => '* Ingrese un monto valido.',
            'monto.min' => '* Ingrese un monto mayor a cero.',
            'fecha.after' => '* Solo se admiten pagos desde el 15/12/2020.',
            'file.required' => '* El voucher es obligatorio.',
            'file.mimes' => '* Solo se admiten formatos pdf, jpg, jpeg o png.',
            'file.max' => '* El peso maximo del archivo debe ser menor a 6 MB.',
        ]);

        $contexto = $this->contextoEstudiante();

        if (! $contexto) {
            return $this->fallo('No se encontro una inscripcion activa para el ciclo actual.');
        }

        try {
            return response()->json($this->pagos->validarPago([
                'estudiantes_id' => $contexto['estudiante_id'],
                'periodos_id' => $contexto['periodo_id'],
                'secuencia' => $data['secuencia'],
                'monto' => $data['monto'],
                'fecha' => $data['fecha'],
                'pagarEnPagalo' => $request->boolean('pagarEnPagalo') ? '1' : '0',
            ], $request->file('file')));
        } catch (RuntimeException $exception) {
            return $this->fallo($exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return $this->fallo('No fue posible validar el pago. Intentelo nuevamente.');
        }
    }

    public function registrar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tokens' => ['required', 'array', 'min:1'],
            'tokens.*' => ['required', 'string', 'max:100', 'distinct'],
        ]);
        $contexto = $this->contextoEstudiante();

        if (! $contexto) {
            return $this->fallo('No se encontro una inscripcion activa para el ciclo actual.');
        }

        try {
            return response()->json($this->pagos->registrarPagos([
                'estudiantes_id' => $contexto['estudiante_id'],
                'periodos_id' => $contexto['periodo_id'],
                'tokens' => array_values(array_unique($data['tokens'])),
            ]));
        } catch (RuntimeException $exception) {
            return $this->fallo($exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return $this->fallo('No fue posible registrar el pago. Intentelo nuevamente.');
        }
    }

    /** @return array{estudiante_id: int, periodo_id: int}|null */
    private function contextoEstudiante(): ?array
    {
        $estudianteId = (int) Auth::id();
        $periodo = Periodo::actual();

        if (! $estudianteId || ! $periodo) {
            return null;
        }

        $inscripcion = Inscripciones::query()
            ->delEstudiante($estudianteId)
            ->delPeriodoActual((int) $periodo->id)
            ->where('estado', '1')
            ->where('matricula', '1')
            ->latest('id')
            ->first();

        if (! $inscripcion) {
            return null;
        }

        return [
            'estudiante_id' => $estudianteId,
            'periodo_id' => (int) $periodo->id,
        ];
    }

    private function fallo(string $message): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $message,
        ]);
    }
}
