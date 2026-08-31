<template>
    <app-layout title="Banco de preguntas" :mode="2">
        <section class="question-demo">
            <header class="demo-hero">
                <div class="hero-copy">
                    <span class="hero-kicker">Prototipo para docentes seleccionados</span>
                    <h1>Banco de preguntas</h1>
                    <p>
                        Organiza preguntas por curso antes de enviarlas para revision.
                        Este demo no guarda informacion en el servidor.
                    </p>
                </div>
                <div class="hero-status">
                    <Tag icon="pi pi-lock" severity="warning" value="Demo no habilitado" />
                    <span><i class="pi pi-calendar"></i>{{ periodo.nombre }}</span>
                </div>
            </header>

            <Message severity="info" :closable="false" class="demo-notice">
                Las preguntas agregadas permanecen solo durante esta visita. El envio y la
                importacion masiva seguiran bloqueados hasta aprobar el modulo definitivo.
            </Message>

            <div v-if="!cursos.length" class="empty-courses">
                <i class="pi pi-book"></i>
                <h2>No hay cursos disponibles</h2>
                <p>El docente no tiene cargas activas en el periodo actual.</p>
            </div>

            <template v-else>
                <div class="workspace-grid">
                    <article class="editor-card">
                        <div class="section-heading">
                            <span class="step-number">1</span>
                            <div>
                                <span class="section-eyebrow">Nueva pregunta</span>
                                <h2>Selecciona el curso y redacta</h2>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="field field-wide">
                                <label for="pregunta-curso">Curso asignado</label>
                                <Dropdown
                                    id="pregunta-curso"
                                    v-model="formulario.curso_id"
                                    :options="cursos"
                                    optionLabel="label"
                                    optionValue="id"
                                    placeholder="Selecciona un curso"
                                    class="w-full"
                                    :class="{ 'p-invalid': errores.curso_id }"
                                />
                                <small v-if="errores.curso_id" class="p-error">{{ errores.curso_id }}</small>
                                <div v-if="cursoSeleccionado" class="course-context">
                                    <span>
                                        <i class="pi pi-users"></i>
                                        {{ cursoSeleccionado.grupos.join(", ") }}
                                    </span>
                                    <Tag
                                        v-for="modalidad in cursoSeleccionado.modalidades"
                                        :key="modalidad"
                                        :severity="modalidad === 'Virtual' ? 'info' : 'success'"
                                        :icon="modalidad === 'Virtual' ? 'pi pi-desktop' : 'pi pi-map-marker'"
                                        :value="modalidad"
                                    />
                                </div>
                            </div>

                            <div class="field">
                                <label for="pregunta-tema">Tema o unidad</label>
                                <InputText
                                    id="pregunta-tema"
                                    v-model.trim="formulario.tema"
                                    placeholder="Ej. Productos notables"
                                    :class="{ 'p-invalid': errores.tema }"
                                />
                                <small v-if="errores.tema" class="p-error">{{ errores.tema }}</small>
                            </div>

                            <div class="field difficulty-field">
                                <label>Nivel de dificultad</label>
                                <SelectButton
                                    v-model="formulario.dificultad"
                                    :options="dificultades"
                                    :allowEmpty="false"
                                />
                            </div>

                            <div class="field field-wide">
                                <label for="pregunta-enunciado">Enunciado</label>
                                <Textarea
                                    id="pregunta-enunciado"
                                    v-model.trim="formulario.enunciado"
                                    rows="4"
                                    autoResize
                                    placeholder="Escribe una pregunta clara y completa..."
                                    :class="{ 'p-invalid': errores.enunciado }"
                                />
                                <div class="field-meta">
                                    <small v-if="errores.enunciado" class="p-error">{{ errores.enunciado }}</small>
                                    <small v-else>{{ formulario.enunciado.length }} caracteres</small>
                                </div>
                            </div>
                        </div>

                        <div class="answers-block">
                            <div class="answers-heading">
                                <div>
                                    <span class="section-eyebrow">Alternativas</span>
                                    <h3>Marca la respuesta correcta</h3>
                                </div>
                                <span class="answer-hint"><i class="pi pi-check-circle"></i> Una sola respuesta</span>
                            </div>

                            <div class="answer-list">
                                <label
                                    v-for="alternativa in formulario.alternativas"
                                    :key="alternativa.clave"
                                    class="answer-row"
                                    :class="{ 'answer-correct': formulario.correcta === alternativa.clave }"
                                    :for="`respuesta-${alternativa.clave}`"
                                >
                                    <RadioButton
                                        :inputId="`respuesta-${alternativa.clave}`"
                                        name="respuesta-correcta"
                                        v-model="formulario.correcta"
                                        :value="alternativa.clave"
                                    />
                                    <span class="answer-key">{{ alternativa.clave }}</span>
                                    <InputText
                                        v-model.trim="alternativa.texto"
                                        :placeholder="`Alternativa ${alternativa.clave}`"
                                        class="answer-input"
                                    />
                                </label>
                            </div>
                            <small v-if="errores.alternativas" class="p-error block mt-2">{{ errores.alternativas }}</small>
                            <small v-if="errores.correcta" class="p-error block mt-2">{{ errores.correcta }}</small>
                        </div>

                        <div class="form-grid supporting-fields">
                            <div class="field field-wide">
                                <label for="pregunta-explicacion">Explicacion para revision <span>(opcional)</span></label>
                                <Textarea
                                    id="pregunta-explicacion"
                                    v-model.trim="formulario.explicacion"
                                    rows="3"
                                    autoResize
                                    placeholder="Indica el procedimiento o fundamento de la respuesta."
                                />
                            </div>

                            <div class="field field-wide">
                                <label for="pregunta-imagen">Imagen de apoyo <span>(opcional, max. 2 MB)</span></label>
                                <div class="file-control">
                                    <input
                                        :key="archivoKey"
                                        id="pregunta-imagen"
                                        type="file"
                                        accept="image/png,image/jpeg,image/webp"
                                        @change="seleccionarImagen"
                                    />
                                    <span v-if="formulario.imagen_nombre" class="file-name">
                                        <i class="pi pi-image"></i>{{ formulario.imagen_nombre }}
                                    </span>
                                </div>
                                <small v-if="errores.imagen" class="p-error">{{ errores.imagen }}</small>
                                <img
                                    v-if="formulario.imagen"
                                    :src="formulario.imagen"
                                    alt="Vista previa del apoyo"
                                    class="image-preview"
                                />
                            </div>
                        </div>

                        <div class="editor-actions">
                            <Button
                                label="Limpiar"
                                icon="pi pi-refresh"
                                class="p-button-outlined p-button-secondary"
                                @click="limpiarFormulario"
                            />
                            <Button
                                label="Agregar al borrador"
                                icon="pi pi-plus"
                                class="p-button-success"
                                @click="agregarPregunta"
                            />
                        </div>
                    </article>

                    <aside class="summary-card">
                        <div class="summary-top">
                            <span class="step-number">2</span>
                            <div>
                                <span class="section-eyebrow">Resumen</span>
                                <h2>Tu lote de trabajo</h2>
                            </div>
                        </div>

                        <div class="summary-metrics">
                            <div>
                                <strong>{{ borrador.length }}</strong>
                                <span>Preguntas</span>
                            </div>
                            <div>
                                <strong>{{ cursosUsados }}</strong>
                                <span>Cursos</span>
                            </div>
                        </div>

                        <ol class="workflow-list">
                            <li class="complete"><i class="pi pi-check"></i><span>Elegir una carga vigente</span></li>
                            <li :class="{ complete: borrador.length }"><i :class="borrador.length ? 'pi pi-check' : 'pi pi-pencil'"></i><span>Preparar el borrador</span></li>
                            <li><i class="pi pi-lock"></i><span>Enviar para revision</span></li>
                        </ol>

                        <div class="bulk-placeholder">
                            <i class="pi pi-file-excel"></i>
                            <div>
                                <strong>Importacion masiva</strong>
                                <span>La plantilla XLSX se incorporara en la siguiente etapa.</span>
                            </div>
                        </div>

                        <Button
                            label="Enviar lote (proximamente)"
                            icon="pi pi-lock"
                            class="w-full p-button-warning"
                            :disabled="true"
                        />
                    </aside>
                </div>

                <section class="draft-card">
                    <div class="section-heading draft-heading">
                        <span class="step-number">3</span>
                        <div>
                            <span class="section-eyebrow">Revision previa</span>
                            <h2>Preguntas en borrador</h2>
                        </div>
                    </div>

                    <DataTable
                        :value="borrador"
                        responsiveLayout="stack"
                        breakpoint="900px"
                        class="p-datatable-sm draft-table"
                    >
                        <template #empty>
                            <div class="draft-empty">
                                <i class="pi pi-inbox"></i>
                                <span>Agrega la primera pregunta para construir el lote.</span>
                            </div>
                        </template>
                        <Column field="curso" header="Curso"></Column>
                        <Column field="tema" header="Tema"></Column>
                        <Column field="enunciado" header="Pregunta">
                            <template #body="slotProps">
                                <span class="question-cell">{{ resumir(slotProps.data.enunciado) }}</span>
                            </template>
                        </Column>
                        <Column field="dificultad" header="Nivel">
                            <template #body="slotProps">
                                <Tag :severity="dificultadSeverity(slotProps.data.dificultad)" :value="slotProps.data.dificultad" />
                            </template>
                        </Column>
                        <Column field="correcta" header="Clave">
                            <template #body="slotProps">
                                <span class="correct-key">{{ slotProps.data.correcta }}</span>
                            </template>
                        </Column>
                        <Column header="Acciones">
                            <template #body="slotProps">
                                <div class="row-actions">
                                    <Button
                                        icon="pi pi-eye"
                                        class="p-button-sm p-button-outlined"
                                        aria-label="Ver pregunta"
                                        @click="verPregunta(slotProps.data)"
                                    />
                                    <Button
                                        icon="pi pi-trash"
                                        class="p-button-sm p-button-outlined p-button-danger"
                                        aria-label="Quitar pregunta"
                                        @click="eliminarPregunta(slotProps.data.id)"
                                    />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </section>
            </template>

            <Dialog
                v-model:visible="vistaPreviaVisible"
                header="Vista previa de la pregunta"
                :modal="true"
                position="top"
                :style="{ width: '620px' }"
                :breakpoints="{ '680px': 'calc(100vw - 1rem)' }"
            >
                <article v-if="preguntaPrevia" class="question-preview-dialog">
                    <div class="preview-meta">
                        <Tag severity="warning" :value="preguntaPrevia.dificultad" />
                        <span>{{ preguntaPrevia.curso }} / {{ preguntaPrevia.tema }}</span>
                    </div>
                    <h3>{{ preguntaPrevia.enunciado }}</h3>
                    <img v-if="preguntaPrevia.imagen" :src="preguntaPrevia.imagen" alt="Apoyo de la pregunta" />
                    <div class="preview-answers">
                        <div
                            v-for="alternativa in preguntaPrevia.alternativas"
                            :key="alternativa.clave"
                            :class="{ correct: alternativa.clave === preguntaPrevia.correcta }"
                        >
                            <strong>{{ alternativa.clave }}</strong>
                            <span>{{ alternativa.texto }}</span>
                            <i v-if="alternativa.clave === preguntaPrevia.correcta" class="pi pi-check-circle"></i>
                        </div>
                    </div>
                    <div v-if="preguntaPrevia.explicacion" class="preview-explanation">
                        <strong>Explicacion</strong>
                        <p>{{ preguntaPrevia.explicacion }}</p>
                    </div>
                </article>
            </Dialog>
        </section>
    </app-layout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout";
