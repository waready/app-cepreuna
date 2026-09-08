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
                <div class="attendance-legend col-12">
                    <Tag value="Presente" severity="success"></Tag>
                    <Tag value="Tarde" severity="warning"></Tag>
                    <Tag value="Falta" severity="danger"></Tag>
                    <Tag value="Permiso" severity="info"></Tag>
                </div>
                <div class="col-12">
                    <vue-cal
                        :key="calendarView"
                        class="attendance-calendar vuecal--blue-theme"
                        locale="es"
                        :default-view="calendarView"
                        :hide-view-selector="mobileCalendar"
                        :disable-views="['years', 'year', 'month']"
                        :time-from="inicio * 60"
                        :time-to="fin * 60 + 60"
                        :events="events"
                        :on-event-click="onEventClick"
                    />
                </div>
            </div>
        </div>

        <Dialog v-model:visible="detailDialog" :style="{ width: 'min(92vw, 30rem)' }" header="Detalle de asistencia" :modal="true" position="top" class="attendance-detail-dialog">
            <div class="attendance-detail">
                <div class="attendance-detail-row">
                    <span>Estado</span>
                    <Tag :value="selectedAttendance.estado_texto" :severity="selectedSeverity"></Tag>
                </div>
                <div class="attendance-detail-row">
                    <span>Fecha</span>
                    <strong>{{ formatDate(selectedAttendance.fecha) }}</strong>
                </div>
                <div v-if="selectedAttendance.estado === '4'" class="attendance-description">
                    <span>Descripcion del permiso</span>
                    <p>{{ selectedAttendance.descripcion || "Sin descripcion registrada." }}</p>
                </div>
            </div>
            <template #footer>
                <Button label="Cerrar" icon="pi pi-times" class="p-button-secondary" @click="detailDialog = false" />
            </template>
        </Dialog>
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
        const detailDialog = ref(false);
        const selectedAttendance = ref({});
        const mobileCalendar = ref(window.innerWidth <= 576);
        const calendarView = computed(() => (mobileCalendar.value ? "day" : "week"));
        const selectedSeverity = computed(() => {
            return {
                1: "success",
                2: "warning",
                3: "danger",
                4: "info",
            }[selectedAttendance.value.estado];
        });
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
        const onEventClick = (event, nativeEvent) => {
            selectedAttendance.value = event;
            detailDialog.value = true;
            if (nativeEvent) nativeEvent.stopPropagation();
        };
        const formatDate = (value) => {
            if (!value) return "";
            const [year, month, day] = value.split("-");
            return `${day}/${month}/${year}`;
        };

        return {
            title,
            inicio,
            fin,
            events,
            mobileCalendar,
            calendarView,
            detailDialog,
            selectedAttendance,
            selectedSeverity,
            getAsistencias,
            getRangoFechas,
            onEventClick,
            formatDate,
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

.attendance-detail {
    display: grid;
    gap: 1rem;
}

.attendance-detail-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.attendance-description {
    padding: 1rem;
    border: 1px solid #bfdbfe;
    border-radius: 0.75rem;
    background: #eff6ff;
}

.attendance-description span {
    color: #1e3a8a;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
}

.attendance-description p {
    margin: 0.5rem 0 0;
    color: #1f2937;
    line-height: 1.5;
    overflow-wrap: anywhere;
}
</style>
