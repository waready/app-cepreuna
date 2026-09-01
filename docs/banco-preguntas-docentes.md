# Entrega de preguntas en Word

## Responsabilidad de esta aplicacion

`CEPRE_APP` contiene solamente el flujo del docente:

1. Muestra el modulo a los docentes habilitados.
2. Lista los cursos activos del periodo actual.
3. Recibe un archivo `.docx` con exactamente dos preguntas.
4. Registra la entrega en estado `en_revision`.
5. Muestra al docente la decision, el comentario y el Word corregido cuando
   existan.
6. Permite una nueva version solo cuando la anterior fue observada.

La revision administrativa, las decisiones y las migraciones pertenecen al
repositorio `waready/proyecto_migracion`. Esta aplicacion no incluye rutas ni
pantallas para revisores.

## Formato solicitado

Cada Word debe conservar la estructura de la plantilla e incluir:

- Exactamente 2 preguntas del curso, nivel y silabo correspondiente.
- Cinco alternativas identificadas de A a E.
- Respuesta correcta resaltada.
- Justificacion o solucion de cada pregunta.
- Bibliografia con autor, anio, titulo y editorial.
- Imagenes legibles cuando sean necesarias.

La cantidad se confirma en el formulario y se valida manualmente durante la
revision. No se cuentan preguntas automaticamente porque el documento puede
contener tablas, formulas e imagenes.

## Configuracion

El modulo permanece controlado por una bandera y una lista de docentes:

```dotenv
DOCENTE_PREGUNTAS_DEMO_ENABLED=false
DOCENTE_PREGUNTAS_DOCENTES_IDS=
BANCO_PREGUNTAS_MAX_FILE_KB=10240
BANCO_PREGUNTAS_API_URL=https://backend.example/api
BANCO_PREGUNTAS_API_TOKEN=una-clave-aleatoria-larga-y-exclusiva
BANCO_PREGUNTAS_API_TIMEOUT=30
```

`CEPRE_APP` no guarda documentos del banco de preguntas. Envia el Word por una
conexion servidor-a-servidor al backend multiciclo y tambien canaliza por esa
API las descargas del docente. El token nunca se entrega al navegador.

El backend debe configurar la misma clave en `CEPRE_APP_INTEGRATION_TOKEN` y es
el unico proceso que necesita acceso a su almacenamiento privado.

No se debe habilitar el modulo hasta que `proyecto_migracion` haya creado las
tablas `banco_pregunta_lotes` y `banco_pregunta_revisiones`.
