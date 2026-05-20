<template>
    <Toast />
    <div class="flex lg:flex-row px-8 py-2 justify-content-center">
        <!-- Menu Lateral -->
        <!-- <div class="flex flex-column col-4 align-items-center">
            <Menu :model="items" class="w-auto" />
        </div> -->

        <!-- Principal -->
        <div class="card col-10 px-6">
            <div class="grid">
                <div class="flex flex-column col-12 text-center">
                    <h4>
                        <b> BIENVENIDO {{ docente.nombres }} {{ docente.paterno }} {{ docente.materno }}</b>
                    </h4>

                    <div class="flex flex-row justify-content-end">
                        <p><b>DNI:</b> {{ docente.dni }}</p>
                    </div>
                </div>
                <div class="text-justify mb-2 px-4">
                    <p>
                        A continuación, deberá adjuntar los documentos necesarios para proceder con su pago, asegurese de que se encuentren en formato PDF y haber seguido las indicaciones de
                        presentación, para evitar la observación y rechazo de los mismos y así agilizar este proceso.
                    </p>
                </div>
            </div>
            <div>
                <!-- Para nuevos -->
                <Fieldset v-if="datos.length == 0" legend="Documentos Requeridos para el Pago Docente">
                    <div v-for="tipoDocumento in tipoDocumentos" :key="tipoDocumento" class="grid mx-4 align-items-end">
                        <div v-if="tipoDocumento.id == 1" class="col-12 md:col-12 px-5">
                            <div class="field grid p-fluid">
                                <label for="dni" class="col-12 font-bold">{{ tipoDocumento.denominacion }}</label>
                                <div class="col-12">
                                    <FileInput :size="2" is-pdf placeholder-button-text="Buscar" v-model="form.dni" placeholder-input-text="Seleccione Archivo" />
                                    <small class="p-error" v-if="errors.dni">{{ errors.dni[0] }}</small>
                                </div>
                            </div>
                        </div>
                        <div v-if="tipoDocumento.id == 2" class="col-12 md:col-12 px-5">
                            <div class="field grid p-fluid">
                                <label for="suspencion" class="col-12 font-bold">{{ tipoDocumento.denominacion }}</label>
                                <div class="col-12">
                                    <FileInput :size="2" is-pdf placeholder-button-text="Buscar" v-model="form.suspencion" placeholder-input-text="Seleccione Archivo" />
                                    <small class="p-error" v-if="errors.suspencion">{{ errors.suspencion[0] }}</small>
                                </div>
                            </div>
                        </div>
                        <div v-if="tipoDocumento.id == 3" class="col-12 md:col-12 px-5">
                            <div class="field grid p-fluid">
                                <label for="reciboHonorarios" class="col-12 font-bold">{{ tipoDocumento.denominacion }}</label>
                                <div class="col-12">
                                    <FileInput :size="2" is-pdf placeholder-button-text="Buscar" v-model="form.reciboHonorarios" placeholder-input-text="Seleccione Archivo" />
                                    <small class="p-error" v-if="errors.reciboHonorarios">{{ errors.reciboHonorarios[0] }}</small>
                                </div>
                            </div>
                        </div>
                        <div v-if="tipoDocumento.id == 4" class="col-12 md:col-12 px-5">
                            <div class="field grid p-fluid">
                                <label for="osce" class="col-12 font-bold">{{ tipoDocumento.denominacion }}</label>
                                <div class="col-12">
                                    <FileInput :size="2" is-pdf placeholder-button-text="Buscar" v-model="form.osce" placeholder-input-text="Seleccione Archivo" />
                                    <small class="p-error" v-if="errors.osce">{{ errors.osce[0] }}</small>
                                </div>
                            </div>
                        </div>
                        <div v-if="tipoDocumento.id == 5" class="col-12 md:col-12 px-5">
                            <div class="field grid p-fluid">
                                <label for="formato1" class="col-12 font-bold">{{ tipoDocumento.denominacion }}</label>
                                <div class="col-12">
                                    <FileInput :size="2" is-pdf placeholder-button-text="Buscar" v-model="form.formato1" placeholder-input-text="Seleccione Archivo" />
                                    <small class="p-error" v-if="errors.formato1">{{ errors.formato1[0] }}</small>
                                </div>
                            </div>
                        </div>
                        <div v-if="tipoDocumento.id == 6" class="col-12 md:col-12 px-5">
                            <div class="field grid p-fluid">
                                <label for="declaracion" class="col-12 font-bold">{{ tipoDocumento.denominacion }}</label>
                                <div class="col-12">
                                    <FileInput :size="2" is-pdf placeholder-button-text="Buscar" v-model="form.declaracion" placeholder-input-text="Seleccione Archivo" />
                                    <small class="p-error" v-if="errors.declaracion">{{ errors.declaracion[0] }}</small>
                                </div>
                            </div>
                        </div>
                        <div v-if="tipoDocumento.id == 7" class="col-12 md:col-12 px-5">
                            <div class="field grid p-fluid">
                                <label for="informe" class="col-12 font-bold">{{ tipoDocumento.denominacion }}</label>
                                <div class="col-12">
                                    <FileInput :size="2" is-pdf placeholder-button-text="Buscar" v-model="form.informe" placeholder-input-text="Seleccione Archivo" />
                                    <small class="p-error" v-if="errors.informe">{{ errors.informe[0] }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </Fieldset>

                <!-- Para existentes -->
                <Fieldset v-else legend="Documentos Requeridos para el Pago Docente">
                    <div v-for="data in datos" :key="data" class="grid mx-4 align-items-end">
                        <div v-if="data.id == 1" class="col-6 md:col-6 px-5">
                            <div class="field grid p-fluid">
                                <label for="dni" class="col-12 font-bold">{{ data.denominacion }}</label>
                                <div v-if="data.estado == 3" class="col-12">
                                    <FileInput :size="2" is-pdf placeholder-button-text="Buscar" v-model="form.dni" placeholder-input-text="Seleccione Archivo" />
                                    <small class="p-error" v-if="errors.dni">{{ errors.dni[0] }}</small>
                                </div>
                                <div v-else-if="data.estado == 2" class="flex w-full justify-content-end">
                                    <Message severity="success" :closable="false">Su documento ha sido aprobado.</Message>
                                </div>
                                <div v-else class="flex w-full justify-content-end">
                                    <Message severity="info" :closable="false">Su documento esta siendo revisado.</Message>
                                </div>
                            </div>
                        </div>
                        <div v-if="data.id == 1 && data.estado == 3" class="col-6 md:col-6 text-right">
                            <Message severity="warn">{{ data.observacion }}</Message>
                        </div>
                        <div v-if="data.id == 2" class="col-6 md:col-6 px-5">
                            <div class="field grid p-fluid">
                                <label for="suspencion" class="col-12 font-bold">{{ data.denominacion }}</label>
                                <div v-if="data.estado == 3" class="col-12">
                                    <FileInput :size="2" is-pdf placeholder-button-text="Buscar" v-model="form.suspencion" placeholder-input-text="Seleccione Archivo" />
                                    <small class="p-error" v-if="errors.suspencion">{{ errors.suspencion[0] }}</small>
                                </div>
                                <div v-else-if="data.estado == 2" class="flex w-full justify-content-end">
                                    <Message severity="success" :closable="false">Su documento ha sido aprobado.</Message>
                                </div>
                                <div v-else class="flex w-full justify-content-end">
                                    <Message severity="info" :closable="false">Su documento esta siendo revisado.</Message>
                                </div>
                            </div>
                        </div>
                        <div v-if="data.id == 2 && data.estado == 3" class="col-6 md:col-6 text-right">
                            <Message severity="warn">{{ data.observacion }}</Message>
                        </div>
                        <div v-if="data.id == 3" class="col-6 md:col-6 px-5">
                            <div class="field grid p-fluid">
                                <label for="reciboHonorarios" class="col-12 font-bold">{{ data.denominacion }}</label>
                                <div v-if="data.estado == 3" class="col-12">
                                    <FileInput :size="2" is-pdf placeholder-button-text="Buscar" v-model="form.reciboHonorarios" placeholder-input-text="Seleccione Archivo" />
                                    <small class="p-error" v-if="errors.reciboHonorarios">{{ errors.reciboHonorarios[0] }}</small>
                                </div>
                                <div v-else-if="data.estado == 2" class="flex w-full justify-content-end">
                                    <Message severity="success" :closable="false">Su documento ha sido aprobado.</Message>
                                </div>
                                <div v-else class="flex w-full justify-content-end">
                                    <Message severity="info" :closable="false">Su documento esta siendo revisado.</Message>
                                </div>
                            </div>
                        </div>
                        <div v-if="data.id == 3 && data.estado == 3" class="col-6 md:col-6 text-right">
                            <Message severity="warn">{{ data.observacion }}</Message>
                        </div>
                        <div v-if="data.id == 4" class="col-6 md:col-6 px-5">
                            <div class="field grid p-fluid">
                                <label for="osce" class="col-12 font-bold">{{ data.denominacion }}</label>
                                <div v-if="data.estado == 3" class="col-12">
                                    <FileInput :size="2" is-pdf placeholder-button-text="Buscar" v-model="form.osce" placeholder-input-text="Seleccione Archivo" />
                                    <small class="p-error" v-if="errors.osce">{{ errors.osce[0] }}</small>
                                </div>
                                <div v-else-if="data.estado == 2" class="flex w-full justify-content-end">
                                    <Message severity="success" :closable="false">Su documento ha sido aprobado.</Message>
                                </div>
                                <div v-else class="flex w-full justify-content-end">
                                    <Message severity="info" :closable="false">Su documento esta siendo revisado.</Message>
                                </div>
                            </div>
                        </div>
                        <div v-if="data.id == 4 && data.estado == 3" class="col-6 md:col-6 text-right">
                            <Message severity="warn">{{ data.observacion }}</Message>
                        </div>
                        <div v-if="data.id == 5" class="col-6 md:col-6 px-5">
                            <div class="field grid p-fluid">
                                <label for="formato1" class="col-12 font-bold">{{ data.denominacion }}</label>
                                <div v-if="data.estado == 3" class="col-12">
                                    <FileInput :size="2" is-pdf placeholder-button-text="Buscar" v-model="form.formato1" placeholder-input-text="Seleccione Archivo" />
                                    <small class="p-error" v-if="errors.formato1">{{ errors.formato1[0] }}</small>
                                </div>
                                <div v-else-if="data.estado == 2" class="flex w-full justify-content-end">
                                    <Message severity="success" :closable="false">Su documento ha sido aprobado.</Message>
                                </div>
                                <div v-else class="flex w-full justify-content-end">
                                    <Message severity="info" :closable="false">Su documento esta siendo revisado.</Message>
                                </div>
                            </div>
                        </div>
                        <div v-if="data.id == 5 && data.estado == 3" class="col-6 md:col-6 text-right">
                            <Message severity="warn">{{ data.observacion }}</Message>
                        </div>
                        <div v-if="data.id == 6" class="col-6 md:col-6 px-5">
                            <div class="field grid p-fluid">
                                <label for="declaracion" class="col-12 font-bold">{{ data.denominacion }}</label>
                                <div v-if="data.estado == 3" class="col-12">
                                    <FileInput :size="2" is-pdf placeholder-button-text="Buscar" v-model="form.declaracion" placeholder-input-text="Seleccione Archivo" />
                                    <small class="p-error" v-if="errors.declaracion">{{ errors.declaracion[0] }}</small>
                                </div>
                                <div v-else-if="data.estado == 2" class="flex w-full justify-content-end">
                                    <Message severity="success" :closable="false">Su documento ha sido aprobado.</Message>
                                </div>
                                <div v-else class="flex w-full justify-content-end">
                                    <Message severity="info" :closable="false">Su documento esta siendo revisado.</Message>
                                </div>
                            </div>
                        </div>
                        <div v-if="data.id == 6 && data.estado == 3" class="col-6 md:col-6 text-right">
                            <Message severity="warn">{{ data.observacion }}</Message>
                        </div>
                        <div v-if="data.id == 7" class="col-6 md:col-6 px-5">
                            <div class="field grid p-fluid">
                                <label for="informe" class="col-12 font-bold">{{ data.denominacion }}</label>
                                <div v-if="data.estado == 3" class="col-12">
                                    <FileInput :size="2" is-pdf placeholder-button-text="Buscar" v-model="form.informe" placeholder-input-text="Seleccione Archivo" />
                                    <small class="p-error" v-if="errors.informe">{{ errors.informe[0] }}</small>
                                </div>
                                <div v-else-if="data.estado == 2" class="flex w-full justify-content-end">
                                    <Message severity="success" :closable="false">Su documento ha sido aprobado.</Message>
                                </div>
                                <div v-else class="flex w-full justify-content-end">
                                    <Message severity="info" :closable="false">Su documento esta siendo revisado.</Message>
                                </div>
                            </div>
                        </div>
                        <div v-if="data.id == 7 && data.estado == 3" class="col-6 md:col-6 text-right">
                            <Message severity="warn">{{ data.observacion }}</Message>
                        </div>
                    </div>
                </Fieldset>
                <div class="flex justify-content-center">
                    <Button label="Enviar Documentos" class="p-button-raised p-button-primary-theme mt-3" @click="subirArchivos()" />
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Menu from "primevue/menu";
import InputNumber from "primevue/inputnumber";
import Dialog from "primevue/dialog";
import FileInput from "@/components/FileInput.vue";
import { useToast } from "primevue/usetoast";
import { useForm } from "@inertiajs/inertia-vue3";
import { ref, onMounted, watch, toRefs } from "vue";
import { Inertia } from "@inertiajs/inertia";
import Panel from "primevue/panel";

export default {
    components: {
        Menu,
        FileInput,
        InputNumber,
        Dialog,
        Panel,
    },
    props: { docente: Object, datosTramite: Object, tipoDocumentos: Array },

    setup(props, { emit }) {
        const toast = useToast();
        const { docente, datosTramite } = toRefs(props);
        const datos = ref(datosTramite.value);
        const errors = ref({});
        const form = useForm({
            dni: null,
            suspencion: null,
            reciboHonorarios: null,
            osce: null,
            formato1: null,
            declaracion: null,
            informe: null,
            docente: {},
        });
        const subirArchivos = () => {
            // form.docente = docente.value;

            let formData = new FormData();
            let formDocente = JSON.stringify(docente.value);
            let formDni = JSON.stringify(form.dni);
            let formSuspencion = JSON.stringify(form.suspencion);
            let formReciboHonorarios = JSON.stringify(form.reciboHonorarios);
            let formOSCE = JSON.stringify(form.osce);
            let formFormato1 = JSON.stringify(form.formato1);
            let formDeclaracion = JSON.stringify(form.declaracion);
            let formInforme = JSON.stringify(form.informe);

            formData.append("docente", formDocente);
            formData.append("dni", form.dni.file);
            formData.append("suspencion", form.suspencion.file);
            formData.append("reciboHonorarios", form.reciboHonorarios.file);
            formData.append("osce", form.osce.file);
            formData.append("formato1", form.formato1.file);
            formData.append("declaracion", form.declaracion.file);
            formData.append("informe", form.informe.file);

            axios
                .post(route("tramitePago.subir"), formData)
                .then((response) => {
                    if (response.data.status) {
                        toast.add({
                            severity: "success",
                            summary: "¡Exito...!",
                            detail: response.data.message,
                            life: 5000,
                        });
                        // docente.value = response.data.docente;
                        emit("statusForm", { status: true, docente: docente.value });
                        datos.value = response.data.datosTramite;
                        form.reset();
                    } else {
                        toast.add({
                            severity: "error",
                            summary: "¡Error!",
                            detail: response.data.message,
                            life: 5000,
                        });
                        emit("statusForm", { status: false });
                    }
                })
                .catch((error) => {
                    console.log(error.response);
                    if (error.response.status === 422) {
                        errors.value = error.response.data.errors;
                    }
                });
        };

        onMounted(() => {
            // console.log(datosTramite.value);
            datosTramite.value.forEach((element) => {
                // console.log(element);
                switch (element.tipo_documentos_id) {
                    case 1:
                        if (element.estado != "3") {
                            form.dni = "existe";
                        }
                        break;
                    case 2:
                        if (element.estado != "3") {
                            form.suspencion = "existe";
                        }
                        break;
                    case 3:
                        if (element.estado != "3") {
                            form.reciboHonorarios = "existe";
                        }
                        break;
                    case 4:
                        if (element.estado != "3") {
                            form.osce = "existe";
                        }
                        break;
                    case 5:
                        if (element.estado != "3") {
                            form.formato1 = "existe";
                        }
                        break;
                    case 6:
                        if (element.estado != "3") {
                            form.declaracion = "existe";
                        }
                        break;
                    case 7:
                        if (element.estado != "3") {
                            form.informe = true;
                        }
                        break;
                }
            });
        });

        return {
            form,
            errors,
            subirArchivos,
            datos,
        };
    },
};
</script>

<style scoped>
@media screen and (max-width: 576px) {
    .display-mobile {
        display: flex !important;
    }
}
</style>
