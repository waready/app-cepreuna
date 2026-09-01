<template>
    <app-layout title="Entrega de preguntas" :mode="2">
        <section class="submission-page">
            <header class="submission-hero">
                <div>
                    <span class="hero-kicker">Banco de preguntas</span>
                    <h1>Entrega tus preguntas en Word</h1>
                    <p>
                        Presenta un documento editable por curso y semana. El equipo revisor
                        validara el contenido antes de aprobarlo.
                    </p>
                </div>
                <div class="hero-actions">
                    <span class="period-pill">
                        <i class="pi pi-calendar"></i>
                        {{ periodo.nombre }}
                    </span>
                    <a
                        :href="route('docentes.recursos.preguntas-demo.plantilla')"
                        class="template-link"
                    >
                        <i class="pi pi-download"></i>
                        Descargar formato Word
                    </a>
                </div>
            </header>

            <Message
                v-if="!persistenciaDisponible"
                severity="warn"
                :closable="false"
                class="module-notice"
            >
                El envio permanecera bloqueado hasta que el sistema multiciclo instale las
                tablas del modulo.
            </Message>

            <div v-if="!cursos.length" class="empty-state">
                <i class="pi pi-book"></i>
                <h2>No hay cursos disponibles</h2>
                <p>No tienes cargas activas en el periodo actual.</p>
            </div>

            <div v-else class="workflow-grid">
                <aside class="requirements-card">
                    <div class="card-title">
                        <span class="step-badge">1</span>
                        <div>
                            <small>Antes de adjuntar</small>
                            <h2>Revisa el formato</h2>
                        </div>
                    </div>

                    <div class="document-preview">
                        <span class="word-icon"><i class="pi pi-file"></i></span>
                        <div>
                            <strong>2 preguntas por entrega</strong>
                            <span>Un solo archivo Word editable</span>
                        </div>
                    </div>

                    <ol class="requirements-list">
                        <li>
                            <i class="pi pi-check"></i>
                            <span>Incluye exactamente <strong>2 preguntas</strong> del curso y nivel indicados.</span>
                        </li>
                        <li>
                            <i class="pi pi-check"></i>
                            <span>Usa cinco alternativas, de <strong>A a E</strong>, y resalta la respuesta correcta.</span>
                        </li>
                        <li>
                            <i class="pi pi-check"></i>
                            <span>Agrega la justificacion o solucion de cada pregunta.</span>
                        </li>
                        <li>
                            <i class="pi pi-check"></i>
                            <span>Registra bibliografia con autor, anio, titulo y editorial.</span>
                        </li>
                        <li>
                            <i class="pi pi-check"></i>
                            <span>En Matematica, Fisica o Quimica puedes insertar una solucion fotografiada si es legible.</span>
                        </li>
                    </ol>

                    <a
                        :href="route('docentes.recursos.preguntas-demo.plantilla')"
                        class="format-reminder"
                    >
                        <i class="pi pi-file-word"></i>
                        <span>
                            <strong>Usa el modelo oficial</strong>
                            <small>Abre, edita y conserva su estructura.</small>
                        </span>
                        <i class="pi pi-arrow-down"></i>
                    </a>
                </aside>

                <article class="upload-card">
                    <div class="card-title">
                        <span class="step-badge accent">2</span>
                        <div>
                            <small>Nueva entrega</small>
                            <h2>Adjunta el documento</h2>
                        </div>
                    </div>

                    <form @submit.prevent="enviar">
                        <div class="form-grid">
                            <div class="field field-wide">
                                <label for="entrega-curso">Curso asignado</label>
                                <Dropdown
                                    id="entrega-curso"
                                    v-model="form.curso_id"
                                    :options="cursos"
                                    optionLabel="label"
                                    optionValue="id"
                                    placeholder="Selecciona un curso"
                                    class="w-full"
                                    :class="{ 'p-invalid': form.errors.curso_id }"
                                />
                                <small v-if="form.errors.curso_id" class="p-error">{{ form.errors.curso_id }}</small>
                                <div v-if="cursoSeleccionado" class="course-context">
                                    <span><i class="pi pi-users"></i>{{ cursoSeleccionado.grupos.join(", ") }}</span>
                                    <Tag
                                        v-for="modalidad in cursoSeleccionado.modalidades"
                                        :key="modalidad"
                                        :severity="modalidad === 'Virtual' ? 'info' : 'success'"
                                        :value="modalidad"
                                    />
                                </div>
                            </div>

                            <div class="field">
                                <label for="entrega-semana">Semana</label>
                                <InputNumber
                                    id="entrega-semana"
                                    v-model="form.semana"
                                    :min="1"
                                    :max="30"
                                    :useGrouping="false"
                                    placeholder="Ej. 4"
                                    :class="{ 'p-invalid': form.errors.semana }"
                                />
                                <small v-if="form.errors.semana" class="p-error">{{ form.errors.semana }}</small>
                            </div>

                            <div class="field">
                                <label for="entrega-nivel">Nivel</label>
                                <Dropdown
                                    id="entrega-nivel"
                                    v-model="form.nivel"
                                    :options="niveles"
                                    optionLabel="label"
                                    optionValue="value"
                                    placeholder="Selecciona el nivel"
                                    class="w-full"
                                    :class="{ 'p-invalid': form.errors.nivel }"
                                />
                                <small v-if="form.errors.nivel" class="p-error">{{ form.errors.nivel }}</small>
                            </div>
                        </div>

                        <div class="field file-field">
                            <label>Documento Word</label>
                            <label
                                for="entrega-archivo"
                                class="drop-zone"
                                :class="{ selected: form.archivo, invalid: form.errors.archivo }"
                            >
                                <input
                                    :key="archivoKey"
                                    id="entrega-archivo"
                                    type="file"
                                    accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                    @change="seleccionarArchivo"
                                />
                                <span class="drop-icon"><i :class="form.archivo ? 'pi pi-check' : 'pi pi-upload'"></i></span>
                                <span v-if="form.archivo" class="file-copy">
                                    <strong>{{ form.archivo.name }}</strong>
                                    <small>{{ formatoBytes(form.archivo.size) }} · listo para enviar</small>
                                </span>
                                <span v-else class="file-copy">
                                    <strong>Selecciona tu archivo .docx</strong>
                                    <small>Documento editable de hasta 10 MB</small>
                                </span>
                                <span class="browse-label">Examinar</span>
                            </label>
                            <small v-if="form.errors.archivo" class="p-error">{{ form.errors.archivo }}</small>
                        </div>

                        <label class="confirmation-row" for="confirmacion-dos-preguntas">
                            <Checkbox
                                inputId="confirmacion-dos-preguntas"
                                v-model="form.confirmacion_dos_preguntas"
                                :binary="true"
                            />
                            <span>
                                Confirmo que el Word sigue el formato y contiene exactamente
                                <strong>2 preguntas</strong>.
                            </span>
                        </label>
                        <small v-if="form.errors.confirmacion_dos_preguntas" class="p-error confirmation-error">
                            {{ form.errors.confirmacion_dos_preguntas }}
                        </small>

                        <div class="submit-row">
                            <div>
                                <i class="pi pi-lock"></i>
                                El archivo sera privado y solo lo vera el equipo revisor.
                            </div>
                            <Button
                                type="submit"
                                label="Enviar a revision"
                                icon="pi pi-send"
                                class="submit-button"
                                :loading="form.processing"
                                :disabled="!puedeEnviar"
                            />
                        </div>
                    </form>
                </article>
            </div>

            <section class="history-card">
                <div class="history-heading">
                    <div>
                        <small>Seguimiento</small>
                        <h2>Mis entregas del periodo</h2>
                    </div>
                    <span>{{ entregas.length }} {{ entregas.length === 1 ? "entrega" : "entregas" }}</span>
                </div>

                <DataTable
                    :value="entregas"
                    responsiveLayout="stack"
                    breakpoint="860px"
                    class="p-datatable-sm submission-table"
                >
                    <template #empty>
                        <div class="table-empty">
                            <i class="pi pi-inbox"></i>
                            <span>Todavia no tienes entregas registradas.</span>
                        </div>
                    </template>
                    <Column field="curso" header="Curso"></Column>
                    <Column header="Entrega">
                        <template #body="slotProps">
                            <div class="delivery-meta">
                                <strong>Semana {{ slotProps.data.semana }}</strong>
                                <span>{{ nivelLabel(slotProps.data.nivel) }} · version {{ slotProps.data.version }}</span>
                            </div>
                        </template>
                    </Column>
                    <Column header="Archivo">
                        <template #body="slotProps">
                            <a
                                :href="route('docentes.recursos.preguntas-demo.download', slotProps.data.id)"
                                class="file-download"
                            >
                                <i class="pi pi-file-word"></i>
                                <span>{{ slotProps.data.archivo_nombre }}</span>
                            </a>
                        </template>
                    </Column>
                    <Column field="enviado_at" header="Enviado"></Column>
                    <Column header="Estado">
                        <template #body="slotProps">
                            <div class="status-cell">
                                <Tag
                                    :severity="estadoSeverity(slotProps.data.estado)"
                                    :value="estadoLabel(slotProps.data.estado)"
                                />
                                <small v-if="slotProps.data.comentario">{{ slotProps.data.comentario }}</small>
                                <a
                                    v-if="slotProps.data.archivo_revision"
                                    :href="route(
                                        'docentes.recursos.preguntas-demo.download-revision',
                                        [slotProps.data.id, slotProps.data.archivo_revision.id]
                                    )"
                                    class="review-file-link"
                                >
                                    <i class="pi pi-download"></i>
                                    Descargar Word revisado
                                </a>
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </section>
        </section>
    </app-layout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout";
