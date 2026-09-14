<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PagoArchivosApi
{
    public function validarPago(array $data, UploadedFile $voucher): array
    {
        $path = $voucher->getRealPath();
        $stream = is_string($path) ? fopen($path, 'rb') : false;

        if ($stream === false) {
            throw new RuntimeException('No se pudo leer el comprobante adjunto.');
        }

        try {
            $response = $this->client()
                ->attach(
                    'voucher',
                    $stream,
                    $voucher->getClientOriginalName(),
                    ['Content-Type' => $voucher->getMimeType() ?: 'application/octet-stream']
                )
                ->post($this->url('/pagos/validar'), $data);
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'El servicio central de pagos no esta disponible.',
                0,
                $exception
            );
        } finally {
            fclose($stream);
        }

        $this->ensureSuccessful($response);
        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('El servicio central devolvio una respuesta de pago invalida.');
        }

        return $payload;
    }

    public function registrarPagos(array $data): array
    {
        try {
            $response = $this->client()->post($this->url('/pagos/registrar'), $data);
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'El servicio central de pagos no esta disponible.',
                0,
                $exception
            );
        }

        $this->ensureSuccessful($response);
        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('El servicio central devolvio una respuesta de pago invalida.');
        }

        return $payload;
    }

    public function guardarVoucher(UploadedFile $voucher): string
    {
        $path = $voucher->getRealPath();
        $stream = is_string($path) ? fopen($path, 'rb') : false;

        if ($stream === false) {
            throw new RuntimeException('No se pudo leer el comprobante adjunto.');
        }

        try {
            $response = $this->client()
                ->attach(
                    'voucher',
                    $stream,
                    $voucher->getClientOriginalName(),
                    ['Content-Type' => $voucher->getMimeType() ?: 'application/octet-stream']
                )
                ->post($this->url('/vouchers'));
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'El servicio central de comprobantes no esta disponible.',
                0,
                $exception
            );
        } finally {
            fclose($stream);
        }

        $this->ensureSuccessful($response);
        $voucherPath = data_get($response->json(), 'data.path');

        if (! is_string($voucherPath) || trim($voucherPath) === '') {
            throw new RuntimeException('El servicio central no devolvio la ruta del comprobante.');
        }

        return $voucherPath;
    }

    public function eliminarVoucher(?string $voucherPath): void
    {
        if (! $voucherPath) {
            return;
        }

        try {
            $response = $this->client()->delete($this->url('/vouchers'), [
                'path' => $voucherPath,
            ]);
            $this->ensureSuccessful($response);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function client()
    {
        $token = (string) config('services.pago_archivos.token');

        if ($token === '') {
            throw new RuntimeException('La integracion de comprobantes no esta configurada.');
        }

        return Http::withHeaders([
            'X-Cepre-App-Token' => $token,
            'Accept' => 'application/json',
        ])->timeout((int) config('services.pago_archivos.timeout', 30));
    }

    private function url(string $suffix): string
    {
        $baseUrl = rtrim((string) config('services.pago_archivos.url'), '/');

        if ($baseUrl === '') {
            throw new RuntimeException('La URL del servicio central de comprobantes no esta configurada.');
        }

        return $baseUrl.'/integraciones/cepre-app'.$suffix;
    }

    private function ensureSuccessful(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $payload = $response->json();
        $message = $this->firstValidationMessage($payload);

        if ($message === null && is_array($payload) && ! empty($payload['message'])) {
            $message = (string) $payload['message'];
        }

        throw new RuntimeException($message ?: 'El servicio central no pudo procesar el pago.');
    }

    private function firstValidationMessage($payload): ?string
    {
        if (! is_array($payload) || ! isset($payload['errors']) || ! is_array($payload['errors'])) {
            return null;
        }

        foreach ($payload['errors'] as $messages) {
            if (is_array($messages) && isset($messages[0]) && is_string($messages[0])) {
                return $messages[0];
            }

            if (is_string($messages) && $messages !== '') {
                return $messages;
            }
        }

        return null;
    }
}
