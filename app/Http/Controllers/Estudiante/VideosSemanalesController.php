<?php

namespace App\Http\Controllers\Estudiante;

use Inertia\Inertia;
use App\Models\Matricula;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

/**
 * Modulos de video por semana que no se administran desde el sistema: el
 * contenido vive en un archivo de config y cada modulo es uno de esos
 * archivos. Hoy sirve a los seminarios de idiomas y al reforzamiento.
 */
class VideosSemanalesController extends Controller
{
    public function seminarios()
    {
        return $this->render('seminarios');
    }

    public function reforzamiento()
    {
        return $this->render('reforzamiento');
    }

    private function render(string $modulo)
    {
        $total = $this->totalSemanas($modulo);

        return Inertia::render('Estudiante/VideosSemanales', [
            'data' => [
                'titulo' => config("{$modulo}.titulo", 'Videos'),
                'descripcion' => config("{$modulo}.descripcion", ''),
                'conCuadernillo' => (bool) config("{$modulo}.con_cuadernillo", false),
                'totalSemanas' => $total,
                'grupos' => $this->grupos($modulo, $total),
            ],
        ]);
    }

    /**
     * Las semanas de cada grupo, rellenando con null las que todavia no tienen
     * contenido para que la vista las muestre como "Proximamente".
     */
    private function grupos(string $modulo, int $total): array
    {
        $declarados = (array) config("{$modulo}.grupos", []);

        // El area solo se consulta si algun grupo la pide: hoy ninguno lo hace
        // y no tiene sentido pegarle a la base en cada carga de la pagina.
        $area = $this->algunGrupoFiltraPorArea($declarados) ? $this->areaDelEstudiante() : null;
        $grupos = [];

        foreach ($declarados as $clave => $grupo) {
            if (! $this->visibleParaElArea($grupo, $area)) {
                continue;
            }

            $declaradas = (array) ($grupo['semanas'] ?? []);
            $semanas = [];

            for ($numero = 1; $numero <= $total; $numero++) {
                $semana = (array) ($declaradas[$numero] ?? []);
                $video = $semana['video'] ?? null;
                $pdf = $this->urlDocumento($modulo, $semana['pdf'] ?? null);

                $semanas[] = [
                    'semana' => $numero,
                    'video' => $video,
                    'pdf' => $pdf,
                    'disponible' => filled($video) || filled($pdf),
                ];
            }

            $grupos[] = [
                'clave' => $clave,
                'nombre' => $grupo['nombre'] ?? ucfirst((string) $clave),
                'color' => $grupo['color'] ?? '#64748b',
                'publicadas' => count(array_filter($semanas, fn (array $s): bool => $s['disponible'])),
                'semanas' => $semanas,
            ];
        }

        return $grupos;
    }

    /**
     * Un grupo sin 'areas' lo ve todo el mundo; con 'areas' solo quienes
     * cursan esa area. Si no se puede determinar el area, se muestra: es
     * preferible mostrar de mas que esconderle contenido a un estudiante.
     */
    private function visibleParaElArea(array $grupo, ?int $area): bool
    {
        $areas = array_filter((array) ($grupo['areas'] ?? []));

        if (! $areas || $area === null) {
            return true;
        }

        return in_array($area, array_map('intval', $areas), true);
    }

    /**
     * @param array<string, array<string, mixed>> $grupos
     */
    private function algunGrupoFiltraPorArea(array $grupos): bool
    {
        foreach ($grupos as $grupo) {
            if (array_filter((array) ($grupo['areas'] ?? []))) {
                return true;
            }
        }

        return false;
    }

    private function areaDelEstudiante(): ?int
    {
        $matricula = Matricula::query()
            ->with('grupoAula')
            ->delEstudiante(Auth::user()->id)
            ->delPeriodoActual()
            ->latest('id')
            ->first();

        $area = optional(optional($matricula)->grupoAula)->area;

        return $area ? (int) $area->id : null;
    }

    private function totalSemanas(string $modulo): int
    {
        return max(1, (int) config("{$modulo}.total_semanas", 8));
    }

    /**
     * Un enlace externo se respeta tal cual; una ruta relativa se resuelve
     * contra el host de documentos, como el resto de la aplicacion.
     */
    private function urlDocumento(string $modulo, ?string $documento): ?string
    {
        if (blank($documento)) {
            return null;
        }

        if (preg_match('#^https?://#i', $documento)) {
            return $documento;
        }

        $base = rtrim((string) config('app.external_image_url'), '/');
        $carpeta = trim((string) config("{$modulo}.ruta_pdf", ''), '/');
        $archivo = ltrim($documento, '/');

        return $base.'/'.($carpeta !== '' ? $carpeta.'/' : '').$archivo;
    }
}
