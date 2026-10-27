<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reforzamiento de cursos
    |--------------------------------------------------------------------------
    |
    | Mismo funcionamiento que [seminarios.php] y lo lee el mismo controlador,
    | VideosSemanalesController: cuatro semanas por curso y solo videos. Para
    | publicar una semana se anade su linea y nada mas; las que no tienen
    | contenido se muestran como "Proximamente".
    |
    | Sobre 'areas': aqui no se usa a proposito. Quimica, Fisica, Educacion
    | Civica y Economia los llevan las tres areas, pero "Biologia y Anatomia"
    | solo existe como curso unico en Sociales e Ingenierias -Biomedicas los
    | lleva separados- y "Historia y Geografia" solo en Ingenierias y
    | Biomedicas, porque Sociales los lleva separados. Filtrar por area dejaria
    | fuera precisamente a quienes estudian ese contenido con mas profundidad,
    | asi que el reforzamiento se ofrece a todos. Si algun dia hace falta
    | restringir un curso: 'areas' => [1, 2] (1 Biomedicas, 2 Ingenierias,
    | 3 Sociales).
    |
    */

    'titulo' => 'Reforzamiento',

    'descripcion' => 'Cuatro semanas de reforzamiento por curso, con el video de cada sesion.',

    'con_cuadernillo' => false,

    'total_semanas' => 4,

    'ruta_pdf' => 'storage/documentos/reforzamiento',

    'grupos' => [

        'biologia-anatomia' => [
            'nombre' => 'Biología y Anatomía',
            'color' => '#15803d',
            'semanas' => [
                1 => ['video' => 'https://youtu.be/YIoA3UW8frQ'],
                2 => ['video' => 'https://youtu.be/9UebIGciJqg'],
                3 => ['video' => 'https://youtu.be/QEFl5IwBAOI'],
            ],
        ],

        'quimica' => [
            'nombre' => 'Química',
            'color' => '#7e22ce',
            'semanas' => [
                1 => ['video' => 'https://youtu.be/ZWRXTk9429c'],
                2 => ['video' => 'https://youtu.be/3Sucpnv_1gw'],
                3 => ['video' => 'https://youtu.be/AynLQr3ms48'],
            ],
        ],

        'fisica' => [
            'nombre' => 'Física',
            'color' => '#2563eb',
            'semanas' => [
                1 => ['video' => 'https://youtu.be/FbgzYP_uK8g'],
                2 => ['video' => 'https://youtu.be/yy9eL5flkIM'],
                3 => ['video' => 'https://youtu.be/KAleyWe4sOM'],
            ],
        ],

        'educacion-civica' => [
            'nombre' => 'Educación Cívica',
            'color' => '#b45309',
            'semanas' => [
                1 => ['video' => 'https://youtu.be/xSUXITw1oe8'],
                2 => ['video' => 'https://youtu.be/r2VvX2l41KY'],
                3 => ['video' => 'https://youtu.be/NEzS6WKZVtk'],
            ],
        ],

        'economia' => [
            'nombre' => 'Economía',
            'color' => '#0f766e',
            'semanas' => [
                1 => ['video' => 'https://youtu.be/oeQW95uZ6Y8'],
                2 => ['video' => 'https://youtu.be/Sod4Mt5aVws'],
                3 => ['video' => 'https://youtu.be/qUgbivxug7w'],
            ],
        ],

        'historia-geografia' => [
            'nombre' => 'Historia y Geografía',
            'color' => '#b91c1c',
            'semanas' => [
                1 => ['video' => 'https://youtu.be/lI_rLADs2TE'],
                2 => ['video' => 'https://youtu.be/uvztU8Lk5zQ'],
                3 => ['video' => 'https://youtu.be/VswnfKX__As'],
            ],
        ],

    ],

];
