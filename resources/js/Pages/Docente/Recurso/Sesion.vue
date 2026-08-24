<template>
    <Toast />
    <app-layout :title="title" :mode="2">
        <div class="card shadow-6">
            <div class="grid hidden sm:flex">
                <div class="col-12">
                    <h5 class="font-bold">Cursos</h5>
                </div>
            </div>
            <Button label="Agregar" class="mb-2 p-button-success" icon="pi pi-plus" @click="nuevo" />
            <DataTable :value="sesiones" responsiveLayout="stack" breakpoint="1024px" class="p-datatable-sm sesiones-table" :loading="loading"
                :paginator="true"
                :rows="10"
                paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                :rowsPerPageOptions="[10,20,50]"
                currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
                sortField="fecha" :sortOrder="-1"
            >
                <Column field="fecha" header="Fecha" :sortable="true"></Column>
                <Column field="curso" header="Curso"></Column>
                <Column field="grupo" header="Grupo"></Column>
                <Column field="modalidad" header="Modalidad">
                    <template #body="slotProps">
                        <Tag
                            class="modalidad-tag"
                            :icon="modalidadIcon(slotProps.data.modalidad)"
                            :severity="modalidadSeverity(slotProps.data.modalidad)"
                            :value="slotProps.data.modalidad"
                        />
                    </template>
                </Column>
                <Column field="tema" header="Tema"></Column>

                <Column field="id" header="Opciones">
                    <template #body="slotProps">
                        <Button label="Editar" icon="pi pi-pencil" class="p-button-sm" @click="editSesion(slotProps.data)" />
                    </template>
                </Column>
                <!-- <Column field="" header="Opciones"></Column> -->
            </DataTable>
        </div>
        <!-- <pre>{{ users }}</pre> -->

        <Dialog v-model:visible="sesionDialog" :style="{ width: '500px' }" :breakpoints="{ '640px': 'calc(100vw - 1rem)' }" :header="titulo" :modal="true" position="top" class="bg-info">
            <div class="grid mx-4" style="margin-top:-20px">
                <div class="col-12 md:col-12">
                    <div class="field p-fluid">
                        <label for="curso">Curso</label>
                        <AutoComplete
                            id="curso"
                            v-model="selectedCursos"
                            :suggestions="filteredCursos"
                            @complete="searchCursos($event)"
                            :dropdown="true"
                            field="name"
                            forceSelection
                        >
                            <template #item="slotProps">
                                <div class="curso-opcion">
                                    <span class="curso-opcion-nombre">{{ slotProps.item.curso }} ({{ slotProps.item.grupo }})</span>
                                    <Tag
                                        class="modalidad-tag"
                                        :icon="modalidadIcon(slotProps.item.modalidad)"
                                        :severity="modalidadSeverity(slotProps.item.modalidad)"
                                        :value="slotProps.item.modalidad"
                                    />
                                </div>
                            </template>
                        </AutoComplete>
                        <small class="p-error" v-if="errors.carga">{{ errors.carga }}</small>
                    </div>
                    <div class="field p-fluid">
                        <label for="fecha">Fecha</label>
                        <Calendar id="fecha" v-model="form.fecha" autocomplete="off" dateFormat="dd/mm/yy" :showIcon="true" />
                        <small class="p-error" v-if="errors.fecha">{{ errors.fecha }}</small>
                    </div>
                    <div class="field p-fluid">
                        <label for="tema">Tema</label>
                        <Textarea id="tema" v-model="form.tema" rows="5" cols="30" />
                        <small class="p-error" v-if="errors.tema">{{ errors.tema }}</small>
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Cerrar" icon="pi pi-times" @click="closeBasic" class="p-button-secondary p-button-sm"/>
                <Button label="Guardar" icon="pi pi-check" @click="save" autofocus :loading="saveLoading"  class="p-button-success p-button p-button-sm"/>
            </template>
        </Dialog>
    </app-layout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout";