import { computed, reactive, ref } from "vue";
import { useToast } from "primevue/usetoast";

const formularioVacio = () => ({
    curso_id: null,
    tema: "",
    dificultad: "Intermedia",
    enunciado: "",
    correcta: "",
    explicacion: "",
    imagen: "",
    imagen_nombre: "",
    alternativas: ["A", "B", "C", "D"].map((clave) => ({ clave, texto: "" })),
});

export default {
    components: { AppLayout },
    props: {
        cursos: { type: Array, default: () => [] },
        periodo: { type: Object, required: true },
    },
    setup(props) {
        const toast = useToast();
        const dificultades = ["Basica", "Intermedia", "Avanzada"];
        const formulario = reactive(formularioVacio());
        const errores = reactive({});
        const borrador = ref([]);
        const archivoKey = ref(0);
        const vistaPreviaVisible = ref(false);
        const preguntaPrevia = ref(null);

        const cursoSeleccionado = computed(() =>
            props.cursos.find((curso) => curso.id === formulario.curso_id)
        );
        const cursosUsados = computed(
            () => new Set(borrador.value.map((pregunta) => pregunta.curso_id)).size
        );

        const limpiarErrores = () => {
            Object.keys(errores).forEach((campo) => delete errores[campo]);
        };

        const validarFormulario = () => {
            limpiarErrores();

            if (!formulario.curso_id) errores.curso_id = "Selecciona el curso de la pregunta.";
            if (formulario.tema.length < 3) errores.tema = "Indica un tema o unidad.";
            if (formulario.enunciado.length < 10) errores.enunciado = "El enunciado debe tener al menos 10 caracteres.";

            const respuestas = formulario.alternativas.map((alternativa) => alternativa.texto.trim());
            if (respuestas.some((respuesta) => !respuesta)) {
                errores.alternativas = "Completa las cuatro alternativas.";
            } else if (new Set(respuestas.map((respuesta) => respuesta.toLowerCase())).size !== respuestas.length) {
                errores.alternativas = "Las alternativas no pueden repetirse.";
            }

            if (!formulario.correcta) errores.correcta = "Marca la alternativa correcta.";

            return Object.keys(errores).length === 0;
        };

        const limpiarFormulario = () => {
            Object.assign(formulario, formularioVacio());
            limpiarErrores();
            archivoKey.value += 1;
        };

        const seleccionarImagen = (event) => {
            const archivo = event.target.files?.[0];
            delete errores.imagen;

            if (!archivo) {
                formulario.imagen = "";
                formulario.imagen_nombre = "";
                return;
            }

            if (!archivo.type.startsWith("image/") || archivo.size > 2 * 1024 * 1024) {
                errores.imagen = "Selecciona una imagen JPG, PNG o WEBP de hasta 2 MB.";
                formulario.imagen = "";
                formulario.imagen_nombre = "";
                archivoKey.value += 1;
                return;
            }

            const lector = new FileReader();
            lector.onload = () => {
                formulario.imagen = lector.result;
                formulario.imagen_nombre = archivo.name;
            };
            lector.readAsDataURL(archivo);
        };

        const agregarPregunta = () => {
            if (!validarFormulario()) {
                toast.add({
                    severity: "warn",
                    summary: "Revisa la pregunta",
                    detail: "Completa los campos marcados antes de agregarla.",
                    life: 3200,
                });
                return;
            }

            const curso = cursoSeleccionado.value;
            borrador.value.push({
                id: `${Date.now()}-${borrador.value.length + 1}`,
                curso_id: curso.id,
                curso: curso.curso,
                grupos: [...curso.grupos],
                modalidades: [...curso.modalidades],
                tema: formulario.tema,
                dificultad: formulario.dificultad,
                enunciado: formulario.enunciado,
                correcta: formulario.correcta,
                explicacion: formulario.explicacion,
                imagen: formulario.imagen,
                imagen_nombre: formulario.imagen_nombre,
                alternativas: formulario.alternativas.map((alternativa) => ({ ...alternativa })),
            });

            limpiarFormulario();
            toast.add({
                severity: "success",
                summary: "Pregunta agregada",
                detail: "Se incorporo al borrador temporal del demo.",
                life: 2600,
            });
        };

        const eliminarPregunta = (id) => {
            borrador.value = borrador.value.filter((pregunta) => pregunta.id !== id);
        };

        const verPregunta = (pregunta) => {
            preguntaPrevia.value = pregunta;
            vistaPreviaVisible.value = true;
        };

        const resumir = (texto) => (texto.length > 95 ? `${texto.slice(0, 95)}...` : texto);
        const dificultadSeverity = (dificultad) => {
            if (dificultad === "Basica") return "success";
            if (dificultad === "Avanzada") return "danger";
            return "warning";
        };

        return {
            agregarPregunta,
            archivoKey,
            borrador,
            cursoSeleccionado,
            cursosUsados,
            dificultades,
            dificultadSeverity,
            eliminarPregunta,
            errores,
            formulario,
            limpiarFormulario,
            preguntaPrevia,
            resumir,
            seleccionarImagen,
            verPregunta,
            vistaPreviaVisible,
        };
    },
};
</script>

