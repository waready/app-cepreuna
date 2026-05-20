<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GrupoAula;
use App\Models\PlantillaHorario;
use App\Models\Turno;
use App\Models\Horario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Periodo;
use PDF;

class ReporteController extends Controller
{
    public function rpDocentePartePdf(Request $request){
        // dd($request);
        $grupo = $request->grupo;
        $today = date("Y-m-d");
        $fecha = new \DateTime($today);
        $semana = $fecha->format("N");
        $dias = ["Lunes", "Martes", "Miercoles", "Jueves", "Viernes"];

        $dia = $dias[(int)($semana - 1)];
        $fecha = $fecha->format("d/m/Y");


        $grupoAula = GrupoAula::with(["grupo","aula","area","turno"])->find($grupo);
        // dd($grupoAula);
        $response["turno"] = Turno::find($grupoAula->turnos_id);
        $plantillaHorario = [];
        $plantilla = PlantillaHorario::select(
            "id",
            DB::raw("DATE_FORMAT(hora_inicio,'%H:%i') as horaInicio"),
            DB::raw("DATE_FORMAT(hora_fin,'%H:%i') as horaFin"),
            "tipo"
        )
            ->where("turnos_id", $grupoAula->turnos_id)
            // ->where("dia",$semana)
            ->where("estado", "1")
            ->get();

        foreach ($plantilla as $k => $val) {
            $obj = new \stdClass;
            $obj->id = $val->id;
            $obj->hora_inicio = $val->horaInicio;
            $obj->hora_fin = $val->horaFin;
            $obj->tipo = $val->tipo;
            $obj->horario = Horario::with(['curso', 'carga'])
                ->select("horarios.*","ad.estado", "ad.id as idAsistencia", "ad.docentes_id as IdDocente")
                ->whereHas('carga', function (Builder $query) use ($grupo) {
                    $query->where('grupo_aulas_id', $grupo)
                        ->where("estado", "1");
                })
                ->leftJoin('asistencia_docentes as ad', function ($join) use ($today) {
                    $join->on('ad.carga_academicas_id', '=', 'horarios.carga_academicas_id')
                        ->where('ad.fecha', '=', $today);
                })
                // ->where("carga_academicas_id",$inscripcionDocente->id)
                ->where("plantilla_horarios_id", $val->id)
                ->where("dia", $semana)
                ->orderBy("dia", "asc")
                ->first();
            $plantillaHorario[] = $obj;
        }
        // $response["horario"] = $plantillaHorario;
        // dd($plantillaHorario);

        // $asistencia = AsistenciaDocente::with(["docente", "carga", "sesiones", "user"])
        //     ->where("fecha", $fecha)
        //     ->whereHas('carga', function (Builder $query) use ($grupo) {
        //         $query->where('grupo_aulas_id', $grupo);
        //         // ->where("estado", "1");
        //     })
        //     ->get();
        // dd($asistencia);
        // $usuario = User::find($asistencia->users_id);
        // $grupoAula = GrupoAula::with("grupo")->find($grupo);
        // dd($grupoAula);
        $user = Auth::user()->paterno . ' ' . Auth::user()->materno . ' ' . Auth::user()->name;

        // $fecha = date("d/m/Y", strtotime($fecha));
        // $hora = date("H:i:s", strtotime($asistencia->created_at));
        // dd($estudiantes);
        $periodo = Periodo::where("estado", "1")->first();
        $pdf = new PDF();
        $pdf::SetMargins(10, 35, 10);
        PDF::setFooterCallback(function ($pdf) use ($user) {
            $pdf->SetY(-15);
            // $y = $pdf->SetY(-15);
            $pdf->Line(10, 283, 200, 283);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->Cell(170, 10, $user . ' - ' . date("d/m/Y h:i a"), "t", false, 'L', 0, '', 0, false, 'T', 'M');
            $pdf->Cell(0, 10, 'Pagina ' . $pdf->getAliasNumPage() . '/' . $pdf->getAliasNbPages(), "t", false, 'L', 0, '', 0, false, 'T', 'M');
        });
        PDF::setHeaderCallback(function ($pdf) {
            $pdf->SetY(10);
            $pdf->Image('images/UNAPUNO.png', 50, 6, 20, 20, 'PNG', '', '', true, 150, '', false, false, 0, false, false, false);
            $pdf->Image('images/logo.png', 220, 6, 30, 20, 'PNG', '', '', true, 150, '', false, false, 0, false, false, false);
            $pdf->SetFont('helvetica', 'b', 14);
            $pdf->Cell(0, 6, 'UNIVERSIDAD NACIONAL DEL ALTIPLANO PUNO', 0, 1, 'C', 0, '', 0);
            $pdf->SetFont('helvetica', 'b', 12);
            $pdf->Cell(0, 6, "Centro de Estudios Pre Universitario", 0, 1, 'C', 0, '', 0);
        });
        $pdf::SetTitle('Asistencia Estudiante');
        $pdf::AddPage('L');

        $y = $pdf::GetY();
        $pdf::SetY($y);

        $pdf::SetFont('helvetica', 'b', 12);
        // $pdf::Cell(0, 6, 'FICHA DE INSCRIPCION DOCENTE CEPREUNA CICLO '.$periodo->inicio_ciclo.' - '.$periodo->fin_ciclo, 0, 1, 'C', 0, '', 0);
        $pdf::MultiCell(0, 10, 'PARTE DOCENTES', 0, 'C', 0, 1, '', '', true);
        // $pdf::ln();
        // *******************
        $pdf::SetFont('helvetica', 'b', 10);
        $pdf::Cell(20, 6, 'GRUPO:', 0, 0, 'L', 0, '', 1);
        $pdf::SetFont('helvetica', '', 9);
        $pdf::Cell(60, 6, $grupoAula->grupo->denominacion, 0, 0, 'L', 0, '', 1);

        $pdf::SetFont('helvetica', 'b', 10);
        $pdf::Cell(20, 6, 'SEDE:', 0, 0, 'L', 0, '', 1);
        $pdf::SetFont('helvetica', '', 9);
        $pdf::Cell(60, 6, $grupoAula->aula->local->sede->denominacion, 0, 0, 'L', 0, '', 1);

        $pdf::SetFont('helvetica', 'b', 10);
        $pdf::Cell(20, 6, 'AREA:', 0, 0, 'L', 0, '', 1);
        $pdf::SetFont('helvetica', '', 9);
        $pdf::Cell(60, 6,  $grupoAula->area->denominacion, 0, 1, 'L', 0, '', 1);
        // *******************
        $pdf::SetFont('helvetica', 'b', 10);
        $pdf::Cell(20, 6, 'AUXILIAR:', 0, 0, 'L', 0, '', 1);
        $pdf::SetFont('helvetica', '', 9);
        $pdf::Cell(60, 6, $user, 0, 0, 'L', 0, '', 1);

        $pdf::SetFont('helvetica', 'b', 10);
        $pdf::Cell(20, 6, 'TURNO:', 0, 0, 'L', 0, '', 1);
        $pdf::SetFont('helvetica', '', 9);
        $pdf::Cell(30, 6, $grupoAula->turno->denominacion, 0, 0, 'L', 0, '', 1);

        $pdf::SetFont('helvetica', 'b', 10);
        $pdf::Cell(20, 6, 'LOCAL:', 0, 0, 'L', 0, '', 1);
        $pdf::SetFont('helvetica', '', 9);
        $pdf::Cell(50, 6, $grupoAula->aula->local->direccion, 0, 0, 'L', 0, '', 1);

        $pdf::SetFont('helvetica', 'b', 10);
        $pdf::Cell(20, 6, 'FECHA:', 0, 0, 'L', 0, '', 1);
        $pdf::SetFont('helvetica', '', 9);
        $pdf::Cell(40, 6,  $fecha, 0, 1, 'L', 0, '', 1);
        // *******************
        $style = array(
            'border' => 0,
            'vpadding' => 0,
            'hpadding' => 0,
            'fgcolor' => array(0,0,0),
            'bgcolor' => false, //array(255,255,255)
            'module_width' => 1, // width of a single module in points
            'module_height' => 1 // height of a single module in points
        );

        $pdf::ln();
        $pdf::SetFont('helvetica', '', 9);
        $tabla = '<table cellspacing="0" cellpadding="3" border="1">
                    <thead>
                        <tr style="font-weight: bold;">
                            <td width="45"  align="center">HORA</td>
                            <td width="60"  align="center">DNI</td>
                            <td width="130" align="center">APELLIDOS Y NOMBRES</td>
                            <td width="90"  align="center">CURSO</td>
                            <td width="130"  align="center">TEMA</td>
                            <td width="50"  align="center">HORA DE ENTRADA</td>
                            <td width="90"  align="center">FIRMA</td>
                            <td width="50"  align="center">HORA DE SALIDA</td>
                            <td width="90"  align="center">FIRMA</td>
                            <td width="50"  align="center">QR</td>
                            </tr>
                    </thead></table>';
        // $pdf::SetFont('helvetica', '', 10);
        // dd($plantillaHorario);
        $tabla .= '<table cellspacing="0" cellpadding="6" border="1"><tbody>';
        $temp = 0;
        $hora_ini = "";
        $hora_fin = "";
        $dni = "";
        $nombre = "";
        $curso = "";
        $i=0;
        $qr = [];
        // dd($plantillaHorario);
        foreach ($plantillaHorario as $key => $value) {
            if($value->horario!=null){
                // echo $key."<br>";
                if($i == 0){
                    $hora_ini = $value->hora_inicio;
                }
                if(($key+1)!=count($plantillaHorario)){
                    if(isset($plantillaHorario[$key+1]->horario->carga->id)&&$value->horario->carga->id == $plantillaHorario[$key+1]->horario->carga->id){
                        // $hora_ini = $value->hora_inicio;
                        $i=1;
                    }else{
                        // $hora_ini = $value->hora_inicio;
                        $hora_fin = $value->hora_fin;
                        $dni = $value->horario->carga->docente->nro_documento;
                        $nombre = $value->horario->carga->docente->paterno . ' ' . $value->horario->carga->docente->materno . ' ' . $value->horario->carga->docente->nombres;
                        $curso = $value->horario->carga->curso->denominacion;
                        $tabla .= '<tr>
                                    <td width="45" height="60" align="center">' . $hora_ini.' '.$hora_fin. '</td>
                                    <td width="60"  align="center">' . $dni . '</td>
                                    <td width="130" align="left">' . $nombre . '</td>
                                    <td width="90"  align="center">' . $curso . '</td>
                                    <td width="130"  align="center"></td>
                                    <td width="50"  align="center"></td>
                                    <td width="90"  align="center"></td>
                                    <td width="50"  align="center"></td>
                                    <td width="90"  align="center"></td>
                                    <td width="50"  align="center"></td>
                                    </tr>';
                        $i=0;
                        $obj = new \stdClass;
                        $obj->cargas_id = $value->horario->carga->id;
                        $obj->docentes_id = $value->horario->carga->docentes_id;
                        $obj->estado = $value->horario->estado;
                        $qr[]= $obj;
                    }
                }else{
                    $hora_fin = $value->hora_fin;
                    $dni = $value->horario->carga->docente->nro_documento;
                    $nombre = $value->horario->carga->docente->paterno . ' ' . $value->horario->carga->docente->materno . ' ' . $value->horario->carga->docente->nombres;
                    $curso = $value->horario->carga->curso->denominacion;
                    $tabla .= '<tr>
                                <td width="45" height="60"  align="center">' . $hora_ini.' '.$hora_fin. '</td>
                                <td width="60"  align="center">' . $dni . '</td>
                                <td width="130" align="left">' . $nombre . '</td>
                                <td width="90"  align="center">' . $curso . '</td>
                                <td width="130"  align="center"></td>
                                <td width="50"  align="center"></td>
                                <td width="90"  align="center"></td>
                                <td width="50"  align="center"></td>
                                <td width="90"  align="center"></td>
                                <td width="50"  align="center"></td>
                                </tr>';
                    $i=0;
                    $obj = new \stdClass;
                    $obj->cargas_id = $value->horario->carga->id;
                    $obj->docentes_id = $value->horario->carga->docentes_id;
                    $obj->estado = $value->horario->estado;
                    $qr[]= $obj;
                }

            }

        }
        // dd($qr);
        // $pdf::SetFont('helvetica', '', 10);
        $tabla .= '<tbody>';
        $tabla .= '</tbody></table>';
        $tabla .= '<table cellpadding="4">
                    <tr><td>REEMPLAZO</td></tr>
                    </table>';
        $tabla .= '<table cellspacing="0" cellpadding="8" border="1">
                    <thead>
                        <tr style="font-weight: bold;">
                            <td width="45"  align="center"></td>
                            <td width="60"  align="center"></td>
                            <td width="170" align="center"></td>
                            <td width="90"  align="center"></td>
                            <td width="140"  align="center"></td>
                            <td width="50"  align="center"></td>
                            <td width="90"  align="center"></td>
                            <td width="50"  align="center"></td>
                            <td width="90"  align="center"></td>
                        </tr>
                        <tr style="font-weight: bold;">
                            <td width="45"  align="center"></td>
                            <td width="60"  align="center"></td>
                            <td width="170" align="center"></td>
                            <td width="90"  align="center"></td>
                            <td width="140"  align="center"></td>
                            <td width="50"  align="center"></td>
                            <td width="90"  align="center"></td>
                            <td width="50"  align="center"></td>
                            <td width="90"  align="center"></td>
                        </tr>
                        <tr style="font-weight: bold;">
                            <td width="45"  align="center"></td>
                            <td width="60"  align="center"></td>
                            <td width="170" align="center"></td>
                            <td width="90"  align="center"></td>
                            <td width="140"  align="center"></td>
                            <td width="50"  align="center"></td>
                            <td width="90"  align="center"></td>
                            <td width="50"  align="center"></td>
                            <td width="90"  align="center"></td>
                        </tr>
                    </thead></table>';
        $pdf::writeHTML($tabla, true, false, true, false, 'C');
        $x = 270.5;
        $y = 75;
        foreach ($qr as $key => $value) {
            $text = $value->cargas_id."-".$value->docentes_id."-".$value->estado;
            $pdf::write2DBarcode($text, 'QRCODE,L', $x, $y, 15, 15, $style, 'N');
            $pdf::SetFont('helvetica', 'b', 9);
            $pdf::MultiCell(50, 4,$value->cargas_id."-".$value->docentes_id , 0, 'L', 0, 1, $x-1,$y+15, true);
            $y= $y+21;

        }
        // $pdf::SetFont('helvetica', 'b', 10);
        // $pdf::Cell(20, 6, 'REEMPLAZO', 0, 1, 'L', 0, '', 1);

        $pdf::SetAutoPageBreak(TRUE, 0);
        $pdf::Output($grupoAula->aula->local->sede->denominacion.'_'.$grupoAula->grupo->denominacion.'.pdf', 'D');
    }
}