import { useToast } from "primevue/usetoast";
import { Inertia } from "@inertiajs/inertia";
import { useForm } from "@inertiajs/inertia-vue3";
import { ref, onMounted, watch, toRefs, computed } from "vue";
import axios from "axios";
// import AppTopBarSocial from "../Sigma/AppTopbarSocial.vue";
export default {
    components: {
        AppLayout,
    },
    props: {
        errors: Object,
        response: Object,
        data: Object,
    },
    setup(props) {
        const { response, data } = toRefs(props);
        const title = ref("Cursos");
        const titulo = ref("");
        const editar = ref(false);
        const id = ref("");
        const toast = useToast();
        const urlExterno = ref(data.value.url_external);
        const sesiones = ref([]);
        const loading = ref(false);
        const loadLazyData = () => {
            loading.value = true;
            axios
                .get(route("docentes.recursos.lista-sesion"), {
                    // params: {
                    //     area: form.area ? form.area.id : "",
                    //     turno: form.area ? form.turno.id : "",
                    //     sede: form.area ? form.sede.id : "",
                    // },
                })
                .then((response) => {
                    loading.value = false;
                    // this.grupo = response.data[0].id;
                    sesiones.value = response.data.sesiones;
                });
        };
        const curso = ref();
        const form = useForm({
            id: "",
            fecha: "",
            tema: "",
            carga: "",
        });
        const sesionDialog = ref(false);
        const nuevo = () => {
            editar.value = false;
            titulo.value = "Nueva Sesion"
            sesionDialog.value = true;
            selectedCursos.value=null;
            // form.id = id;
            id.value = '';
        }
        const editSesion = (item) => {
            editar.value = true;
            titulo.value = "Editar Sesion"
            id.value = item.id;
            form.tema = item.tema;
            let fecha = new Date(item.fecha);
            fecha.setDate(fecha.getDate() + 1);
            form.fecha = fecha;
            selectedCursos.value = {
                id: item.carga_academicas_id,
                curso: item.curso,
                grupo: item.grupo,
                modalidad: item.modalidad,
                name: `${item.curso} (${item.grupo}) - ${item.modalidad}`,
            };

            // axios
            // .get(route("docentes.recursos.sesiones-edit",item.id), {
            // })
            // .then((response) => {
            //     console.log(response);
            // });

            sesionDialog.value = true;
            // form.id = id;
        }

        const saveLoading = ref(false);
        const save = () => {
            // console.log(selectedCursos.value);
            form.carga = selectedCursos.value!=null?selectedCursos.value.id:'';
            // console.log(form);
            saveLoading.value = true;
            if(editar.value){
                Inertia.put(route("docentes.recursos.update-sesion",id.value), form, {
                    onSuccess: () => {
                        if (response.value.status) {
                            loadLazyData();
                            toast.add({
                                severity: "success",
                                summary: "¡Exito...!",
                                detail: response.value.message,
                                life: 5000,
                            });

                            sesionDialog.value = false;
                            form.reset();
                            selectedCursos.value=null;

                            saveLoading.value = false;
                        } else {
                            toast.add({
                                severity: "error",
                                summary: "¡Error!",
                                detail: response.value.message,
                                life: 5000,
                            });
                            saveLoading.value = false;
                        }
                    },
                    onError: () => {
                        saveLoading.value = false;
                    },
                });
            }else{
                Inertia.post(route("docentes.recursos.store-sesion"), form, {
                    onSuccess: () => {
                        if (response.value.status) {
                            loadLazyData();
                            toast.add({
                                severity: "success",
                                summary: "¡Exito...!",
                                detail: response.value.message,
                                life: 5000,
                            });

                            sesionDialog.value = false;
                            form.reset();
                            selectedCursos.value=null;

                            saveLoading.value = false;
                        } else {
                            toast.add({
                                severity: "error",
                                summary: "¡Error!",
                                detail: response.value.message,
                                life: 5000,
                            });
                            saveLoading.value = false;
                        }
                    },
                    onError: () => {
                        saveLoading.value = false;
                    },
                });
            }

        }
        // autocomplete permisos
        const cursos = ref([]);
        const getCursos = () =>{
            axios
            .get(route("docentes.recursos.get-cursos-carga"), {
                // params: {
                //     area: form.area ? form.area.id : "",
                //     turno: form.area ? form.turno.id : "",
                //     sede: form.area ? form.sede.id : "",
                // },
            })
            .then((response) => {
                // this.grupo = response.data[0].id;
                cursos.value = response.data;
            });
        }
        const selectedCursos = ref();
        const filteredCursos = ref();
        const searchCursos = (event) => {
            if (!event.query.trim().length) {
                filteredCursos.value = [...cursos.value];
            } else {
                filteredCursos.value = cursos.value.filter((curso) => {
                    return curso.name.toLowerCase().includes(event.query.toLowerCase());
                });
            }
        };
        const modalidadSeverity = (modalidad) => (modalidad === "Virtual" ? "info" : "success");
        const modalidadIcon = (modalidad) => (modalidad === "Virtual" ? "pi pi-desktop" : "pi pi-map-marker");
        const closeBasic = () => {
            // lugarDialog.value = false;
            // estudianteDialog.value = false;
            sesionDialog.value = false;
        }
        onMounted(() => {
            loadLazyData();
            getCursos();
        });
        return {
            title,
            titulo,
            sesiones,
            loading,
            loadLazyData,
            closeBasic,
            sesionDialog,
            form,
            curso,
            nuevo,
            editSesion,
            saveLoading,
            save,
            selectedCursos,
            filteredCursos,
            searchCursos,
            modalidadSeverity,
            modalidadIcon,
        };
    },
};
</script>
<style>
.turnos .p-timeline-event-opposite{
    min-width: 40px !important;
    flex: 0;
}

.modalidad-tag {
    min-width: 7.5rem;
    justify-content: center;
}

.curso-opcion {
    display: flex;
    width: 100%;
    min-width: 0;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

.curso-opcion-nombre {
    min-width: 0;
    overflow-wrap: anywhere;
}

@media (max-width: 576px) {
    .curso-opcion {
        align-items: flex-start;
        flex-direction: column;
        gap: 0.4rem;
    }
}
</style>
