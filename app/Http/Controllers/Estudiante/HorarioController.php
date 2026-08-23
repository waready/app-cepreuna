<?php

namespace App\Http\Controllers\Estudiante;

use App\Http\Controllers\Controller;
use App\Models\AuxiliarGrupo;
use Auth;
use App\Models\Matricula;
use App\Models\Turno;
use App\Models\PlantillaHorario;
use DB;
use Inertia\Inertia;

class HorarioController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth:estudiante');
    // }
    public function index()
    {
        // dd(Auth::user()->nombres);
        // return view("web.estudiante.horario");
        return Inertia::render('Estudiante/Horario');
    }

    protected function diasSemana(): array
    {
        return [
            ["id" => '1', "nombre" => "Lu"],
            ["id" => '2', "nombre" => "Ma"],
            ["id" => '3', "nombre" => "Mi"],
            ["id" => '4', "nombre" => "Ju"],
            ["id" => '5', "nombre" => "Vi"],
        ];
    }

    protected function construirTurnoHorario($turno, $plantillas, array $dias, array $horariosPorClave)
    {
        $turnoHorario = new \stdClass;
        $turnoHorario->id = $turno->id;
        $turnoHorario->turno = $turno->denominacion;
        $turnoHorario->dias = [];

        foreach ($dias as $dia) {
            $diaHorario = new \stdClass;
            $diaHorario->dia = $dia["nombre"];
            $diaHorario->disponibilidad = [];

            foreach ($plantillas as $plantilla) {
                $slot = new \stdClass;
                $slot->hora_inicio = $plantilla->horaInicio;
                $slot->hora_fin = $plantilla->horaFin;
                $slot->tipo = $plantilla->tipo;
                $slot->horario = $horariosPorClave[$dia["id"] . '-' . $plantilla->id] ?? null;
                $diaHorario->disponibilidad[] = $slot;
            }

            $turnoHorario->dias[] = $diaHorario;
        }

        return $turnoHorario;
    }

    public function getHorario()
    {
        // dd(Auth::user()->docentes_id);
        $estudiante = Auth::user()->id;
        $matricula = Matricula::select("matriculas.*", "g.denominacion as grupo", "a.denominacion as area")
            ->join("grupo_aulas as ga", "ga.id", "matriculas.grupo_aulas_id")
            ->join("grupos as g", "g.id", "ga.grupos_id")
            ->join("areas as a", "a.id", "ga.areas_id")
            ->delEstudiante($estudiante)
            ->delPeriodoActual()
            ->orderByDesc("matriculas.id")
            ->first();

        if (!$matricula) {
            return response()->json([
                "grupo" => "",
                "area" => "",
                "horario" => [],
                "auxiliar_grupo" => null,
            ]);
        }

        $response["grupo"] = $matricula->grupo;
        $response["area"] = $matricula->area;
        $horario = [];
        $dias = $this->diasSemana();

        $auxiliarGrupo = AuxiliarGrupo::with('auxiliar.user')
            ->where('grupo_aulas_id', $matricula->grupo_aulas_id)
            ->where('periodos_id', $matricula->periodos_id)
            ->first();

        $turno = Turno::select("id", "denominacion")->find($matricula->turnos_id);
        $plantillas = collect();
        $horariosPorClave = [];

        if ($turno) {
            $plantillas = PlantillaHorario::select(
                "id",
                "turnos_id",
                DB::raw("LEFT(hora_inicio, 5) as horaInicio"),
                DB::raw("LEFT(hora_fin, 5) as horaFin"),
                "tipo"
            )
                ->where("estado", "1")
                ->where("turnos_id", $turno->id)
                ->orderBy("orden")
                ->orderBy("id")
                ->get();

            if ($plantillas->isNotEmpty()) {
                $horarios = DB::table("horarios as h")
                    ->select(
                        "h.id",
                        "h.plantilla_horarios_id",
                        "h.dia",
                        "c.denominacion as curso_denominacion",
                        "c.color as curso_color",
                        DB::raw("CONCAT(d.nombres,' ',d.paterno) as docente")
                    )
                    ->join("carga_academicas as ca", "ca.id", "h.carga_academicas_id")
                    ->join("cursos as c", "c.id", "ca.cursos_id")
                    ->join("docentes as d", "d.id", "ca.docentes_id")
                    ->where("h.periodos_id", $matricula->periodos_id)
                    ->where("ca.periodos_id", $matricula->periodos_id)
                    ->where("ca.grupo_aulas_id", $matricula->grupo_aulas_id)
                    ->where("ca.estado", "1")
                    ->whereIn("h.plantilla_horarios_id", $plantillas->pluck("id"))
                    ->whereIn("h.dia", collect($dias)->pluck("id"))
                    ->orderBy("h.id")
                    ->get();

                foreach ($horarios as $item) {
                    $clave = $item->dia . '-' . $item->plantilla_horarios_id;
                    if (isset($horariosPorClave[$clave])) {
                        continue;
                    }

                    $horarioItem = new \stdClass;
                    $horarioItem->id = $item->id;
                    $horarioItem->docente = $item->docente;
                    $horarioItem->curso = (object) [
                        "denominacion" => $item->curso_denominacion,
                        "color" => $item->curso_color,
                    ];

                    $horariosPorClave[$clave] = $horarioItem;
                }
            }

            $horario[] = $this->construirTurnoHorario($turno, $plantillas, $dias, $horariosPorClave);
        }
        $response["horario"] = $horario;
        $response["auxiliar_grupo"] = $auxiliarGrupo;

        return response()->json($response);
    }
}
