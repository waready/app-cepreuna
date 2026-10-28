<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente del tramite documentario, que vive en el backend de sistemas.
 *
 * Las dos aplicaciones comparten base de datos pero no codigo: el panel puede
 * leer las tablas, pero los PDF solo los genera el backend. Por eso todo pasa
 * por aqui, con la misma credencial de integracion que el banco de preguntas.
 *
 * El docente siempre se identifica por su documento. El backend comprueba que
 * el expediente pedido sea suyo, asi que un docente no puede descargar los
 * papeles de otro aunque conozca el codigo.
 */
class TramiteApi
{
    /** Lo que el panel muestra: informes del ciclo con su lista de verificacion. */
    public function expediente(string $documento): array
    {
        $r = $this->cliente()->get($this->url("/expediente/{$documento}"));

        // Solo el "no se encontro al docente" del backend -que trae `mensaje`-
        // significa que no hay nada que mostrar. Cualquier otro 404 es una URL
        // mal configurada y tiene que verse como error: tratarlo como "sin
        // informes" escondio una configuracion equivocada en produccion.
        if ($r->status() === 404 && is_array($r->json()) && array_key_exists('mensaje', $r->json())) {
            return ['docente' => null, 'informes' => []];
        }

        $r->throw();

        return $r->json() ?? ['docente' => null, 'informes' => []];
    }

    /** Pide al backend que arme o recalcule el expediente de un periodo. */
    public function armar(string $documento, int $periodoId): array
    {
        $r = $this->cliente()->post($this->url("/expediente/{$documento}/armar/{$periodoId}"));
        $r->throw();

        return $r->json() ?? [];
    }

    /**
     * Devuelve el PDF ya generado. Se trae entero a memoria y no se guarda:
     * el documento se arma al vuelo con los datos del momento y no tiene
     * sentido conservar copias que envejecen.
     */
    public function documento(string $documento, string $codigo, string $tipo): string
    {
        $r = $this->cliente()
            ->accept('application/pdf')
            ->get($this->url("/expediente/{$documento}/{$codigo}/{$tipo}"));

        $r->throw();

        return $r->body();
    }

    /**
     * Manda un anexo del docente al backend, que lo deja en su ficha.
     *
     * El archivo viaja por aqui y no se guarda en este lado: los documentos
     * del docente viven todos en el mismo sitio, y duplicarlos seria tener dos
     * versiones del mismo papel sin saber cual vale.
     */
    public function subir(string $documento, string $codigo, string $tipo, string $contenido, string $nombre): array
    {
        $r = $this->cliente()
            ->attach('archivo', $contenido, $nombre)
            ->post($this->url("/expediente/{$documento}/{$codigo}/{$tipo}/subir"));

        $r->throw();

        return $r->json() ?? [];
    }

    /** Marca o desmarca un documento como que no le corresponde al docente. */
    public function declarar(string $documento, string $codigo, string $tipo, bool $noAplica): array
    {
        $r = $this->cliente()->post(
            $this->url("/expediente/{$documento}/{$codigo}/{$tipo}/declarar"),
            ['no_aplica' => $noAplica],
        );

        $r->throw();

        return $r->json() ?? [];
    }

    private function cliente(): PendingRequest
    {
        $token = (string) config('services.tramite.token');

        if ($token === '') {
            throw new RuntimeException('La integracion del tramite documentario no esta configurada.');
        }

        return Http::withHeaders([
            'X-Cepre-App-Token' => $token,
            'Accept' => 'application/json',
        ])->timeout((int) config('services.tramite.timeout', 60));
    }

    private function url(string $sufijo): string
    {
        $base = rtrim((string) config('services.tramite.url'), '/');

        if ($base === '') {
            throw new RuntimeException('La URL del tramite documentario no esta configurada.');
        }

        return $base.$sufijo;
    }
}
