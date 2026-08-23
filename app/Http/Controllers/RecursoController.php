<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Sede;
use App\Models\User;
use App\Models\Turno;
use App\Models\Ubigeo;
use App\Models\Docente;
use App\Models\Horario;
use App\Models\Periodo;
use App\Models\Auxiliar;
use App\Models\Sesiones;
use App\Models\GrupoAula;
use App\Models\Estudiante;
use App\Models\DocenteApto;
use App\Models\Notificacion;
use Illuminate\Http\Request;
use App\Models\AuxiliarGrupo;
use App\Models\CargaAcademica;
use App\Models\PlantillaHorario;
use App\Models\AsistenciaDocente;
use App\Models\Ciclo;
use App\Support\MediaUrl;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Builder;

class RecursoController extends Controller
{
    public function getSedes(Request $request)
    {
        if (isset($request->all)) {
            $sedes = Sede::get();
        } else {
            $sedes = Sede::where("estado", '1')->get();
        }

        return response()->json($sedes);
    }
    public function getAreas()
    {
        $areas = Area::get();

        return response()->json($areas);
    }


    public function getTurnos()
    {
        $turnos = Turno::where('estado', '1')->get();

        return response()->json($turnos);
    }
    public function getGrupoAulaAuxiliar(Request $request)
    {

        if (auth()->user()->hasRole('Super Admin|Administrador|Coordinador Auxiliar')) {
            $response = GrupoAula::select("grupo_aulas.*", DB::raw("CONCAT(s.denominacion,' ',g.denominacion) as grupo"), "s.id as IdSede")
                ->join("grupos as g", "g.id", "grupo_aulas.grupos_id");
        } else {
            $id = Auth::user()->id;
            $auxiliar = Auxiliar::where("users_id", $id)->first();
            $response = AuxiliarGrupo::select("grupo_aulas.*", DB::raw("CONCAT(s.denominacion,' ',g.denominacion) as grupo"), "s.id as IdSede")
                ->join("grupo_aulas", "grupo_aulas.id", "auxiliar_grupos.grupo_aulas_id")
                ->join("grupos as g", "g.id", "grupo_aulas.grupos_id")
                ->where("auxiliar_grupos.auxiliares_id", $auxiliar->id);
        }
        $response = $response->join("aulas as a", "a.id", "grupo_aulas.aulas_id")
            ->join("locales as l", "l.id", "a.locales_id")
            ->join("sedes as s", "s.id", "l.sedes_id")
            ->orderBy("s.id", "asc")
            ->orderBy("g.denominacion", "asc");
        // $response = GrupoAula::select("grupo_aulas.*", "g.denominacion as grupo")
        //     ->join("grupos as g", "g.id", "grupo_aulas.grupos_id")
        //     ->orderBy("g.denominacion", "asc");
        // ->where("ag.users_id",$id);
        if (isset($request->sede)) {
            $response = $response->where("l.sedes_id", $request->sede);
        }
        if (isset($request->area)) {
            $response = $response->where("areas_id", $request->area);
        }
        if (isset($request->turno)) {
            $response = $response->where("turnos_id", $request->turno);
        }
        $response = $response->get();

        return response()->json($response);
    }
    public function getGrupoAulaAuxiliarAgrupado(Request $request)
    {

        if (auth()->user()->hasRole('Super Admin|Administrador|Coordinador Auxiliar')) {
            $query = GrupoAula::select("grupo_aulas.*", DB::raw("CONCAT(s.denominacion,' ',g.denominacion) as grupo"), "s.id as IdSede", "s.denominacion as SedeDenominacion")
                ->join("grupos as g", "g.id", "grupo_aulas.grupos_id");
        } else {
            $id = Auth::user()->id;
            $auxiliar = Auxiliar::where("users_id", $id)->first();
            $query = AuxiliarGrupo::select("grupo_aulas.*", DB::raw("CONCAT(s.denominacion,' ',g.denominacion) as grupo"), "s.id as IdSede", "s.denominacion as SedeDenominacion")
                ->join("grupo_aulas", "grupo_aulas.id", "auxiliar_grupos.grupo_aulas_id")
                ->join("grupos as g", "g.id", "grupo_aulas.grupos_id")
                ->where("auxiliar_grupos.auxiliares_id", $auxiliar->id);
        }
        $query = $query->join("aulas as a", "a.id", "grupo_aulas.aulas_id")
            ->join("locales as l", "l.id", "a.locales_id")
            ->join("sedes as s", "s.id", "l.sedes_id")
            ->orderBy("s.id", "asc")
            ->orderBy("g.denominacion", "asc");
        // $query = GrupoAula::select("grupo_aulas.*", "g.denominacion as grupo")
        //     ->join("grupos as g", "g.id", "grupo_aulas.grupos_id")
        //     ->orderBy("g.denominacion", "asc");
        // ->where("ag.users_id",$id);
        if (isset($request->sede)) {
            $query = $query->where("l.sedes_id", $request->sede);
        }
        if (isset($request->area)) {
            $query = $query->where("areas_id", $request->area);
        }
        if (isset($request->turno)) {
            $query = $query->where("turnos_id", $request->turno);
        }
        $query = $query->get();

        $i = 0;
        $idSede = 0;
        $grupos = [];
        $allGrupos = [];
        $sede = "";
        foreach ($query as $key => $value) {
            $obj = new \stdClass;
            $obj->id = $value->id;
            $obj->grupo = $value->grupo;
            $obj->options = "";
            $grupos[] = $obj;

            if (isset($query[$key + 1]) && $value->IdSede != $query[$key + 1]->IdSede) {
                $objeto = new \stdClass;
                $objeto->sede = $sede;
                $objeto->grupos = $grupos;
                $grupos = [];
                $allGrupos[] = $objeto;
            }
            // if($idSede!=$value->IdSede&&$key!=0){

            // }
            if ($key + 1 == count($query)) {
                $objeto = new \stdClass;
                $objeto->sede = $value->SedeDenominacion;
                $objeto->grupos = $grupos;
                $grupos = [];
                $allGrupos[] = $objeto;
            }
            $idSede = $value->IdSede;
            $sede = $value->SedeDenominacion;
            // $grupos[$i][]=
        }
        $response["sedes"] = $allGrupos;

        return response()->json($response);
    }
    public function getCargaAcademicaAsistencia(Request $request)
    {
        // dd($request);
        $fecha = new \DateTime($request->fecha);
        $semana = $fecha->format("N");
        $dias = ["Lunes", "Martes", "Miercoles", "Jueves", "Viernes", "Sabado", "Domingo"];

        $response["dia"] = $dias[(int)($semana - 1)] ?? null;
        $response["fecha"] = $fecha->format("d/m/Y");
        $fecha = $fecha->format("Y-m-d");
        $periodo = Periodo::actual();

        $response["cargaAcademica"] = collect();

        if ($periodo) {
            $response["cargaAcademica"] = CargaAcademica::with(["curso", "docente"])
                ->select('carga_academicas.*', 'da.usuario')
                ->leftJoin('docente_aptos as da', 'da.docentes_id', 'carga_academicas.docentes_id')
                ->where("carga_academicas.grupo_aulas_id", $request->grupo)
                ->where("carga_academicas.periodos_id", $periodo->id)
                ->where("carga_academicas.estado", "1")
                ->orderBy("carga_academicas.cursos_id", "asc")
                ->orderBy("carga_academicas.tipo", "asc")
                ->get();
        }

        $grupoAula = GrupoAula::find($request->grupo);
        // dd($grupoAula);
        $response["turno"] = $grupoAula ? Turno::find($grupoAula->turnos_id) : null;
        $plantillaHorario = [];

        if (!$grupoAula) {
            $response["horario"] = $plantillaHorario;

            return response()->json($response);
        }

        $plantilla = PlantillaHorario::select(
            "id",
            DB::raw("LEFT(hora_inicio, 5) as horaInicio"),
            DB::raw("LEFT(hora_fin, 5) as horaFin"),
            "tipo"
        )
            ->where("turnos_id", $grupoAula->turnos_id)
            // ->where("dia",$semana)
            ->where("estado", "1")
            ->get();

        $horariosPorPlantilla = collect();

        if ($periodo && $plantilla->isNotEmpty()) {
            $horariosPorPlantilla = Horario::with(['curso', 'carga'])
                ->select("horarios.*", "ad.estado", "ad.id as idAsistencia", "ad.docentes_id as IdDocente")
                ->join('carga_academicas as ca', function ($join) use ($request, $periodo) {
                    $join->on('ca.id', '=', 'horarios.carga_academicas_id')
                        ->where('ca.grupo_aulas_id', '=', $request->grupo)
                        ->where('ca.periodos_id', '=', $periodo->id)
                        ->where('ca.estado', '=', '1');
                })
                ->leftJoin('asistencia_docentes as ad', function ($join) use ($fecha) {
                    $join->on('ad.carga_academicas_id', '=', 'horarios.carga_academicas_id')
                        ->where('ad.fecha', '=', $fecha);
                })
                ->where("horarios.periodos_id", $periodo->id)
                ->where("horarios.dia", $semana)
                ->whereIn("horarios.plantilla_horarios_id", $plantilla->pluck('id')->all())
                ->orderBy("horarios.plantilla_horarios_id", "asc")
                ->orderBy("horarios.id", "asc")
                ->get()
                ->groupBy('plantilla_horarios_id')
                ->map(function ($horarios) {
                    return $horarios->first();
                });
        }

        foreach ($plantilla as $k => $val) {
            $obj = new \stdClass;
            $obj->id = $val->id;
            $obj->hora_inicio = $val->horaInicio;
            $obj->hora_fin = $val->horaFin;
            $obj->tipo = $val->tipo;
            $obj->horario = $horariosPorPlantilla->get($val->id);
            $plantillaHorario[] = $obj;
        }
        $response["horario"] = $plantillaHorario;
        return response()->json($response);
    }
    public function getSesiones(Request $request)
    {
        $getCarga = CargaAcademica::find($request->carga);
        // dd(auth('sanctum')->user());
        // $key = array_search(, array_column($getCarga, 'label'));
        // dd($request->dicenbt);
        $fecha = date("Y-m-d");
        $date = new \DateTime($fecha);
        $semana = $date->format("N");
        $response["sesion"] = Sesiones::where([
            ["carga_academicas_id", $request->carga],
            ["fecha", $fecha]
        ])
            ->first();

        $horario = Horario::select("horarios.*", "ph.hora_inicio", "ph.hora_fin")
            ->where([
                ["carga_academicas_id", $request->carga],
                ["dia", $semana]
            ])
            ->join("plantilla_horarios as ph", "ph.id", "horarios.plantilla_horarios_id")
            ->orderBy("hora_inicio", "asc")
            ->get();

        $asistencia = AsistenciaDocente::with(["sesiones"])->where([
            ["fecha", $fecha],
            ["carga_academicas_id", $request->carga],
            ["docentes_id", $request->docente],
        ])
            ->first();
        if ($asistencia) {
            $response["status"] = true;
            $response["asistencia"] = $asistencia;
        } else {
            $response["status"] = false;
            $response["asistencia"] = [];
        }
        // dd(reset($horario));
        // var_dump($horario);
        $response["cantidadHoras"] = count($horario);
        // $response["docente"] = Docente::find($request->docente);
        $carga = CargaAcademica::with("docente")->where("id", $request->carga)->where("docentes_id", $request->docente)->first();
        if ($carga && count($horario) > 0) {
            $response["status_carga"] = true;
        } else {
            $response["status_carga"] = false;
        }
        $response["carga"] = $carga;
        // find($request->carga);
        return $response;
    }
    public function getDepartamentos()
    {
        $departamentos = Ubigeo::select('codigo_departamento', 'departamento')->distinct()->get();

        return response()->json($departamentos);
    }
    public function getProvincias(Request $request)
    {
        $provincias = Ubigeo::select('codigo_provincia', 'provincia')->distinct()->where('codigo_departamento', $request->codigo)->get();

        return response()->json($provincias);
    }
    public function getDistritos(Request $request)
    {
        $distritos = Ubigeo::select('id', 'distrito')->distinct()->where('codigo_provincia', $request->codigo)->get();

        return response()->json($distritos);
    }
    public function alertNotificaciones()
    {
        $idUser = Auth::user()->id;
        $idRole = Auth::user()->roles[0]->id;
        $notificaciones = Notificacion::where("user_id", $idUser)
            ->where("role_id", $idRole)
            ->where("estado", "0")
            ->get();

        if (count($notificaciones) > 0) {
            $response["status"] = true;
            $response["count"] = count($notificaciones);
            return $response;
        } else {
            $response["status"] = false;
            $response["count"] = count($notificaciones);
            return $response;
        }
        // return count($notificaciones);
    }
    public function getNotificaciones(Request $request)
    {
        // dd(Auth::user()->roles[0]->id);
        $idUser = Auth::user()->id;
        $idRole = Auth::user()->roles[0]->id;
        if ($request->page == 1) {
            Notificacion::where("user_id", $idUser)
                ->where("role_id", $idRole)
                ->update(["estado" => "1"]);
        }
        $notificaciones = Notificacion::with(["comentario", "publicacion"])
            // ->select("notificaciones.*","c.descripcion as comentario_","p.descripcion as public_")
            // // ->leftJoin("comentarios as c","c.id","notificaciones.comentario_id")
            // ->leftJoin("comentarios as c", function ($join) {
            //     $join->on('c.id', '=', 'notificaciones.comentario_id')
            //          ->where('notificaciones.tipo', '=', '2');
            // })
            // // ->leftJoin()
            // ->leftJoin("publicaciones as p", function ($join) {
            //     $join->on('p.id', '=', 'notificaciones.publicacion_id')
            //          ->where('notificaciones.tipo', '=', '1');
            // })
            ->where("notificaciones.user_id", $idUser)
            ->where("notificaciones.role_id", $idRole)
            ->orderBy("notificaciones.id", "desc")
            ->paginate(5);

        return $notificaciones;
    }
    public function getDataUser(Request $request)
    {
        $idUser = $request->idUser;
        $rolName = (string) $request->rolName;
        $objeto = (object) [
            'nombres' => 'Usuario no disponible',
            'estado_foto' => false,
            'path_foto' => '',
            'rol' => $rolName ?: 'Usuario',
        ];

        switch ($rolName) {
            case 'Estudiante':
                $query = Estudiante::select("nombres", "paterno", "foto")->find($idUser);

                if ($query) {
                    $objeto->nombres = trim($query->nombres . " " . $query->paterno);
                    $objeto->path_foto = MediaUrl::profile($query->foto);
                    $objeto->estado_foto = $objeto->path_foto !== '';
                }

                break;
            case 'Docente':
                $query = DocenteApto::with('docente')->find($idUser);

                if ($query && $query->docente) {
                    $objeto->nombres = trim($query->docente->nombres . " " . $query->docente->paterno);
                    $objeto->path_foto = MediaUrl::profile($query->docente->foto);
                    $objeto->estado_foto = $objeto->path_foto !== '';
                }

                break;

            default:
                $query = User::find($idUser);

                if ($query) {
                    $objeto->nombres = trim($query->name . " " . $query->paterno);
                    $objeto->path_foto = MediaUrl::publicAsset($query->profile_photo_path);
                    $objeto->estado_foto = $objeto->path_foto !== '';
                }

                break;
        }

        $response["datos"] = $objeto;
        $response["id"] = $request->filled('id')
            ? Crypt::encryptString($request->id)
            : null;

        return $response;
    }

    public function getCiclos()
    {
        $ciclos = Ciclo::orderBy('id', 'desc')->get();

        return $ciclos;
    }
}