<style scoped>
.question-demo {
    --demo-ink: #23313d;
    --demo-muted: #687783;
    --demo-accent: #cb4b1b;
    --demo-accent-dark: #78391f;
    display: grid;
    gap: 1.1rem;
}

.demo-hero {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
    overflow: hidden;
    padding: 1.6rem;
    border: 1px solid #ead8cf;
    border-radius: 20px;
    background:
        radial-gradient(circle at 88% 12%, rgba(203, 75, 27, 0.14), transparent 29%),
        linear-gradient(125deg, #fff8f3 0%, #fff 58%, #f3e9e1 100%);
    box-shadow: 0 14px 34px rgba(61, 42, 31, 0.08);
}

.demo-hero::after {
    content: "?";
    position: absolute;
    right: 1.5rem;
    bottom: -3.9rem;
    color: rgba(120, 57, 31, 0.07);
    font-size: 12rem;
    font-weight: 900;
    line-height: 1;
}

.hero-copy,
.hero-status {
    position: relative;
    z-index: 1;
}

.hero-copy {
    max-width: 720px;
}

.hero-kicker,
.section-eyebrow {
    color: var(--demo-accent);
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.11em;
    text-transform: uppercase;
}

.hero-copy h1 {
    margin: 0.3rem 0 0.45rem;
    color: var(--demo-ink);
    font-size: clamp(1.55rem, 3vw, 2.35rem);
}

.hero-copy p {
    max-width: 650px;
    margin: 0;
    color: var(--demo-muted);
    font-size: 0.96rem;
    line-height: 1.6;
}

.hero-status {
    display: grid;
    justify-items: end;
    gap: 0.75rem;
    white-space: nowrap;
}

.hero-status > span {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    color: var(--demo-accent-dark);
    font-size: 0.85rem;
    font-weight: 700;
}

.demo-notice {
    margin: 0;
}

.workspace-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(260px, 320px);
    align-items: start;
    gap: 1.1rem;
}

