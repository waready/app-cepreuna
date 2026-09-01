<template>
    <app-layout title="Revision de preguntas" :mode="2">
        <section class="review-page">
            <header class="review-hero">
                <div>
                    <span>Bandeja de control</span>
                    <h1>Revision de entregas Word</h1>
                    <p>Descarga el documento, valida sus dos preguntas y registra una decision trazable.</p>
                </div>
                <div class="period-card">
                    <i class="pi pi-calendar"></i>
                    <span>Periodo activo</span>
                    <strong>{{ periodo.nombre }}</strong>
                </div>
            </header>

            <Message v-if="!persistenciaDisponible" severity="warn" :closable="false">
                La bandeja esta en modo de demostracion. Las tablas aun no fueron instaladas y
                ninguna accion de revision esta habilitada.
            </Message>

            <section class="review-card">
                <div class="toolbar">
                    <div>
                        <small>Documentos recibidos</small>
                        <h2>{{ pendientes }} pendientes de revision</h2>
                    </div>
                    <Dropdown
                        v-model="filtroEstado"
                        :options="filtros"
                        optionLabel="label"
                        optionValue="value"
                        class="status-filter"
                    />
                </div>

                <DataTable
                    :value="entregasFiltradas"
                    responsiveLayout="stack"
                    breakpoint="960px"
                    class="p-datatable-sm review-table"
                    :paginator="entregasFiltradas.length > 12"
                    :rows="12"
                >
                    <template #empty>
                        <div class="empty-table">
                            <i class="pi pi-check-circle"></i>
                            <strong>No hay entregas en esta vista</strong>
                            <span>Cambia el filtro para consultar otros estados.</span>
                        </div>
                    </template>
                    <Column header="Docente y curso">
                        <template #body="slotProps">
                            <div class="identity-cell">
                                <strong>{{ slotProps.data.docente }}</strong>
                                <span>{{ slotProps.data.curso }}</span>
                            </div>
                        </template>
                    </Column>
                    <Column header="Contexto">
                        <template #body="slotProps">
                            <div class="context-cell">
                                <span>Semana {{ slotProps.data.semana }}</span>
                                <span>{{ nivelLabel(slotProps.data.nivel) }}</span>
                                <span>Version {{ slotProps.data.version }}</span>
                            </div>
                        </template>
                    </Column>
                    <Column header="Documento">
                        <template #body="slotProps">
                            <a
                                :href="route('banco-preguntas.revision.download', slotProps.data.id)"
                                class="document-link"
                            >
                                <i class="pi pi-file-word"></i>
                                <span>
                                    <strong>{{ slotProps.data.archivo_nombre }}</strong>
                                    <small>Enviado {{ slotProps.data.enviado_at }}</small>
                                </span>
                                <i class="pi pi-download"></i>
                            </a>
                        </template>
                    </Column>
                    <Column header="Estado">
                        <template #body="slotProps">
                            <div class="state-cell">
                                <Tag
                                    :severity="estadoSeverity(slotProps.data.estado)"
                                    :value="estadoLabel(slotProps.data.estado)"
                                />
                                <small v-if="slotProps.data.comentario">{{ slotProps.data.comentario }}</small>
                            </div>
                        </template>
                    </Column>
                    <Column header="Decision">
                        <template #body="slotProps">
                            <div v-if="slotProps.data.estado === 'en_revision'" class="decision-actions">
                                <Button
                                    icon="pi pi-check"
                                    class="p-button-sm p-button-success"
                                    aria-label="Aprobar"
                                    @click="abrirDecision(slotProps.data, 'aprobar')"
                                />
                                <Button
                                    icon="pi pi-comment"
                                    class="p-button-sm p-button-warning"
                                    aria-label="Observar"
                                    @click="abrirDecision(slotProps.data, 'observar')"
                                />
                                <Button
                                    icon="pi pi-times"
                                    class="p-button-sm p-button-danger"
                                    aria-label="Rechazar"
                                    @click="abrirDecision(slotProps.data, 'rechazar')"
                                />
                            </div>
                            <span v-else class="reviewed-by">
                                {{ slotProps.data.revisor || "Revision registrada" }}
                            </span>
                        </template>
                    </Column>
                </DataTable>
            </section>

            <Dialog
                v-model:visible="dialogVisible"
                :header="dialogTitle"
                :modal="true"
                :style="{ width: '560px' }"
                :breakpoints="{ '620px': 'calc(100vw - 1rem)' }"
            >
                <div v-if="entregaSeleccionada" class="decision-dialog">
                    <div class="selected-delivery">
                        <span class="selected-icon"><i class="pi pi-file-word"></i></span>
                        <div>
                            <strong>{{ entregaSeleccionada.curso }} · semana {{ entregaSeleccionada.semana }}</strong>
                            <span>{{ entregaSeleccionada.docente }}</span>
                        </div>
                    </div>

                    <Message :severity="decisionSeverity" :closable="false">
                        {{ decisionHelp }}
                    </Message>

                    <div class="dialog-field">
                        <label for="decision-comentario">
                            Comentario
                            <span v-if="form.accion === 'observar' || form.accion === 'rechazar'">(obligatorio)</span>
                        </label>
                        <Textarea
                            id="decision-comentario"
                            v-model.trim="form.comentario"
                            rows="5"
                            autoResize
                            placeholder="Describe claramente el resultado de la revision..."
                            :class="{ 'p-invalid': form.errors.comentario }"
                        />
                        <small v-if="form.errors.comentario" class="p-error">{{ form.errors.comentario }}</small>
                    </div>

                    <div class="dialog-field">
                        <label for="archivo-revision">Word corregido <span>(opcional)</span></label>
                        <input
                            :key="archivoKey"
                            id="archivo-revision"
                            type="file"
                            accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                            @change="seleccionarArchivo"
                        />
                        <small v-if="form.archivo_revision" class="selected-file">
                            <i class="pi pi-paperclip"></i>{{ form.archivo_revision.name }}
                        </small>
                        <small v-if="form.errors.archivo_revision" class="p-error">{{ form.errors.archivo_revision }}</small>
                    </div>
                </div>

                <template #footer>
                    <Button
                        label="Cancelar"
                        class="p-button-text p-button-secondary"
                        @click="dialogVisible = false"
                    />
                    <Button
                        :label="decisionButton"
                        :icon="decisionIcon"
                        :class="decisionButtonClass"
                        :loading="form.processing"
                        @click="guardarDecision"
                    />
                </template>
            </Dialog>
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
        entregas: { type: Array, default: () => [] },
        periodo: { type: Object, required: true },
        persistenciaDisponible: { type: Boolean, default: false },
    },
    setup(props) {
        const toast = useToast();
        const filtroEstado = ref("todos");
        const dialogVisible = ref(false);
        const entregaSeleccionada = ref(null);
        const archivoKey = ref(0);
        const filtros = [
            { label: "Todos los estados", value: "todos" },
            { label: "Pendientes", value: "en_revision" },
            { label: "Aprobados", value: "aprobado" },
            { label: "Observados", value: "observado" },
            { label: "Rechazados", value: "rechazado" },
        ];
        const form = useForm({
            accion: "",
            comentario: "",
            archivo_revision: null,
        });

        const entregasFiltradas = computed(() =>
            filtroEstado.value === "todos"
                ? props.entregas
                : props.entregas.filter((entrega) => entrega.estado === filtroEstado.value)
        );
        const pendientes = computed(
            () => props.entregas.filter((entrega) => entrega.estado === "en_revision").length
        );
        const dialogTitle = computed(() => ({
            aprobar: "Aprobar entrega",
            observar: "Observar entrega",
            rechazar: "Rechazar entrega",
        }[form.accion] || "Registrar decision"));
        const decisionHelp = computed(() => ({
            aprobar: "Confirma que el Word cumple el formato y que sus dos preguntas estan listas.",
            observar: "Indica lo que debe corregirse. El docente podra enviar una nueva version.",
            rechazar: "Explica por que la entrega no puede continuar en este tramite.",
        }[form.accion] || ""));
        const decisionSeverity = computed(() => ({
            aprobar: "success",
            observar: "warn",
            rechazar: "error",
        }[form.accion] || "info"));
        const decisionButton = computed(() => ({
            aprobar: "Confirmar aprobacion",
            observar: "Enviar observacion",
            rechazar: "Confirmar rechazo",
        }[form.accion] || "Guardar"));
        const decisionIcon = computed(() => ({
            aprobar: "pi pi-check",
            observar: "pi pi-comment",
            rechazar: "pi pi-times",
        }[form.accion] || "pi pi-save"));
        const decisionButtonClass = computed(() => ({
            aprobar: "p-button-success",
            observar: "p-button-warning",
            rechazar: "p-button-danger",
        }[form.accion] || ""));

        const abrirDecision = (entrega, accion) => {
            form.reset();
            form.clearErrors();
            form.accion = accion;
            entregaSeleccionada.value = entrega;
            archivoKey.value += 1;
            dialogVisible.value = true;
        };
        const seleccionarArchivo = (event) => {
            form.archivo_revision = event.target.files?.[0] || null;
            form.clearErrors("archivo_revision");
        };
        const guardarDecision = () => {
            form.post(
                route("banco-preguntas.revision.decision", entregaSeleccionada.value.id),
                {
                    forceFormData: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        dialogVisible.value = false;
                        toast.add({
                            severity: "success",
                            summary: "Decision registrada",
                            detail: "El estado de la entrega fue actualizado.",
                            life: 3500,
                        });
                    },
                    onError: () => {
                        toast.add({
                            severity: "warn",
                            summary: "Revisa la decision",
                            detail: "Completa los campos indicados.",
                            life: 3500,
                        });
                    },
                }
            );
        };
        const nivelLabel = (nivel) => ({
            basico: "Basico",
            intermedio: "Intermedio",
            avanzado: "Avanzado",
        }[nivel] || nivel);
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
            abrirDecision,
            archivoKey,
            decisionButton,
            decisionButtonClass,
            decisionHelp,
            decisionIcon,
            decisionSeverity,
            dialogTitle,
            dialogVisible,
            entregasFiltradas,
            entregaSeleccionada,
            estadoLabel,
            estadoSeverity,
            filtroEstado,
            filtros,
            form,
            guardarDecision,
            nivelLabel,
            pendientes,
            seleccionarArchivo,
        };
    },
};
</script>

