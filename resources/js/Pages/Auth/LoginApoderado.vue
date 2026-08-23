<template>
    <Toast />
    <div class="guardian-login-page sm:flex flex-column align-items-center panel-login">
        <Card class="guardian-login-card" style="margin-top: 10px; width: 40rem">
            <template #header>
                <div class="layout-logo text-center">
                    <img class="mt-2" alt="logo" src="/assets/layout/images/logo.png" style="height: 30px; width: auto" />
                    <h6 class="font-bold my-2" style="color: #999">Centro de Estudios Pre Universitario</h6>
                </div>
            </template>
            <template #title>
                <div class="text-center mt-2">Validar Datos</div>
            </template>
            <template #content>
                <div class="grid">
                    <div class="field col-12 mb-0">
                        <h5 class="mb-0 text-orange-500"><b>Datos del estudiante</b></h5>
                    </div>
                </div>
                <hr class="mt-0">
                <div class="grid">
                    <div class="field col-6">
                        <label for="paterno">Apellido Paterno</label>
                        <div class="p-inputgroup">
                            <span class="p-inputgroup-addon">
                                <i class="pi pi-user"></i>
                            </span>
                            <InputText id="paterno" type="text" v-model="form.paterno" />
                        </div>
                        <small v-show="errors.paterno !== null" class="p-error">{{ errors.paterno }}</small>
                    </div>
                    <div class="field col-6">
                        <label for="materno">Apellido Materno</label>
                        <div class="p-inputgroup">
                            <span class="p-inputgroup-addon">
                                <i class="pi pi-user"></i>
                            </span>
                            <InputText id="materno" type="text" v-model="form.materno" />
                        </div>
                        <small v-show="errors.materno !== null" class="p-error">{{ errors.materno }}</small>
                    </div>
                    <div class="field col-6">
                        <label for="documento">Documento</label>
                        <div class="p-inputgroup">
                            <span class="p-inputgroup-addon">
                                <i class="pi pi-id-card"></i>
                            </span>
                            <InputText id="documento" type="text" v-model="form.documento" />
                        </div>
                        <small v-show="errors.documento !== null" class="p-error">{{ errors.documento }}</small>
                    </div>
                    <div class="field col-6">
                        <label for="fecha_nac">Fecha de Nacimiento</label>
                        <div class="p-inputgroup">
                            <span class="p-inputgroup-addon">
                                <i class="pi pi-calendar"></i>
                            </span>
                            <Calendar id="fecha_nac" v-model="form.fecha_nac"  dateFormat="dd/mm/yy" />
                        </div>
                        <small v-show="errors.fecha_nac !== null" class="p-error">{{ errors.fecha_nac }}</small>
                    </div>
                </div>
                <div class="grid">
                    <div class="field col-12 mb-0">
                        <h5 class="mb-0 text-orange-500"><b>Datos del apoderado</b></h5>
                    </div>
                </div>
                <hr class="mt-0">
                <div class="grid">
                    <div class="field col-6">
                        <label for="documento_apoderado">Documento</label>
                        <div class="p-inputgroup">
                            <span class="p-inputgroup-addon">
                                <i class="pi pi-id-card"></i>
                            </span>
                            <InputText id="documento_apoderado" type="text" v-model="form.documento_apoderado" @input="searchApoderado" />
                        </div>
                        <small v-show="errors.documento_apoderado !== null" class="p-error">{{ errors.documento_apoderado }}</small>
                    </div>
                    <div class="field col-6">
                        <label for="paterno_apoderado">Apellido Paterno</label>
                        <div class="p-inputgroup">
                            <span class="p-inputgroup-addon">
                                <i class="pi pi-user"></i>
                            </span>
                            <InputText id="paterno_apoderado" type="text" v-model="form.paterno_apoderado" />
                        </div>
                        <small v-show="errors.paterno_apoderado !== null" class="p-error">{{ errors.paterno_apoderado }}</small>
                    </div>
                    <div class="field col-6">
                        <label for="materno_apoderado">Apellido Materno</label>
                        <div class="p-inputgroup">
                            <span class="p-inputgroup-addon">
                                <i class="pi pi-user"></i>
                            </span>
                            <InputText id="materno_apoderado" type="text" v-model="form.materno_apoderado" />
                        </div>
                        <small v-show="errors.materno_apoderado !== null" class="p-error">{{ errors.materno_apoderado }}</small>
                    </div>
                    <div class="field col-6">
                        <label for="nombres_apoderado">Nombres</label>
                        <div class="p-inputgroup">
                            <span class="p-inputgroup-addon">
                                <i class="pi pi-user"></i>
                            </span>
                            <InputText id="nombres_apoderado" type="text" v-model="form.nombres_apoderado" />
                        </div>
                        <small v-show="errors.nombres_apoderado !== null" class="p-error">{{ errors.nombres_apoderado }}</small>
                    </div>
                    <div class="field col-6">
                        <label for="celular_apoderado">Celular</label>
                        <div class="p-inputgroup">
                            <span class="p-inputgroup-addon">
                                <i class="pi pi-phone"></i>
                            </span>
                            <InputText id="celular_apoderado" type="text" v-model="form.celular_apoderado" />
                        </div>
                        <small v-show="errors.celular_apoderado !== null" class="p-error">{{ errors.celular_apoderado }}</small>
                    </div>
                    <div class="field col-6">
                        <label for="parentesco_apoderado">Parentesco</label>
                        <div class="p-inputgroup">
                            <span class="p-inputgroup-addon">
                                <i class="pi pi-users"></i>
                            </span>
                            <Dropdown
                                id="parentesco_apoderado"
                                v-model="form.parentesco_apoderado"
                                :options="parentescos"
                                optionLabel="denominacion"
                                optionValue="id"
                                placeholder="Seleccionar parentesco"
                            />
                        </div>
                        <small v-show="errors.parentesco_apoderado !== null" class="p-error">{{ errors.parentesco_apoderado }}</small>
                    </div>

                    <div class="field col-12 m-0 text-center">
                        <!-- <small v-show="form.errors.auth !== null && form.submitted" id="" class="p-error">{{ form.errors.auth }}</small> -->
                        <!-- <inertia-link v-if="canResetPassword" :href="route('password.request')" class="underline text-sm text-gray-600 hover:text-gray-900"> Olvide mi contraseña </inertia-link> -->
                    </div>
                    <div class="p-fluid col-12 px-6 my-0">
                        <Button label="Ingresar" class="p-button-raised p-button-primary-theme" @click="submit()" />
                    </div>
                </div>
            </template>
            <template #footer>
                <div class="text-center">
                    <small><em>APP CEPREUNA v. 1.0 © 2023 todos los derechos reservados </em></small>
                </div>
            </template>
        </Card>
    </div>
