<template>
    <Toast />
    <app-layout :title="title" :mode="2">
        <div class="card shadow-6">
            <div class="grid hidden sm:flex">
                <div class="col-12">
                    <h5 class="font-bold">Asistencias</h5>
                </div>
            </div>
            <div class="grid">
                <div class="col-12"><Tag value="Presente" severity="success"></Tag> <Tag value="Tarde" severity="warning"></Tag> <Tag value="Falta" severity="danger"></Tag></div>
                <div class="col-12">
                    <vue-cal class="vuecal--blue-theme" locale="es" :disable-views="['years', 'year', 'month']" :time-from="inicio * 60" :time-to="fin * 60 + 60" :events="events" />
                </div>
            </div>
        </div>
    </app-layout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout";
import { useToast } from "primevue/usetoast";

import { Inertia } from "@inertiajs/inertia";
import { useForm } from "@inertiajs/inertia-vue3";

import { ref, onMounted, watch, toRefs } from "vue";
import axios from "axios";
import VueCal from "vue-cal";
import "vue-cal/dist/vuecal.css";
import "vue-cal/dist/i18n/es.js";
export default {
    // components: {
    //     AppTopBarMobile,
    // },
    props: {
        errors: Object,
        response: Object,
    },
    setup(props) {
        const title = ref("Asistencia");
        const toast = useToast();
        const { response } = toRefs(props);

        const inicio = ref(0);
        const fin = ref(0);
        const events = ref([]);

        onMounted(() => {
            getAsistencias();
            getRangoFechas();
        });

        const getAsistencias = () => {
            axios.get(route("estudiantes.get-asistencias")).then((response) => {
                // console.log(response);
                events.value = response.data.asistencias;
            });
        };
        const getRangoFechas = () => {
            axios.get(route("estudiantes.get-rango-fechas")).then((response) => {
                inicio.value = response.data.inicio.split(":")[0];
                fin.value = response.data.fin.split(":")[0];
            });
        };

        return {
            title,
            inicio,
            fin,
            events,
            getAsistencias,
            getRangoFechas,
        };
    },
    components: {
        AppLayout,
        VueCal,
    },
};
</script>
<style scoped></style>
