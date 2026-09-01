<?php

$docentesPreguntas = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('DOCENTE_PREGUNTAS_DOCENTES_IDS', ''))
)));

return [
    'docente_preguntas_demo' => [
        'enabled' => (bool) env('DOCENTE_PREGUNTAS_DEMO_ENABLED', true),
        'docentes_ids' => $docentesPreguntas,
        'max_file_kb' => (int) env('BANCO_PREGUNTAS_MAX_FILE_KB', 10240),
    ],
];
