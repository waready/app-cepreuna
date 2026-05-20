<template>
    <Toast />
    <app-layout :title="title" :mode="2">
        <div class="card shadow-6">
            <div class="grid hidden sm:flex">
                <div class="col-12">
                    <h5 class="font-bold">Libro de Reclamaciones</h5>
                </div>
            </div>
            <Toolbar class="mb-4">
                <template #start>
                    <Button label="Nuevo" icon="pi pi-plus" class="p-button-success mr-2" @click="openNew" />
                </template>
            </Toolbar>
            <DataTable
                :value="reclamos"
                :lazy="true"
                :paginator="true"
                :rows="10"
                ref="dt"
                :totalRecords="totalRecords"
                :loading="loading"
                @page="onPage($event)"
                @sort="onSort($event)"
                filterDisplay="row"
                responsiveLayout="stack"
                stripedRows
                showGridlines
                class="p-datatable-sm"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                :rowsPerPageOptions="[5, 10, 25]"
            >
                <Column field="descripcion" header="Asunto del Reclamo" ref="descripcion" :sortable="true" style="min-width: 35rem"> </Column>
                <Column field="fecha_ingreso" header="Fecha de Ingreso" ref="fecha_ingreso" :sortable="true" style="min-width: 3rem"> </Column>
                <Column field="estado" header="Estado" ref="estado" :sortable="true" style="min-width: 6rem">
                    <template #body="processedData">
                        <p v-if="processedData.data.estado == '0'" class="flex justify-content-center"><Tag severity="info" icon="pi pi-envelope">Enviado</Tag></p>
                        <p v-if="processedData.data.estado == '1'" class="flex justify-content-center"><Tag severity="success" icon="pi pi-check-square">Atendido</Tag></p>
                    </template>
                </Column>
                <Column field="respuesta" header="Respuesta" ref="respuesta" :sortable="true" style="min-width: 8rem">
                    <template #body="processedData">
                        <p class="flex justify-content-center">
                            <Button
                                v-if="processedData.data.estado == '1'"
                                icon="pi pi-comment"
                                class="p-button-raised p-button-warning p-1"
                                label="Ver Respuesta"
                                @click="verRespuesta(processedData.data.respuesta)"
                            />
                        </p>
                    </template>
                </Column>
                <Column field="fecha_respuesta" header="Fecha de Respuesta" ref="fecha_respuesta" :sortable="true" style="min-width: 3rem"> </Column>
            </DataTable>
            <Dialog v-model:visible="dialog" :style="{ width: '700px' }" header="Detalle de Reclamo" :modal="true" class="fluid bg-primary">
                <div class="flex flex-column">
                    <input type="hidden" :v-model="(form.user_name = userDatos.nombres)" />
                    <input type="hidden" :v-model="(form.user_paterno = userDatos.paterno)" />
                    <input type="hidden" :v-model="(form.user_materno = userDatos.materno)" />
                    <input type="hidden" :v-model="(form.user_dni = userDatos.nro_documento || userDatos.dni)" />
                    <!-- <input type="hidden" :v-model="(form.user_correo = userDatos.email)" /> -->
                    <!-- <input type="hidden" :v-model="(form.user_celular = userDatos.celular)" /> -->
                    <Panel class="flex col-12 flex-column mb-2">
                        <template #header>
                            <b>Hoja de Reclamación</b>
                        </template>
                        <div class="flex flex-column">
                            <div class="flex col-12">
                                <div class="">
                                    <label class="font-bold">Fecha</label>
                                    <div class="p-inputgroup">
                                        <span class="p-inputgroup-addon">
                                            <i class="pi pi-calendar"></i>
                                        </span>
                                        <InputText :placeholder="timestamp" disabled />
                                        <!-- <input type="hidden" v-model="(form.fecha = timestamp)"> -->
                                    </div>
                                </div>
                            </div>
                            <!-- <div class="grid p-fluid"> -->
                            <!-- <div class="col-12 md:col-6">
                                <label for="" class="font-bold">Establecimiento</label>
                                <div class="p-inputgroup">
                                    <span class="p-inputgroup-addon">
                                        <i class="pi pi-building"></i>
                                    </span>
                                    <Dropdown placeholder="Seleccione una opción" :options="establecimientos" optionLabel="name" v-model="form.establecimiento" />
                                </div>
                            </div> -->
                            <!-- <div class="col-12 md:col-6">
                                <label for="" class="font-bold">Dirección</label>
                                <div class="p-inputgroup">
                                    <span class="p-inputgroup-addon">
                                        <i class="pi pi-map-marker"></i>
                                    </span>
                                    <InputText :placeholder="establecimientos.direccion" disabled />
                                </div>
                            </div> -->
                            <!-- </div> -->
                        </div>
                    </Panel>

                    <Panel class="flex col-12 flex-column mb-2">
                        <template #header>
                            <b>Identificación del Consumidor Reclamante</b>
                        </template>
                        <div class="grid p-fluid">
                            <div class="col-12 md:col-6">
                                <label for="" class="font-bold">Nombre</label>
                                <div class="p-inputgroup">
                                    <span class="p-inputgroup-addon">
                                        <i class="pi pi-user"></i>
                                    </span>
                                    <InputText :placeholder="userDatos.nombres + ' ' + userDatos.paterno + ' ' + userDatos.materno" disabled />
                                </div>
                            </div>
                            <div class="col-12 md:col-6">
                                <label for="" class="font-bold">Domicilio</label>
                                <div class="p-inputgroup">
                                    <span class="p-inputgroup-addon">
                                        <i class="pi pi-map-marker"></i>
                                    </span>
                                    <InputText placeholder="" v-model="form.user_domicilio" />
                                </div>
                                <small class="p-error">{{ errors.user_domicilio }}</small>
                            </div>
                        </div>
                        <div class="grid p-fluid">
                            <div class="col-12 md:col-4">
                                <label for="" class="font-bold">DNI</label>
                                <div class="p-inputgroup">
                                    <span class="p-inputgroup-addon">
                                        <i class="pi pi-id-card"></i>
                                    </span>
                                    <InputText :placeholder="userDatos.nro_documento || userDatos.dni" disabled />
                                </div>
                            </div>
                            <div class="col-12 md:col-4">
                                <label for="" class="font-bold">Correo</label>
                                <div class="p-inputgroup">
                                    <span class="p-inputgroup-addon">
                                        <i class="pi pi-envelope"></i>
                                    </span>
                                    <InputText v-model="form.user_correo"/>
                                    <!-- <InputText :placeholder="userDatos.email" disabled /> -->
                                </div>
                                <small class="p-error">{{ errors.user_correo }}</small>
                            </div>
                            <div class="col-12 md:col-4">
                                <label for="" class="font-bold">Celular</label>
                                <div class="p-inputgroup">
                                    <span class="p-inputgroup-addon">
                                        <i class="pi pi-phone"></i>
                                    </span>
                                    <InputText v-model="form.user_celular"/>
                                    <!-- <InputText :placeholder="userDatos.celular" disabled /> -->
                                </div>
                                <small class="p-error">{{ errors.user_celular }}</small>
                            </div>
                        </div>
                        <div class="grid p-fluid">
                            <div class="col-12">
                                <label for="" class="font-bold">Padre/Madre</label>
                                <div class="p-inputgroup">
                                    <span class="p-inputgroup-addon">
                                        <i class="pi pi-users"></i>
                                    </span>
                                    <InputText placeholder="Para el caso de menores de edad" v-model="form.apoderado" />
                                </div>
                            </div>
                        </div>
                    </Panel>

                    <Panel class="flex col-12 flex-column mb-2">
                        <template #header>
                            <b>Identificación del bien contratado</b>
                        </template>
                        <div class="grid p-fluid">
                            <!-- <div class="col-12 md:col-6">
                                <label for="" class="font-bold">Tipo de Bien</label>
                                <Dropdown placeholder="Seleccione el Tipo de Bien" :options="tiposBien" optionLabel="name" v-model="form.tipo_bien" />
                                <small class="p-error">{{ errors.tipo_bien }}</small>
                            </div> -->
                            <div class="col-12">
                                <label for="" class="font-bold">Descripción/Asunto</label>
                                <div class="p-inputgroup">
                                    <span class="p-inputgroup-addon">
                                        <i class="pi pi-bookmark"></i>
                                    </span>
                                    <InputText placeholder="" v-model="form.descripcion" />
                                </div>
                                <small class="p-error">{{ errors.descripcion }}</small>
                            </div>
                        </div>
                        <!-- <div class="grid p-fluid"> -->
                        <!-- <div class="col-12 md:col-6">
                            <label for="" class="font-bold">Clasificación</label>
                            <Dropdown placeholder="Seleccione una opción" :options="clasificaciones" optionLabel="name" v-model="form.clasificacion" />
                        </div> -->
                        <!-- <div class="col-12 md:col-4">
                                <label for="" class="font-bold">Monto reclamado</label>
                                <div class="p-inputgroup">
                                    <span class="p-inputgroup-addon"> S/ </span>
                                    <InputNumber placeholder="" v-model="form.monto" mode="decimal" :minFractionDigits="2" :maxFractionDigits="2" />
                                </div>
                                <small class="p-error">{{ errors.monto }}</small>
                            </div> -->
                        <!-- </div> -->
                    </Panel>

                    <Panel class="flex col-12 flex-column mb-2">
                        <template #header>
                            <b>Detalle de la reclamación y pedido del consumidor</b>
                        </template>
                        <div class="col-12 flex-column">
                            <label for="" class="font-bold">Tipo de Reclamación</label>
                            <div class="flex flex-column md:flex-row align-items-center">
                                <Dropdown placeholder="Seleccione" class="col-12 md:col-4 mr-2" :options="tiposReclamacion" optionLabel="name" v-model="form.tipo_reclamacion" />
                                <small id="">(*) Disconformidad no relacionada a los productos o servicios; o, malestar o descontento respecto a la atención al público.</small>
                            </div>
                            <small class="p-error">{{ errors.tipo_reclamacion }}</small>
                        </div>
                        <div class="col-12">
                            <label for="" class="font-bold">Detalle</label>
                            <Textarea class="col-12" v-model="form.detalle" />
                            <small id="">
                                (*)Importante :En caso su reclamo o queja se haya generado a raíz de una atención telefónica o por WhatsApp, se sugiere registrar el número telefónico des el cual se
                                contactó con CEPRE UNA.
                            </small>
                            <small class="p-error">{{ errors.detalle }}</small>
                        </div>
                        <div class="col-12">
                            <label for="" class="font-bold">Pedido</label>
                            <Textarea class="col-12" v-model="form.pedido" />
                            <small id=""> (*)Explicar de forma concreta lo que solicita. </small>
                            <small class="p-error">{{ errors.pedido }}</small>
                        </div>
                        <div class="col-12">
                            <label for="" class="font-bold">Adjuntar Evidencia</label>
                            <FileInput :size="2" is-image placeholder-button-text="Seleccionar archivo" placeholder-input-text="Sin archivos seleccionados" v-model="form.evidencia" />
                            <small class="p-error">{{ errors.evidencia }}</small>
                        </div>
                    </Panel>

                    <Panel class="flex col-12 flex-column mb-2">
                        <template #header>
                            <b>Observaciones y acciones tomadas por el proveedor</b>
                        </template>
                        La respuesta a la presente será atendida a través de esta plataforma en la sección de Libro de Reclamaciones.
                        <!-- La respuesta a la presente será atendida mediante correo electrónico a su correo personal, indicado en la presente hoja de reclamación. -->
                    </Panel>

                    <Panel class="flex col-12 flex-column mb-2">
                        <ul>
                            <li>La formulación de reclamo no impide acudir a otras vias de solución de controversias ni es requisito previo para interponer una denuncia ante el INDECOPI.</li>
                            <li>
                                El proveedor deberá dar respuesta al reclamo en un plazo no mayor a treinta (30) días calendario, pudiendo ampliar el plazo hasta por treinta (30) días más, previa
                                comunicación al consumidor.
                            </li>
                        </ul>
                    </Panel>

                    <Button label="Enviar" class="p-button-raised p-button-primary-theme" @click="enviarReclamo()" />
                </div>
            </Dialog>

            <Dialog v-model:visible="dialogRespuesta" :style="{ width: '700px' }" header="Respuesta al Reclamo" :modal="true" class="fluid bg-primary">
                {{ respuestaRetorno }}
                <!-- <p>HOLA</p> -->
            </Dialog>
        </div>
    </app-layout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout";