</template>
<script>
// import AppLayout from "@/Layouts/AppLayout";
import { ref,toRefs } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";
import { Inertia } from "@inertiajs/inertia";
import axios from "axios";
import { useToast } from "primevue/usetoast";


export default {
    components: {
        // AppTopBarSocial,
        // TabsMenu,
        // TabSubmenu,
        // AppLayout,
        // useToast
    },
    props: {
        errors: Object,
        response: Object,
        data: Object,
    },
    setup(props) {
        const title = ref("CEPREUNA");
        const msg = ref("Bienvenido al Panel Principal");
        const { data,response } = toRefs(props);
        const toast = useToast();
        const parentescos = data.value.parentescos;
        const form = useForm({
            paterno: "",
            materno: "",
            documento: "",
            fecha_nac: "",
        });
        const searchApoderado = () =>{
            axios
            .get(route("get-apoderados.login"), {
                params: {
                    paterno: form.paterno ? form.paterno : "",
                    materno: form.materno ? form.materno : "",
                    documento: form.documento ? form.documento : "",
                    fecha_nac: form.fecha_nac ? form.fecha_nac : "",
                    documento_apoderado: form.documento_apoderado ? form.documento_apoderado : "",
                },
            })
            .then((response) => {
                // loading.value = false;
                // this.grupo = response.data[0].id;
                if(response.data.status){
                    form.paterno_apoderado = response.data.apoderado.paterno;
                    form.materno_apoderado = response.data.apoderado.materno;
                    form.nombres_apoderado = response.data.apoderado.nombres;
                    form.celular_apoderado = response.data.apoderado.celular;
                    form.parentesco_apoderado = response.data.apoderado.parentescos_id;
                }else{
                    form.paterno_apoderado = "";
                    form.materno_apoderado = "";
                    form.nombres_apoderado = "";
                    form.celular_apoderado = "";
                    form.parentesco_apoderado = "";
                }
                // sesiones.value = response.data.sesiones;
            });
        }
        const submit = () =>{
            Inertia.post(route("login-apoderados.login"), form, {
                    onSuccess: () => {
                        if (response.value.status) {
                            // loadLazyData();
                            // toast.add({
                            //     severity: "success",
                            //     summary: "¡Exito...!",
                            //     detail: response.value.message,
                            //     life: 5000,
                            // });

                            // sesionDialog.value = false;
                            // form.reset();
                            // selectedCursos.value=null;

                            // saveLoading.value = false;
                        } else {
                            toast.add({
                                severity: "error",
                                summary: "¡Error!",
                                detail: response.value.message,
                                life: 5000,
                            });
                            // saveLoading.value = false;
                        }
                    },
                    onError: () => {
                        // saveLoading.value = false;
                    },
                });
        }
        return {
            msg,
            title,
            parentescos,
            form,
            submit,
            searchApoderado,
        };
    },
};
</script>
<style scoped>
@media screen and (max-width: 576px) {
    .display-mobile {
        display: flex !important;
    }

    .guardian-login-page {
        display: flex;
        width: 100%;
        min-height: 100vh;
        min-height: 100dvh;
        padding: max(0.75rem, env(safe-area-inset-top)) 0.75rem max(0.75rem, env(safe-area-inset-bottom));
    }

    .guardian-login-card {
        width: 100% !important;
        max-width: 40rem;
        margin: 0 !important;
    }

    .guardian-login-card :deep(.col-6) {
        width: 100%;
    }
}
</style>
