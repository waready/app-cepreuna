# Entrega y revision de preguntas en Word

## Estado actual

El modulo permanece oculto y apagado. El codigo, las migraciones y la plantilla
estan versionados, pero no se ha ejecutado ninguna migracion ni se ha modificado
la base de datos instalada.

El archivo `MODELO DE PREGUNTAS Y RECOMENDACIONES.docx` entregado por el usuario
se usa como referencia y plantilla descargable. Sus indicaciones no sustituyen
las reglas de la aplicacion.

## Flujo funcional

1. Un docente incluido en la lista permitida ingresa al modulo.
2. Selecciona uno de sus cursos activos del periodo actual, semana y nivel.
3. Adjunta un unico archivo `.docx` de hasta 10 MB.
4. Confirma que el documento contiene exactamente dos preguntas.
5. La entrega queda en `en_revision` y el archivo se almacena de forma privada.
6. Un usuario revisor autorizado descarga el Word y registra una decision:
   `aprobado`, `observado` o `rechazado`.
7. Si queda `observado`, el docente puede presentar una nueva version para el
   mismo curso y semana. Las versiones anteriores se conservan.

La cantidad de preguntas se confirma en el formulario y se valida manualmente
durante la revision. No se intenta contar preguntas automaticamente dentro del
Word, porque su contenido puede incluir tablas, formulas e imagenes.

## Formato solicitado

Cada Word debe conservar la estructura del modelo y contener:

- Exactamente 2 preguntas del curso, nivel y silabo correspondiente.
- Cinco alternativas identificadas de A a E.
- Respuesta correcta resaltada.
- Justificacion o solucion de cada pregunta.
- Bibliografia con autor, anio, titulo y editorial.
- Imagenes legibles cuando sean necesarias; en Matematica, Fisica y Quimica la
  solucion puede incorporarse como fotografia.

El Excel `carga-cursos-horas (2).xlsx` se considero solo como referencia de la
carga proporcionada. No se importa a las tablas. La aplicacion consulta las
cargas academicas vigentes de la base para evitar asignaciones desactualizadas
o de otro periodo.

## Persistencia propuesta

### `banco_pregunta_lotes`

Representa cada entrega Word. Guarda periodo, curso, docente, semana, nivel,
version, ruta y nombre del archivo, estado y marcas de tiempo.

Campos:

- `id`
- `periodos_id`
- `cursos_id`
- `docentes_id`
- `semana`
- `nivel`
- `version`
- `archivo_path`
- `archivo_nombre`
- `estado`
- `created_at`
- `updated_at`

La combinacion periodo, curso, docente, semana y version es unica.

### `banco_pregunta_revisiones`

Conserva el historial auditable de decisiones. Registra usuario, accion,
comentario y, opcionalmente, una version Word corregida por el revisor.
Cada version entregada admite una sola revision; un reenvio observado crea un
nuevo lote con la siguiente version.

Campos:

- `id`
- `banco_pregunta_lote_id`
- `users_id`
- `accion`
- `comentario`
- `archivo_path`
- `archivo_nombre`
- `created_at`

No se duplican cantidad fija, MIME, tamano, comentario, revisor ni fechas de
revision en la entrega. La aplicacion obtiene esos datos del archivo o de la
ultima revision cuando son necesarios.

Los documentos no se exponen mediante `public/storage`. Se guardan en:

```text
storage/app/banco-preguntas/{periodo_id}/{docente_id}/
```

Solo se descargan mediante controladores que verifican la identidad del docente
o la lista administrativa de revisores.

## Activacion futura

Antes de habilitar el modulo se debe:

1. Aprobar el flujo y respaldar la base de datos.
2. Ejecutar exclusivamente las dos migraciones con prefijo `2026_08_31`.
3. Configurar los docentes en `DOCENTE_PREGUNTAS_DOCENTES_IDS`.
4. Configurar los usuarios revisores en `BANCO_PREGUNTAS_REVISORES_IDS`.
5. Activar `DOCENTE_PREGUNTAS_DEMO_ENABLED=true` y
   `BANCO_PREGUNTAS_REVISION_ENABLED=true`.
6. Validar carga, descarga, observacion y reenvio en un ambiente de prueba.

No se debe ejecutar `php artisan migrate` ni activar las banderas en produccion
sin completar estos pasos.
