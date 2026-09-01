<?php

namespace App\Support;

use ZipArchive;

class DocumentoWord
{
    public const MIME = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    public static function esDocxValido(string $path): bool
    {
        if (!is_file($path)) {
            return false;
        }

        if (class_exists(ZipArchive::class)) {
            $zip = new ZipArchive();
            $abierto = $zip->open($path) === true;

            if (!$abierto) {
                return false;
            }

            $valido = $zip->locateName('[Content_Types].xml') !== false
                && $zip->locateName('word/document.xml') !== false;
            $zip->close();

            return $valido;
        }

        $archivo = fopen($path, 'rb');
        if ($archivo === false) {
            return false;
        }

        $firma = fread($archivo, 2);
        fclose($archivo);

        return $firma === 'PK';
    }
}
