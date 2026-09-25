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

    /**
     * Quien dicta hoy cada curso del grupo, para los bloques cuya carga quedo
     * de baja. Se prefiere al titular sobre el suplente: el horario semanal no
     * tiene fecha, y una suplencia de un dia no hace suyo el curso.
     *
     * @return array<int|string, string>
     */
    protected function docenteVigentePorCurso($grupoAulasId, $periodosId): array
    {
        return DB::table("carga_academicas as ca")
            ->select("ca.cursos_id", DB::raw("CONCAT(d.nombres,' ',d.paterno) as docente"))
            ->join("docentes as d", "d.id", "ca.docentes_id")
            ->where("ca.periodos_id", $periodosId)
            ->where("ca.grupo_aulas_id", $grupoAulasId)
            ->where("ca.estado", "1")
            ->orderByRaw("ca.tipo = '1' DESC")
            ->orderBy("ca.id")
            ->get()
            ->groupBy("cursos_id")
            ->map(fn ($cargas) => trim((string) $cargas->first()->docente))
            ->filter()
            ->all();
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
                $docentePorCurso = $this->docenteVigentePorCurso(
                    $matricula->grupo_aulas_id,
                    $matricula->periodos_id
                );

                // La franja es la unidad, no la carga. Un bloque de `horarios`
                // que sigue existiendo es una clase que sigue en la rejilla:
                // cuando se retira un curso se le borran los bloques, asi que
                // lo que queda esta vigente aunque su carga no lo este.
                //
                // Filtrando `ca.estado = '1'` la app dejaba la franja VACIA en
                // los casos en que la carga quedo de baja sin que nadie la
                // tomara -la suplencia de un dia suspende al titular y no
                // siempre se le reactiva-, y el estudiante no veia una clase
                // que si tiene. El panel de intranet la muestra, que es por
                // donde salio el reporte: Fisica del S-103 los viernes.
                //
                // El orden decide la franja: primero la carga vigente, que es
                // quien dicta; luego el titular antes que el suplente, porque
                // este es un horario semanal sin fecha y la suplencia de un dia
                // no cambia de quien es el curso; y a igualdad, el bloque mas
                // antiguo, para no alterar lo que ya se venia mostrando.
                $horarios = DB::table("horarios as h")
                    ->select(
                        "h.id",
                        "h.plantilla_horarios_id",
                        "h.dia",
                        "ca.cursos_id",
                        "ca.estado as carga_estado",
                        "c.denominacion as curso_denominacion",
                        "c.color as curso_color",
                        DB::raw("CONCAT(d.nombres,' ',d.paterno) as docente")
                    )
                    ->join("carga_academicas as ca", "ca.id", "h.carga_academicas_id")
                    ->join("cursos as c", "c.id", "ca.cursos_id")
                    // LEFT: una carga sin docente asignado tiene que salir con
                    // el curso y un "Por asignar". Con INNER desaparecia la
                    // franja entera, y un grupo al que todavia no le reparten
                    // docentes veia el horario en blanco.
                    ->leftJoin("docentes as d", "d.id", "ca.docentes_id")
                    ->where("h.periodos_id", $matricula->periodos_id)
                    ->where("ca.periodos_id", $matricula->periodos_id)
                    ->where("ca.grupo_aulas_id", $matricula->grupo_aulas_id)
                    ->whereIn("h.plantilla_horarios_id", $plantillas->pluck("id"))
                    ->whereIn("h.dia", collect($dias)->pluck("id"))
                    ->orderByRaw("ca.estado = '1' DESC")
                    ->orderByRaw("ca.tipo = '1' DESC")
                    ->orderBy("h.id")
                    ->get();

                foreach ($horarios as $item) {
                    $clave = $item->dia . '-' . $item->plantilla_horarios_id;
                    if (isset($horariosPorClave[$clave])) {
                        continue;
                    }

                    // De una carga de baja no sirve su docente: si hubo
                    // reemplazo, el que dicta es el de la carga vigente.
                    $docente = trim((string) $item->docente);
                    if ((string) $item->carga_estado !== '1') {
                        $docente = $docentePorCurso[$item->cursos_id] ?? $docente;
                    }

                    $horarioItem = new \stdClass;
                    $horarioItem->id = $item->id;
                    $horarioItem->docente = $docente !== '' ? $docente : 'Por asignar';
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