.editor-card,
.summary-card,
.draft-card,
.empty-courses {
    border: 1px solid #e1e7eb;
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 10px 28px rgba(35, 49, 61, 0.06);
}

.editor-card,
.draft-card {
    padding: 1.35rem;
}

.summary-card {
    position: sticky;
    top: 1rem;
    display: grid;
    gap: 1.1rem;
    padding: 1.2rem;
    background: linear-gradient(160deg, #fff 0%, #fff9f4 100%);
}

.section-heading,
.summary-top {
    display: flex;
    align-items: center;
    gap: 0.8rem;
}

.section-heading {
    margin-bottom: 1.25rem;
}

.step-number {
    display: inline-grid;
    width: 2.35rem;
    height: 2.35rem;
    flex: 0 0 auto;
    place-items: center;
    border-radius: 12px;
    background: var(--demo-accent-dark);
    color: #fff;
    font-weight: 800;
    box-shadow: 0 6px 14px rgba(120, 57, 31, 0.2);
}

.section-heading h2,
.summary-top h2,
.answers-heading h3 {
    margin: 0.18rem 0 0;
    color: var(--demo-ink);
}

.section-heading h2,
.summary-top h2 {
    font-size: 1.1rem;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
}

.field {
    min-width: 0;
    margin: 0;
}

.field-wide {
    grid-column: 1 / -1;
}

.field label {
    display: block;
    margin-bottom: 0.45rem;
    color: #3e4c57;
    font-size: 0.83rem;
    font-weight: 700;
}

.field label span {
    color: var(--demo-muted);
    font-weight: 500;
}

.field :deep(.p-inputtext),
.field :deep(.p-dropdown),
.field textarea {
    width: 100%;
}

.field-meta {
    display: flex;
    justify-content: space-between;
    min-height: 1.25rem;
    margin-top: 0.25rem;
    color: var(--demo-muted);
}

.course-context {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.45rem;
    margin-top: 0.55rem;
}

.course-context > span {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    margin-right: auto;
    color: var(--demo-muted);
    font-size: 0.8rem;
}

.difficulty-field :deep(.p-selectbutton) {
    display: flex;
}

.difficulty-field :deep(.p-button) {
    flex: 1;
    padding-inline: 0.55rem;
    font-size: 0.8rem;
}

.answers-block {
    margin: 1.25rem 0;
    padding: 1rem;
    border: 1px solid #e5e9ec;
    border-radius: 15px;
    background: #f8fafb;
}

.answers-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 0.8rem;
}