<style scoped>
.review-page {
    --ink: #26343d;
    --muted: #6c7981;
    --accent: #cc4b1b;
    display: grid;
    gap: 1rem;
    color: var(--ink);
}

.review-hero {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
    padding: 1.5rem;
    border: 1px solid #ead8cf;
    border-radius: 17px;
    background:
        linear-gradient(115deg, rgba(255, 250, 244, 0.98), rgba(255, 255, 255, 0.94)),
        repeating-linear-gradient(90deg, transparent 0 24px, rgba(204, 75, 27, 0.08) 25px);
    box-shadow: 0 12px 28px rgba(45, 53, 57, 0.07);
}

.review-hero > div:first-child > span,
.toolbar small {
    color: var(--accent);
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.review-hero h1 {
    margin: 0.25rem 0 0.4rem;
    font-family: Georgia, "Times New Roman", serif;
    font-size: clamp(1.55rem, 3vw, 2.25rem);
}

.review-hero p {
    margin: 0;
    color: var(--muted);
}

.period-card {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 0.1rem 0.65rem;
    min-width: 210px;
    padding: 0.85rem 1rem;
    border-radius: 12px;
    background: white;
    box-shadow: 0 6px 18px rgba(53, 60, 65, 0.08);
}

.period-card i {
    grid-row: 1 / 3;
    align-self: center;
    color: var(--accent);
    font-size: 1.2rem;
}

.period-card span {
    color: var(--muted);
    font-size: 0.72rem;
}

.review-card {
    overflow: hidden;
    border: 1px solid #e2e6e8;
    border-radius: 15px;
    background: white;
    box-shadow: 0 10px 25px rgba(39, 52, 61, 0.06);
}

.toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.15rem 1.3rem;
    border-bottom: 1px solid #e7eaec;
}

