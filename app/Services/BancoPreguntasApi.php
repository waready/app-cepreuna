<?php

namespace App\Services;

use App\Exceptions\BancoPreguntasApiException;
use App\Support\DocumentoWord;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class BancoPreguntasApi
{
    public function crearEntrega(array $payload, UploadedFile $archivo)
    {
        $path = $archivo->getRealPath();
        $stream = is_string($path) ? fopen($path, 'rb') : false;

        if ($stream === false) {
            throw new BancoPreguntasApiException('No se pudo leer el documento Word.');
        }

        try {
            $response = $this->client()
                ->attach(
                    'archivo',
                    $stream,
                    $archivo->getClientOriginalName(),
                    ['Content-Type' => DocumentoWord::MIME]
                )
                ->post($this->url(''), $payload);
        } catch (ConnectionException $exception) {
            throw new BancoPreguntasApiException(
                'El servicio central de documentos no esta disponible.',
                503
            );
        } finally {
            fclose($stream);
        }

        $this->ensureSuccessful($response);

        return $response->json();
    }

    public function descargarEntrega($loteId, $docenteId, $periodoId)
    {
        return $this->download(
            $this->url('/'.(int) $loteId.'/archivo'),
            $docenteId,
            $periodoId
        );
    }

    public function descargarRevision($loteId, $docenteId, $periodoId)
    {
        return $this->download(
            $this->url('/'.(int) $loteId.'/revision'),
            $docenteId,
            $periodoId
        );
    }

    public function eliminarEntrega($loteId, $docenteId, $periodoId)
    {
        try {
            $response = $this->client()->delete($this->url('/'.(int) $loteId), [
                'docentes_id' => (int) $docenteId,
                'periodos_id' => (int) $periodoId,
            ]);
        } catch (ConnectionException $exception) {
            throw new BancoPreguntasApiException(
                'El servicio central de documentos no esta disponible.',
                503
            );
        }

        $this->ensureSuccessful($response);

        return $response->json();
    }

    private function download($url, $docenteId, $periodoId)
    {
        try {
            $response = $this->client()->get($url, [
                'docentes_id' => (int) $docenteId,
                'periodos_id' => (int) $periodoId,
            ]);
        } catch (ConnectionException $exception) {
            throw new BancoPreguntasApiException(
                'El servicio central de documentos no esta disponible.',
                503
            );
        }

        $this->ensureSuccessful($response);

        return $response->body();
    }

    private function client()
    {
        $token = (string) config('services.banco_preguntas.token');

        if ($token === '') {
            throw new BancoPreguntasApiException(
                'La integracion del banco de preguntas no esta configurada.',
                503
            );
        }

        return Http::withHeaders([
            'X-Cepre-App-Token' => $token,
            'Accept' => 'application/json',
        ])->timeout((int) config('services.banco_preguntas.timeout', 30));
    }

    private function url($suffix)
    {
        $baseUrl = rtrim((string) config('services.banco_preguntas.url'), '/');

        if ($baseUrl === '') {
            throw new BancoPreguntasApiException(
                'La URL del banco de preguntas no esta configurada.',
                503
            );
        }

        return $baseUrl.'/integraciones/cepre-app/banco-preguntas'.$suffix;
    }

    private function ensureSuccessful(Response $response)
    {
        if ($response->successful()) {
            return;
        }

        $payload = $response->json();
        $errors = is_array($payload) && isset($payload['errors']) && is_array($payload['errors'])
            ? $payload['errors']
            : [];
        $message = is_array($payload) && ! empty($payload['message'])
            ? (string) $payload['message']
            : 'El servicio central no pudo procesar el documento.';

        throw new BancoPreguntasApiException($message, $response->status(), $errors);
    }
}
