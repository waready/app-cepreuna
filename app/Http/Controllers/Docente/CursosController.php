<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Models\CargaAcademica;
use App\Models\Curricula;
use App\Models\CurriculaDetalle;
use App\Models\Periodo;
use App\Models\Sesiones;
use App\Services\GrupoAulaContactService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CursosController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:docente');
    }

    public function index()
    {
        // dd(Auth::user()->usuario);
        // return view('web.docente.cursos');
        $response["url_external"] = config('app.external_image_url');
        return Inertia::render('Docente/Recurso/Curso',["data"=>$response]);
    }
    public function getCarga()
    {
        $contexto = $this->contextoActual();
        if (!$contexto) {
            return response()->json(['carga' => []]);
        }
        [$docenteApto, $periodo] = $contexto;
        // $carga = CargaAcademica::with('curso')->select('link')->where('docentes_id',$docenteApto->docentes_id)->get();

        $cargaAcademica = DB::table('carga_academicas as ca')
            ->select(
                'ca.link',
                'c.denominacion as curso',
                'g.denominacion as grupo',
                'ca.id',
                'ga.areas_id as area',
                'c.id as c',
                'ca.tipo',
                'ca.estado',
                'ga.id as grupo_aula_id',
                'a.codigo as Aula',
                'l.denominacion as Local',
                'l.direccion as DireccionLocal',
                'l.foto as Foto',
                's.denominacion as Sede',
                DB::raw($this->modalidadSedeSql('s') . ' as modalidad')
            )
            ->join('cursos as c', 'c.id', 'ca.cursos_id')
            ->join('grupo_aulas as ga', 'ga.id', 'ca.grupo_aulas_id')
            ->join('aulas as a', 'a.id', 'ga.aulas_id')
            ->join('locales as l', 'l.id', 'a.locales_id')
            ->join('sedes as s', 's.id', 'l.sedes_id')
            ->join('grupos as g', 'g.id', 'ga.grupos_id')
            ->where('ca.docentes_id', $docenteApto->docentes_id)
            ->where('ca.periodos_id', $periodo->id)
            ->where('ca.estado', '1')
            ->orderBy('c.denominacion')
            ->orderBy('g.denominacion')
            ->get();

        $contactosPorGrupo = app(GrupoAulaContactService::class)->obtener(
            $cargaAcademica->pluck('grupo_aula_id')->all(),
            (int) $periodo->id
        );

        $cargaAcademica->each(function ($carga) use ($contactosPorGrupo) {
            $contactos = $contactosPorGrupo->get((int) $carga->grupo_aula_id, []);
            $carga->auxiliar = $contactos['auxiliar'] ?? null;
            $carga->coordinador = $contactos['coordinador'] ?? null;
        });

        // $response['docente'] = Docente::with('tipoDocumento','gradoAcademico','programa')->find($docenteApto->docentes_id);
        $response['carga'] = $cargaAcademica;
        return response()->json($response);
    }
    public function storeLink(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'meet' => 'required',

        ], $messages = [
            'required' => '* El link de meet es obligatorio.',
        ]);

        $contexto = $this->contextoActual();
        abort_unless($contexto, 403);
        [$docenteApto, $periodo] = $contexto;

        $carga = $this->cargaActualDelDocente(
            (int) $request->id,
            $docenteApto->docentes_id,
            $periodo->id,
            true
        );
        abort_unless($carga, 403);

        DB::beginTransaction();
        try {
            $carga->link = $request->meet;
            $carga->save();

            DB::commit();
            $response["message"] = 'Registro Guardado';
            $response["status"] = true;
        } catch (\Exception $e) {
            DB::rollback();
            $response["message"] =  'Error al Guardar, intentelo nuevamante.';
            $response["status"] =  false;
        }

        return redirect()->back()
            ->with('response', $response);
    }
    public function indexTemario()
    {
        // return view('web.docente.temario');
        return Inertia::render('Docente/Recurso/Temario');
    }
    public function getCursosDocenteTemario()
    {
        $temarios = [];
        $contexto = $this->contextoActual();
        if (!$contexto) {
            return response()->json(["temarios" => $temarios]);
        }
        [$docenteApto, $periodo] = $contexto;
        $cargaAcademica = DB::table('carga_academicas as ca')
            ->select(
                'c.denominacion as curso',
                'c.color as color',
                'c.id as id',
                'a.id as idArea',
                'a.denominacion as area'
            )
            ->join('cursos as c', 'c.id', 'ca.cursos_id')
            ->join('grupo_aulas as ga', 'ga.id', 'ca.grupo_aulas_id')
            ->join('areas as a', 'a.id', 'ga.areas_id')
            ->where('ca.docentes_id', $docenteApto->docentes_id)
            ->where('ca.periodos_id', $periodo->id)
            ->where('ca.estado', '1')
            ->distinct()
            ->orderBy('a.id', 'asc')
            ->orderBy('c.id', 'asc')
            ->get();

        $curriculasPorArea = $this->obtenerCurriculasPorArea($cargaAcademica->pluck('idArea')->all());
        $curriculaDetalles = $this->obtenerCurriculaDetallesPorAreaYCurso($cargaAcademica, $curriculasPorArea);

        $temariosPorDetalle = collect();

        if ($curriculaDetalles->isNotEmpty()) {
            $temariosPorDetalle = DB::table('temarios')
                ->select('path', 'id', 'curricula_detalles_id')
                ->where('periodos_id', $periodo->id)
                ->whereIn('curricula_detalles_id', $curriculaDetalles->pluck('id')->all())
                ->get()
                ->keyBy('curricula_detalles_id');
        }

        foreach ($cargaAcademica as $k => $val) {
            $curricula = $curriculasPorArea->get($val->idArea);
            $curriculaDetalle = $curricula
                ? $curriculaDetalles->get($curricula->id . '-' . $val->id)
                : null;

            if (!$curriculaDetalle) {
                continue;
            }

            $obj = new \stdClass;
            $obj->id = $val->id;
            $obj->area = $val->area;
            $obj->curso = $val->curso;
            $obj->color = $val->color;
            $obj->base_path = config('app.external_image_url');
            $obj->temarios = $temariosPorDetalle->get($curriculaDetalle->id);
            $temarios[] = $obj;
        }
        $response["temarios"] = $temarios;
        return response()->json($response);
    }
    public function indexCuadernillo()
    {
        // return view('web.docente.cuadernillos');
        $response["url_external"] = config('app.external_image_url');
        return Inertia::render('Docente/Recurso/Cuadernillo',["data"=>$response]);
    }
    public function getCursosDocente()
    {
        $cuadernillos = [];
        $contexto = $this->contextoActual();
        if (!$contexto) {
            return response()->json(["cuadernillos" => $cuadernillos]);
        }
        [$docenteApto, $periodo] = $contexto;
        $cargaAcademica = DB::table('carga_academicas as ca')
            ->select(
                'c.denominacion as curso',
                'c.color as color',
                'c.id as id',
                'a.id as idArea',
                'a.denominacion as area'
            )
            ->join('cursos as c', 'c.id', 'ca.cursos_id')
            ->join('grupo_aulas as ga', 'ga.id', 'ca.grupo_aulas_id')
            ->join('areas as a', 'a.id', 'ga.areas_id')
            ->where('ca.docentes_id', $docenteApto->docentes_id)
            ->where('ca.periodos_id', $periodo->id)
            ->where('ca.estado', '1')
            ->distinct()
            ->orderBy('a.id', 'asc')
            ->orderBy('c.id', 'asc')
            ->get();

        $curriculasPorArea = $this->obtenerCurriculasPorArea($cargaAcademica->pluck('idArea')->all());
        $curriculaDetalles = $this->obtenerCurriculaDetallesPorAreaYCurso($cargaAcademica, $curriculasPorArea);

        $cuadernillosPorDetalle = collect();

        if ($curriculaDetalles->isNotEmpty()) {
            $cuadernillosPorDetalle = DB::table('cuadernillos')
                ->select('semana', 'path', 'id', 'tipo', 'curricula_detalles_id')
                ->where('periodos_id', $periodo->id)
                ->whereIn('curricula_detalles_id', $curriculaDetalles->pluck('id')->all())
                ->orderBy('semana')
                ->get()
                ->groupBy('curricula_detalles_id');
        }

        foreach ($cargaAcademica as $k => $val) {
            $curricula = $curriculasPorArea->get($val->idArea);
            $curriculaDetalle = $curricula
                ? $curriculaDetalles->get($curricula->id . '-' . $val->id)
                : null;

            if (!$curriculaDetalle) {
                continue;
            }

            $cuadernillosDetalle = $cuadernillosPorDetalle->get($curriculaDetalle->id, collect());
            $obj = new \stdClass;
            $obj->id = $val->id;
            $obj->area = $val->area;
            $obj->curso = $val->curso;
            $obj->color = $val->color;
            $obj->base_path = config('app.external_image_url');
            $obj->cuadernillos = $cuadernillosDetalle
                ->where('tipo', '1')
                ->values()
                ->map(function ($cuadernillo) {
                    unset($cuadernillo->tipo, $cuadernillo->curricula_detalles_id);

                    return $cuadernillo;
                });
            $obj->cuadernillosEstudiante = $cuadernillosDetalle
                ->where('tipo', '2')
                ->values()
                ->map(function ($cuadernillo) {
                    unset($cuadernillo->tipo, $cuadernillo->curricula_detalles_id);

                    return $cuadernillo;
                });
            $cuadernillos[] = $obj;
        }
        $response["cuadernillos"] = $cuadernillos;
        return response()->json($response);
    }
    public function getUrlCuadernillo(Request $request)
    {
        $contexto = $this->contextoActual();
        if (!$contexto) {
            return "";
        }
        [$docenteApto, $periodo] = $contexto;

        $tieneCurso = DB::table('carga_academicas as ca')
            ->join('grupo_aulas as ga', 'ga.id', 'ca.grupo_aulas_id')
            ->where('ca.docentes_id', $docenteApto->docentes_id)
            ->where('ca.periodos_id', $periodo->id)
            ->where('ca.estado', '1')
            ->where('ca.cursos_id', $request->curso)
            ->where('ga.areas_id', $request->area)
            ->exists();

        abort_unless($tieneCurso, 403);

        $cuadernillo = DB::table('cuadernillos as cu')
            ->select('cu.path')
            ->join('curricula_detalles as cd', 'cd.id', 'cu.curricula_detalles_id')
            ->join('curriculas as cur', 'cur.id', 'cd.curriculas_id')
            ->where('cu.semana', $request->semana)
            ->where('cu.tipo', '1')
            ->where('cu.periodos_id', $periodo->id)
            ->where('cd.cursos_id', $request->curso)
            ->where('cur.areas_id', $request->area)
            ->orderBy('cur.id', 'asc')
            ->first();
        // dd($cuadernillo);

        if (empty($cuadernillo)) {
            return "";
        }

        return response()->json($cuadernillo);
    }
    public function indexSesiones()
    {
        // return view('web.docente.sesiones');
        $response["url_external"] = config('app.external_image_url');

        return Inertia::render('Docente/Recurso/Sesion',["data"=>$response]);
    }
    public function listaSesion(Request $request)
    {
        $contexto = $this->contextoActual();
        if (!$contexto) {
            return response()->json(['sesiones' => []]);
        }
        [$docenteApto, $periodo] = $contexto;

        // $table = new EloquentVueTables;
        $data = Sesiones::select(
            'sesiones.tema',
            DB::raw('DATE_FORMAT(sesiones.fecha,"%Y-%m-%d") as fecha'),
            'sesiones.carga_academicas_id',
            'c.denominacion as curso',
            'sesiones.id',
            'g.denominacion as grupo',
            DB::raw($this->modalidadSedeSql('s') . ' as modalidad')
        );
        // $data = $table->get(new Sesiones, );

        $data = $data->join('carga_academicas as ca', 'ca.id', 'sesiones.carga_academicas_id')
            ->join('cursos as c', 'c.id', 'ca.cursos_id')
            ->join('grupo_aulas as ga', 'ga.id', 'ca.grupo_aulas_id')
            ->join('grupos as g', 'g.id', 'ga.grupos_id')
            ->join('aulas as au', 'au.id', 'ga.aulas_id')
            ->join('locales as l', 'l.id', 'au.locales_id')
            ->join('sedes as s', 's.id', 'l.sedes_id');

        $data = $data
            ->where('sesiones.periodos_id', $periodo->id)
            ->where('ca.docentes_id', $docenteApto->docentes_id)
            ->where('ca.periodos_id', $periodo->id)
            ->where('ca.estado', '1');
        if (isset($request->curso)) {
            $data = $data->where('ca.id', $request->curso);
        }

        $response["sesiones"] = $data
            ->orderByDesc('sesiones.fecha')
            ->orderByDesc('sesiones.id')
            ->get();
        return response()->json($response);
    }
    public function getCursosCarga()
    {
        $contexto = $this->contextoActual();
        if (!$contexto) {
            return response()->json([]);
        }
        [$docenteApto, $periodo] = $contexto;
        $cargaAcademica = DB::table('carga_academicas as ca')
            ->select(
                'c.denominacion as curso',
                'g.denominacion as grupo',
                DB::raw($this->modalidadSedeSql('s') . ' as modalidad'),
                'ca.id as id'
            )
            ->join('cursos as c', 'c.id', 'ca.cursos_id')
            ->join('grupo_aulas as ga', 'ga.id', 'ca.grupo_aulas_id')
            ->join('grupos as g', 'g.id', 'ga.grupos_id')
            ->join('areas as a', 'a.id', 'ga.areas_id')
            ->join('aulas as au', 'au.id', 'ga.aulas_id')
            ->join('locales as l', 'l.id', 'au.locales_id')
            ->join('sedes as s', 's.id', 'l.sedes_id')
            ->where([
                ['ca.docentes_id', $docenteApto->docentes_id],
                ['ca.estado', '1'],
                ['ca.periodos_id', $periodo->id]
            ])
            ->orderBy('c.denominacion')
            ->orderBy('g.denominacion')
            ->get()
            ->map(function ($carga) {
                $carga->name = sprintf(
                    '%s (%s) - %s',
                    $carga->curso,
                    $carga->grupo,
                    $carga->modalidad
                );

                return $carga;
            });

        return response()->json($cargaAcademica);
    }
    public function storeSesion(Request $request)
    {
        $request->validate([
            'fecha' => 'required|date',
            'tema' => 'required',
            'carga' => 'required|integer',

        ], $messages = [
            'required' => '* El campo :attribute es obligatorio.',
        ]);

        $contexto = $this->contextoActual();
        abort_unless($contexto, 403);
        [$docenteApto, $periodo] = $contexto;

        $carga = $this->cargaActualDelDocente(
            (int) $request->carga,
            $docenteApto->docentes_id,
            $periodo->id,
            true
        );
        abort_unless($carga, 403);

        $date = new \DateTime($request->fecha);
        $fechaFormat = $date->format('Y-m-d');
        // dd($request->all());
        DB::beginTransaction();
        try {
            $data = new Sesiones;
            $data->tema = $request->tema;
            $data->fecha = $fechaFormat;
            $data->carga_academicas_id = $carga->id;
            $data->periodos_id = $periodo->id;
            $data->save();

            DB::commit();
            $response["message"] = 'Registro guardado correctamente';
            $response["status"] = true;
        } catch (\Exception $e) {
            DB::rollback();
            $response["message"] =  'Error al guardar, intentelo nuevamante.';
            $response["status"] =  false;
            $response["error"] = $e;
        }

        return redirect()->back()
            ->with('response', $response);
    }
    public function editSesion($id)
    {
        $contexto = $this->contextoActual();
        abort_unless($contexto, 403);
        [$docenteApto, $periodo] = $contexto;

        $sesion = $this->sesionActualDelDocente(
            (int) $id,
            $docenteApto->docentes_id,
            $periodo->id
        );
        abort_unless($sesion, 403);

        return $sesion;
    }
    public function updateSesion(Request $request, $id)
    {
        $request->validate([
            'fecha' => 'required|date',
            'tema' => 'required',
            'carga' => 'required|integer',

        ], $messages = [
            'required' => '* El campo :attribute es obligatorio.',
        ]);

        $contexto = $this->contextoActual();
        abort_unless($contexto, 403);
        [$docenteApto, $periodo] = $contexto;

        $sesion = $this->sesionActualDelDocente(
            (int) $id,
            $docenteApto->docentes_id,
            $periodo->id
        );
        $carga = $this->cargaActualDelDocente(
            (int) $request->carga,
            $docenteApto->docentes_id,
            $periodo->id,
            true
        );
        abort_unless($sesion && $carga, 403);

        $date = new \DateTime($request->fecha);
        $fechaFormat = $date->format('Y-m-d');
        // dd($request->all());
        DB::beginTransaction();
        try {
            $sesion->tema = $request->tema;
            $sesion->fecha = $fechaFormat;
            $sesion->carga_academicas_id = $carga->id;
            $sesion->periodos_id = $periodo->id;
            $sesion->save();

            DB::commit();
            $response["message"] = 'Registro actualizado correctamente';
            $response["status"] = true;
        } catch (\Exception $e) {
            DB::rollback();
            $response["message"] =  'Error al actualizar registro, intentelo nuevamante.';
            $response["status"] =  false;
            $response["e"] = $e->getMessage();
        }

        return redirect()->back()
            ->with('response', $response);
    }
    public function getEstudiantes($id)
    {
        $contexto = $this->contextoActual();
        if (!$contexto) {
            return response()->json(['estudiantes' => []]);
        }
        [$docenteApto, $periodo] = $contexto;

        $tieneGrupo = CargaAcademica::query()
            ->delDocenteEnPeriodo($docenteApto->docentes_id, $periodo->id)
            ->where('estado', '1')
            ->where('grupo_aulas_id', $id)
            ->exists();
        abort_unless($tieneGrupo, 403);

        $response["estudiantes"] = DB::table('matriculas as m')
            ->select(
                DB::raw("CONCAT(e.paterno,' ',e.materno,' ',e.nombres) as nombres"),
                "e.paterno",
                "e.materno",
                "e.nombres",
                "e.nro_documento",
                "e.usuario"
            )
            ->join("estudiantes as e", "e.id", "m.estudiantes_id")
            ->where("m.grupo_aulas_id", $id)
            ->where("m.periodos_id", $periodo->id)
            ->orderBy("e.paterno")
            ->orderBy("e.materno")
            ->orderBy("e.nombres")
            ->get();

        return $response;
    }

    private function contextoActual(): ?array
    {
        $periodo = Periodo::actual();
        $docenteApto = Auth::guard('docente')->user();

        if (!$periodo || !$docenteApto || !$docenteApto->estaHabilitadoEnPeriodo($periodo->id)) {
            return null;
        }

        return [$docenteApto, $periodo];
    }

    private function cargaActualDelDocente($cargaId, $docenteId, $periodoId, bool $soloActiva = false)
    {
        return CargaAcademica::query()
            ->whereKey($cargaId)
            ->delDocenteEnPeriodo($docenteId, $periodoId)
            ->when($soloActiva, function ($query) {
                $query->where('estado', '1');
            })
            ->first();
    }

    private function sesionActualDelDocente($sesionId, $docenteId, $periodoId)
    {
        return Sesiones::query()
            ->whereKey($sesionId)
            ->delDocenteEnPeriodo($docenteId, $periodoId)
            ->whereHas('carga', function ($query) {
                $query->where('estado', '1');
            })
            ->first();
    }

    private function modalidadSedeSql(string $alias): string
    {
        return "CASE WHEN {$alias}.id = 1 OR LOWER(COALESCE({$alias}.denominacion, '')) LIKE '%virtual%' THEN 'Virtual' ELSE 'Presencial' END";
    }

    private function obtenerCurriculasPorArea(array $areaIds)
    {
        if (empty($areaIds)) {
            return collect();
        }

        return Curricula::whereIn('areas_id', $areaIds)
            ->orderBy('id')
            ->get()
            ->groupBy('areas_id')
            ->map(function ($curriculas) {
                return $curriculas->first();
            });
    }

    private function obtenerCurriculaDetallesPorAreaYCurso($cargaAcademica, $curriculasPorArea)
    {
        if ($cargaAcademica->isEmpty() || $curriculasPorArea->isEmpty()) {
            return collect();
        }

        $curriculaIds = $curriculasPorArea->pluck('id')->filter()->unique()->values()->all();
        $cursoIds = $cargaAcademica->pluck('id')->unique()->values()->all();

        if (empty($curriculaIds) || empty($cursoIds)) {
            return collect();
        }

        return CurriculaDetalle::whereIn('curriculas_id', $curriculaIds)
            ->whereIn('cursos_id', $cursoIds)
            ->get()
            ->keyBy(function ($curriculaDetalle) {
                return $curriculaDetalle->curriculas_id . '-' . $curriculaDetalle->cursos_id;
            });
    }
}
