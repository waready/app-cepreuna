<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Models\CargaAcademica;
use Illuminate\Http\Request;
use App\Models\Docente;
use App\Models\DocenteApto;
use App\Models\AdjuntoGrado;
use App\Models\InscripcionDocente;
use App\Models\Periodo;


use Illuminate\Support\Facades\Auth;

class PerfilController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:docente');
    }
    public function index()
    {
        // dd(Auth::user()->usuario);
        return view('web.docente.perfil');
    }
    public function getDocente(){
        $idDocenteApto = Auth::guard('docente')->id();
        $docenteApto = DocenteApto::find($idDocenteApto);
        $periodo = Periodo::actual();

        if (!$docenteApto || !$periodo || !$docenteApto->tieneCargaEnPeriodo($periodo->id)) {
            return response()->json(['docente' => null, 'docentea' => null, 'grados' => []]);
        }

        $response['docente'] = Docente::with('tipoDocumento','gradoAcademico','programa')->find($docenteApto->docentes_id);
        $response['docentea'] = $docenteApto->usuario;
        $inscripcion = InscripcionDocente::where("docentes_id",$docenteApto->docentes_id)
            ->where("periodos_id", $periodo->id)
            ->latest('id')
            ->first();
        // dd($inscripcion);
        $response["grados"] = $inscripcion
            ? AdjuntoGrado::with(["gradoAcademico","programa"])->where("inscripcion_docentes_id",$inscripcion->id)->get()
            : [];
        return response()->json($response);
    }
}
