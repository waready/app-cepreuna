<template>
    <app-layout :title="title" :mode="2">
        <div class="card shadow-6">
            <div class="grid hidden sm:flex">
                <div class="col-12">
                    <h5 class="font-bold">Horarios</h5>
                </div>
            </div>
            <div class="grid">
                <div class="col-12 turnos" v-for="horario in horarios" :key="horario.id">
                    <h5>{{horario.turno}}</h5>
                    <Timeline :value="horario.dias" align="left">
                        <template #opposite="slotProps">
                            <small class="p-text-secondary">{{slotProps.item.dia}}</small>
                        </template>
                        <template #content="slotProps">
                            <Card>
                                <!-- <template #title>
                                    {{slotProps.item.status}}
                                </template>-->
                                <!-- <template #subtitle>
                                    Lunes
                                </template> -->
                                <template #content>
                                    <template v-for="dis in slotProps.item.disponibilidad" :key="dis.hora_inicio">
                                        <div class="grid border-500 shadow-2 py-1 my-1" v-if="dis.horario!=null" :style="'background:'+dis.horario.curso.color">
                                            <div class="col-6 md:col-4 pb-0"><Tag icon="pi pi-clock" severity="Info" :value="dis.hora_inicio+' - '+dis.hora_fin"></Tag></div>
                                            <div class="col-6 md:col-3 pb-0"><Tag severity="warning" :value="'('+dis.horario.grupo+')'"></Tag></div>
                                            <div class="col-6 md:col-5" >
                                                <b>{{dis.horario.curso.denominacion}}</b>
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
        const getHorario = () => {
            axios
                .get(route("docentes.get-horario"), {
                    // params: {
                    //     area: form.area ? form.area.id : "",
                    //     turno: form.area ? form.turno.id : "",
                    //     sede: form.area ? form.sede.id : "",
                    // },
                })
                .then((response) => {
                    // this.grupo = response.data[0].id;
                    horarios.value = response.data.horario;
                });
        };

        onMounted(() => {
            getHorario();
        });
        return {
            title,
            horarios,
            getHorario,
        };
    },
};
</script>
<style>
.turnos .p-timeline-event-opposite{
    min-width: 40px !important;
    flex: 0;
}
</style>