.answers-heading h3 {
    font-size: 1rem;
}

.answer-hint {
    color: #4f7d55;
    font-size: 0.78rem;
    font-weight: 700;
}

.answer-list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.65rem;
}

.answer-row {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    min-width: 0;
    margin: 0;
    padding: 0.55rem;
    border: 1px solid #dfe5e8;
    border-radius: 11px;
    background: #fff;
    transition: border-color 150ms ease, box-shadow 150ms ease, background 150ms ease;
}

.answer-row.answer-correct {
    border-color: #76a87d;
    background: #f3fbf4;
    box-shadow: 0 0 0 2px rgba(76, 139, 84, 0.1);
}

.answer-key,
.correct-key {
    display: inline-grid;
    place-items: center;
    border-radius: 8px;
    background: #f0e3db;
    color: var(--demo-accent-dark);
    font-weight: 800;
}

.answer-key {
    width: 1.8rem;
    height: 1.8rem;
    flex: 0 0 auto;
}

.answer-input {
    min-width: 0;
    flex: 1;
}

.supporting-fields {
    padding-top: 0.1rem;
}

.file-control {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.65rem;
    padding: 0.7rem;
    border: 1px dashed #bdc8cf;
    border-radius: 11px;
    background: #fbfcfc;
}

.file-control input {
    max-width: 100%;
}