import FileInput from "@/components/FileInput.vue";
import { useToast } from "primevue/usetoast";
import { Inertia } from "@inertiajs/inertia";
import { useForm } from "@inertiajs/inertia-vue3";
import { ref, onMounted, watch, toRefs } from "vue";
import axios from "axios";

export default {
    props: {
        user: Object,
        usuario: Object,
        errors: Object,
        response: Object,
    },
    setup(props) {
        const { user, usuario } = toRefs(props);
        const userDatos = ref(usuario.value);
        const title = ref("Libro de Reclamaciones");
        const tiposReclamacion = ref([{ name: "Queja" }, { name: "Reclamo" }]);
        const toast = useToast();
        const { response } = toRefs(props);
        const form = useForm({
            user_name: "",
            user_paterno: "",
            user_materno: "",
            user_dni: "",
            user_domicilio: "",
            user_correo: "",
            user_celular: "",
            establecimiento: "",
            apoderado: "",
            descripcion: "",
            tipo_reclamacion: null,
            detalle: "",
            pedido: "",
            evidencia: null,
            fecha: "",
        });
        const dt = ref();
        const loading = ref(false);
        const totalRecords = ref(0);
        const page = ref(1);
        const reclamos = ref();
        const filters = ref({
            descripcion: { value: "", matchMode: "contains" },
            estado: { value: "", matchMode: "contains" },
        });
        const lazyParams = ref({});
        const columns = ref([
            { field: "descripcion", header: "Asunto" },
            { field: "fecha_ingreso", header: "Fecha de Ingreso" },
            { field: "estado", header: "Estado" },
            { field: "respuesta", header: "Respuesta" },
            { field: "fecha_respuesta", header: "Fecha de Respuesta"},
        ]);

        const loadLazyData = () => {
            // console.log("HOLA");
            loading.value = true;

            axios
                .get(route("libroReclamaciones.tabla"), { params: { lazyEvent: JSON.stringify(lazyParams.value), page: page.value } })
                .then((response) => {
                    reclamos.value = response.data.data;
                    // // console.log(reclamos.value);
                    totalRecords.value = response.data.total;
                    loading.value = false;
                })
                .catch((errors) => {
                    console.log(errors);
                });
        };
        const onPage = (event) => {
            page.value = event.originalEvent.page + 1;
            lazyParams.value = event;
            loadLazyData();
        };
        const onSort = (event) => {
            page.value = 1;
            lazyParams.value = event;
            loadLazyData();
        };
        const onFilter = () => {
            loading.value = true;
            lazyParams.value.filters = filters.value;
            loadLazyData();
        };
        watch(filters, () => {
            onFilter();
        });

        const timestamp = ref([]);
        const currentDate = () => {
            timestamp.value = new Date(Date.now()).toLocaleDateString();
            // form.fecha = timestamp.value;
            const today = new Date();
            form.fecha = today.getFullYear() + "/" + String(today.getMonth() + 1).padStart(2, "0") + "/" + String(today.getDate()).padStart(2, "0");
        };

        const saveLoading = ref(false);
        const enviarReclamo = () => {
            saveLoading.value = true;
            Inertia.post(route("libroReclamaciones.store"), form, {
                onSuccess: () => {
                    if (response.value.status) {
                        loadLazyData();
                        toast.add({
                            severity: "success",
                            summary: "¡Exito...!",
                            detail: response.value.message,
                            life: 5000,
                        });
                        form.reset();
                        dialog.value = false;
                    } else {
                        toast.add({
                            severity: "error",
                            summary: "¡Error!",
                            detail: response.value.message,
                            life: 5000,
                        });
                    }
                    saveLoading.value = false;
                },
                onError: (errors) => {
                    saveLoading.value = false;
                },
            });
        };

        const dialogRespuesta = ref(false);
        const respuestaRetorno = ref();
        const verRespuesta = (respuesta) => {
            dialogRespuesta.value = true;
            respuestaRetorno.value = respuesta;
        };

        const dialog = ref(false);
        const openNew = () => {
            dialog.value = true;
        };

        onMounted(() => {
            loading.value = true;
            lazyParams.value = {
                first: 0,
                rows: dt.value.rows,
                sortField: null,
                sortOrder: null,
                filters: filters.value,
            };
            currentDate();
            loadLazyData();
        });

        return {
            title,
            tiposReclamacion,
            userDatos,
            form,
            timestamp,
            currentDate,
            enviarReclamo,
            saveLoading,
            dialog,
            openNew,
            reclamos,
            dt,
            loading,
            totalRecords,
            reclamos,
            filters,
            lazyParams,
            columns,
            loadLazyData,
            onPage,
            onSort,
            dialogRespuesta,
            verRespuesta,
            respuestaRetorno,
        };
    },
    components: {
        AppLayout,
        FileInput,
    },
};
</script>
