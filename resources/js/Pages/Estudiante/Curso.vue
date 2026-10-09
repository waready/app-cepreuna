<template>
    <Toast />
    <app-layout :title="title" :mode="2">
        <div class="card shadow-6 cursos-estudiante-panel">
            <div class="grid hidden sm:flex">
                <div class="col-12">
                    <h5 class="font-bold">Cursos</h5>
                </div>
            </div>
            <div class="grid">
                <div class="col-12 acordion">
                    <!-- <div class="p-accordion p-component">
                        <div class="p-accordion-tab p-accordion-tab-active">
                            <div class="p-accordion-header p-highlight">
                                <a role="tab" class="p-accordion-header-link" tabindex="0" aria-expanded="true" id="pv_id_1_0_header" aria-controls="pv_id_1_0_content">
                                    <span class="p-accordion-toggle-icon pi pi-chevron-down"></span>
                                    <span class="p-accordion-header-text">Biología I</span>
                                </a>
                            </div>
                            <div class="p-toggleable-content" role="region" id="pv_id_1_0_content" aria-labelledby="pv_id_1_0_header">
                                <div class="p-accordion-content">
                                    <p>
                                        Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis
                                        nostrud
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div> -->
                    <Accordion :multiple="true" :activeIndex="[0]">
                        <AccordionTab v-for="carga in cargas" :key="carga.id">
                            <template #header>
                                <div class="curso-nombre">
                                    <i class="pi pi-bookmark-fill curso-icono" :style="{ color: carga.curso.color, fontSize: '19px' }"></i>
                                    <span class="curso-nombre-texto">{{ carga.curso.denominacion }}</span>
                                    <i style="color: rgb(25, 234, 133)" v-if="carga.encuesta_realizada" class="pi pi-circle-on"></i>
                                </div>
                            </template>
                            <div v-if="carga.docente" class="curso-detalle">
                                <div class="curso-dato curso-dato-docente">
                                    <span class="curso-etiqueta">Docente</span>
                                    <span class="curso-valor">{{ carga.docente.paterno }} {{ carga.docente.materno }} {{ carga.docente.nombres }}</span>
                                </div>
                                <div class="curso-dato">
                                    <span class="curso-etiqueta">Condición</span>
                                    <span class="curso-valor" v-if="carga.tipo == '2'">Remplazo</span>
                                    <span class="curso-valor" v-else>Titular</span>
                                </div>
                                <div class="curso-dato">
                                    <span class="curso-etiqueta">Meet</span>
                                    <span class="curso-valor">
                                        <a v-if="carga.link" :href="carga.link" target="_blank" rel="noopener">
                                            <Tag icon="pi pi-video" severity="info" value="Ir a Meet"></Tag>
                                        </a>
                                        <span v-else class="curso-sin-dato">Sin enlace</span>
                                    </span>
                                </div>
                                <div class="curso-dato">
                                    <span class="curso-etiqueta">Encuesta</span>
                                    <span class="curso-valor">
                                        <Tag
                                            icon="pi pi-check-square"
                                            severity="success"
                                            value="Calificar"
                                            v-if="carga.grupo_aula.estado_encuesta == '1' && !carga.encuesta_realizada"
                                            @click="calificar(carga)"
                                            style="cursor: pointer"
                                        >
                                        </Tag>
                                        <Tag icon="pi pi-check" v-else-if="carga.encuesta_realizada" :disabled="true" style="cursor: not-allowed" class="bg-gray-500">Calificado</Tag>
                                        <span v-else class="curso-sin-dato">No disponible</span>
                                    </span>
                                </div>
                            </div>
                            <div v-else class="curso-sin-docente">
                                <Tag class="bg-gray-500">Docente no asignado</Tag>
                            </div>
                        </AccordionTab>
                    </Accordion>
                </div>
            </div>
        </div>
        <!-- Calificar Docente -->
        <Dialog v-model:visible="calificacionDialog" :style="{ width: '700px' }" :breakpoints="{ '768px': 'calc(100vw - 1rem)' }" header="Calificación Docente" :modal="true" position="top" class="calificacion-dialog fluid bg-info">
            <form @submit.prevent="" action="" autocomplete="off">
                <div class="grid">
                    <div>
                        <p style="text-align: justify;">Estimado estudiante, responda cada criterio para conocer el nivel de desempeño del docente durante el período actual. Agradecemos anticipadamente su participación.</p>
                    </div>
                    <div class="col-3 pb-0">
                        <p class="m-0 font-bold">Docente:</p>
                    </div>
                    <div class="col-9 pb-0 float-left">
                        <p class="m-0 text-base">{{ docente.paterno }} {{ docente.materno }} {{ docente.nombres }}</p>
                    </div>
                    <div class="col-3 pb-0">
                        <p class="m-0 font-bold">Curso:</p>
                    </div>
                    <div class="col-3 pb-0">
                        <p class="m-0 text-base">{{ curso.denominacion }}</p>
                    </div>
                    <div class="col-3 pb-0">
                        <p class="m-0 font-bold">Fecha:</p>
                    </div>
                    <div class="col-3 pb-0">
                        <p class="m-0 text-base">{{ formatearFecha(fechaActual) }}</p>
                    </div>
                    <!-- <div class="col-3 pb-0">
                        <p class="m-0 font-bold">Inicio:</p>
                    </div>
                    <div class="col-3 pb-0">
                        <p class="m-0 text-base">{{ asistencia.hora_inicio }}</p>
                    </div>
                    <div class="col-3 pb-0">
                        <p class="m-0 font-bold">Fin:</p>
                    </div>
                    <div class="col-3 pb-0">
                        <p class="m-0 text-base">{{ asistencia.hora_fin }}</p>
                    </div> -->
                    <div class="col-12">
                        <InlineMessage severity="info">Califique del 1 al 5 al docente asignado al curso de acuerdo a los siguientes criterios de evaluación.</InlineMessage>
                    </div>
                </div>
                <div class="grid">
                    <div v-if="criterios.length != 0">
                        <!--Panel v-for="(criterio, i) in criterios" :key="criterio.id" :header="criterio.denominacion" class="shadow-3 mt-3">
                            <template v-if="i !== criterios.length - 1">
                                <RadioImage :link="criterio.id + '1'" :src="'/images/reacciones/1f621.svg'" v-model="preguntas[criterio.id]" :value="1" :text="'NUNCA'" />
                                <RadioImage :link="criterio.id + '2'" :src="'/images/reacciones/2639.svg'" v-model="preguntas[criterio.id]" :value="2" :text="'CASI NUNCA'" />
                                <RadioImage :link="criterio.id + '3'" :src="'/images/reacciones/1f610.svg'" v-model="preguntas[criterio.id]" :value="3" :text="'A VECES'" />
                                <RadioImage :link="criterio.id + '4'" :src="'/images/reacciones/1f642.svg'" v-model="preguntas[criterio.id]" :value="4" :text="'CASI SIEMPRE'" />
                                <RadioImage :link="criterio.id + '5'" :src="'/images/reacciones/1f600.svg'" v-model="preguntas[criterio.id]" :value="5" :text="'SIEMPRE'" />
                            </template>
                            <template v-else>
                                <div>
                                    <label>
                                        <input type="radio" :name="'pregunta-' + criterio.id" v-model="preguntas[criterio.id]" :value="'1'" />
                                        Sí
                                    </label>
                                    <label>
                                        <input type="radio" :name="'pregunta-' + criterio.id" v-model="preguntas[criterio.id]" :value="'5'" />
                                        No
                                    </label>
                                </div>
                            </template>
                        </Panel-->
                        <Panel v-for="criterio in criterios" :key="criterio.id" :header="criterio.denominacion" class="shadow-3 mt-3">
                            <RadioImage :link="criterio.id + '1'" :src="'/images/reacciones/1f621.svg'" v-model="preguntas[criterio.id]" :value="1" :text="'NUNCA'" />
                            <RadioImage :link="criterio.id + '2'" :src="'/images/reacciones/2639.svg'" v-model="preguntas[criterio.id]" :value="2" :text="'CASI NUNCA'" />
                            <RadioImage :link="criterio.id + '3'" :src="'/images/reacciones/1f610.svg'" v-model="preguntas[criterio.id]" :value="3" :text="'A VECES'" />
                            <RadioImage :link="criterio.id + '4'" :src="'/images/reacciones/1f642.svg'" v-model="preguntas[criterio.id]" :value="4" :text="'CASI SIEMPRE'" />
                            <RadioImage :link="criterio.id + '5'" :src="'/images/reacciones/1f600.svg'" v-model="preguntas[criterio.id]" :value="5" :text="'SIEMPRE'" />
                        </Panel>
                    </div>
                    <div v-else class="col-12 text-center"><em> Actualmente no existen criterios de evaluación</em></div>
                </div>
            </form>
            <template #footer>
                <Button label="Cancelar" icon="pi pi-times" class="p-button-secondary" @click="calificacionDialog = false" />
                <Button label="Guardar" icon="pi pi-check" class="p-button-success" :loading="saveLoading" :disabled="saveLoading || criterios.length === 0" @click="GuardarCalificacion" />
            </template>
        </Dialog>
        <!-- <pre>{{ JSON.stringify(inscripcion, null, 2) }}</pre> -->
    </app-layout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout";
