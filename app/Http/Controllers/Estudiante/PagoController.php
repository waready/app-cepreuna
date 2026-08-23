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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PagoController extends Controller
{
    private $dateTime;
    private $dateTimePartial;

    public function __construct()
    {
        // $this->middleware('auth:estudiante');
        date_default_timezone_set("America/Lima"); //Zona horaria de Peru
        $this->dateTime = date("Y-m-d H:i:s");
        $this->dateTimePartial = date("m-Y");
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
        $tarifaEstudiante = TarifaEstudiante::where([["estudiantes_id", $idEstudiante], ["nro_cuota", "<=", $response['cronograma']->nro_cuota]])->get();
        $deuda = 0;
        foreach ($tarifaEstudiante as $key => $value) {

            if ($value->nro_cuota == 0) {
                $deuda = $deuda + $value->monto - $value->pagado;
            } else {

                if ($value->monto - $value->pagado <= 0) {
                    $deuda = 0;
                } else {
                    $crono = CronogramaPago::where("nro_cuota", $value->nro_cuota)->first();
                    if (date('Y-m-d') <= date("Y-m-d", strtotime($crono->fin))) {
                        $deuda = $deuda + $value->monto - $value->pagado;
                    } else {
                        $deuda = $deuda + $value->monto - $value->pagado + 30.00 - $value->mora;
                    }
                }
            }
        }
        $response['deuda'] = number_format($deuda, 2);
        $response['tipo_descuento'] = $descuento;
        $response['vouchers'] = $this->getVouchersPago();
        $response['tarifario'] = $tarifaEstudiante = TarifaEstudiante::where("estudiantes_id", $idEstudiante)->get();
        $response['url'] = config('app.external_image_url');
        $estudiante = Estudiante::where('estudiantes.id', $idEstudiante)->first();

        $json = file_get_contents('data_puntaje.json');
        $obj = json_decode($json);
        $key = array_search($estudiante->nro_documento, array_column($obj, 'user'));
        // dd();
        if (!$key) {
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

        $estudiante = $inscripcion->estudiante()->with('colegio')->first();
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
        // dd($descuento);
        if ($descuento == '0') {
            $response['total_pagar'] = 0;
        } else {
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

            $response['total_pagar'] = floatVal($tarifaInscripcion->importe) + floatVal($tarifaMensual->importe * $response['cronograma']->nro_cuota);
        }
        // dd($tarifaMensual->importe.'-'.$tarifaInscripcion->importe);
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
    public function validarPagoCuota(Request $request)
    {

        $idEstudiante = Auth::user()->id;
        $inscripcion = Inscripciones::query()
            ->delEstudiante($idEstudiante)
            ->delPeriodoActual()
            ->latest('id')
            ->first();

        if (! $inscripcion) {
            return response()->json($this->respuestaOperacionFallida($this->mensajeInscripcionActiva()));
        }

        $rules = $request->validate([
            'secuencia' => 'required',
            'monto' => 'required',
            // 'fecha' => 'required',
            'fecha' => 'required|date|after:2020-12-14|date_format:Y-m-d',
            'file' => 'required|mimes:pdf,jpg,jpeg,png|max:2000',
        ], $messages = [
            'required' => '* El campo es obligatorio.',
            'fecha.after' => '* Solo se admiten pagos desde el 15/12/2020.',
            'file.required' => '* El voucher es obligatorio',
            'file.mimes' => '* Solo se admiten formatos pdf,jpg,jpeg,png.',
            'file.max' => '* El peso maximo del archivo debe ser menor a 6 MB.'
        ]);
        // dd($request->all());

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
        // dd($request->file('file')->getClientOriginalExtension());
        $token = Str::random(40);

        // $tarifa = Tarifa::where('id',$request->tarifa)->first();

        $bancoPagoValidacion = BancoPago::where([
            ["secuencia", $request->secuencia],
            ["imp_pag", $request->monto],
            ["fch_pag", $request->fecha],
            ["concepto", '00000067']
        ])
            ->first();

        if ($bancoPagoValidacion->cuenta == '0701010736') {
            $pago = Pago::where([
                ["secuencia", $request->secuencia],
                ["monto", $request->monto],
                ["fecha", $request->fecha],
            ])->first();
        } else {
            $pago = Pago::where([
                ["secuencia", $request->secuencia],
                ["monto", $request->monto],
                ["fecha", $request->fecha],
                ["nro_documento", $request->documento],
            ])->first();
        }


        if (empty($pago)) {
            if ($bancoPagoValidacion->cuenta == '0701010736') {
                $bancoPago = BancoPago::where([
                    ["secuencia", $request->secuencia],
                    ["imp_pag", $request->monto],
                    ["fch_pag", $request->fecha],
                    ["concepto", '00000067']
                ])
                    ->first();
            } else {
                $bancoPago = BancoPago::where([
                    ["secuencia", $request->secuencia],
                    ["imp_pag", $request->monto],
                    ["fch_pag", $request->fecha],
                    ["num_doc", str_pad($request->documento, 15, '0', STR_PAD_LEFT)],
                    ["concepto", '00000067']
                ])
                    ->first();
            }


            if (empty($bancoPago)) {
                $response = array(
                    "message" => 'Datos invalidos o el concepto de pago no pertenece a  CEPREUNA, intentelo nuevamente.',
                    "status" => false,
                );
            } else {
                $voucherAdjunto = $this->save_file($request->file, $request->file('file')->getClientOriginalExtension());

                DB::beginTransaction();
                try {

                    $nuevoPago = new Pago();
                    $nuevoPago->monto = $request->monto;
                    $nuevoPago->secuencia = $request->secuencia;
                    $nuevoPago->fecha = $request->fecha;
                    $nuevoPago->nro_documento = $request->documento;
                    $nuevoPago->tipo_pago = $descuento;
                    $nuevoPago->token = $token . 'b' . time();
                    $nuevoPago->voucher = $voucherAdjunto;
                    $nuevoPago->save();

                    DB::commit();
                    $message = 'Pago validado correctamente.';
                    $status = true;
                    $token = $nuevoPago->token;
                    $monto = $nuevoPago->monto;
                    $secuencia = $nuevoPago->secuencia;
                    $fecha = $nuevoPago->fecha;
                } catch (\Exception $e) {
                    DB::rollback();
                    $message = 'Error al validar pago, intentelo nuevamente.';
                    $status = false;
                }

                if ($status == true) {
                    $response = array(
                        "message" => $message,
                        "status" => $status,
                        "token" => $token,
                        "monto" => $monto,
                        "secuencia" => $secuencia,
                        "fecha" => $fecha,
                    );
                } else {
                    $response = array(
                        "message" => $message,
                        "status" => $status,
                    );
                }
            }
        } else {
            if ($pago->estado == '1') {

                $response = array(
                    "message" => 'Pago validado.',
                    "status" => true,
                    "token" => $pago->token,
                    "monto" => $pago->monto,
                    "secuencia" => $pago->secuencia,
                    "fecha" => $pago->fecha,
                );
            } else {
                $response = array(
                    "message" => 'El pago ya ha sido registrado anteriormente.',
                    "status" => false,
                );
            }
        }


        return response()->json($response);
    }
    public function registrarPagoCuota(Request $request)
    {
        // dd($request->all());
        $idEstudiante = Auth::user()->id;
        $inscripcion = Inscripciones::query()
            ->delEstudiante($idEstudiante)
            ->delPeriodoActual()
            ->latest('id')
            ->first();

        if (! $inscripcion) {
            return response()->json($this->respuestaOperacionFallida($this->mensajeInscripcionActiva()));
        }

        $estudiante = $inscripcion->estudiante()->with('colegio')->first();

        $tokens = $request->tokens;

        $cont = 0;
        $validarPago = 0;
        $comisionBanco = 0;

        if (isset($tokens)) {
            while ($cont < count($tokens)) {

                $validarPago = Pago::where('token', $tokens[$cont])->first();
                $comisionBanco = $comisionBanco + 1;

                if (empty($validarPago)) {
                    $response = array(
                        "message" => '* No se encontraron pagos.',
                        "status" => false,
                    );
                } else {
                    if ($validarPago->estado == '1') {
                        // $sumaPagoDB = $sumaPagoDB + $validarPago->monto;
                        // validar pago con el numero de documento del estudiante
                        $bancoPagoValidacion = BancoPago::where([
                            ["secuencia", $validarPago->secuencia],
                            ["imp_pag", $validarPago->monto],
                            ["fch_pag", $validarPago->fecha],
                            ["concepto", '00000067']
                        ])
                            ->first();

                        if ($bancoPagoValidacion->cuenta == '0701010736') {
                            $validarDocumento = BancoPago::where([
                                ["secuencia", $validarPago->secuencia],
                                ["imp_pag", $validarPago->monto],
                                ["fch_pag", $validarPago->fecha],
                            ])
                                ->first();
                        } else {
                            $validarDocumento = BancoPago::where([
                                ["secuencia", $validarPago->secuencia],
                                ["imp_pag", $validarPago->monto],
                                ["fch_pag", $validarPago->fecha],
                                ["num_doc", str_pad($estudiante->nro_documento, 15, '0', STR_PAD_LEFT)],
                            ])
                                ->first();
                        }


                        if (empty($validarDocumento)) {
                            $response = array(
                                "message" => '* Error al validar pago, Ud. esta intentando ingresar un pago que no esta a su nombre.',
                                "status" => false,
                            );
                        } else {

                            DB::beginTransaction();
                            try {
                                $pago = Pago::find($validarPago->id);
                                $pago->estado = '2';
                                $pago->save();

                                $mensualPago = new InscripcionPago();
                                if ($bancoPagoValidacion->cuenta == '0701010736') {
                                    $mensualPago->monto = round($validarPago->monto, 2);
                                } else {
                                    $mensualPago->monto = round($validarPago->monto - 1, 2);
                                }
                                $mensualPago->inscripciones_id = $inscripcion->id;
                                $mensualPago->pagos_id = $validarPago->id;
                                $mensualPago->concepto_pagos_id = 2;
                                $mensualPago->save();
                                // ambas validaciones correctas
                                // SELECT * FROM tarifa_estudiantes AS te WHERE te.monto != te.pagado AND te.estudiantes_id=14 ORDER BY te.id ASC LIMIT 1;
                                $tarifaEstudiante = TarifaEstudiante::where([["estudiantes_id", $idEstudiante], [DB::raw("monto"), "!=", DB::raw("pagado")]])->orderBy("id", "asc")->get();
                                $deudaCuota = 0;
                                // $pagoActual =
                                if ($bancoPagoValidacion->cuenta == '0701010736') {
                                    $restoActual = $validarDocumento->imp_pag;
                                } else {
                                    $restoActual = $validarDocumento->imp_pag - 1;
                                }

                                foreach ($tarifaEstudiante as $key => $tarifa) {
                                    $deudaCuota = $tarifa->monto - $tarifa->pagado;
                                    // $pagar = $validarDocumento->imp_pag
                                    $crono = CronogramaPago::where("nro_cuota", $tarifa->nro_cuota)->first();
                                    if (strtotime($validarDocumento->fch_pag) > strtotime($crono->fin)) {

                                        if ($restoActual >= $deudaCuota + 30) {

                                            $storeTarifa = TarifaEstudiante::find($tarifa->id);
                                            $tmpMora = $storeTarifa->mora;
                                            $storeTarifa->mora = 30;
                                            $storeTarifa->pagado = $tarifa->monto;
                                            $storeTarifa->save();
                                            $restoActual = $restoActual - ($deudaCuota + 30) + $tmpMora;
                                        } else {
                                            $storeTarifa = TarifaEstudiante::find($tarifa->id);
                                            $tmpMora = $storeTarifa->mora;
                                            if ($restoActual >= 30) {
                                                $storeTarifa->mora = 30;
                                                $storeTarifa->pagado = $storeTarifa->pagado + ($restoActual - 30) + $tmpMora;
                                            } else {
                                                $storeTarifa->pagado = $storeTarifa->pagado + $restoActual;
                                            }

                                            $storeTarifa->save();
                                            $restoActual = 0;
                                        }
                                    } else {
                                        if ($restoActual >= $deudaCuota) {

                                            $storeTarifa = TarifaEstudiante::find($tarifa->id);
                                            // $storeTarifa->mora = 30;
                                            $storeTarifa->pagado = $tarifa->monto;
                                            $storeTarifa->save();
                                            $restoActual = $restoActual - $deudaCuota;
                                        } else {
                                            $storeTarifa = TarifaEstudiante::find($tarifa->id);
                                            $storeTarifa->pagado = $storeTarifa->pagado + $restoActual;
                                            $storeTarifa->save();
                                            $restoActual = 0;
                                        }
                                    }
                                }

                                if ($restoActual > 0) {
                                    $storeTarifa = TarifaEstudiante::where([["estudiantes_id", $idEstudiante], ["nro_cuota", 4]])->orderBy("id", "asc")->first();
                                    $storeTarifa->pagado = $storeTarifa->pagado + $restoActual;
                                    $storeTarifa->save();
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
                        }
                    } else {
                        $response = array(
                            "message" => '* No se encontraron pagos.',
                            "status" => false,
                        );
                    }
                }
                $cont = $cont + 1;
            }
        } else {

            $response = array(
                "message" => '* No se encontraron pagos.',
                "status" => false,
            );
        }

        // if (isset($tokens)) {
        //     while ($cont < count($tokens)) {

        //         $validarPago = Pago::where('token', $tokens[$cont])->first();
        //         $comisionBanco = $comisionBanco + 0.60;

        //         if (empty($validarPago)) {
        //             $pagoExistente = false;
        //         } else {
        //             if ($validarPago->estado == '1') {
        //                 $sumaPagoDB = $sumaPagoDB + $validarPago->monto;
        //                 // validar pago con el numero de documento del estudiante
        //                 $validarDocumento = BancoPago::where([
        //                     ["secuencia", $validarPago->secuencia],
        //                     ["imp_pag", $validarPago->monto],
        //                     ["fch_pag", $validarPago->fecha],
        //                     ["num_doc", str_pad($estudiante->nro_documento, 15, '0', STR_PAD_LEFT)],
        //                 ])
        //                     ->first();
        //                 if (empty($validarDocumento)) {
        //                     $documentoValidado = false;
        //                 } else {

        //                     if (strtotime($validarDocumento->fecha) > strtotime($cronograma->fin)) {
        //                         dd(strtotime($validarDocumento->fecha) > strtotime($cronograma->fin));
        //                         $validarFecha = false;
        //                     }
        //                 }
        //             } else {
        //                 $pagoExistente = false;
        //             }
        //         }
        //         $cont = $cont + 1;
        //     }
        // } else {
        //     $pagoExistente = false;
        // }

        // // total a pagar hasta la cuota actual
        // $descuento = '0';
        // switch ($inscripcion->tipo_estudiante) {
        //     case '1':
        //         $descuento = '1';
        //         break;
        //     case '2':
        //         $descuento = '2';
        //         break;
        //     case '3':
        //         $descuento = '2';
        //         break;
        //     case '4':
        //         $descuento = '2';
        //         break;
        //     case '6':
        //         $descuento = '2';
        //         break;
        //     default:
        //         $descuento = '0';
        //         break;
        // }

        // $tarifaInscripcion = Tarifa::where([
        //     ['modalidad', $inscripcion->modalidad],
        //     ['concepto_pagos_id', '1'],
        //     ['tipo_estudiante', $descuento]
        // ])->first();

        // $tarifaMensual = Tarifa::where([
        //     ['modalidad', $inscripcion->modalidad],
        //     ['concepto_pagos_id', '2'],
        //     ['tipo_estudiante', $descuento]
        // ])->first();

        // if ($validarFecha) {
        // $totalPagar = floatVal($tarifaInscripcion->importe) + floatVal($tarifaMensual->importe * $cronograma->nro_cuota);
        // $tarifaEstudiante = TarifaEstudiante::where([["estudiantes_id", $idEstudiante], ["nro_cuota", "<=", $cronograma->nro_cuota]])->get();

        // $deuda = 0;
        // $arrayDeudas = array();
        // foreach ($tarifaEstudiante as $key => $value) {

        //     if ($value->nro_cuota == 0) {
        //         $deuda = $deuda + $value->monto - $value->pagado;
        //     } else {

        //         if ($value->monto - $value->pagado == 0) {
        //             $deuda = 0;
        //         } else {
        //             $crono = CronogramaPago::where("nro_cuota", $value->nro_cuota)->first();
        //             if (date('Y-m-d') <= date("Y-m-d", strtotime($crono->fin . "+ 1 days"))) {
        //                 $deuda = $deuda + $value->monto - $value->pagado;
        //             } else {
        //                 if ($validarFecha) {
        //                     $deuda = $deuda + $value->monto - $value->pagado;
        //                 } else {
        //                     $deuda = $deuda + $value->monto - $value->pagado + 30.00;
        //                     array_push($arrayDeudas, $value->nro_cuota);
        //                 }
        //             }
        //         }
        //     }
        // }
        // dd(date('Y-m-d') <= date("Y-m-d", strtotime($crono->fin . "+ 1 days")));
        // dd($deuda);

        // $totalPagar = number_format($deuda, 2);
        // $importeActual = $sumaPagoDB - $comisionBanco;

        // if ($pagoExistente) {
        //     if ($documentoValidado) {
        //         if (round($importeActual, 2) >= $totalPagar) {
        //             DB::beginTransaction();
        //             try {
        //                 $cont = 0;
        //                 while ($cont < count($tokens)) {
        //                     $pago = Pago::where('token', $tokens[$cont])->first();

        //                     $pago = Pago::find($pago->id);
        //                     $pago->estado = '2';
        //                     $pago->save();

        //                     $pagoSinComision = round($pago->monto - 0.60, 2);

        //                     $mensualPago = new InscripcionPago();
        //                     $mensualPago->monto = $pagoSinComision;
        //                     $mensualPago->inscripciones_id = $inscripcion->id;
        //                     $mensualPago->pagos_id = $pago->id;
        //                     $mensualPago->concepto_pagos_id = 2;
        //                     $mensualPago->save();

        //                     $cont = $cont + 1;
        //                 }
        //                 foreach ($tarifaEstudiante as $key => $value) {
        //                     $tarifaMensual = TarifaEstudiante::where([["estudiantes_id", $idEstudiante], ["nro_cuota", $value->nro_cuota]])->first();
        //                     $tarifaMensual->pagado = $tarifaMensual->monto;
        //                     if (in_array($value->nro_cuota, $arrayDeudas)) {
        //                         $tarifaMensual->mora = 30.00;
        //                     }
        //                     $tarifaMensual->save();
        //                 }
        //                 $tarifaAdicional = TarifaEstudiante::where([["estudiantes_id", $idEstudiante], ["nro_cuota", $cronograma->nro_cuota + 1]])->first();
        //                 $tarifaAdicional->pagado = $importeActual - $totalPagar;
        //                 $tarifaAdicional->save();

        //                 DB::commit();
        //                 $message = 'Pago registrado correctamente.';
        //                 $status = true;
        //                 $error = '';
        //             } catch (\Exception $e) {
        //                 DB::rollback();
        //                 $message = 'Error al registrar pago, intentelo nuevamante.';
        //                 $status = false;
        //                 $error = $e;
        //             }
        //             $response = array(
        //                 "message" => $message,
        //                 "status" => $status,
        //                 "error" => $error
        //             );
        //         } else {
        //             $response = array(
        //                 "message" => '* El monto total de pago es menor al monto total a pagar.',
        //                 "status" => false,
        //             );
        //         }
        //     } else {
        //         $response = array(
        //             "message" => '* Error al validar pago, Ud. esta intentando ingresar un pago que no esta a su nombre.',
        //             "status" => false,
        //         );
        //     }
        // } else {
        //     $response = array(
        //         "message" => '* No se encontraron pagos.',
        //         "status" => false,
        //     );
        // }
        // } else {
        //     $tarifaEstudiante = TarifaEstudiante::where([["estudiantes_id", $idEstudiante], ["nro_cuota", "<=", $cronograma->nro_cuota]])->get();
        //     $deuda = 0;
        //     foreach ($tarifaEstudiante as $key => $value) {

        //         if ($value->nro_cuota == 0) {
        //             $deuda = $deuda + $value->monto - $value->pagado;
        //         } else {

        //             if ($value->monto - $value->pagado == 0) {
        //                 $deuda = 0;
        //             } else {
        //                 $crono = CronogramaPago::where("nro_cuota", $value->nro_cuota)->first();
        //                 if (date('Y-m-d') <= date("Y-m-d", strtotime($crono->fin . "+ 1 days"))) {
        //                     $deuda = $deuda + $value->monto - $value->pagado;
        //                 } else {
        //                     $deuda = $deuda + $value->monto - $value->pagado + 30.00;
        //                 }
        //             }
        //         }
        //     }

        //     $totalPagar = number_format($deuda, 2);
        //     $importeActual = $sumaPagoDB - $comisionBanco;

        //     if ($pagoExistente) {
        //         if ($documentoValidado) {
        //             if (round($importeActual, 2) >= $totalPagar) {
        //                 DB::beginTransaction();
        //                 try {
        //                     $cont = 0;
        //                     while ($cont < count($tokens)) {
        //                         $pago = Pago::where('token', $tokens[$cont])->first();

        //                         $pago = Pago::find($pago->id);
        //                         $pago->estado = '2';
        //                         $pago->save();
        //                         if ($cont == 0) {
        //                             $pagoSinComision = round($pago->monto - 0.60, 2) - floatVal($tarifaMora->importe);
        //                             $mora = new InscripcionPago();
        //                             $mora->monto = 30.00;
        //                             $mora->inscripciones_id = $inscripcion->id;
        //                             $mora->pagos_id = $pago->id;
        //                             $mora->concepto_pagos_id = 3;
        //                             $mora->save();
        //                         } else {
        //                             $pagoSinComision = round($pago->monto - 0.60, 2);
        //                         }
        //                         $mensualPago = new InscripcionPago();
        //                         $mensualPago->monto = $pagoSinComision;
        //                         $mensualPago->inscripciones_id = $inscripcion->id;
        //                         $mensualPago->pagos_id = $pago->id;
        //                         $mensualPago->concepto_pagos_id = 2;
        //                         $mensualPago->save();

        //                         $cont = $cont + 1;
        //                     }
        //                     DB::commit();
        //                     $message = 'Pago registrado correctamente.';
        //                     $status = true;
        //                     $error = '';
        //                 } catch (\Exception $e) {
        //                     DB::rollback();
        //                     $message = 'Error al registrar pago, intentelo nuevamante.';
        //                     $status = false;
        //                     $error = $e;
        //                 }
        //                 $response = array(
        //                     "message" => $message,
        //                     "status" => $status,
        //                     "error" => $error
        //                 );
        //             } else {
        //                 $response = array(
        //                     "message" => '* El monto total de pago es menor al monto total a pagar.',
        //                     "status" => false,
        //                 );
        //             }
        //         } else {
        //             $response = array(
        //                 "message" => '* Error al validar pago, Ud. esta intentando ingresar un pago que no esta a su nombre.',
        //                 "status" => false,
        //             );
        //         }
        //     } else {
        //         $response = array(
        //             "message" => '* No se encontraron pagos.',
        //             "status" => false,
        //         );
        //     }
        // }
        return response()->json($response);
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
    public function save_file($file, $extension)
    {
        $date = date('Ymd_His');
        $first = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 4);
        $file_name = $date . $first . '.' . $extension;
        $name_complete = $this->dateTimePartial . '/' . $file_name;
        Storage::disk('vouchers')->putFileAs($this->dateTimePartial, $file, $file_name);
        return $name_complete;
    }
}