import { computed, ref } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";
import { useToast } from "primevue/usetoast";

export default {
    components: { AppLayout },
    props: {
        cursos: { type: Array, default: () => [] },
        entregas: { type: Array, default: () => [] },
        periodo: { type: Object, required: true },
        persistenciaDisponible: { type: Boolean, default: false },
    },
    setup(props) {
        const toast = useToast();
        const archivoKey = ref(0);
        const niveles = [
            { label: "Basico", value: "basico" },
            { label: "Intermedio", value: "intermedio" },
            { label: "Avanzado", value: "avanzado" },
        ];
        const form = useForm({
            curso_id: null,
            semana: null,
            nivel: null,
            confirmacion_dos_preguntas: false,
            archivo: null,
        });

        const cursoSeleccionado = computed(() =>
            props.cursos.find((curso) => curso.id === form.curso_id)
        );
        const puedeEnviar = computed(
            () =>
                props.persistenciaDisponible &&
                form.curso_id &&
                form.semana &&
                form.nivel &&
                form.archivo &&
                form.confirmacion_dos_preguntas &&
                !form.processing
        );

        const seleccionarArchivo = (event) => {
            form.archivo = event.target.files?.[0] || null;
            form.clearErrors("archivo");
        };

        const enviar = () => {
            form.post(route("docentes.recursos.preguntas-demo.store"), {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => {
                    form.reset();
                    archivoKey.value += 1;
                    toast.add({
                        severity: "success",
                        summary: "Entrega registrada",
                        detail: "El documento fue enviado para revision.",
                        life: 3500,
                    });
                },
                onError: () => {
                    toast.add({
                        severity: "warn",
                        summary: "Revisa la entrega",
                        detail: "Hay datos que necesitan tu atencion.",
                        life: 3500,
                    });
                },
            });
        };

        const formatoBytes = (bytes) => {
            if (!bytes) return "0 KB";
            if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} KB`;
            return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
        };
        const nivelLabel = (nivel) => niveles.find((item) => item.value === nivel)?.label || nivel;
        const estadoLabel = (estado) => ({
            en_revision: "En revision",
            aprobado: "Aprobado",
            observado: "Observado",
            rechazado: "Rechazado",
        }[estado] || estado);
        const estadoSeverity = (estado) => ({
            en_revision: "info",
            aprobado: "success",
            observado: "warning",
            rechazado: "danger",
        }[estado] || "info");

        return {
            archivoKey,
            cursoSeleccionado,
            enviar,
            estadoLabel,
            estadoSeverity,
            form,
            formatoBytes,
            nivelLabel,
            niveles,
            puedeEnviar,
            seleccionarArchivo,
        };
    },
};
</script>

<style scoped>
.submission-page {
    --ink: #29353d;
    --muted: #6f7b82;
    --accent: #d9541e;
    --accent-dark: #9b3714;
    --paper: #fffdf9;
    display: grid;
    gap: 1.15rem;
    color: var(--ink);
}

.submission-hero {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
    overflow: hidden;
    padding: 1.7rem;
    border: 1px solid #efd8ca;
    border-radius: 18px;
    background:
        radial-gradient(circle at 92% 0, rgba(255, 190, 130, 0.33), transparent 34%),
        linear-gradient(135deg, #fffaf4 0%, #fff 70%);
    box-shadow: 0 14px 32px rgba(87, 49, 31, 0.08);
}

.submission-hero::after {
    position: absolute;
    right: -28px;
    bottom: -52px;
    width: 180px;
    height: 180px;
    border: 28px solid rgba(217, 84, 30, 0.07);
    border-radius: 50%;
    content: "";
}

.hero-kicker,
.card-title small,
.history-heading small {
    color: var(--accent);
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.submission-hero h1 {
    margin: 0.3rem 0 0.45rem;
    color: #27343c;
    font-family: Georgia, "Times New Roman", serif;
    font-size: clamp(1.65rem, 3vw, 2.45rem);
    line-height: 1.05;
}

.submission-hero p {
    max-width: 650px;
    margin: 0;
    color: var(--muted);
    font-size: 0.98rem;
    line-height: 1.55;
}

.hero-actions {
    z-index: 1;
    display: grid;
    flex: 0 0 auto;
    gap: 0.65rem;
    min-width: 230px;
}

.period-pill,
.template-link {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.55rem;
    padding: 0.75rem 1rem;
    border-radius: 10px;
    font-weight: 700;
}

.period-pill {
    border: 1px solid #ead8cd;
    background: rgba(255, 255, 255, 0.82);
    color: #6e4a38;
}

.template-link {
    background: var(--accent);
    color: white;
    text-decoration: none;
    box-shadow: 0 9px 20px rgba(217, 84, 30, 0.22);
}

.template-link:hover {
    background: var(--accent-dark);
    color: white;
}

.module-notice {
    margin: 0;
}

.workflow-grid {
    display: grid;
    grid-template-columns: minmax(270px, 0.74fr) minmax(0, 1.45fr);
    gap: 1.1rem;
}

.requirements-card,
.upload-card,
.history-card,
.empty-state {
    border: 1px solid #e3e7e9;
    border-radius: 16px;
    background: white;
    box-shadow: 0 10px 25px rgba(37, 51, 60, 0.06);
}

.requirements-card,
.upload-card {
    padding: 1.35rem;
}

.card-title {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    margin-bottom: 1.15rem;
}

.card-title h2,
.history-heading h2 {
    margin: 0.12rem 0 0;
    color: var(--ink);
    font-size: 1.18rem;
}

.step-badge {
    display: grid;
    flex: 0 0 38px;
    width: 38px;
    height: 38px;
    place-items: center;
    border-radius: 11px;
    background: #edf1f2;
    color: #58666e;
    font-weight: 900;
}

.step-badge.accent {
    background: #fff0e8;
    color: var(--accent);
}

.document-preview {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    padding: 0.9rem;
    border-radius: 12px;
    background: #fff7f1;
}

.word-icon {
    display: grid;
    width: 42px;
    height: 48px;
    place-items: center;
    border-radius: 8px;
    background: #2367b1;
    color: white;
    font-size: 1.2rem;
}

.document-preview div,
.file-copy,
.delivery-meta,
.status-cell {
    display: grid;
    gap: 0.18rem;
    min-width: 0;
}

.document-preview span,
.file-copy small,
.delivery-meta span {
    color: var(--muted);
    font-size: 0.8rem;
}

.requirements-list {
    display: grid;
    gap: 0.85rem;
    margin: 1.2rem 0;
    padding: 0;
    list-style: none;
}

.requirements-list li {
    display: grid;
    grid-template-columns: 22px 1fr;
    gap: 0.55rem;
    color: #556169;
    font-size: 0.88rem;
    line-height: 1.45;
}

.requirements-list i {
    display: grid;
    width: 21px;
    height: 21px;
    place-items: center;
    border-radius: 50%;
    background: #e8f6ee;
    color: #258a51;
    font-size: 0.65rem;
}

.format-reminder {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: 0.7rem;
    padding: 0.9rem;
    border: 1px dashed #cfb8aa;
    border-radius: 11px;
    color: #624538;
    text-decoration: none;
}

.format-reminder > i:first-child {
    color: #2367b1;
    font-size: 1.35rem;
}

.format-reminder span {
    display: grid;
}

.format-reminder small {
    color: var(--muted);
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.field {
    display: grid;
    align-content: start;
    gap: 0.42rem;
}

.field-wide {
    grid-column: 1 / -1;
}

.field > label {
    color: #43515a;
    font-size: 0.84rem;
    font-weight: 800;
}

.field :deep(.p-inputnumber),
.field :deep(.p-inputnumber-input) {
    width: 100%;
}

.course-context {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.45rem;
    margin-top: 0.25rem;
}

.course-context > span {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    color: var(--muted);
    font-size: 0.78rem;
}

.file-field {
    margin-top: 1.05rem;
}

.drop-zone {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: center;
    gap: 0.9rem;
    min-height: 96px;
    padding: 1rem;
    border: 2px dashed #ccd4d8;
    border-radius: 13px;
    background: #fafcfc;
    cursor: pointer;
    transition: border-color 160ms ease, background 160ms ease;
}

.drop-zone:hover,
.drop-zone.selected {
    border-color: #e0774d;
    background: #fff9f5;
}

.drop-zone.invalid {
    border-color: #e24c4c;
}

.drop-zone input {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    opacity: 0;
}

.drop-icon {
    display: grid;
    width: 46px;
    height: 46px;
    place-items: center;
    border-radius: 13px;
    background: #ffeadf;
    color: var(--accent);
    font-size: 1.1rem;
}

.file-copy strong {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.browse-label {
    padding: 0.52rem 0.72rem;
    border-radius: 8px;
    background: #eef1f2;
    color: #4d5a62;
    font-size: 0.78rem;
    font-weight: 800;
}

.confirmation-row {
    display: flex;
    align-items: flex-start;
    gap: 0.7rem;
    margin-top: 1rem;
    padding: 0.9rem;
    border-radius: 10px;
    background: #f7f9f9;
    color: #4f5c64;
    font-size: 0.86rem;
    line-height: 1.45;
    cursor: pointer;
}

.confirmation-error {
    display: block;
    margin-top: 0.3rem;
}

.submit-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-top: 1.1rem;
    padding-top: 1rem;
    border-top: 1px solid #edf0f1;
}

.submit-row > div {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    color: var(--muted);
    font-size: 0.77rem;
}

.submit-button {
    background: var(--accent);
    border-color: var(--accent);
}

.history-card {
    overflow: hidden;
}

.history-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.2rem 1.35rem;
    border-bottom: 1px solid #e8ebed;
}

.history-heading > span {
    padding: 0.38rem 0.65rem;
    border-radius: 99px;
    background: #eef2f3;
    color: #5f6d75;
    font-size: 0.75rem;
    font-weight: 800;
}

.submission-table :deep(.p-datatable-wrapper) {
    border-radius: 0 0 16px 16px;
}

.file-download {
    display: inline-flex;
    max-width: 250px;
    align-items: center;
    gap: 0.42rem;
    color: #2265a8;
    font-size: 0.82rem;
    text-decoration: none;
}

.file-download span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.status-cell small {
    max-width: 280px;
    color: #8a5a35;
    line-height: 1.35;
}

.review-file-link {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    color: #24649f;
    font-size: 0.75rem;
    font-weight: 700;
    text-decoration: none;
}

.table-empty,
.empty-state {
    display: grid;
    justify-items: center;
    gap: 0.5rem;
    padding: 2rem;
    color: var(--muted);
    text-align: center;
}

.table-empty i,
.empty-state > i {
    color: #d4a184;
    font-size: 2rem;
}

.empty-state h2,
.empty-state p {
    margin: 0;
}

@media (max-width: 980px) {
    .workflow-grid {
        grid-template-columns: 1fr;
    }

    .requirements-list {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 680px) {
    .submission-page {
        gap: 0.8rem;
    }

    .submission-hero {
        align-items: stretch;
        padding: 1.2rem;
        border-radius: 13px;
        flex-direction: column;
    }

    .hero-actions {
        width: 100%;
        min-width: 0;
    }

    .workflow-grid {
        gap: 0.8rem;
    }

    .requirements-card,
    .upload-card {
        padding: 1rem;
        border-radius: 13px;
    }

    .requirements-list,
    .form-grid {
        grid-template-columns: 1fr;
    }

    .field-wide {
        grid-column: auto;
    }

    .drop-zone {
        grid-template-columns: auto minmax(0, 1fr);
    }

    .browse-label {
        display: none;
    }

    .submit-row {
        align-items: stretch;
        flex-direction: column;
    }

    .submit-row :deep(.p-button) {
        width: 100%;
        justify-content: center;
    }

    .history-heading {
        align-items: flex-start;
        padding: 1rem;
    }

    .submission-table :deep(.p-datatable-tbody > tr > td) {
        display: grid;
        grid-template-columns: minmax(90px, 34%) 1fr;
        gap: 0.6rem;
        align-items: start;
        text-align: left;
    }

    .file-download,
    .status-cell {
        max-width: 100%;
    }
}
</style>
