<template>
    <app-layout :title="title" :mode="2">
        <div class="card shadow-6">
            <div class="grid hidden sm:flex">
                <div class="col-12">
                    <h5 class="font-bold">Horarios</h5>
                </div>
            </div>
            <div class="grid">
                <div v-if="cargando" class="col-12 py-5 text-center text-600">Cargando horario...</div>
                <div v-else-if="errorHorario" class="col-12 p-3 border-round bg-red-50 text-red-700">
                    {{ errorHorario }}
                </div>
                <div v-else-if="horarios.length === 0" class="col-12 py-5 text-center text-600">
                    No hay un horario asignado para el periodo actual.
                </div>
                <div class="col-12 turnos" v-for="horario in horarios" :key="horario.id">
                    <h5>{{ horario.turno }} - {{ area }} - {{ grupo }}</h5>
                    <template v-if="datosAuxiliar">
                        <h6 class="mb-0"><b>Auxiliar:</b> {{ datosAuxiliar.nombre }}</h6>
                        <h6 class="mt-0"><b>Celular:</b> {{ datosAuxiliar.telefono }}</h6>
                    </template>
                    <h6 v-else class="mt-0 text-600"><b>Auxiliar:</b> Por asignar</h6>
                    <Timeline :value="horario.dias" align="left">
                        <template #opposite="slotProps">
                            <small class="p-text-secondary">{{ slotProps.item.dia }}</small>
                        </template>
                        <template #content="slotProps">
                            <Card>
                                <template #content>
                                    <template v-for="dis in slotProps.item.disponibilidad" :key="dis.hora_inicio">
                                        <div class="grid border-500 shadow-2 py-1 my-1" v-if="dis.horario != null" :style="'background:' + dis.horario.curso.color">
                                            <div class="col-6 md:col-4"><Tag icon="pi pi-clock" severity="Info" :value="dis.hora_inicio + ' - ' + dis.hora_fin"></Tag></div>
                                            <div class="col-6 md:col-8">
                                                {{ dis.horario.curso.denominacion }}
                                            </div>
                                        </div>
                                    </template>
                                </template>
                            </Card>
                        </template>
                    </Timeline>
                </div>
            </div>
        </div>
        <!-- <pre>{{ users }}</pre> -->
    </app-layout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout";

import { ref, onMounted, watch, toRefs, computed } from "vue";
import axios from "axios";
// import AppTopBarSocial from "../Sigma/AppTopbarSocial.vue";
export default {
    components: {
        AppLayout,
    },
    props: {
        data: Object,
    },
    setup() {
        const title = ref("Horarios");
        const horarios = ref([]);
        const area = ref("");
        const grupo = ref("");
        const auxiliar = ref(null);
        const cargando = ref(true);
        const errorHorario = ref("");
        const datosAuxiliar = computed(() => {
            const detalle = auxiliar.value?.auxiliar;
            const usuario = detalle?.user;

            if (!detalle || !usuario) {
                return null;
            }

            return {
                nombre: [usuario.paterno, usuario.materno, usuario.name].filter(Boolean).join(" "),
                telefono: detalle.telefono || "No registrado",
            };
        });
        const getHorario = () => {
            cargando.value = true;
            errorHorario.value = "";
            axios
                .get(route("estudiantes.get-horario"), {
                    // params: {
                    //     area: form.area ? form.area.id : "",
                    //     turno: form.area ? form.turno.id : "",
                    //     sede: form.area ? form.sede.id : "",
                    // },
                })
                .then((response) => {
                    // this.grupo = response.data[0].id;
                    horarios.value = response.data.horario;
                    grupo.value = response.data.grupo;
                    area.value = response.data.area;
                    auxiliar.value = response.data.auxiliar_grupo;
                })
                .catch(() => {
                    errorHorario.value = "No se pudo cargar el horario. Intente nuevamente.";
                })
                .finally(() => {
                    cargando.value = false;
                });
        };

        onMounted(() => {
            getHorario();
        });
        return {
            title,
            horarios,
            area,
            grupo,
            getHorario,
            auxiliar,
            cargando,
            errorHorario,
            datosAuxiliar,
        };
    },
};
</script>
<style>
.turnos .p-timeline-event-opposite {
    min-width: 40px !important;
    flex: 0;
}
</style>
