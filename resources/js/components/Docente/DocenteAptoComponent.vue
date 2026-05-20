<template>
    <Toast />
    <div class="flex flex-column align-items-center">
        <Card style="margin-top: 40px; width: 28rem">
            <template #header>
                <div class="layout-logo text-center">
                    <h6 class="font-bold my-2 pt-6" style="color: #999">Centro de Estudios Pre Universitario</h6>
                </div>
            </template>
            <template #title>
                <div class="text-center mt-2">Consulte si es uno de los Docentes Aptos</div>
            </template>
            <template #content>
                <div class="text-center mb-4"><p>Ingrese el número de su Documento Nacional de Identidad.</p></div>
                <div class="fluid">
                    <div class="field p-col-12 px-6 p-my-1">
                        <label for="dni">Número de DNI</label>
                        <div class="p-inputgroup">
                            <span class="p-inputgroup-addon">
                                <i class="pi pi-id-card"></i>
                            </span>
                            <InputMask v-model="form.dni" mask="99999999" />
                        </div>
                        <small v-if="errors.dni" class="p-error">{{ errors.dni[0] }}</small>
                    </div>
                    <div class="p-fluid col-12 px-6 my-0">
                        <Button label="Consultar" class="p-button-raised p-button-primary-theme" @click="buscar()" />
                    </div>
                </div>
            </template>
            <template #footer>
                <div class="text-center">
                    <small><em>APP CEPREUNA v. 1.0 © 2023 todos los derechos reservados </em></small>
                </div>
            </template>
        </Card>
        <Dialog v-model:visible="display" header="Consulta" :breakpoints="{ '960px': '75vw', '640px': '90vw' }" :style="{ width: '50vw' }">
            <div class="flex col-12">
                <div class="flex flex-column text-center col-6">
                    <p class="mb-0 pb-0">Nombres:</p>
                    <b>{{ docente.nombres }}</b>
                    <p class="mt-1 mb-0 pb-0">Apellidos:</p>
                    <b>{{ docente.paterno + " " + docente.materno }}</b>
                    <p class="mt-1 mb-0 pb-0">DNI:</p>
                    <b>{{ docente.nro_documento }}</b>
                </div>
                <Divider layout="vertical" />
                <div class="flex col-6 flex-column text-center">
                    <div>
                        <Badge value="Docente Apto" severity="success" size="large" class="m-2"></Badge>
                        <!-- <Message severity="success" :closable="false" class="col-10 pb-0">Docente Apto</Message> -->
                        <!-- <p class="mb-0 pb-0">Sus accesos fueron enviados al correo:</p>
                        <b>{{ docente.email }}</b> -->
                    </div>
                    <!-- <div v-if="docente.acceso == '0'">
                        <Badge value="Docente No Apto" severity="danger" size="large" class="mt-5"></Badge>
                    </div> -->
                </div>
            </div>
        </Dialog>
    </div>
</template>

<script>
import InputMask from "primevue/inputmask";
import Dialog from "primevue/dialog";
import Divider from "primevue/divider";
import Badge from "primevue/badge";
import { useForm } from "@inertiajs/inertia-vue3";
import { ref, watch, toRefs } from "vue";
import { useToast } from "primevue/usetoast";
import { Inertia } from "@inertiajs/inertia";
import axios from "axios";

export default {
    components: {
        InputMask,
        Dialog,
        Divider,
        Badge,
    },
    setup(props, { emit }) {
        const toast = useToast();
        const errors = ref({
            dni: "",
        });
        const form = useForm({
            dni: "",
        });
        const display = ref(false);
        const docente = ref({});
        const existe = ref();
        const buscar = () => {
            axios
                .post(route("docenteApto.buscar"), form)
                .then((response) => {
                    if (response.data.status) {
                        console.log(response);
                        docente.value = response.data.docente;
                        display.value = true;
                        console.log(docente.value);
                    } else {
                        toast.add({
                            severity: "warn",
                            summary: "El docente no esta apto.",
                            detail: response.data.message,
                            life: 5000,
                        });
                        display.value = false;
                    }
                })
                .catch((error) => {
                    errors.value = error.response.data.errors;
                });
        };

        return {
            errors,
            form,
            buscar,
            docente,
            existe,
            display,
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
