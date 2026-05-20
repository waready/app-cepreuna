<template>
    <Toast />
    <app-layout :title="title" :mode="2">
        <div class="card shadow-6">
            <div class="grid hidden sm:flex">
                <div class="col-12 text-center">
                    <Avatar
                        v-if="!$page.props.usuario.profile_photo_path"
                        :image="$page.props.usuario.profile_photo_url"
                        class="mr-2 mt-5 mb-1 p-avatar-circle"
                        size="xlarge"
                        shape="circle"
                        style="width: 8rem; height: 8rem; background-color: #ff9c56"
                    />
                    <Avatar v-else :image="$page.props.usuario.profile_photo_path" class="mr-2 mt-5 mb-1" size="xlarge" shape="circle" style="width: 8rem; height: 8rem; background-color: #ff9c56" />
                </div>
            </div>
            <div class="grid">
                <div class="col-12 text-center">
                    <h5 class="font-bold">{{ $page.props.usuario.paterno + " " + $page.props.usuario.materno + " " + $page.props.usuario.nombres }}</h5>
                    <label>{{ $page.props.usuario.email }}</label>
                </div>
                <div v-if="data.estudiante && data.estudiante.matricula.habilitado_estado == '1'" class="col-12 text-center">
                    <InlineMessage severity="success">Ud. se encuentra habilitado para su inscripción al examen del periodo actual de CEPREUNA.</InlineMessage><br />

                    <a class="p-button p-component p-button-sm p-button-danger mt-2" :href="$page.props.usuario.url + '/dga/estudiantes/pdf-constancia/' + idMatricula" target="_blank">
                        <span class="pi pi-file-pdf p-button-icon p-button-icon-left"></span>
                        <span class="p-button-label">Descargar Constancia</span>
                    </a>
                </div>
                <div class="col-12 md:col-6">
                    <div class="grid">
                        <div class="col-12">
                            <Fieldset legend="Datos Personales">
                                <template v-if="data.estudiante">
                                    <b>Celular</b>
                                    <p>{{ data.estudiante.celular }}</p>
                                    <b>Fecha de Nacimiento</b>
                                    <p>{{ data.estudiante.fecha_nac }}</p>
                                    <b>Lugar de Nacimiento</b>
                                    <p v-if="data.estudiante.ubigeo_nacimiento">
                                        {{ data.estudiante.ubigeo_nacimiento.departamento + "-" + data.estudiante.ubigeo_nacimiento.provincia + "-" + data.estudiante.ubigeo_nacimiento.distrito }}
                                    </p>
                                    <b>Lugar de Procedencia</b>
                                    <p v-if="data.estudiante.ubigeo">{{ data.estudiante.ubigeo.departamento + "-" + data.estudiante.ubigeo.provincia + "-" + data.estudiante.ubigeo.distrito }}</p>
                                </template>
                            </Fieldset>
                        </div>
                    </div>
                </div>
                <div class="col-12 md:col-6">
                    <div class="grid">
                        <div class="col-12">
                            <Fieldset legend="Datos Adicionales">
                                <template v-if="data.estudiante">
                                    <b>Colegio</b>
                                    <p>{{ data.estudiante.colegio.denominacion }}</p>
                                    <b>Año de Egreso</b>
                                    <p>{{ data.estudiante.anio_egreso }}</p>
                                    <hr />
                                    <template v-if="data.estudiante.edit == '0'">
                                        <div class="field-checkbox">
                                            <Checkbox id="binary" v-model="checkDeclaracion" :value="false" binary />
                                            <label for="binary" class="text-blue-700"
                                                >Declaro bajo juramento que los datos registrados
                                                <b>nombres, apellidos, DNI, fecha de nacimiento, lugar de nacimiento, año de egreso y la fotografía son correctos y válidos </b> para el examen de
                                                CEPREUNA.</label
                                            >
                                        </div>
                                        <div class="text-center">
                                            <Button :disabled="!checkDeclaracion" label="Aceptar" @click="confirmarDatos" class="p-button-success" />
                                            <Button :disabled="checkDeclaracion" label="Corregir Datos" @click="perfilDialog = true" class="p-button-secondary ml-2" />
                                        </div>
                                    </template>
                                    <template v-else>
                                        <!-- <a id="" class="btn btn-danger p-1" :href="'/estudiante/pdf-declaracion-jurada/' + data.estudiante.id" role="button" download
                                            ><i class="fas fa-file-pdf"></i> Descargar Declaracion Jurada</a
                                        > -->
                                        <InlineMessage severity="success">La confirmación de sus datos se realizo de manera satisfactoria.</InlineMessage>
                                    </template>
                                </template>
                            </Fieldset>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Agregar y Editar Registro -->
            <Dialog v-model:visible="perfilDialog" :style="{ width: '500px' }" header="Modificación de Datos" :modal="true" class="fluid bg-primary">
                <form @submit.prevent="" action="" autocomplete="off">
                    <div class="grid mx-4">
                        <div class="col-12 md:col-12">
                            <div class="field grid p-fluid">
                                <div class="col-12 mb-3">
                                    <InlineMessage severity="info"
                                        >La fotografía debe ser tomada de frente y con <b>fondo blanco</b>, sin prendas en la cabeza, sin gafas oscuras o cualquier otra prenda que impida o dificulte
                                        la identificación de la persona.</InlineMessage
                                    >
                                </div>
                                <div class="col-12 md:col-12">
                                    <UploadImage @imagen64="form.foto = $event" />
                                    <small class="p-error" v-if="errors.foto">{{ errors.foto }}</small>
                                </div>
                            </div>
                            <div class="field grid p-fluid">
                                <label for="nombres" class="col-12 mb-2 md:col-4 md:mb-0">Nombres</label>
                                <div class="col-12 md:col-8">
                                    <InputText id="nombres" type="text" v-model.trim="form.nombres" autofocus />
                                    <small class="p-error" v-if="errors.nombres">{{ errors.nombres }}</small>
                                </div>
                            </div>
                            <div class="field grid p-fluid">
                                <label for="paterno" class="col-12 mb-2 md:col-4 md:mb-0">Apellido Paterno</label>
                                <div class="col-12 md:col-8">
                                    <InputText id="paterno" type="text" v-model.trim="form.paterno" autofocus />
                                    <small class="p-error" v-if="errors.paterno">{{ errors.paterno }}</small>
                                </div>
                            </div>
                            <div class="field grid p-fluid">
                                <label for="materno" class="col-12 mb-2 md:col-4 md:mb-0">Apellido Materno</label>
                                <div class="col-12 md:col-8">
                                    <InputText id="materno" type="text" v-model.trim="form.materno" autofocus />
                                    <small class="p-error" v-if="errors.materno">{{ errors.materno }}</small>
                                </div>
                            </div>
                            <!-- <div class="field grid p-fluid">
                                <label for="nro_documento" class="col-12 mb-2 md:col-4 md:mb-0">Número de Documento</label>
                                <div class="col-12 md:col-8">
                                    <InputText id="nro_documento" type="text" v-model.trim="form.nro_documento" autofocus />
                                    <small class="p-error" v-if="errors.nro_documento">{{ errors.nro_documento }}</small>
                                </div>
                            </div> -->
                            <div class="field grid p-fluid">
                                <label for="fecha_nacimiento" class="col-12 mb-2 md:col-4 md:mb-0">Fecha de Nacimiento</label>
                                <div class="col-12 md:col-8">
                                    <Calendar
                                        id="fecha_nacimiento"
                                        v-model="form.fecha_nacimiento"
                                        :showIcon="true"
                                        :monthNavigator="true"
                                        yearRange="1900:2030"
                                        dateFormat="dd-mm-yy"
                                        placeholder="dd-mm-yyyy"
                                    />
                                    <small class="p-error" v-if="errors.fecha_nacimiento">{{ errors.fecha_nacimiento }}</small>
                                </div>
                            </div>
                            <div class="field grid p-fluid">
                                <label for="anio_egreso" class="col-12 mb-2 md:col-4 md:mb-0">Año de Egreso</label>
                                <div class="col-12 md:col-8">
                                    <InputText id="anio_egreso" type="text" v-model.trim="form.anio_egreso" autofocus />
                                    <small class="p-error" v-if="errors.anio_egreso">{{ errors.anio_egreso }}</small>
                                </div>
                            </div>
                            <div class="field grid p-fluid">
                                <label for="pais" class="col-12 mb-2 md:col-4 md:mb-0">Pais de Nacimiento</label>
                                <div class="col-12 md:col-8">
                                    <AutoComplete
                                        id="pais"
                                        v-model="selectedPais"
                                        :suggestions="filteredPaises"
                                        @complete="searchPaises($event)"
                                        field="denominacion"
                                        :dropdown="true"
                                        @item-select="changePais"
                                    />
                                    <small class="p-error" v-if="errors.pais">{{ errors.pais }}</small>
                                </div>
                            </div>
                            <div class="field grid p-fluid">
                                <label for="departamento" class="col-12 mb-2 md:col-4 md:mb-0">Departamento de Nacimiento</label>
                                <div class="col-12 md:col-8">
                                    <AutoComplete
                                        id="departamento"
                                        v-model="selectedDepartamento"
                                        :suggestions="filteredDepartamentos"
                                        @complete="searchDepartamentos($event)"
                                        field="departamento"
                                        :dropdown="true"
                                        @item-select="changeDepartamento"
                                        :disabled="disabledDepartamento"
                                    />
                                    <small class="p-error" v-if="errors.departamento">{{ errors.departamento }}</small>
                                </div>
                            </div>
                            <div class="field grid p-fluid">
                                <label for="provincia" class="col-12 mb-2 md:col-4 md:mb-0">Provincia de Nacimiento</label>
                                <div class="col-12 md:col-8">
                                    <AutoComplete
                                        id="provincia"
                                        v-model="selectedProvincia"
                                        :suggestions="filteredProvincias"
                                        @complete="searchProvincias($event)"
                                        field="provincia"
                                        :dropdown="true"
                                        @item-select="changeProvincia"
                                        :disabled="disabledProvincia"
                                    />
                                    <small class="p-error" v-if="errors.provincia">{{ errors.provincia }}</small>
                                </div>
                            </div>
                            <div class="field grid p-fluid">
                                <label for="distrito" class="col-12 mb-2 md:col-4 md:mb-0">Distrito de Nacimiento</label>
                                <div class="col-12 md:col-8">
                                    <AutoComplete
                                        id="distrito"
                                        v-model="selectedDistrito"
                                        :suggestions="filteredDistritos"
                                        @complete="searchDistritos($event)"
                                        field="distrito"
                                        :dropdown="true"
                                        :disabled="disabledDistrito"
                                    />
                                    <small class="p-error" v-if="errors.ubigeo">{{ errors.ubigeo }}</small>
                                </div>
                            </div>
                            <div class="grid">
                                <div class="col-12 md:col-12">
                                    <hr />
                                    <p class="text-blue-700 text-justify">
                                        Al guardar declaro bajo juramento que los datos registrados
                                        <b>nombres, apellidos, DNI, fecha de nacimiento, lugar de nacimiento, año de egreso y la fotografía son correctos y válidos </b> para el examen de CEPREUNA. A
                                        si también cuento con el <b>CERTIFICADO DE ESTUDIOS DE SECUNDARIA CUNCLUIDO</b>.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                <template #footer>
                    <Button label="Cancelar" icon="pi pi-times" class="p-button-secondary" :loading="saveLoading" @click="perfilDialog = false" />
                    <Button label="Guardar" icon="pi pi-check" class="p-button-success" :loading="saveLoading" @click="saveForm" />
                </template>
            </Dialog>
        </div>
        <!-- <pre>{{ users }}</pre> -->
    </app-layout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout";
