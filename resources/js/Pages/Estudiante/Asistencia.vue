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
                <div class="attendance-legend col-12"><Tag value="Presente" severity="success"></Tag> <Tag value="Tarde" severity="warning"></Tag> <Tag value="Falta" severity="danger"></Tag></div>
                <div class="col-12">
                    <vue-cal :key="calendarView" class="attendance-calendar vuecal--blue-theme" locale="es" :default-view="calendarView" :hide-view-selector="mobileCalendar" :disable-views="['years', 'year', 'month']" :time-from="inicio * 60" :time-to="fin * 60 + 60" :events="events" />
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

import { ref, computed, onMounted, onBeforeUnmount, watch, toRefs } from "vue";
import axios from "axios";
import VueCal from "vue-cal";
import "vue-cal/dist/vuecal.css";
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
        const mobileCalendar = ref(window.innerWidth <= 576);
        const calendarView = computed(() => (mobileCalendar.value ? "day" : "week"));
        const syncViewport = () => {
            mobileCalendar.value = window.innerWidth <= 576;
        };

        onMounted(() => {
            getAsistencias();
            getRangoFechas();
            window.addEventListener("resize", syncViewport, { passive: true });
        });
        onBeforeUnmount(() => window.removeEventListener("resize", syncViewport));

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
            mobileCalendar,
            calendarView,
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
<style scoped>
.attendance-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
}
</style>