.file-name {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    min-width: 0;
    overflow-wrap: anywhere;
    color: var(--demo-muted);
    font-size: 0.78rem;
}

.image-preview {
    width: min(100%, 360px);
    max-height: 210px;
    margin-top: 0.65rem;
    border: 1px solid #e0e5e8;
    border-radius: 12px;
    object-fit: contain;
}

.editor-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.7rem;
    margin-top: 1.2rem;
}

.summary-metrics {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.65rem;
}

.summary-metrics div {
    display: grid;
    gap: 0.15rem;
    padding: 0.85rem;
    border-radius: 13px;
    background: #f5eee9;
}

.summary-metrics strong {
    color: var(--demo-accent-dark);
    font-size: 1.6rem;
}

.summary-metrics span {
    color: var(--demo-muted);
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
}

.workflow-list {
    display: grid;
    gap: 0.75rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.workflow-list li {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    color: #78858e;
    font-size: 0.84rem;
}

.workflow-list i {
    display: inline-grid;
    width: 1.65rem;
    height: 1.65rem;
    place-items: center;
    border-radius: 50%;
    background: #edf0f2;
    font-size: 0.72rem;
}

.workflow-list .complete {
    color: #416e47;
    font-weight: 700;
}

.workflow-list .complete i {
    background: #dff1e2;
}

.bulk-placeholder {
    display: flex;
    gap: 0.7rem;
    padding: 0.85rem;
    border: 1px dashed #d6bcae;
    border-radius: 12px;
    color: var(--demo-muted);
}

.bulk-placeholder > i {
    margin-top: 0.1rem;
    color: #4f8c57;
    font-size: 1.35rem;
}

.bulk-placeholder strong,
.bulk-placeholder span {
    display: block;
}

.bulk-placeholder strong {
    margin-bottom: 0.2rem;
    color: var(--demo-ink);
    font-size: 0.82rem;
}

.bulk-placeholder span {
    font-size: 0.75rem;
    line-height: 1.4;
}

.draft-heading {
    margin-bottom: 1rem;
}

.draft-empty,
.empty-courses {
    display: grid;
    justify-items: center;
    gap: 0.5rem;
    padding: 2rem;
    color: var(--demo-muted);
    text-align: center;
}

.draft-empty i,
.empty-courses i {
    color: #c6d0d6;
    font-size: 2rem;
}

.empty-courses h2,
.empty-courses p {
    margin: 0;
}

.empty-courses h2 {
    color: var(--demo-ink);
    font-size: 1.15rem;
}

.question-cell {
    display: block;
    max-width: 460px;
    line-height: 1.4;
}

.correct-key {
    width: 2rem;
    height: 2rem;
}

.row-actions {
    display: flex;
    gap: 0.4rem;
}

.question-preview-dialog {
    color: var(--demo-ink);
}

.preview-meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.6rem;
    color: var(--demo-muted);
    font-size: 0.82rem;
}

