<?php

namespace App\Http\Controllers;

use App\Models\AdjuntoGrado;
use App\Models\DocenteApto;
use App\Models\Estudiante;
use App\Models\InscripcionDocente;
use App\Models\Pais;
use App\Models\Periodo;
use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PerfilController extends Controller
{
    public function index()
    {
        if (Auth::guard('docente')->check()) {
            return $this->perfilDocente();
        }

        $dni = Auth::user()->nro_documento;
        $periodo = Periodo::actual();

        $estudiante = Estudiante::with([
            'colegio',
            'ubigeo',
            'ubigeo_nacimiento',
            'matricula' => function ($query) use ($periodo) {
                if ($periodo) {
                    $query->delPeriodoActual($periodo->id)->latest('id');
                }
            }
        ])
            ->where('estudiantes.nro_documento', $dni)
            ->whereHas('matricula', function (Builder $query) use ($periodo) {
                if ($periodo) {
                    $query->delPeriodoActual($periodo->id);
                    return;
                }

                $query->whereNotNull('matriculas.id');
            })
            ->first();

        if (isset($estudiante)) {
            $response["estudiante"] = $estudiante;
        }

        $response["paises"] = Pais::get();
        // $response["matricula"] = Matricula::where("estudiantes_id", $estudiante)
        //     ->first();
        // $response["estado"] = false;
        // $response['estudiante'] = Estudiante::with('tipoDocumento', 'colegio', 'ubigeo')
        //     ->select('nombres', 'paterno', 'materno', 'foto', 'edit', DB::raw("DATE_FORMAT(fecha_nac,'%d/%m/%Y') as fecha_nac"), 'anio_egreso', 'nro_documento', 'ubigeos_id')
        //     ->where('id', $estudiante)->first();


        return Inertia::render('Estudiante/Perfil', ["data" => $response]);
    }

    private function perfilDocente()
    {
        $periodo = Periodo::actual();
        $docenteApto = DocenteApto::query()
            ->with(['docente.tipoDocumento', 'docente.gradoAcademico', 'docente.programa'])
            ->find(Auth::guard('docente')->id());

        abort_unless(
            $periodo
                && $docenteApto
                && $docenteApto->docente
                && (int) $docenteApto->periodos_id === (int) $periodo->id,
            403
        );

        $inscripcion = InscripcionDocente::query()
            ->where('docentes_id', $docenteApto->docentes_id)
            ->where('periodos_id', $periodo->id)
            ->latest('id')
            ->first();

        $grados = $inscripcion
            ? AdjuntoGrado::with(['gradoAcademico', 'programa'])
                ->where('inscripcion_docentes_id', $inscripcion->id)
                ->get()
            : collect();

        return Inertia::render('Docente/Perfil', [
            'docente' => $docenteApto->docente,
            'cuenta' => ['usuario' => $docenteApto->usuario],
            'grados' => $grados,
            'periodo' => $periodo,
            'fotoPerfil' => MediaUrl::profile($docenteApto->docente->foto),
        ]);
    }
    public function actualizarEstudiante(Request $request)
    {
        $idEstudiante = Auth::user()->id;

        $this->validate($request, [
            'nombres' => 'required|regex:/^[a-zA-Z áéíóúÁÉÍÓÚñÑ]+$/',
            'paterno' => 'required|regex:/^[a-zA-Z áéíóúÁÉÍÓÚñÑ]+$/',
            'materno' => 'required|regex:/^[a-zA-Z áéíóúÁÉÍÓÚñÑ]+$/',
            // 'nro_documento' => 'required|regex:/^[0-9]+$/|size:8',
            'pais' => 'required',
            "fecha_nacimiento" => 'required',
            "anio_egreso" => 'required|integer|min:1900',
            'ubigeo' => 'required_if:pais,1',
            'foto' => 'required',
        ], $messages = [
            'required' => '* El campo es obligatorio.',
            'nombres.regex' => '* Solo se admiten letras.',
            'paterno.regex' => '* Solo se admiten letras.',
            'materno.regex' => '* Solo se admiten letras.',
            // 'nro_documento.regex' => '* Solo se admiten números.',
            'foto.required' => '* La fotografia es obligatoria.',
            // 'nro_documento.size' => '* Solo se admiten 8 números.',
            'ubigeo.required_if' => '* El campo distrito es obligatorio.',
            'fecha_nacimiento.required' => '* La fecha de nacimiento es obligatorio',
            'anio_egreso.min' => '* El año ingresado no es valido',
        ]);


        DB::beginTransaction();
        try {
            $this->save_image($request->foto, $idEstudiante);
            $estudiante = Estudiante::find($idEstudiante);
            $estudiante->nombres = mb_strtoupper($request->nombres);
            $estudiante->paterno = mb_strtoupper($request->paterno);
            $estudiante->materno = mb_strtoupper($request->materno);
            // $estudiante->nro_documento = $request->nro_documento;
            $estudiante->fecha_nac = date("Y-m-d", strtotime($request->fecha_nacimiento));
            $estudiante->anio_egreso = $request->anio_egreso;

            if ($request->pais > 1) {
                $estudiante->ubigeos_nacimiento = 0;
            } else {
                $estudiante->ubigeos_nacimiento = $request->ubigeo;
            }
            $estudiante->edit = "1";
            $estudiante->save();

            DB::commit();
            $response["message"] = "Datos actualizados correctamente";
            $response["status"] = true;
        } catch (\Exception $e) {
            DB::rollback();
            $response["message"] = "Error al actualizar datos, intentelo de nuevo";
            $response["status"] = false;
            $response["error"] = $e;
        }

        return redirect()->back()
            ->with('response', $response);
    }
    public function confirmarDatos()
    {
        $idEstudiante = Auth::user()->id;
        DB::beginTransaction();
        try {

            $estudiante = Estudiante::find($idEstudiante);

            $estudiante->edit = "1";
            $estudiante->save();

            DB::commit();
            $response["message"] = "Verificación de datos completa";
            $response["status"] = true;
        } catch (\Exception $e) {
            DB::rollback();
            $response["message"] = "Error al verificar datos, intentelo de nuevo";
            $response["status"] = false;
        }

        return redirect()->back()
            ->with('response', $response);
    }
    public function save_image($base64_image, $id)
    {
        $url = config('app.external_image_url') . '/api/perfil/guardar-foto/' . $id;
        $request = array('foto' => $base64_image);
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/json",
                'method'  => 'POST',
                'content' => json_encode($request),
            ),
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );
        $context  = stream_context_create($options);

        $response = file_get_contents($url, false, $context);
        $data = (array) json_decode($response);

        return $data;
        // $data = array('foto' => $base64_image);

        // $curl = curl_init();

        // curl_setopt_array($curl, array(
        //     CURLOPT_URL => env("EXTERNALURLIMAGE") . '/api/perfil/guardar-foto/' . $id,
        //     CURLOPT_RETURNTRANSFER => true,
        //     CURLOPT_ENCODING => '',
        //     CURLOPT_MAXREDIRS => 10,
        //     CURLOPT_TIMEOUT => 0,
        //     CURLOPT_FOLLOWLOCATION => true,
        //     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        //     CURLOPT_CUSTOMREQUEST => 'POST',
        //     CURLOPT_POSTFIELDS => $data,
        //     CURLOPT_HTTPHEADER => array(
        //         'Content-Type: application/json',
        //     ),
        // ));

        // $response = curl_exec($curl);

        // curl_close($curl);
        // return $response;
        // dd($response);
    }
}
