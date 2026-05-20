<?php

namespace App\Http\Controllers\Estudiante;

use TCPDF;
use Carbon\Carbon;
use App\Models\Pago;
use Inertia\Inertia;
use App\Models\Tarifa;
use App\Models\Periodo;
use App\Models\BancoPago;
use App\Models\Matricula;
use App\Models\Estudiante;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Inscripciones;
use App\Models\CronogramaPago;
use App\Models\InscripcionPago;
use App\Models\TarifaEstudiante;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\PreguntasVocacionales;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use App\Models\RespuestaEstudianteVocacional;

class TestController extends Controller
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


    public function index()
    {
        $idEstudiante = Auth::user()->id;
    
        // Validar fechas dinámicamente (solo 10 y 11 del mes actual)
        $hoy = Carbon::now();
        $fechaInicio = Carbon::create($hoy->year, $hoy->month, 9)->startOfDay(); // Día 10
        $fechaFin = Carbon::create($hoy->year, $hoy->month, 11)->endOfDay(); // Día 11
    
        if ($hoy->lt($fechaInicio)) {
            $estadoTest = 'proximamente'; // aún no llega
        } elseif ($hoy->between($fechaInicio, $fechaFin)) {
            $estadoTest = 'activo'; // test activo
        } else {
            $estadoTest = 'cerrado'; // ya pasó
        }
    
        // Obtener inscripción y estudiante
        $inscripcion = Inscripciones::where('estudiantes_id', $idEstudiante)->first();
        $periodo = Periodo::where('estado', '1')->first();
    
        $estudiante = $inscripcion ? $inscripcion->estudiante->load('colegio') : null;
    
        // Cargar preguntas solo si el test está activo
        $preguntas = $estadoTest === 'activo' ? PreguntasVocacionales::all() : [];
    
        // Validar si el estudiante ya respondió el test
        $validacion = DB::table('respuesta_estudiante_vocacional')
            ->where('estudiantes_id', $idEstudiante)
            ->first();
    
        $response = [
            "inscripcion" => $inscripcion,
            "periodo" => $periodo,
            "estudiante" => $estudiante,
            "preguntas" => $preguntas,
            "validacion" => $validacion,
            "estado_test" => $estadoTest,
        ];
    
        return Inertia::render('Estudiante/Test', ["data" => $response]);
    }
    


    public function store(Request $request)
    {
        // Validar la solicitud
        $validatedData = $request->validate([
            'estudianteId' => 'required|exists:estudiantes,id', // Validar que el estudiante exista
            'respuestas' => 'required|array', // Validar que las respuestas sean un array
            'respuestas.*' => 'boolean', // Validar que cada respuesta sea un booleano
        ]);
    
        // Obtener el ID del estudiante y las respuestas
        $estudianteId = $validatedData['estudianteId'];
        $respuestas = $validatedData['respuestas'];
    
        // Inicializar puntajes para categorías
        $puntajeIngenieria = 0;
        $puntajeBiomedicas = 0;
        $puntajeSociales = 0;
    
        // Iniciar transacción
        DB::beginTransaction();
    
        try {
            // Procesar las respuestas por categorías
            $detalles = [];
            foreach ($respuestas as $preguntaId => $respuesta) {
                // Determinar categoría de la pregunta
                $categoria = $this->obtenerAreaPregunta($preguntaId);
    
                // Calcular puntaje
                $puntaje = $respuesta ? 1 : 0;
    
                // Acumular puntaje en la categoría correspondiente
                switch ($categoria) {
                    case '1':
                        $puntajeSociales += $puntaje * 0.5;
                        break;
                    case '2':
                        $puntajeIngenieria += $puntaje;
                        break;
                    case '3':
                        $puntajeBiomedicas += $puntaje;
                        break;
                }
    
                // Preparar detalle
                $detalles[] = [
                    'nro_documento' => Auth::user()->nro_documento, // Documento del estudiante autenticado
                    'puntaje' => $puntaje,
                    'tipo' => $respuesta ? "1" : "0",
                    'preguntas_id' => $preguntaId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
    
            // Insertar en la tabla principal (respuesta_estudiante_vocacional)
            $respuestaId = DB::table('respuesta_estudiante_vocacional')->insertGetId([
                'puntaje_ingeneria' => $puntajeIngenieria,
                'puntaje_biomedicas' => $puntajeBiomedicas,
                'puntaje_sociales' => $puntajeSociales,
                'estudiantes_id' => $estudianteId, // ID del estudiante
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    
            // Insertar los detalles (respuesta_estudiante_vocacional_detalles)
            foreach ($detalles as &$detalle) {
                $detalle['respuesta_id'] = $respuestaId;
            }
    
            DB::table('respuesta_estudiante_vocacional_detalles')->insert($detalles);
    
            // Determinar el área con mayor puntaje
            $areas = [
                'Ingeniería' => $puntajeIngenieria,
                'Biomedicas' => $puntajeBiomedicas,
                'Ciencias Sociales' => $puntajeSociales,
            ];
    
            $areaGanadora = array_keys($areas, max($areas))[0];
    
            // Confirmar transacción
            DB::commit();
    
            // Retornar respuesta al cliente
            return response()->json([
                'message' => 'Resultados guardados correctamente',
                'puntajes' => [
                    'ingenieria' => $puntajeIngenieria,
                    'biomedicas' => $puntajeBiomedicas,
                    'sociales' => $puntajeSociales,
                ],
                'area_sugerida' => $areaGanadora,
            ]);
        } catch (\Exception $e) {
            // Revertir transacción en caso de error
            DB::rollBack();
    
            return response()->json([
                'message' => 'Hubo un problema al guardar los resultados',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Obtener el área (categoría) de una pregunta.
     *
     * @param int $preguntaId
     * @return string|null
     */
    private function obtenerAreaPregunta($preguntaId)
    {
        $pregunta = PreguntasVocacionales::where('id', $preguntaId)->first();

        if (!$pregunta) {
            throw new \Exception("La pregunta con ID {$preguntaId} no existe.");
        }

        return $pregunta->area ?? '0';
    }

   
    public function pdfConstancia($id)
    {
        // Obtener estudiante con relaciones necesarias
        $estudiante = Estudiante::with([
            'tipoDocumento',
            'colegio',
            'pais',
            'ubigeo',
        ])->find($id);
    
        if (!$estudiante) {
            return response()->json(['error' => 'Estudiante no encontrado'], 404);
        }
    
        // Obtener inscripción
        $inscripcion = Inscripciones::with(['sede', 'area', 'turno'])
            ->where('estudiantes_id', $id)
            ->first();
    
        if (!$inscripcion) {
            return response()->json(['error' => 'Inscripción no encontrada'], 404);
        }
    
        $periodo = Periodo::find($inscripcion->periodos_id);
        if (!$periodo) {
            return response()->json(['error' => 'Periodo no encontrado'], 404);
        }
    
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        // === CONFIGURACIÓN GENERAL ===
        $pdf->SetCreator('CEPREUNA');
        $pdf->SetAuthor('CEPREUNA');
        $pdf->SetTitle('Constancia de Examen Vocacional');

        $pdf->AddPage();
        $pdf->SetMargins(0,0,0);
        $pdf->SetAutoPageBreak(true, 0);
        $pdf->Image('images/fondo-constancia.png', 0, 0, 210, "", 'PNG', '', '', false, 150, '', false, false, 0, false, false, false);
        $pdf->SetMargins(20, 40, 20);
        $pdf->setCellHeightRatio(1.5); // Ajustar la altura de las celdas


        // Logo UNAPUNO (izquierda)
        $pdf->Image('images/UNAPUNO.png', 20, 8, 20, 20, 'PNG', '', '', false, 150, '', false, false, 0, false, false, false);

        // Logo CEPREUNA (derecha)
        $pdf->Image('images/logo_cepre.png', 165, 6, 25, 25, 'PNG', '', '', false, 140, '', false, false, 0, false, false, false);

        // === ENCABEZADO ===
        // Posicionar el texto del encabezado para que esté alineado con los logos
        $pdf->SetFont('helvetica', 'B', 13);
        $pdf->Cell(0, 5, 'UNIVERSIDAD NACIONAL DEL ALTIPLANO PUNO', 0, 1, 'C', 0, '', 0);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 5, "Centro de Estudios Pre Universitario", 0, 1, 'C', 0, '', 0);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 5, 'Jr Acora #235', 0, 1, 'C', 0, '', 0);
        $pdf->Cell(0, 5, '“Año de la recuperación y consolidación de la economía peruana”', 0, 1, 'C', 0, '', 0);
        $pdf->writeHTML('<hr>');
        
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->Cell(0, 5, 'TEST VOCACIONAL', 0, 1, 'C', 0, '', 0);
        $pdf->Cell(0, 5, 'CONSTANCIA DE PARTICIPACIÓN', 0, 1, 'C', 0, '', 0);

        $pdf->ln();

        $pdf->SetFont('helvetica', '', 15);
        // === CONTENIDO PRINCIPAL ===
        $nombre = "{$estudiante->paterno} {$estudiante->materno} {$estudiante->nombres}";
        $ciclo = "{$periodo->inicio_ciclo} - {$periodo->fin_ciclo}";

       
        $pdf->SetFont('helvetica', '', 15);
        $pdf->MultiCell(0, 6, 'El que suscribe, Presidente de la Comisión del Centro Preuniversitario de la Universidad Nacional del Altiplano – Puno.', 0, 'J');
        
        $pdf->Ln(6);
        $pdf->SetFont('helvetica', 'B', 13);
        $pdf->Cell(0, 6, 'HACE CONSTAR QUE:', 0, 1, 'L');
        
        $pdf->Ln(6);
        $pdf->SetFont('helvetica', '', 15);
        $pdf->Cell(0, 6, 'Sr. (Srta):', 0, 1, 'L');
        
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 8, strtoupper($nombre), 0, 1, 'C');
        
        $pdf->Ln(6);
        $pdf->SetFont('helvetica', '', 15);
        $pdf->MultiCell(0, 6, "Ha participado de manera satisfactoria en el Test Vocacional correspondiente al ciclo Marzo – Julio 2025, organizado por el Centro de Estudios Preuniversitario de la Universidad Nacional del Altiplano – Puno(CEPREUNA).", 0, 'J');
        
        $pdf->Ln(6);
        $pdf->MultiCell(0, 6, 'La presente constancia se expide con fines de orientación vocacional , como testimonio de su participación en este proceso, el cual tiene como propósito brindar apoyo y guía a los postulantes interesados en los procesos de admisión a la Universidad Nacional del Altiplano – Puno.', 0, 'J');

        
       
        // === PIE DE PÁGINA ===
        $pdf->SetXY(20, 275);
        $pdf->SetFont('helvetica', '', 8);
        $footerText = "Oficina de Cómputo e Informática || CEPREUNA {$periodo->inicio_ciclo} - {$periodo->fin_ciclo} - Generado: " . date('d/m/Y h:i a');
        $pdf->Cell(0, 10, $footerText, 0, 0, 'C');
        
        // === SALIDA DEL PDF ===
        $pdf->Output('constancia.pdf', 'I');
    }
    
    /**
     * Agrega una imagen al PDF si existe.
     */


}
