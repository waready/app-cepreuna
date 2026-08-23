<template>
    <Toast />
    <app-layout :title="title" :mode="2">
        <div class="card shadow-6">
            <div class="grid hidden sm:flex">
                <div class="col-12">
                    <h5 class="font-bold">Cursos</h5>
                </div>
            </div>
            <DataTable :value="cargas" responsiveLayout="stack" breakpoint="960px" class="p-datatable-sm cursos-table" :loading="loading">
                <template #empty>
                    <div class="py-4 text-center text-600">No hay cursos asignados para el periodo actual.</div>
                </template>
                <Column field="curso" header="Curso"></Column>
                <Column field="tipo" header="Tipo">
                    <template #body="slotProps">
                        <span>{{ slotProps.data.tipo == '1' ? 'Titular' : 'Reemplazo' }}</span>
                    </template>
                </Column>
                <Column field="grupo" header="Grupo"></Column>
                <Column header="Auxiliar">
                    <template #body="slotProps">
                        <div v-if="slotProps.data.auxiliar" class="contacto-persona">
                            <span class="contacto-nombre">{{ slotProps.data.auxiliar.nombre }}</span>
                            <a
                                v-if="slotProps.data.auxiliar.telefono"
                                class="contacto-telefono"
                                :href="telefonoHref(slotProps.data.auxiliar.telefono)"
                            >
                                <i class="pi pi-phone"></i>
                                {{ slotProps.data.auxiliar.telefono }}
                            </a>
                            <span v-else class="contacto-sin-telefono">No registrado</span>
                        </div>
                        <span v-else class="contacto-pendiente">Por asignar</span>
                    </template>
                </Column>
                <Column header="Coordinador">
                    <template #body="slotProps">
                        <div v-if="slotProps.data.coordinador" class="contacto-persona">
                            <span class="contacto-nombre">{{ slotProps.data.coordinador.nombre }}</span>
                            <a
                                v-if="slotProps.data.coordinador.telefono"
                                class="contacto-telefono"
                                :href="telefonoHref(slotProps.data.coordinador.telefono)"
                            >
                                <i class="pi pi-phone"></i>
                                {{ slotProps.data.coordinador.telefono }}
                            </a>
                            <span v-else class="contacto-sin-telefono">No registrado</span>
                        </div>
                        <span v-else class="contacto-pendiente">Por asignar</span>
                    </template>
                </Column>
                <Column field="estado" header="Estado">
                    <template #body="slotProps">
                        <template v-if="slotProps.data.estado=='1'">
                            <Tag class="mr-2" severity="success" value="Activo"></Tag>
                        </template>
                        <template v-else>
                            <Tag class="mr-2" severity="danger" value="Inactivo"></Tag>
                        </template>
                    </template>
                </Column>
                <Column field="estado" header="Dirección">
                    <template #body="slotProps">
                        <Button label="Ver Lugar" class="p-button-sm p-button-warning" icon="pi pi-map-marker" @click="verLugar(slotProps.data)" />
                    </template>
                </Column>
                <Column field="estado" header="Estudiantes">
                    <template #body="slotProps">
                        <Button label="Ver Estudiantes" class="p-button-sm p-button-secondary" icon="pi pi-users" @click="getEstudiante(slotProps.data.grupo_aula_id)" />
                    </template>
                </Column>
                <Column field="link" header="Enlace Meet">
                    <template #body="slotProps">
                        <template v-if="slotProps.data.link != null">
                            <a :href="slotProps.data.link" class="p-button p-component p-button-sm mr-1" target="_blank"><i class="pi pi-video"></i> Ir a Meet</a>
                            <Button label="Editar Enlace" class="p-button-success p-button-sm" icon="pi pi-pencil" @click="idCarga(slotProps.data.id)" />
                        </template>
                        <template v-else>
                            <Button label="Agregar Enlace" class="p-button-info p-button-sm" icon="pi pi-plus" @click="idCarga(slotProps.data.id)" />
                        </template>
                    </template>
                </Column>
                <!-- <Column field="" header="Opciones"></Column> -->
            </DataTable>
        </div>
        <!-- <pre>{{ users }}</pre> -->
        <Dialog v-model:visible="lugarDialog" :style="{ width: '500px' }" header="Dirección del grupo" position="top" class="bg-info">
            <div class="grid" style="margin-top:-30px">
                <div class="col-3 pb-0">
                        <p class="m-0 font-bold">Sede:</p>
                    </div>
                    <div class="col-9 pb-0">
                        <p class="m-0 text-base text-green-500">{{lugar.sede}}</p>
                    </div>
                    <div class="col-3 pb-0">
                        <p class="m-0 font-bold">Local</p>
                    </div>
                    <div class="col-9 pb-0">
                        <p class="m-0 text-base text-green-500">{{lugar.local}}</p>
                    </div>
                    <div class="col-3 pb-0">
                        <p class="m-0 font-bold">Dirección</p>
                    </div>
                    <div class="col-9 pb-0">
                        <p class="m-0 text-base text-green-500">{{lugar.direccion}}</p>
                    </div>
                    <div class="col-3 pb-0">
                        <p class="m-0 font-bold">Aula</p>
                    </div>
                    <div class="col-9 pb-0">
                        <p class="m-0 text-base text-green-500">{{lugar.aula}}</p>
                    </div>
                    <!-- <div class="col-12" v-if="lugar.foto"> -->
                        <img :src="lugar.foto" class="col-10 col-offset-1" alt="" />
                    <!-- </div> -->
            </div>
            <template #footer>
                <Button label="Cerrar" icon="pi pi-times" @click="closeBasic" class="p-button-secondary p-button-sm md:p-button"/>
            </template>
        </Dialog>
        <Dialog v-model:visible="estudianteDialog" header="Lista de Estudiantes" :style="{ width: '400px' }" position="top" :modal="true" :contentStyle="{height: '500px'}">
            <div class="grid" style="margin-top:-30px">
                <DataTable :value="estudiantes"
                    responsiveLayout="scroll"
                    class="p-datatable-sm col-12"
                    :paginator="true"
                    :rows="10"
                    paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                    :rowsPerPageOptions="[10,20,50]"
                    currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
                >
                    <Column field="paterno" header="Ap. Paterno"></Column>
                    <Column field="materno" header="Ap. Materno"></Column>
                    <Column field="nombres" header="Nombres"></Column>
                    <Column field="nro_documento" header="DNI"></Column>
                    <Column field="usuario" header="Correo"></Column>
                </DataTable>
            </div>
            <template #footer>
                <ExcelComponent class="p-button p-button-success p-component p-button-sm" :data="json_data" :columns="json_fields" :filename="'ListaEstudiantes'" :sheetname="'Reporte'"><i class="pi pi-file-excel"></i> Descargar Excel</ExcelComponent>
                <Button label="Cerrar" icon="pi pi-times" @click="closeBasic" class="p-button-secondary p-button-sm md:p-button"/>
            </template>
        </Dialog>

        <Dialog v-model:visible="enlaceDialog" :style="{ width: '500px' }" header="Link Meet"  :modal="true" position="top" class="bg-info">
            <div class="grid mx-4">
                <div class="col-12 md:col-12">
                    <div class="field grid p-fluid">
                        <label for="link" class="col-12 mb-2 md:col-3 md:mb-0">Link de Meet</label>
                        <div class="col-12 md:col-9">
                            <InputText id="link" type="text" v-model.trim="form.meet" autofocus/>
                            <small id="username1-help">Ejemplo: <b>https://meet.google.com/tov-uxq1ao-uhv</b></small>
                            <br>
                            <small class="p-error" v-if="errors.meet">{{ errors.meet }}</small>
                        </div>
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Cerrar" icon="pi pi-times" @click="closeBasic" class="p-button-secondary"/>
                <Button label="Guardar" icon="pi pi-check" @click="saveEnlace" autofocus :loading="saveLoading"  class="p-button-success p-button"/>
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
import ExcelComponent from "../../../components/ExcelComponent.vue";
// import AppTopBarSocial from "../Sigma/AppTopbarSocial.vue";
export default {
    components: {
        AppLayout,
        ExcelComponent,
    },
    props: {
        errors: Object,
        response: Object,
        data: Object,
    },
    setup(props) {
        const { response, data } = toRefs(props);
        const title = ref("Cursos");
        const toast = useToast();
        const urlExterno = ref(data.value.url_external);
        const cargas = ref([]);
        const loading = ref(false);
        const loadLazyData = () => {
            loading.value = true;
            axios
                .get(route("docentes.recursos.get-carga"), {
                    // params: {
                    //     area: form.area ? form.area.id : "",
                    //     turno: form.area ? form.turno.id : "",
                    //     sede: form.area ? form.sede.id : "",
                    // },
                })
                .then((response) => {
                    cargas.value = response.data.carga || [];
                })
                .catch(() => {
                    cargas.value = [];
                    toast.add({
                        severity: "error",
                        summary: "No se pudieron cargar los cursos",
                        detail: "Intente nuevamente en unos momentos.",
                        life: 5000,
                    });
                })
                .finally(() => {
                    loading.value = false;
                });
        };
        const telefonoHref = (telefono) => {
            return "tel:" + String(telefono).replace(/[^\d+]/g, "");
        };
        const lugarDialog = ref(false);
        const lugar = ref({
            sede:"",
            local:"",
            direccion:"",
            aula:"",
            foto:"",
        });
        const verLugar = (item) =>{
            console.log(item);
            lugar.value.sede = item.Sede;
            lugar.value.local = item.Local;
            lugar.value.direccion = item.DireccionLocal;
            lugar.value.aula = item.Aula;
            lugar.value.foto =  urlExterno.value+"/images/locales/" + item.Foto;

            lugarDialog.value = true;
        }
        const estudianteDialog = ref(false);
        const estudiantes = ref([]);
        const getEstudiante = (id) =>{
            axios
                .get(route("docentes.recursos.get-estudiantes",id))
                .then((response) => {
                    // this.grupo = response.data[0].id;
                    estudiantes.value = response.data.estudiantes;
                    json_data.value = response.data.estudiantes;
                });
            estudianteDialog.value = true;
        }
        const form = useForm({
            id: "",
            meet: "",
        });
        const enlaceDialog = ref(false);
        const idCarga = (id) => {
            enlaceDialog.value = true;
            form.id = id;
        }
        const closeBasic = () => {
            lugarDialog.value = false;
            estudianteDialog.value = false;
            enlaceDialog.value = false;
        }
        const saveLoading = ref(false);
        const saveEnlace = () => {
            saveLoading.value = true;
            Inertia.post(route("docentes.recursos.carga-update"), form, {
                onSuccess: () => {
                    if (response.value.status) {
                        loadLazyData();
                        toast.add({
                            severity: "success",
                            summary: "¡Exito...!",
                            detail: response.value.message,
                            life: 5000,
                        });

                        enlaceDialog.value = false;
                        // errors.value = [];
                        form.reset();

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
                    // console.log(errors);
                    // errors.value = errors;
                    saveLoading.value = false;
                    // registerBuyDialog.value = false;
                    // submitted.value = false;
                },
            });
        }
        const json_data = ref([]);
        const json_fields = ref([
            {
                label: "Apellido Paterno",
                field: "paterno"
            },
            {
                label: "Apellido Materno",
                field: "materno"
            },
            {
                label: "Nombres",
                field: "nombres"
            },
            {
                label: "DNI",
                field: "nro_documento"
            },
            {
                label: "Correo",
                field: "usuario"
            }
        ]);
        onMounted(() => {
            loadLazyData();
        });
        return {
            title,
            cargas,
            loadLazyData,
            lugarDialog,
            verLugar,
            closeBasic,
            lugar,
            getEstudiante,
            estudiantes,
            estudianteDialog,
            enlaceDialog,
            form,
            idCarga,
            saveLoading,
            saveEnlace,
            loading,
            json_fields,
            json_data,
            telefonoHref,
        };
    },
};
</script>
<style>
.turnos .p-timeline-event-opposite{
    min-width: 40px !important;
    flex: 0;
}

.contacto-persona {
    display: flex;
    min-width: 11rem;
    flex-direction: column;
    gap: 0.3rem;
}

.contacto-nombre {
    color: #263238;
    font-weight: 600;
    line-height: 1.25;
}

.contacto-telefono {
    color: #b74816;
    font-weight: 600;
    text-decoration: none;
}

.contacto-telefono:hover {
    text-decoration: underline;
}

.contacto-sin-telefono,
.contacto-pendiente {
    color: #78909c;
    font-size: 0.85rem;
}

@media (max-width: 960px) {
    .cursos-table .p-datatable-tbody > tr > td {
        align-items: flex-start;
    }

    .contacto-persona {
        min-width: 0;
        text-align: right;
    }
}
</style>