import { useToast } from "primevue/usetoast";

import { ref, onMounted, toRefs } from "vue";
import axios from "axios";
import RadioImage from "../../components/RadioImage.vue";

export default {
    // components: {
    //     AppTopBarMobile,
    // },
    props: {
        errors: Object,
        response: Object,
        calificacion: Array,
        inscripcion: Object,
    },
    setup(props) {
        const title = ref("Cursos");
        const toast = useToast();
        const { calificacion } = toRefs(props);

        onMounted(() => {
            getCarga();
            getCriterios();
        });

        const cargas = ref([]);
        const getCarga = () => {
            return axios.get(route("estudiantes.get-carga")).then((response) => {
                cargas.value = response.data.carga;
            });
        };

        const criterios = ref([]);
        const getCriterios = () => {
            return axios.get(route("estudiantes.get-criterios-docente")).then((response) => {
                criterios.value = response.data;
            });
        };

        const fields = ref({
            id: "",
        });
        const idCarga = (id) => {
            fields.value.id = id;
        };

        const docente = ref({
            paterno: "",
            materno: "",
            nombres: "",
        });
        const curso = ref({
            denominacion: "",
        });

        const asistencia = ref({});
        const calificacionId = ref("");
        const cargaId = ref("");
        const calificacionDialog = ref(false);
        const calificar = (data) => {
            preguntas.value = {};
            errorForm.value = false;
            cargaId.value = data.id;
            docente.value = data.docente;
            curso.value = data.curso;
            asistencia.value = "";
            calificacionDialog.value = true;
        };

        const fechaActual = ref(new Date());

        // Función para obtener el mes en formato abreviado
        const obtenerMesAbreviado = (mes) => {
            const mesesAbreviados = ["ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"];
            return mesesAbreviados[mes];
        };

        // Función para formatear una fecha
        const formatearFecha = (fecha) => {
            if (!fecha) return "";
            const año = fecha.getFullYear();
            const mes = obtenerMesAbreviado(fecha.getMonth());
            const dia = fecha.getDate();
            return `${dia}/${mes}/${año}`;
        };
        const preguntas = ref({});
        const errorForm = ref(false);
        const saveLoading = ref(false);
        const GuardarCalificacion = () => {
            if (saveLoading.value) return;

            errorForm.value = false;
            const respuestasCompletas =
                criterios.value.length > 0 &&
                criterios.value.every((criterio) => {
                    const puntaje = Number(preguntas.value[criterio.id]);
                    return Number.isInteger(puntaje) && puntaje >= 1 && puntaje <= 5;
                });

            if (!respuestasCompletas) {
                errorForm.value = true;
                toast.add({
                    severity: "error",
                    summary: "¡Error!",
                    detail: "Debe responder todos los criterios con un puntaje del 1 al 5.",
                    life: 5000,
                });

                return;
            }

            const respuestas = {};
            criterios.value.forEach((criterio) => {
                respuestas[criterio.id] = Number(preguntas.value[criterio.id]);
            });

            saveLoading.value = true;
            axios
                .post(route("estudiantes.calificar-docente-carga"), {
                    preguntas: respuestas,
                    cargaId: cargaId.value,
                })
                .then((response) => {
                    if (!response.data.status) {
                        throw new Error(response.data.message || "No se pudo registrar la calificación.");
                    }

                    toast.add({
                        severity: "success",
                        summary: "¡Listo!",
                        detail: response.data.message,
                        life: 5000,
                    });
                    calificacionDialog.value = false;
                    preguntas.value = {};
                    getCarga();
                })
                .catch((error) => {
                    errorForm.value = true;
                    const errores = error.response?.data?.errors;
                    const primerError = errores ? Object.values(errores)[0]?.[0] : null;

                    toast.add({
                        severity: "error",
                        summary: "¡Error!",
                        detail: primerError || error.response?.data?.message || error.message || "No se pudo registrar la calificación.",
                        life: 5000,
                    });
                })
                .finally(() => {
                    saveLoading.value = false;
                });
        };
        return {
            title,
            getCarga,
            getCriterios,
            idCarga,
            calificar,
            GuardarCalificacion,
            cargas,
            criterios,
            fields,
            docente,
            curso,
            asistencia,
            calificacionId,
            cargaId,
            preguntas,
            errorForm,
            calificacion,
            calificacionDialog,
            saveLoading,
            fechaActual,
            formatearFecha,
            ...props,
        };
    },
    components: {
        AppLayout,
        RadioImage,
    },
};
</script>
<style scoped>
.acordion .curso-nombre {
    display: flex;
    width: 100%;
    min-width: 0;
    align-items: center;
    gap: 0.5rem;
}

