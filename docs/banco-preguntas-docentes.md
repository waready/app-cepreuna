# Banco de preguntas para docentes

## Estado actual

El modulo sigue siendo un demo oculto. Las migraciones de este documento estan
versionadas, pero no se han ejecutado en ningun ambiente y no existe escritura
desde la interfaz.

## Almacenamiento propuesto

### `banco_pregunta_lotes`

Representa una entrega de un docente para un curso y periodo determinados.
Guarda la version y el flujo de revision:

- `borrador`
- `en_revision`
- `aprobado`
- `observado`

La combinacion periodo, curso, docente y version es unica. Esto permite conservar
entregas anteriores sin mezclar preguntas de ciclos distintos.

### `banco_preguntas`

Contiene el tema, enunciado, tipo, dificultad, explicacion, orden y estado de cada
pregunta. Cada registro pertenece a un lote.

Las imagenes no se guardaran como binario en MySQL. El archivo se almacenara en:

```text
storage/app/public/banco-preguntas/{periodo_id}/{docente_id}/{uuid}.webp
```

La columna `imagen_path` conservara unicamente la ruta relativa.

### `banco_pregunta_alternativas`

Guarda las alternativas A-D de cada pregunta, su orden y la marca
`es_correcta`. La validacion de la aplicacion debera asegurar que exista una sola
respuesta correcta por pregunta.

## Seguridad prevista

- Solo los docentes incluidos en `DOCENTE_PREGUNTAS_DOCENTES_IDS` podran acceder.
- El docente solo podra usar cursos asignados en el periodo activo.
- El guardado definitivo se realizara en una transaccion para crear lote,
  preguntas y alternativas de forma atomica.
- Un lote enviado a revision quedara bloqueado para el docente.
- Las revisiones se asociaran a un usuario administrativo en `revisado_por`.

## Habilitacion futura

Antes de activar el modulo se debe:

1. Revisar y aprobar el esquema.
2. Respaldar la base de datos.
3. Ejecutar exclusivamente las tres migraciones del banco de preguntas.
4. Implementar y probar el guardado transaccional y la carga de imagenes.
5. Configurar los docentes seleccionados.
6. Activar `DOCENTE_PREGUNTAS_DEMO_ENABLED=true`.

No se debe ejecutar `php artisan migrate` en produccion hasta completar estos
pasos.
