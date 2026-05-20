<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Auth;
use App\Models\Supplier;
use App\Models\Buy;
use Illuminate\Support\Facades\DB;

class SupplierExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function __construct(array $rango)
    {
        $this->rango = $rango;
    }

    public function collection()
    {
        $plant_id = Auth::user()->plant_id;
        // $planta = Plant::find($plant_id);
        $deuda = Buy::selectRaw("supplier_id, SUM(total) as deuda")->where([
            ["plant_id","=",$plant_id],
            ["tipo","=",'0'],
            ["estado","=",'1'],
            ["estado_deuda","=",'0']
        ]);
        // $fecha1 ='';
        // $fecha2 ='';
        if(isset($this->rango)){
            if(count($this->rango)!=0){
                $fecha1 = new \DateTime($this->rango[0]);
                $fecha2 = new \DateTime($this->rango[1]);
                $deuda = $deuda->whereBetween('fecha', [$fecha1->format('Y-m-d'), $fecha2->format('Y-m-d')]);

                // $fecha1 = $fecha1->format('d/m/Y');
                // $fecha2 = $fecha2->format('d/m/Y');
            }
        }
        $deuda=$deuda->groupBy('supplier_id');

        $query = Supplier::select('paterno','materno','nombres','dni',DB::raw('IFNULL(deuda,"0.00")'))
            ->leftJoinSub($deuda, 'c', function ($join) {
                $join->on('suppliers.id', '=', 'c.supplier_id');
            })
            ->where("plant_id",$plant_id)->get();

        return $query;
    }
}
