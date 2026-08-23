<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Models\Turno;
use App\Models\PlantillaHorario;
use App\Models\Periodo;
use App\Services\GrupoAulaContactService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class HorarioController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:docente');
    }

    public function index(){
        // return view("web.docente.horario");
        return Inertia::render('Docente/Horario');
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

    protected function construirTurnoHorario($turno, array $dias, array $plantillasPorTurno, array $horariosPorClave)
    {
        $turnoHorario = new \stdClass;
        $turnoHorario->id = $turno->id;
        $turnoHorario->turno = $turno->denominacion;
        $turnoHorario->dias = [];

        foreach ($dias as $dia) {
            $diaHorario = new \stdClass;
            $diaHorario->dia = $dia["nombre"];
            $diaHorario->disponibilidad = [];

            foreach ($plantillasPorTurno[$turno->id] ?? [] as $plantilla) {
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

    public function getHorario(){
        $periodo = Periodo::actual();
        $docenteApto = Auth::guard('docente')->user();
        $horario = [];

        if (!$periodo || !$docenteApto || !$docenteApto->estaHabilitadoEnPeriodo($periodo->id)) {
            $response["horario"] = $horario;
            $response["contactos"] = [];
            return response()->json($response);
        }

        $turnos = Turno::select("id", "denominacion")->get();
        $dias = $this->diasSemana();

        $plantillas = PlantillaHorario::select(
            "id",
            "turnos_id",
            DB::raw("LEFT(hora_inicio, 5) as horaInicio"),
            DB::raw("LEFT(hora_fin, 5) as horaFin"),
            "tipo"
        )
            ->where("estado", "1")
            ->whereIn("turnos_id", $turnos->pluck("id"))
            ->orderBy("turnos_id")
            ->orderBy("orden")
            ->orderBy("id")
            ->get();

        $plantillasPorTurno = [];
        foreach ($plantillas as $plantilla) {
            $plantillasPorTurno[$plantilla->turnos_id][] = $plantilla;
        }

        $horarios = collect();
        $horariosPorClave = [];
        if ($plantillas->isNotEmpty()) {
            $horarios = DB::table("horarios as h")
                ->select(
                    "h.id",
                    "h.plantilla_horarios_id",
                    "h.dia",
                    "ga.id as grupo_aula_id",
                    "g.denominacion as grupo",
                    "c.denominacion as curso_denominacion",
                    "c.color as curso_color"
                )
                ->join("carga_academicas as ca", "ca.id", "h.carga_academicas_id")
                ->join("grupo_aulas as ga", "ga.id", "ca.grupo_aulas_id")
                ->join("grupos as g", "g.id", "ga.grupos_id")
                ->join("cursos as c", "c.id", "ca.cursos_id")
                ->where("h.periodos_id", $periodo->id)
                ->where("ca.periodos_id", $periodo->id)
                ->where("ca.docentes_id", $docenteApto->docentes_id)
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
                $horarioItem->grupo_aula_id = $item->grupo_aula_id;
                $horarioItem->grupo = $item->grupo;
                $horarioItem->curso = (object) [
                    "denominacion" => $item->curso_denominacion,
                    "color" => $item->curso_color,
                ];

                $horariosPorClave[$clave] = $horarioItem;
            }
        }

        foreach ($turnos as $turno) {
            $horario[] = $this->construirTurnoHorario($turno, $dias, $plantillasPorTurno, $horariosPorClave);
        }

        $contactosPorGrupo = app(GrupoAulaContactService::class)->obtener(
            $horarios->pluck('grupo_aula_id')->all(),
            (int) $periodo->id
        );

        $response["contactos"] = $horarios
            ->unique('grupo_aula_id')
            ->map(function ($item) use ($contactosPorGrupo) {
                $contactos = $contactosPorGrupo->get((int) $item->grupo_aula_id, []);

                return [
                    'grupo_aula_id' => (int) $item->grupo_aula_id,
                    'grupo' => $item->grupo,
                    'auxiliar' => $contactos['auxiliar'] ?? null,
                    'coordinador' => $contactos['coordinador'] ?? null,
                ];
            })
            ->values();
        $response["horario"] = $horario;
        return response()->json($response);
    }
}
