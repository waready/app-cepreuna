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
                    <vue-cal
                        class="vuecal--blue-theme"
                        locale="es"
                        :disable-views="['years', 'year', 'month']"
                        :time-from="7 * 60"
                        :time-to="22 * 60 + 60"
                        :events="events"
                        :on-event-click="onEventClick"
                    />
                </div>
            </div>
        </div>
        <Dialog v-model:visible="showDialog" :style="{ width: '500px' }" header="Detalle de asistencia" :modal="true" position="top" class="fluid bg-info">
            <div class="grid">
                <div class="col-12">
                    <b for="curso"> Curso y Grupo </b>
                    <p>{{ detalles.title }}</p>
                    <b for="curso"> Fecha </b>
                    <p>{{ detalles.fecha_asistencia }}</p>
                    <b for="curso"> Horario </b>
                    <p>{{ detalles.hora_inicio }} - {{ detalles.hora_fin }}</p>
                    <b for="curso"> Observación </b>
                    <div class="alert alert-secondary" role="alert">
                        <p>{{ detalles.obs }}</p>
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Cerrar" icon="pi pi-times" class="p-button-secondary" @click="showDialog = false" />
            </template>
        </Dialog>
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

        // const inicio = ref(0);
        // const fin = ref(0);
        const events = ref([]);

        onMounted(() => {
            getAsistencias();
        });

        const getAsistencias = () => {
            axios.get(route("docentes.get-asistencias")).then((response) => {
                // console.log(response);
                events.value = response.data.asistencias;
            });
        };
        //         const selectedEvent = ref();
        const showDialog = ref(false);
        const detalles = ref([]);
        const onEventClick = (event, e) => {
            // this.selectedEvent = event
            showDialog.value = true;
            detalles.value = event;
            // Prevent navigating to narrower view (default vue-cal behavior).
            e.stopPropagation();
        };
        return {
            title,
            // inicio,
            // fin,
            events,
            getAsistencias,
            onEventClick,
            showDialog,
            detalles,
        };
    },
    components: {
        AppLayout,
        VueCal,
    },
};
</script>
<style scoped></style>