.toolbar h2 {
    margin: 0.15rem 0 0;
    font-size: 1.15rem;
}

.status-filter {
    min-width: 205px;
}

.identity-cell,
.state-cell {
    display: grid;
    gap: 0.2rem;
}

.identity-cell span,
.state-cell small,
.reviewed-by {
    color: var(--muted);
    font-size: 0.78rem;
}

.context-cell {
    display: flex;
    flex-wrap: wrap;
    gap: 0.3rem;
}

.context-cell span {
    padding: 0.25rem 0.42rem;
    border-radius: 6px;
    background: #f0f3f4;
    color: #56636b;
    font-size: 0.72rem;
}

.document-link {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: center;
    gap: 0.55rem;
    max-width: 300px;
    padding: 0.45rem 0.55rem;
    border-radius: 8px;
    background: #f5f8fb;
    color: #28649d;
    text-decoration: none;
}

.document-link > i:first-child {
    font-size: 1.25rem;
}

.document-link span {
    display: grid;
    min-width: 0;
}

.document-link strong {
    overflow: hidden;
    font-size: 0.78rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.document-link small {
    color: var(--muted);
    font-size: 0.68rem;
}

.decision-actions {
    display: flex;
    gap: 0.35rem;
}

.empty-table {
    display: grid;
    justify-items: center;
    gap: 0.35rem;
    padding: 2rem;
    color: var(--muted);
}

.empty-table i {
    color: #63a47d;
    font-size: 2rem;
}

.decision-dialog {
    display: grid;
    gap: 1rem;
}

.selected-delivery {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.8rem;
    border-radius: 10px;
    background: #f6f8f9;
}

.selected-icon {
    display: grid;
    width: 40px;
    height: 44px;
    place-items: center;
    border-radius: 8px;
    background: #2865a5;
    color: white;
}

.selected-delivery div {
    display: grid;
    gap: 0.15rem;
}

.selected-delivery span {
    color: var(--muted);
    font-size: 0.78rem;
}

.dialog-field {
    display: grid;
    gap: 0.4rem;
}

.dialog-field label {
    font-weight: 800;
}

.dialog-field label span {
    color: var(--muted);
    font-size: 0.75rem;
    font-weight: 500;
}

.selected-file {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    color: #28649d;
}

@media (max-width: 680px) {
    .review-hero {
        align-items: stretch;
        padding: 1.1rem;
        border-radius: 13px;
        flex-direction: column;
    }

    .period-card {
        min-width: 0;
    }

    .toolbar {
        align-items: stretch;
        padding: 1rem;
        flex-direction: column;
    }

    .status-filter {
        width: 100%;
        min-width: 0;
    }

    .review-table :deep(.p-datatable-tbody > tr > td) {
        display: grid;
        grid-template-columns: minmax(90px, 32%) 1fr;
        gap: 0.65rem;
        align-items: start;
        text-align: left;
    }

    .document-link {
        max-width: 100%;
    }
}
</style>