import UploadImage from "@/components/UploadImageComponent.vue";
import { ref, onMounted, watch, toRefs, computed } from "vue";
import { useToast } from "primevue/usetoast";

import { Inertia } from "@inertiajs/inertia";
import { useForm } from "@inertiajs/inertia-vue3";
import axios from "axios";
// import AppTopBarSocial from "../Sigma/AppTopbarSocial.vue";
export default {
    components: {
        AppLayout,
        UploadImage,
    },
    props: {
        errors: Object,
        data: Object,
        response: Object,
        usuario: Object,
    },
    setup(props) {
        const title = ref("Mis Datos");
        const toast = useToast();
        const { response, data, usuario } = toRefs(props);

        const horarios = ref([]);
        const area = ref("");
        const grupo = ref("");
        const checkDeclaracion = ref(false);
        const perfilDialog = ref(false);
        const form = useForm({
            nombres: "",
            paterno: "",
            materno: "",
            nro_documento: "",
            fecha_nacimiento: "",
            anio_egreso: "",
            departamento: null,
            provincia: null,
            distrito: null,
            ubigeo: null,
            foto: null,
        });
        const saveLoading = ref(false);
        const confirmarDatos = () => {
            saveLoading.value = true;
            Inertia.post(route("perfil.confirmar-datos"), {
                onSuccess: () => {
                    if (response.value.status) {
                        toast.add({
                            severity: "success",
                            summary: "¡Exito...!",
                            detail: response.value.message,
                            life: 5000,
                        });
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
        const saveForm = () => {
            saveLoading.value = true;
            form.ubigeo = selectedDistrito.value != "" ? selectedDistrito.value.id : "";
            form.pais = selectedPais.value != "" ? selectedPais.value.id : "";
            form.clearErrors();
            Inertia.post(route("perfil.actualizar-estudiante"), form, {
                onSuccess: () => {
                    if (response.value.status) {
                        toast.add({
                            severity: "success",
                            summary: "¡Exito...!",
                            detail: response.value.message,
                            life: 5000,
                        });
                        form.reset();
                        perfilDialog.value = false;
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
        const getHorario = () => {
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
                });
        };
        const departamentos = ref([]);
        const provincias = ref([]);
        const distritos = ref([]);
        const disabledDepartamento = ref(true);
        const disabledProvincia = ref(true);
        const disabledDistrito = ref(true);
        const getDepartamentos = () => {
            axios.get("/recursos/get-departamentos").then(function (response) {
                departamentos.value = response.data;
            });
        };
        const getProvincias = () => {
            axios
                .get("/recursos/get-provincias", {
                    params: {
                        codigo: selectedDepartamento.value.codigo_departamento,
                    },
                })
                .then(function (response) {
                    provincias.value = response.data;
                });
        };
        const getDistritos = () => {
            axios
                .get("/recursos/get-distritos", {
                    params: {
                        codigo: selectedProvincia.value.codigo_provincia,
                    },
                })
                .then(
                    function (response) {
                        distritos.value = response.data;
                    }.bind(this)
                );
        };
        // autocomplete paises
        const selectedPais = ref("");
        const filteredPaises = ref();
        const searchPaises = (event) => {
            if (!event.query.trim().length) {
                filteredPaises.value = [...data.value.paises];
            } else {
                filteredPaises.value = data.value.paises.filter((pais) => {
                    return pais.denominacion.toLowerCase().startsWith(event.query.toLowerCase());
                });
            }
        };
        const changePais = (event) => {
            if (event.value.id == 1) {
                disabledDepartamento.value = false;
                disabledProvincia.value = true;
                disabledDistrito.value = true;
                getDepartamentos();
            } else {
                disabledDepartamento.value = true;
                disabledProvincia.value = true;
                disabledDistrito.value = true;
            }
        };
        // autocomplete departamentos
        const selectedDepartamento = ref();
        const filteredDepartamentos = ref();
        const searchDepartamentos = (event) => {
            if (!event.query.trim().length) {
                filteredDepartamentos.value = [...departamentos.value];
            } else {
                filteredDepartamentos.value = departamentos.value.filter((departamento) => {
                    return departamento.departamento.toLowerCase().startsWith(event.query.toLowerCase());
                });
            }
        };
        const changeDepartamento = (event) => {
            selectedProvincia.value = null;
            selectedDistrito.value = null;
            if (event) {
                disabledProvincia.value = false;
                disabledDistrito.value = true;
            } else {
                disabledProvincia.value = true;
                disabledDistrito.value = true;
            }
            getProvincias();
        };
        // autocomplete provincias
        const selectedProvincia = ref();
        const filteredProvincias = ref();
        const searchProvincias = (event) => {
            if (!event.query.trim().length) {
                filteredProvincias.value = [...provincias.value];
            } else {
                filteredProvincias.value = provincias.value.filter((provincia) => {
                    return provincia.provincia.toLowerCase().startsWith(event.query.toLowerCase());
                });
            }
        };
        const changeProvincia = (event) => {
            selectedDistrito.value = null;
            if (event) {
                disabledDistrito.value = false;
            } else {
                disabledDistrito.value = true;
            }
            getDistritos();
        };
        // autocomplete distritos
        const selectedDistrito = ref("");
        const filteredDistritos = ref();
        const searchDistritos = (event) => {
            if (!event.query.trim().length) {
                filteredDistritos.value = [...distritos.value];
            } else {
                filteredDistritos.value = distritos.value.filter((distrito) => {
                    return distrito.distrito.toLowerCase().startsWith(event.query.toLowerCase());
                });
            }
        };
        // crypt id
        const idMatricula = ref("");
        const encrypt = () => {
            axios.get(usuario.value.url + "/api/perfil/encrypt/" + data.value.estudiante.matricula.id).then((response) => {
                idMatricula.value = response.data;
            });
        };
        onMounted(() => {
            getHorario();
            encrypt();
        });
        return {
            idMatricula,
            title,
            horarios,
            area,
            grupo,
            getHorario,
            checkDeclaracion,
            perfilDialog,
            form,
            saveLoading,
            saveForm,
            disabledDepartamento,
            disabledProvincia,
            disabledDistrito,
            selectedPais,
            filteredPaises,
            searchPaises,
            changePais,
            departamentos,
            provincias,
            distritos,
            selectedDepartamento,
            filteredDepartamentos,
            searchDepartamentos,
            changeDepartamento,
            selectedProvincia,
            filteredProvincias,
            searchProvincias,
            changeProvincia,
            selectedDistrito,
            filteredDistritos,
            searchDistritos,
            confirmarDatos,
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
