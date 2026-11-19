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
                2 => [
                    'video' => 'https://youtu.be/x7j1NuxR5Gs',
                    'pdf' => 'https://drive.google.com/file/d/16zQfTppplBZwIgR3oAtD1rPN0lsV_08f/view?usp=sharing',
                ],
                3 => [
                    'video' => 'https://youtu.be/I3kw2VOj9fU',
                    'pdf' => 'https://drive.google.com/file/d/1xewGHWf0GqVAKBOOp6fXYulNw-K9hoGR/view?usp=sharing',
                ],
                4 => [
                    'video' => 'https://youtu.be/t7y_A2WAFaQ',
                    'pdf' => 'https://drive.google.com/file/d/1up2jAXe9lh49dcqWA3EXHRF2rL3r2woL/view?usp=sharing',
                ],
                5 => [
                    'video' => 'https://youtu.be/KS3BxarAYoY',
                    'pdf' => 'https://drive.google.com/file/d/1jBsx0gb77VlcRLCEZDYLjo2bbQwmD2Fh/view?usp=sharing',
                ],
                6 => [
                    'video' => 'https://youtu.be/9NTrJm1OLy0',
                    'pdf' => 'https://drive.google.com/file/d/1YRofcEBYu6PvBCgsMk2dcgaraYfsYPRj/view?usp=sharing',
                ],
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
                2 => [
                    'video' => 'https://youtu.be/Kb4OnVPFyp4',
                    'pdf' => 'https://drive.google.com/file/d/1suyCYIuq19U71gq9kkDrH7m1iWVkkZiZ/view?usp=sharing',
                ],
                3 => [
                    'video' => 'https://youtu.be/6l65nE2HCwI',
                    'pdf' => 'https://drive.google.com/file/d/1r1LFE2cuGZH5ZckwZBdv2oekPSmNg6C0/view?usp=sharing',
                ],
                4 => [
                    'video' => 'https://youtu.be/a5DJsVtVNMk',
                    'pdf' => 'https://drive.google.com/file/d/1WZ_1__H27hSPmiPVo281L1LlwDJQT0cU/view?usp=sharing',
                ],
                5 => [
                    'video' => 'https://youtu.be/_XQNr6K4Rvg',
                    'pdf' => 'https://drive.google.com/file/d/1RVGEfKSBoth_Cs2buWyPaSKA82h1fG0n/view?usp=sharing',
                ],
                6 => [
                    'video' => 'https://youtu.be/OL76eciLpLM',
                    'pdf' => 'https://drive.google.com/file/d/1CGh59s6L3bUQIN9l7D9b1533TddXJ8sG/view?usp=sharing',
                ],
                // El cuadernillo de esta semana aun no llega; el panel muestra
                // el video y marca "Cuadernillo proximamente".
                7 => [
                    'video' => 'https://youtu.be/-Ind8sv1f3M',
                ],
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
                3 => [
                    'video' => 'https://youtu.be/2ZbzPift4eY',
                    'pdf' => 'https://drive.google.com/file/d/1En-VP9JlRsVuq8SqKvKjTLFB5ZHTW5fz/view?usp=sharing',
                ],
                4 => [
                    'video' => 'https://youtu.be/nARdr5eHkiw',
                    'pdf' => 'https://drive.google.com/file/d/1XxcK1UglYkj8paExeihQU2cZpQbprLcR/view?usp=sharing',
                ],
                5 => [
                    'video' => 'https://youtu.be/Gc3L_f6NPtU',
                    'pdf' => 'https://drive.google.com/file/d/1P-ZSFgTUAPSeE_XMwY9Oa-9-GrtpID19/view?usp=sharing',
                ],
                6 => [
                    'video' => 'https://youtu.be/1tcZgOsM0Jo',
                    'pdf' => 'https://drive.google.com/file/d/1aIkWz2PrrEqmOiXGXnF-4wDjMMmFwZI4/view?usp=sharing',
                ],
                7 => [
                    'video' => 'https://youtu.be/jznGoTyEMmQ',
                    'pdf' => 'https://drive.google.com/file/d/1JZkw8cyasCs0KG3YEwJFY7q_DzeYvIIR/view?usp=sharing',
                ],
                8 => [
                    'video' => 'https://youtu.be/-7dC4cWk-94',
                    'pdf' => 'https://drive.google.com/file/d/1hirhNXR4EaRbPL4SWb2L2tfv67loVtzH/view?usp=sharing',
                ],
            ],
        ],

    ],

];
