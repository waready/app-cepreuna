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
    | dejar todas declaradas desde el principio.
    |
    | En cada semana:
    |   video  enlace de YouTube (o cualquier URL). Opcional.
    |   pdf    el cuadernillo. Si empieza por http se usa tal cual -sirve para
    |          un enlace de Drive-; si no, se trata como ruta relativa al host
    |          de documentos (EXTERNALURLIMAGE), igual que los cuadernillos de
    |          curso, que se piden a '/storage/documentos/...'.
    |
    | Un grupo puede llevar 'areas' => [1, 2] para mostrarse solo a esas areas.
    | Sin esa clave lo ve todo el mundo, que es lo que queremos con los idiomas.
    |
    | Lo lee VideosSemanalesController, compartido con [reforzamiento.php].
    |
    */

    'titulo' => 'Seminarios de Idiomas',

    'descripcion' => 'Cada semana incluye el video de la sesion y su cuadernillo.',

    'con_cuadernillo' => true,

    'total_semanas' => 8,

    'ruta_pdf' => 'storage/documentos/seminarios',

    'grupos' => [

        'ingles' => [
            'nombre' => 'Inglés',
            'color' => '#2563eb',
            'semanas' => [
                1 => [
                    'video' => 'https://youtu.be/FXDVW2SGlaE',
                    'pdf' => 'https://drive.google.com/file/d/13JM95OW-aTOWPbQx84a275x7cfOWY28j/view?usp=sharing',
                ],
                2 => ['video' => 'https://youtu.be/x7j1NuxR5Gs'],
            ],
        ],

        'quechua' => [
            'nombre' => 'Quechua',
            'color' => '#b45309',
            'semanas' => [
                1 => [
                    'video' => 'https://youtu.be/qvtQP3ycpEM',
                    'pdf' => 'https://drive.google.com/file/d/1nXMOvpAOEe0T7m7XusDhagcnYrCqVsD1/view?usp=sharing',
                ],
                2 => ['video' => 'https://youtu.be/Kb4OnVPFyp4'],
            ],
        ],

        'aimara' => [
            'nombre' => 'Aimara',
            'color' => '#15803d',
            'semanas' => [
                1 => [
                    'video' => 'https://youtu.be/fVXBuxweMZA',
                    'pdf' => 'https://drive.google.com/file/d/1q8aCLcK7WNQpvlUAJxpCQwTvYQzh7SoL/view?usp=sharing',
                ],
                2 => [
                    'video' => 'https://youtu.be/DVqw_msCXHU',
                    'pdf' => 'https://drive.google.com/file/d/1XFQifFmJZvpsfr_4zbh5OnAPk75Yfqbj/view?usp=sharing',
                ],
            ],
        ],

    ],

];
