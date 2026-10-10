<?php

namespace App\Http\Controllers\Estudiante;

use Inertia\Inertia;
use App\Http\Controllers\Controller;

class SeminarioController extends Controller
{
    public function index()
    {
        return Inertia::render('Estudiante/Seminario', [
            'data' => [
                'idiomas' => $this->idiomas(),
                'totalSemanas' => $this->totalSemanas(),
            ],
        ]);
    }

    /**
     * Las ocho semanas de cada idioma, rellenando con null las que todavia no
     * tienen contenido para que la vista las muestre como "Proximamente".
     */
    private function idiomas(): array
    {
        $total = $this->totalSemanas();
        $idiomas = [];

        foreach ((array) config('seminarios.idiomas', []) as $clave => $idioma) {
            $declaradas = (array) ($idioma['semanas'] ?? []);
            $semanas = [];

            for ($numero = 1; $numero <= $total; $numero++) {
                $semana = (array) ($declaradas[$numero] ?? []);
                $video = $semana['video'] ?? null;
                $pdf = $this->urlDocumento($semana['pdf'] ?? null);

                $semanas[] = [
                    'semana' => $numero,
                    'video' => $video,
                    'pdf' => $pdf,
                    'disponible' => filled($video) || filled($pdf),
                ];
            }

            $idiomas[] = [
                'clave' => $clave,
                'nombre' => $idioma['nombre'] ?? ucfirst((string) $clave),
                'color' => $idioma['color'] ?? '#64748b',
                'publicadas' => count(array_filter($semanas, fn (array $s): bool => $s['disponible'])),
                'semanas' => $semanas,
            ];
        }

        return $idiomas;
    }

    private function totalSemanas(): int
    {
        return max(1, (int) config('seminarios.total_semanas', 8));
    }

    /**
     * Un enlace externo se respeta tal cual; una ruta relativa se resuelve
     * contra el host de documentos, como el resto de la aplicacion.
     */
    private function urlDocumento(?string $documento): ?string
    {
        if (blank($documento)) {
            return null;
        }

        if (preg_match('#^https?://#i', $documento)) {
            return $documento;
        }

        $base = rtrim((string) config('app.external_image_url'), '/');
        $carpeta = trim((string) config('seminarios.ruta_pdf', ''), '/');
        $archivo = ltrim($documento, '/');

        return $base.'/'.($carpeta !== '' ? $carpeta.'/' : '').$archivo;
    }
}
