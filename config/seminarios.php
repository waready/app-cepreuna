<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Seminarios de idiomas
    |--------------------------------------------------------------------------
    |
    | El modulo no se administra desde el sistema todavia: el contenido se
    | declara aqui y basta editar este archivo para publicar una semana nueva.
    | Las semanas sin datos aparecen como "Proximamente", asi que se pueden
    | dejar las ocho desde el principio.
    |
    | En cada semana:
    |   video  enlace de YouTube (o cualquier URL). Opcional.
    |   pdf    el cuadernillo. Si empieza por http se usa tal cual -sirve para
    |          un enlace de Drive-; si no, se trata como ruta relativa al host
    |          de documentos (EXTERNALURLIMAGE), igual que los cuadernillos de
    |          curso, que se piden a '/storage/documentos/...'.
    |
    */

    'total_semanas' => 8,

    'ruta_pdf' => 'storage/documentos/seminarios',

    'idiomas' => [

        'ingles' => [
            'nombre' => 'Inglés',
            'color' => '#2563eb',
            'semanas' => [
                1 => ['video' => 'https://youtu.be/FXDVW2SGlaE'],
            ],
        ],

        'quechua' => [
            'nombre' => 'Quechua',
            'color' => '#b45309',
            'semanas' => [
                1 => ['video' => 'https://youtu.be/qvtQP3ycpEM'],
            ],
        ],

        'aimara' => [
            'nombre' => 'Aimara',
            'color' => '#15803d',
            'semanas' => [
                1 => ['video' => 'https://youtu.be/fVXBuxweMZA'],
            ],
        ],

    ],

];