.question-preview-dialog h3 {
    margin: 1rem 0;
    font-size: 1.1rem;
    line-height: 1.55;
}

.question-preview-dialog > img {
    width: 100%;
    max-height: 270px;
    margin-bottom: 1rem;
    border-radius: 12px;
    object-fit: contain;
}

.preview-answers {
    display: grid;
    gap: 0.55rem;
}

.preview-answers > div {
    display: grid;
    grid-template-columns: 2rem minmax(0, 1fr) auto;
    align-items: center;
    gap: 0.55rem;
    padding: 0.7rem;
    border: 1px solid #e1e6e9;
    border-radius: 10px;
}

.preview-answers > div.correct {
    border-color: #79aa80;
    background: #f1faf3;
    color: #37673d;
}

.preview-explanation {
    margin-top: 1rem;
    padding: 0.85rem;
    border-left: 3px solid var(--demo-accent);
    border-radius: 8px;
    background: #fff7f2;
}

.preview-explanation p {
    margin: 0.35rem 0 0;
    line-height: 1.5;
}

@media (max-width: 1050px) {
    .workspace-grid {
        grid-template-columns: 1fr;
    }

    .summary-card {
        position: static;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .summary-top,
    .summary-card > .p-button {
        grid-column: 1 / -1;
    }
}

@media (max-width: 700px) {
    .question-demo {
        gap: 0.8rem;
    }

    .demo-hero {
        align-items: flex-start;
        flex-direction: column;
        padding: 1.1rem;
        border-radius: 16px;
    }

    .hero-status {
        width: 100%;
        justify-items: start;
    }

    .editor-card,
    .summary-card,
    .draft-card {
        padding: 1rem;
        border-radius: 14px;
    }

    .form-grid,
    .answer-list,
    .summary-card {
        grid-template-columns: 1fr;
    }

    .field-wide,
    .summary-top,
    .summary-card > .p-button {
        grid-column: auto;
    }

    .answers-heading {
        align-items: flex-start;
        flex-direction: column;
        gap: 0.45rem;
    }

    .difficulty-field :deep(.p-selectbutton) {
        flex-direction: column;
    }

    .editor-actions {
        flex-direction: column-reverse;
    }

    .editor-actions :deep(.p-button) {
        width: 100%;
        justify-content: center;
    }

    .course-context > span {
        width: 100%;
        margin-right: 0;
    }

    .draft-table :deep(.p-datatable-tbody > tr > td) {
        align-items: flex-start;
    }

    .question-cell {
        max-width: none;
        text-align: right;
    }
}
</style>
