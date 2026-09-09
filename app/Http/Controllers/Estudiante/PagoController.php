<?php

namespace App\Http\Controllers\Estudiante;

use App\Http\Controllers\Controller;
use App\Models\BancoPago;
use App\Models\CronogramaPago;
use App\Models\Inscripciones;
use App\Models\InscripcionPago;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\Periodo;
use App\Models\Tarifa;
use App\Models\TarifaEstudiante;
use App\Models\Estudiante;
use App\Services\PagoArchivosApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PagoController extends Controller
{
    private const BANCO_AGENCIA_PAGALO = '0987';
    private const BANCO_CONCEPTO_CEPREUNA = '00000067';
    private const BANCO_CUENTA_SIN_COMISION = '0701010736';

    private $pagoArchivos;

    public function __construct(PagoArchivosApi $pagoArchivos)
    {
        // $this->middleware('auth:estudiante');
        date_default_timezone_set("America/Lima"); //Zona horaria de Peru
        $this->pagoArchivos = $pagoArchivos;
    }

    protected function mensajeInscripcionActiva(): string
    {
        return 'No se encontró una inscripción activa para el ciclo actual.';
    }

    protected function mensajeCronogramaActivo(): string
    {
        return 'No se encontró un cronograma activo para el ciclo actual.';
    }

    protected function respuestaPagoVacia(): array
    {
        return [
            'cronograma' => null,
            'deuda' => '0.00',
            'tipo_descuento' => '',
            'vouchers' => [],
            'tarifario' => [],
            'url' => config('app.external_image_url'),
            'simulacro' => false,
            'usuario' => '',
            'puntaje' => '',
            'carrera' => '',
            'status' => false,
            'message' => $this->mensajeInscripcionActiva(),
        ];
    }

    protected function respuestaOperacionFallida(string $message): array
    {
        return [
            'message' => $message,
            'status' => false,
        ];
    }

    protected function tipoTarifa(Inscripciones $inscripcion): string
    {
        switch ((string) $inscripcion->tipo_estudiante) {
            case '1':
                return '1';
            case '2':
            case '3':
            case '4':
            case '6':
                return '2';
            default:
                return '0';
        }
    }

    protected function tarifarioPeriodoActual($idEstudiante, Periodo $periodo, Inscripciones $inscripcion)
    {
        $tarifarioGuardado = TarifaEstudiante::where([
            ['estudiantes_id', $idEstudiante],
            ['periodos_id', $periodo->id]
        ])
            ->orderBy('nro_cuota', 'asc')
            ->get();

        if ($tarifarioGuardado->count() === 5) {
            return $tarifarioGuardado;
        }

        $estudiante = $inscripcion->estudiante()->with('colegio')->first();
        $tipoColegio = optional(optional($estudiante)->colegio)->tipo_colegios_id;
        $tipoTarifa = $this->tipoTarifa($inscripcion);

        if (! $estudiante || ! $tipoColegio || $tipoTarifa === '0') {
            return collect();
        }

        $tarifasBase = Tarifa::where([
            ['periodos_id', $periodo->id],
            ['modalidad', $inscripcion->modalidad],
            ['tipo_estudiante', $tipoTarifa],
            ['tipo_colegios_id', $tipoColegio],
            ['estado', '1']
        ])
            ->whereIn('concepto_pagos_id', [1, 2])
            ->get()
            ->keyBy(function ($tarifa) {
                return (int) $tarifa->concepto_pagos_id;
            });

        $tarifaInscripcion = $tarifasBase->get(1);
        $tarifaMensual = $tarifasBase->get(2);

        if (! $tarifaInscripcion || ! $tarifaMensual) {
            return collect();
        }

        $saldoPagado = (float) InscripcionPago::where('inscripciones_id', $inscripcion->id)
            ->where('concepto_pagos_id', '!=', '3')
            ->sum('monto');
        $saldoMora = (float) InscripcionPago::where([
            ['inscripciones_id', $inscripcion->id],
            ['concepto_pagos_id', '3']
        ])->sum('monto');
        $tarifario = collect();

        for ($nroCuota = 0; $nroCuota <= 4; $nroCuota++) {
            $monto = (float) ($nroCuota === 0 ? $tarifaInscripcion->importe : $tarifaMensual->importe);
            $pagado = min($saldoPagado, $monto);
            $saldoPagado = max(0, $saldoPagado - $pagado);
            $mora = $nroCuota === 0 ? 0 : min($saldoMora, 30);
            $saldoMora = max(0, $saldoMora - $mora);

            $tarifario->push((object) [
                'periodos_id' => $periodo->id,
                'monto' => number_format($monto, 2, '.', ''),
                'pagado' => number_format($pagado, 2, '.', ''),
                'mora' => number_format($mora, 2, '.', ''),
                'nro_cuota' => $nroCuota,
                'modalidad' => $inscripcion->modalidad,
                'tipo_estudiante' => $inscripcion->tipo_estudiante,
                'estudiantes_id' => $idEstudiante,
            ]);
        }

        if ($saldoPagado > 0 && $tarifario->isNotEmpty()) {
            $ultimaTarifa = $tarifario->last();
            $ultimaTarifa->pagado = number_format((float) $ultimaTarifa->pagado + $saldoPagado, 2, '.', '');
        }

        return $tarifario;
    }


    public function index()
    {
        $idEstudiante = Auth::user()->id;

        $periodo = Periodo::actual();
        $inscripcion = Inscripciones::actualDelEstudiante($idEstudiante, optional($periodo)->id);

        if (! $periodo || ! $inscripcion) {
            return Inertia::render('Estudiante/Pago', ["data" => $this->respuestaPagoVacia()]);
        }

        // $estudiante = $inscripcion->estudiante()->with('colegio')->first();
        // $response['total_pagado'] = InscripcionPago::where([['inscripciones_id', $inscripcion->id], ['concepto_pagos_id', '!=', '3']])->sum('monto');
        $response['cronograma'] = CronogramaPago::select(
            "nro_cuota",
            DB::raw("DATE_FORMAT(inicio,'%d/%m/%Y') as inicio"),
            DB::raw("DATE_FORMAT(fin,'%d/%m/%Y') as fin")
        )
            ->where([
                ['estado', '1'],
                ['periodos_id', $periodo->id]
            ])
            ->first();

        if (! $response['cronograma']) {
            $response = $this->respuestaPagoVacia();
            $response['message'] = $this->mensajeCronogramaActivo();

            return Inertia::render('Estudiante/Pago', ["data" => $response]);
        }

        $descuento = '0';
        switch ($inscripcion->tipo_estudiante) {
            case '1':
                $descuento = 'Normal (sin descuento)';
                break;
            case '2':
                $descuento = 'Hijo de Trabajador';
                break;
            case '3':
                $descuento = 'Descuento Trabajador';
                break;
            case '4':
                $descuento = 'Descuento por Hermanos';
                break;
            case '6':
                $descuento = 'Descuento por Servicio Militar';
                break;
            default:
                $descuento = '';
                break;
        }
        $tarifario = $this->tarifarioPeriodoActual($idEstudiante, $periodo, $inscripcion);
        $tarifaEstudiante = $tarifario->filter(function ($tarifa) use ($response) {
            return (int) $tarifa->nro_cuota <= (int) $response['cronograma']->nro_cuota;
        });
        $deuda = 0;
        foreach ($tarifaEstudiante as $key => $value) {
            $pendiente = max(0, (float) $value->monto - (float) $value->pagado);

            if ($pendiente <= 0) {
                continue;
            }

            if ((int) $value->nro_cuota === 0) {
                $deuda += $pendiente;
            } else {
                $crono = CronogramaPago::where([
                    ["periodos_id", $periodo->id],
                    ["nro_cuota", $value->nro_cuota]
                ])->first();

                if (! $crono || date('Y-m-d') <= date("Y-m-d", strtotime($crono->fin))) {
                    $deuda += $pendiente;
                } else {
                    $deuda += $pendiente + max(0, 30 - (float) $value->mora);
                }
            }
        }
        $response['deuda'] = number_format($deuda, 2);
        $response['tipo_descuento'] = $descuento;
        $response['vouchers'] = $this->getVouchersPago();
        $response['tarifario'] = $tarifario;
        $response['url'] = config('app.external_image_url');
        $estudiante = Estudiante::where('estudiantes.id', $idEstudiante)->first();

        $puntajesPath = public_path('data_puntaje.json');
        $json = is_file($puntajesPath) ? file_get_contents($puntajesPath) : false;
        $obj = $json === false ? [] : (json_decode($json) ?: []);
        $key = array_search($estudiante->nro_documento, array_column($obj, 'user'), true);
        // dd();
        if ($key === false) {
            $response["simulacro"] = false;
            $response["usuario"] = "";
            $response["puntaje"] = "";
            $response["carrera"] = "";
            // $response["password"] = "";
        } else {
            $response["simulacro"] = true;
            $response["usuario"] = $obj[$key]->user;
            $response["puntaje"] = $obj[$key]->puntaje;
            $response["carrera"] = $obj[$key]->carrera;
            // $response["password"] = $obj[$key]->pass;
        }

        return Inertia::render('Estudiante/Pago', ["data" => $response]);
    }
    public function getResumenPago()
    {
        $idEstudiante = Auth::user()->id;
        $periodo = Periodo::actual();
        $inscripcion = Inscripciones::actualDelEstudiante($idEstudiante, optional($periodo)->id);

        if (! $periodo || ! $inscripcion) {
            return [
                'total_pagado' => 0,
                'cronograma' => null,
                'total_pagar' => 0,
                'status' => false,
                'message' => $this->mensajeInscripcionActiva(),
            ];
        }

        $response['total_pagado'] = InscripcionPago::where([['inscripciones_id', $inscripcion->id], ['concepto_pagos_id', '!=', '3']])->sum('monto');
        $response['cronograma'] = CronogramaPago::select('nro_cuota')
            ->where([
                ['estado', '1'],
                ['periodos_id', $periodo->id]
            ])
            ->first();

        if (! $response['cronograma']) {
            return [
                'total_pagado' => $response['total_pagado'],
                'cronograma' => null,
                'total_pagar' => 0,
                'status' => false,
                'message' => $this->mensajeCronogramaActivo(),
            ];
        }

        $tarifario = $this->tarifarioPeriodoActual($idEstudiante, $periodo, $inscripcion);
        $response['total_pagar'] = $tarifario
            ->filter(function ($tarifa) use ($response) {
                return (int) $tarifa->nro_cuota <= (int) $response['cronograma']->nro_cuota;
            })
            ->sum(function ($tarifa) {
                return (float) $tarifa->monto;
            });

        return $response;
    }
    public function getVouchersPago()
    {
        $idEstudiante = Auth::user()->id;
        $inscripcion = Inscripciones::query()
            ->delEstudiante($idEstudiante)
            ->delPeriodoActual()
            ->latest('id')
            ->first();

        if (! $inscripcion) {
            return [];
        }

        $pagos = $inscripcion->pago()->get();

        return $pagos;
    }
    protected function tablaTieneColumna(string $tabla, string $columna): bool
    {
        static $columnas = [];
        $clave = $tabla . '.' . $columna;

        if (! array_key_exists($clave, $columnas)) {
            $columnas[$clave] = Schema::hasColumn($tabla, $columna);
        }

        return $columnas[$clave];
    }

    protected function normalizarDocumentoBanco($documento): string
    {
        $documento = preg_replace('/\\D+/', '', (string) $documento);

        return str_pad((string) $documento, 15, '0', STR_PAD_LEFT);
    }

    protected function normalizarSecuenciaBanco($secuencia): string
    {
        return (string) preg_replace('/\\D+/', '', (string) $secuencia);
    }

    protected function consultaCoincidenciaBanco($documento, $secuencia, $monto, $fecha)
    {
        $documentoBanco = $this->normalizarDocumentoBanco($documento);
        $secuenciaBanco = $this->normalizarSecuenciaBanco($secuencia);
        $secuenciaPagalo = substr($secuenciaBanco, -6);

        $query = BancoPago::query()
            ->where('num_doc', $documentoBanco)
            ->whereDate('fch_pag', $fecha)
            ->where('imp_pag', round((float) $monto, 2))
            ->where('concepto', self::BANCO_CONCEPTO_CEPREUNA)
            ->where(function ($query) use ($secuenciaBanco, $secuenciaPagalo) {
                $query->where('secuencia', $secuenciaBanco)
                    ->orWhere(function ($query) use ($secuenciaPagalo) {
                        $query->where('cod_age', self::BANCO_AGENCIA_PAGALO)
                            ->whereRaw('SUBSTRING(secuencia, 2, 6) = ?', [$secuenciaPagalo]);
                    });
            });

        return $query;
    }

    protected function buscarPagoBancoDisponible($documento, $secuencia, $monto, $fecha): ?BancoPago
    {
        $secuenciaBanco = $this->normalizarSecuenciaBanco($secuencia);
        $query = $this->consultaCoincidenciaBanco($documento, $secuencia, $monto, $fecha);

        if ($this->tablaTieneColumna('banco_pagos', 'fecha_usado')) {
            $query->whereNull('fecha_usado');
        }

        if ($this->tablaTieneColumna('banco_pagos', 'estado')) {
            $query->where(function ($query) {
                $query->whereNull('estado')->orWhere('estado', '<>', '2');
            });
        }

        return $query
            ->orderByRaw('(secuencia = ?) DESC', [$secuenciaBanco])
            ->orderBy('fch_pag')
            ->orderBy('id')
            ->first();
    }

    protected function bancoPagoDisponible(BancoPago $bancoPago): bool
    {
        if ($this->tablaTieneColumna('banco_pagos', 'fecha_usado') && ! empty($bancoPago->fecha_usado)) {
            return false;
        }

        return ! $this->tablaTieneColumna('banco_pagos', 'estado')
            || (string) $bancoPago->estado !== '2';
    }

    protected function buscarPagoAsociadoBanco(BancoPago $bancoPago, $documento, bool $bloquear = false): ?Pago
    {
        if ($this->tablaTieneColumna('pagos', 'banco_pagos_id')) {
            $query = Pago::where('banco_pagos_id', $bancoPago->id);

            if ($bloquear) {
                $query->lockForUpdate();
            }

            $pago = $query->first();
            if ($pago) {
                return $pago;
            }
        }

        $secuencia = $this->normalizarSecuenciaBanco($bancoPago->secuencia);
        $secuenciaCorta = substr($secuencia, -6);
        $documentoSinRelleno = preg_replace('/\\D+/', '', (string) $documento);
        $query = Pago::query()
            ->whereIn('nro_documento', [
                $documentoSinRelleno,
                $this->normalizarDocumentoBanco($documento),
            ])
            ->whereDate('fecha', $bancoPago->fch_pag)
            ->where('monto', round((float) $bancoPago->imp_pag, 2))
            ->where(function ($query) use ($secuencia, $secuenciaCorta) {
                $query->where('secuencia', $secuencia)
                    ->orWhere('secuencia', $secuenciaCorta);
            });

        if ($bloquear) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    protected function pagoPerteneceAlEstudiante(Pago $pago, int $estudianteId, int $periodoId, $documento): bool
    {
        if (
            $this->tablaTieneColumna('pagos', 'estudiantes_id')
            && $pago->estudiantes_id !== null
            && (int) $pago->estudiantes_id !== $estudianteId
        ) {
            return false;
        }

        if (
            $this->tablaTieneColumna('pagos', 'periodos_id')
            && $pago->periodos_id !== null
            && (int) $pago->periodos_id !== $periodoId
        ) {
            return false;
        }

        return $this->normalizarDocumentoBanco($pago->nro_documento)
            === $this->normalizarDocumentoBanco($documento);
    }

    protected function respuestaPagoValidado(Pago $pago, string $message = 'Pago validado correctamente.'): array
    {
        return [
            'message' => $message,
            'status' => true,
            'token' => $pago->token,
            'monto' => $pago->monto,
            'secuencia' => $pago->secuencia,
            'fecha' => $pago->fecha,
        ];
    }

    protected function eliminarVoucherTemporal(?string $voucher): void
    {
        if (! $voucher) {
            return;
        }

        try {
            $this->pagoArchivos->eliminarVoucher($voucher);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function buscarBancoDelPago(Pago $pago, $documento, bool $bloquear = false): ?BancoPago
    {
        if ($this->tablaTieneColumna('pagos', 'banco_pagos_id') && $pago->banco_pagos_id) {
            $query = BancoPago::query()
                ->whereKey($pago->banco_pagos_id)
                ->where('num_doc', $this->normalizarDocumentoBanco($documento));

            if ($bloquear) {
                $query->lockForUpdate();
            }

            return $query->first();
        }

        $secuencia = $this->normalizarSecuenciaBanco($pago->secuencia);
        $secuenciaCorta = substr($secuencia, -6);
        $query = BancoPago::query()
            ->where('num_doc', $this->normalizarDocumentoBanco($documento))
            ->whereDate('fch_pag', $pago->fecha)
            ->where('imp_pag', round((float) $pago->monto, 2))
            ->where('concepto', self::BANCO_CONCEPTO_CEPREUNA)
            ->where(function ($query) use ($secuencia, $secuenciaCorta) {
                $query->where('secuencia', $secuencia)
                    ->orWhere(function ($query) use ($secuenciaCorta) {
                        $query->where('cod_age', self::BANCO_AGENCIA_PAGALO)
                            ->whereRaw('SUBSTRING(secuencia, 2, 6) = ?', [$secuenciaCorta]);
                    });
            })
            ->orderBy('fch_pag')
            ->orderBy('id');

        if ($bloquear) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    protected function montoBancoAplicable(BancoPago $bancoPago): float
    {
        $comision = (string) $bancoPago->cuenta === self::BANCO_CUENTA_SIN_COMISION ? 0 : 1;

        return max(0, round((float) $bancoPago->imp_pag - $comision, 2));
    }

    protected function distribuirPagoEnTarifas(int $estudianteId, int $periodoId, float $monto, $fechaPago): void
    {
        $tarifas = TarifaEstudiante::where([
            ['estudiantes_id', $estudianteId],
            ['periodos_id', $periodoId],
        ])
            ->whereColumn('monto', '!=', 'pagado')
            ->orderBy('nro_cuota')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $restante = $monto;

        foreach ($tarifas as $tarifa) {
            if ($restante <= 0) {
                break;
            }

            $deuda = max(0, round((float) $tarifa->monto - (float) $tarifa->pagado, 2));
            if ($deuda <= 0) {
                continue;
            }

            $cronograma = CronogramaPago::where([
                ['periodos_id', $periodoId],
                ['nro_cuota', $tarifa->nro_cuota],
            ])->first();
            $fueraDeFecha = $cronograma
                && $cronograma->fin
                && strtotime((string) $fechaPago) > strtotime((string) $cronograma->fin);

            if ($fueraDeFecha) {
                $moraAnterior = (float) $tarifa->mora;

                if ($restante >= $deuda + 30) {
                    $tarifa->mora = 30;
                    $tarifa->pagado = $tarifa->monto;
                    $restante = $restante - ($deuda + 30) + $moraAnterior;
                } else {
                    if ($restante >= 30) {
                        $tarifa->mora = 30;
                        $tarifa->pagado = (float) $tarifa->pagado + ($restante - 30) + $moraAnterior;
                    } else {
                        $tarifa->pagado = (float) $tarifa->pagado + $restante;
                    }

                    $restante = 0;
                }
            } elseif ($restante >= $deuda) {
                $tarifa->pagado = $tarifa->monto;
                $restante -= $deuda;
            } else {
                $tarifa->pagado = (float) $tarifa->pagado + $restante;
                $restante = 0;
            }

            $tarifa->save();
        }

        if ($restante <= 0 || $tarifas->isEmpty()) {
            return;
        }

        $ultimaTarifa = TarifaEstudiante::where([
            ['estudiantes_id', $estudianteId],
            ['periodos_id', $periodoId],
            ['nro_cuota', 4],
        ])->orderBy('id')->lockForUpdate()->first();

        if ($ultimaTarifa) {
            $ultimaTarifa->pagado = (float) $ultimaTarifa->pagado + $restante;
            $ultimaTarifa->save();
        }
    }

    public function validarPagoCuota(Request $request)
    {
        $idEstudiante = (int) Auth::id();
        $inscripcion = Inscripciones::query()
            ->delEstudiante($idEstudiante)
            ->delPeriodoActual()
            ->latest('id')
            ->first();

        if (! $inscripcion) {
            return response()->json($this->respuestaOperacionFallida($this->mensajeInscripcionActiva()));
        }

        $request->validate([
            'secuencia' => ['required', 'string', 'max:50'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'fecha' => ['required', 'date', 'after:2020-12-14', 'date_format:Y-m-d'],
            'pagarEnPagalo' => ['nullable'],
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

        $estudiante = $inscripcion->estudiante()->first();
        if (! $estudiante || ! preg_replace('/\\D+/', '', (string) $estudiante->nro_documento)) {
            return response()->json($this->respuestaOperacionFallida(
                'No se encontro el documento del estudiante para validar el pago.'
            ));
        }

        $documento = (string) $estudiante->nro_documento;
        $bancoPago = $this->buscarPagoBancoDisponible(
            $documento,
            $request->input('secuencia'),
            $request->input('monto'),
            $request->input('fecha')
        );

        if (! $bancoPago) {
            $coincidencia = $this->consultaCoincidenciaBanco(
                $documento,
                $request->input('secuencia'),
                $request->input('monto'),
                $request->input('fecha')
            )->orderBy('id')->first();

            $message = $coincidencia
                ? 'El pago ya fue utilizado anteriormente.'
                : 'No se encontro un pago con esos datos. Verifique la secuencia, el monto, la fecha y que el reporte del banco ya haya sido cargado.';

            return response()->json($this->respuestaOperacionFallida($message));
        }

        $pagoExistente = $this->buscarPagoAsociadoBanco($bancoPago, $documento);
        if ($pagoExistente) {
            if (! $this->pagoPerteneceAlEstudiante(
                $pagoExistente,
                $idEstudiante,
                (int) $inscripcion->periodos_id,
                $documento
            )) {
                return response()->json($this->respuestaOperacionFallida(
                    'El pago ya fue utilizado por otra inscripcion.'
                ));
            }

            if ((string) $pagoExistente->estado === '1') {
                return response()->json($this->respuestaPagoValidado($pagoExistente, 'Pago validado.'));
            }

            return response()->json($this->respuestaOperacionFallida(
                'El pago ya ha sido registrado anteriormente.'
            ));
        }

        try {
            $voucherAdjunto = $this->pagoArchivos->guardarVoucher($request->file('file'));
        } catch (\Throwable $e) {
            report($e);

            return response()->json($this->respuestaOperacionFallida(
                'No se pudo guardar el comprobante. Intentelo nuevamente en unos minutos.'
            ));
        }

        try {
            $resultado = DB::transaction(function () use (
                $bancoPago,
                $documento,
                $idEstudiante,
                $inscripcion,
                $voucherAdjunto
            ) {
                $bancoBloqueado = BancoPago::whereKey($bancoPago->id)->lockForUpdate()->first();

                if (! $bancoBloqueado || ! $this->bancoPagoDisponible($bancoBloqueado)) {
                    throw new \RuntimeException('El pago acaba de ser utilizado en otra operacion.');
                }

                $existente = $this->buscarPagoAsociadoBanco($bancoBloqueado, $documento, true);
                if ($existente) {
                    if (
                        ! $this->pagoPerteneceAlEstudiante(
                            $existente,
                            $idEstudiante,
                            (int) $inscripcion->periodos_id,
                            $documento
                        )
                        || (string) $existente->estado !== '1'
                    ) {
                        throw new \RuntimeException('El pago ya fue utilizado anteriormente.');
                    }

                    return ['pago' => $existente, 'creado' => false];
                }

                $nuevoPago = new Pago();
                if ($this->tablaTieneColumna('pagos', 'periodos_id')) {
                    $nuevoPago->periodos_id = $inscripcion->periodos_id;
                }
                if ($this->tablaTieneColumna('pagos', 'estudiantes_id')) {
                    $nuevoPago->estudiantes_id = $idEstudiante;
                }
                if ($this->tablaTieneColumna('pagos', 'concepto_pagos_id')) {
                    $nuevoPago->concepto_pagos_id = 1;
                }
                if ($this->tablaTieneColumna('pagos', 'banco_pagos_id')) {
                    $nuevoPago->banco_pagos_id = $bancoBloqueado->id;
                }
                if ($this->tablaTieneColumna('pagos', 'procedencia')) {
                    $nuevoPago->procedencia = '1';
                }

                $nuevoPago->monto = round((float) $bancoBloqueado->imp_pag, 2);
                $nuevoPago->secuencia = $bancoBloqueado->secuencia;
                $nuevoPago->fecha = $bancoBloqueado->fch_pag;
                $nuevoPago->nro_documento = preg_replace('/\\D+/', '', $documento);
                $nuevoPago->tipo_pago = $this->tipoTarifa($inscripcion);
                $nuevoPago->estado = '1';
                $nuevoPago->token = Str::random(40) . 'b' . time();
                $nuevoPago->voucher = $voucherAdjunto;
                $nuevoPago->save();

                return ['pago' => $nuevoPago, 'creado' => true];
            });

            if (! $resultado['creado']) {
                $this->eliminarVoucherTemporal($voucherAdjunto);
            }

            return response()->json($this->respuestaPagoValidado($resultado['pago']));
        } catch (\RuntimeException $e) {
            $this->eliminarVoucherTemporal($voucherAdjunto);

            return response()->json($this->respuestaOperacionFallida($e->getMessage()));
        } catch (\Throwable $e) {
            $this->eliminarVoucherTemporal($voucherAdjunto);
            report($e);

            return response()->json($this->respuestaOperacionFallida(
                'Error al validar el pago. Intentelo nuevamente.'
            ));
        }
    }

    public function registrarPagoCuota(Request $request)
    {
        $request->validate([
            'tokens' => ['required', 'array', 'min:1'],
            'tokens.*' => ['required', 'string', 'max:100'],
        ]);

        $idEstudiante = (int) Auth::id();
        $inscripcion = Inscripciones::query()
            ->delEstudiante($idEstudiante)
            ->delPeriodoActual()
            ->latest('id')
            ->first();

        if (! $inscripcion) {
            return response()->json($this->respuestaOperacionFallida($this->mensajeInscripcionActiva()));
        }

        $estudiante = $inscripcion->estudiante()->first();
        if (! $estudiante) {
            return response()->json($this->respuestaOperacionFallida(
                'No se encontro al estudiante de la inscripcion activa.'
            ));
        }

        $documento = (string) $estudiante->nro_documento;
        $tokens = array_values(array_unique($request->input('tokens', [])));

        try {
            DB::transaction(function () use ($tokens, $idEstudiante, $inscripcion, $documento) {
                foreach ($tokens as $token) {
                    $pago = Pago::where('token', $token)->lockForUpdate()->first();

                    if (! $pago || ! $this->pagoPerteneceAlEstudiante(
                        $pago,
                        $idEstudiante,
                        (int) $inscripcion->periodos_id,
                        $documento
                    )) {
                        throw new \RuntimeException('No se encontro un pago valido para esta inscripcion.');
                    }

                    if ((string) $pago->estado !== '1') {
                        throw new \RuntimeException('El pago ya ha sido registrado anteriormente.');
                    }

                    $bancoPago = $this->buscarBancoDelPago($pago, $documento, true);
                    if (! $bancoPago) {
                        throw new \RuntimeException(
                            'No se encontro el pago bancario o no pertenece al estudiante.'
                        );
                    }

                    if (! $this->bancoPagoDisponible($bancoPago)) {
                        throw new \RuntimeException('El pago bancario ya fue utilizado anteriormente.');
                    }

                    if (InscripcionPago::where('pagos_id', $pago->id)->exists()) {
                        throw new \RuntimeException('El pago ya se encuentra asociado a una inscripcion.');
                    }

                    $montoAplicable = $this->montoBancoAplicable($bancoPago);
                    if ($montoAplicable <= 0) {
                        throw new \RuntimeException('El monto del pago no es valido.');
                    }

                    $inscripcionPago = new InscripcionPago();
                    $inscripcionPago->monto = $montoAplicable;
                    $inscripcionPago->inscripciones_id = $inscripcion->id;
                    $inscripcionPago->pagos_id = $pago->id;
                    $inscripcionPago->concepto_pagos_id = 2;
                    if ($this->tablaTieneColumna('inscripcion_pagos', 'periodos_id')) {
                        $inscripcionPago->periodos_id = $inscripcion->periodos_id;
                    }
                    $inscripcionPago->save();

                    $this->distribuirPagoEnTarifas(
                        $idEstudiante,
                        (int) $inscripcion->periodos_id,
                        $montoAplicable,
                        $bancoPago->fch_pag
                    );

                    if ($this->tablaTieneColumna('pagos', 'periodos_id')) {
                        $pago->periodos_id = $inscripcion->periodos_id;
                    }
                    if ($this->tablaTieneColumna('pagos', 'estudiantes_id')) {
                        $pago->estudiantes_id = $idEstudiante;
                    }
                    if ($this->tablaTieneColumna('pagos', 'concepto_pagos_id')) {
                        $pago->concepto_pagos_id = 1;
                    }
                    if ($this->tablaTieneColumna('pagos', 'banco_pagos_id')) {
                        $pago->banco_pagos_id = $bancoPago->id;
                    }
                    if ($this->tablaTieneColumna('pagos', 'procedencia')) {
                        $pago->procedencia = '1';
                    }
                    $pago->estado = '2';
                    $pago->save();

                    $bancoPago->estado = '2';
                    if ($this->tablaTieneColumna('banco_pagos', 'fecha_usado')) {
                        $bancoPago->fecha_usado = now();
                    }
                    if ($this->tablaTieneColumna('banco_pagos', 'estudiantes_id')) {
                        $bancoPago->estudiantes_id = $idEstudiante;
                    }
                    $bancoPago->save();
                }
            });

            return response()->json([
                'message' => 'Pago registrado correctamente.',
                'status' => true,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json($this->respuestaOperacionFallida($e->getMessage()));
        } catch (\Throwable $e) {
            report($e);

            return response()->json($this->respuestaOperacionFallida(
                'Error al registrar el pago. Intentelo nuevamente.'
            ));
        }
    }

    public function registrarPagoCuotaMora(Request $request)
    {
        // dd($request->all());
        // dd(strtotime("2022-05-20"));
        $idEstudiante = Auth::user()->id;
        $periodo = Periodo::actual();
        $inscripcion = Inscripciones::actualDelEstudiante($idEstudiante, optional($periodo)->id);

        if (! $periodo || ! $inscripcion) {
            return response()->json($this->respuestaOperacionFallida($this->mensajeInscripcionActiva()));
        }

        $estudiante = $inscripcion->estudiante()->with('colegio')->first();
        $totalPagado = InscripcionPago::where('inscripciones_id', $inscripcion->id)->sum('monto');
        $cronograma = CronogramaPago::select('nro_cuota', 'fin')
            ->where([
                ['estado', '1'],
                ['periodos_id', $periodo->id]
            ])
            ->first();

        if (! $cronograma) {
            return response()->json($this->respuestaOperacionFallida($this->mensajeCronogramaActivo()));
        }

        $tarifaMora = Tarifa::where('concepto_pagos_id', '3')->first();

        $tokens = $request->tokens;
        $pagoExistente = true;
        $documentoValidado = true;
        $sumaPagoDB = 0;
        $cont = 0;
        $validarPago = 0;
        $comisionBanco = 0;
        $validarFecha = true;
        if (isset($tokens)) {
            while ($cont < count($tokens)) {

                $validarPago = Pago::where('token', $tokens[$cont])->first();
                $comisionBanco = $comisionBanco + 1;

                if (empty($validarPago)) {
                    $pagoExistente = false;
                } else {
                    if ($validarPago->estado == '1') {
                        $sumaPagoDB = $sumaPagoDB + $validarPago->monto;
                        // validar pago con el numero de documento del estudiante
                        $validarDocumento = BancoPago::where([
                            ["secuencia", $validarPago->secuencia],
                            ["imp_pag", $validarPago->monto],
                            ["fch_pag", $validarPago->fecha],
                            ["num_doc", str_pad($estudiante->nro_documento, 15, '0', STR_PAD_LEFT)],
                        ])
                            ->first();
                        if (empty($validarDocumento)) {
                            $documentoValidado = false;
                        } else {
                            // dd(strtotime($validarDocumento->fecha) > strtotime($cronograma->fin));
                            if (strtotime($validarDocumento->fch_pag) > strtotime($cronograma->fin)) {
                                // dd(strtotime($validarDocumento->fecha) > strtotime($cronograma->fin));
                                $validarFecha = false;
                            }
                        }
                    } else {
                        $pagoExistente = false;
                    }
                }
                $cont = $cont + 1;
            }
        } else {
            $pagoExistente = false;
        }

        // total a pagar hasta la cuota actual
        $descuento = '0';
        switch ($inscripcion->tipo_estudiante) {
            case '1':
                $descuento = '1';
                break;
            case '2':
                $descuento = '2';
                break;
            case '3':
                $descuento = '2';
                break;
            case '4':
                $descuento = '2';
                break;
            case '6':
                $descuento = '2';
                break;
            default:
                $descuento = '0';
                break;
        }

        $tarifaInscripcion = Tarifa::where([
            ['modalidad', $inscripcion->modalidad],
            ['concepto_pagos_id', '1'],
            ['tipo_estudiante', $descuento]
        ])->first();

        $tarifaMensual = Tarifa::where([
            ['modalidad', $inscripcion->modalidad],
            ['concepto_pagos_id', '2'],
            ['tipo_estudiante', $descuento]
        ])->first();

        if ($validarFecha) {
            $totalPagar = floatVal($tarifaInscripcion->importe) + floatVal($tarifaMensual->importe * $cronograma->nro_cuota);
            $importeActual = $sumaPagoDB - $comisionBanco;

            if ($pagoExistente) {
                if ($documentoValidado) {
                    if (round($totalPagado + $importeActual, 2) >= $totalPagar) {
                        DB::beginTransaction();
                        try {
                            $cont = 0;
                            while ($cont < count($tokens)) {
                                $pago = Pago::where('token', $tokens[$cont])->first();

                                $pago = Pago::find($pago->id);
                                $pago->estado = '2';
                                $pago->save();

                                $pagoSinComision = round($pago->monto - 1, 2);

                                $mensualPago = new InscripcionPago();
                                $mensualPago->monto = $pagoSinComision;
                                $mensualPago->inscripciones_id = $inscripcion->id;
                                $mensualPago->pagos_id = $pago->id;
                                $mensualPago->concepto_pagos_id = 2;
                                $mensualPago->save();

                                $cont = $cont + 1;
                            }
                            DB::commit();
                            $message = 'Pago registrado correctamente.';
                            $status = true;
                            $error = '';
                        } catch (\Exception $e) {
                            DB::rollback();
                            $message = 'Error al registrar pago, intentelo nuevamante.';
                            $status = false;
                            $error = $e;
                        }
                        $response = array(
                            "message" => $message,
                            "status" => $status,
                            "error" => $error
                        );
                    } else {
                        $response = array(
                            "message" => '* El monto total de pago es menor al monto total a pagar.',
                            "status" => false,
                        );
                    }
                } else {
                    $response = array(
                        "message" => '* Error al validar pago, Ud. esta intentando ingresar un pago que no esta a su nombre.',
                        "status" => false,
                    );
                }
            } else {
                $response = array(
                    "message" => '* No se encontraron pagos.',
                    "status" => false,
                );
            }
        } else {
            $totalPagar = floatVal($tarifaInscripcion->importe) + floatVal($tarifaMensual->importe * $cronograma->nro_cuota) + floatVal($tarifaMora->importe);
            $importeActual = $sumaPagoDB - $comisionBanco;

            if ($pagoExistente) {
                if ($documentoValidado) {
                    if (round($totalPagado + $importeActual, 2) >= $totalPagar) {
                        DB::beginTransaction();
                        try {
                            $cont = 0;
                            while ($cont < count($tokens)) {
                                $pago = Pago::where('token', $tokens[$cont])->first();

                                $pago = Pago::find($pago->id);
                                $pago->estado = '2';
                                $pago->save();
                                if ($cont == 0) {
                                    $pagoSinComision = round($pago->monto - 1, 2) - floatVal($tarifaMora->importe);
                                    $mora = new InscripcionPago();
                                    $mora->monto = 30.00;
                                    $mora->inscripciones_id = $inscripcion->id;
                                    $mora->pagos_id = $pago->id;
                                    $mora->concepto_pagos_id = 3;
                                    $mora->save();
                                } else {
                                    $pagoSinComision = round($pago->monto - 1, 2);
                                }
                                $mensualPago = new InscripcionPago();
                                $mensualPago->monto = $pagoSinComision;
                                $mensualPago->inscripciones_id = $inscripcion->id;
                                $mensualPago->pagos_id = $pago->id;
                                $mensualPago->concepto_pagos_id = 2;
                                $mensualPago->save();

                                $cont = $cont + 1;
                            }
                            DB::commit();
                            $message = 'Pago registrado correctamente.';
                            $status = true;
                            $error = '';
                        } catch (\Exception $e) {
                            DB::rollback();
                            $message = 'Error al registrar pago, intentelo nuevamante.';
                            $status = false;
                            $error = $e;
                        }
                        $response = array(
                            "message" => $message,
                            "status" => $status,
                            "error" => $error
                        );
                    } else {
                        $response = array(
                            "message" => '* El monto total de pago es menor al monto total a pagar.',
                            "status" => false,
                        );
                    }
                } else {
                    $response = array(
                        "message" => '* Error al validar pago, Ud. esta intentando ingresar un pago que no esta a su nombre.',
                        "status" => false,
                    );
                }
            } else {
                $response = array(
                    "message" => '* No se encontraron pagos.',
                    "status" => false,
                );
            }
        }
        return response()->json($response);
    }
}