.curso-icono {
    flex: 0 0 auto;
}

.curso-nombre-texto {
    min-width: 0;
    overflow-wrap: anywhere;
    line-height: 1.35;
}

.curso-detalle {
    display: grid;
    width: 100%;
    grid-template-columns: minmax(16rem, 2fr) repeat(3, minmax(8rem, 1fr));
    gap: 0.75rem;
}

.curso-dato {
    display: flex;
    min-width: 0;
    min-height: 4.5rem;
    flex-direction: column;
    gap: 0.35rem;
    padding: 0.75rem 0.875rem;
    border: 1px solid var(--surface-border);
    border-radius: 9px;
    background: var(--surface-50);
}

.curso-etiqueta {
    color: var(--text-color-secondary);
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.025em;
    text-transform: uppercase;
}

.curso-valor {
    min-width: 0;
    color: var(--text-color);
    overflow-wrap: anywhere;
    line-height: 1.35;
}

.curso-sin-dato {
    color: var(--text-color-secondary);
    font-size: 0.9rem;
}

.curso-sin-docente {
    width: 100%;
    padding: 0.5rem 0;
}

@media (max-width: 900px) {
    .curso-detalle {
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    }

    .curso-dato-docente {
        grid-column: 1 / -1;
    }
}

@media (max-width: 576px) {
    .cursos-estudiante-panel .acordion {
        padding: 0;
    }

    .curso-detalle {
        display: block;
    }

    .curso-dato {
        display: grid;
        min-height: 0;
        grid-template-columns: 6.75rem minmax(0, 1fr);
        gap: 0.75rem;
        align-items: start;
        padding: 0.65rem 0;
        border: 0;
        border-bottom: 1px solid var(--surface-border);
        border-radius: 0;
        background: transparent;
    }

    .curso-dato:first-child {
        padding-top: 0;
    }

    .curso-dato:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .curso-etiqueta {
        color: var(--text-color);
        font-size: 0.95rem;
        letter-spacing: 0;
        text-transform: none;
    }

    .calificacion-dialog :deep(.grid > .col-3) {
        width: 40%;
    }

    .calificacion-dialog :deep(.grid > .col-9) {
        width: 60%;
    }
}
</style>
