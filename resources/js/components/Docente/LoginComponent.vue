<template>
    <Toast />
    <div class="hidden sm:flex flex-column align-items-center">
        <Card style="margin-top: 50px; width: 28rem" class="">
            <template #header>
                <div class="layout-logo text-center">
                    <!-- <img class="mt-6" alt="logo" src="/assets/layout/images/logo.png" style="height: 60px; width: auto" /> -->
                    <h6 class="font-bold my-2 pt-6" style="color: #999">Centro de Estudios Pre Universitario</h6>
                </div>
            </template>
            <template #title>
                <div class="text-center mt-2">Valide sus Datos</div>
            </template>
            <template #content>
                <div class="text-center mb-4"><p>Los accesos fueron enviados a su correo personal ingresado al momento de su inscripción como docente.</p></div>
                <div class="fluid">
                    <div class="field p-col-12 px-6 p-my-1">
                        <label for="mail">Correo</label>
                        <div class="p-inputgroup">
                            <span class="p-inputgroup-addon">
                                <i class="pi pi-user"></i>
                            </span>
                            <InputText id="inputgroup" type="text" v-model="form.email" />
                        </div>
                        <small v-if="errors.email" class="p-error">{{ errors.email[0] }}</small>
                    </div>

                    <div class="field col-12 px-6 my-1">
                        <label for="password">Contraseña</label>
                        <div class="p-inputgroup">
                            <span class="p-inputgroup-addon">
                                <i class="pi pi-lock"></i>
                            </span>
                            <Password id="password" v-model="form.password" toggleMask :feedback="false" @keydown.enter="submit()" />
                        </div>
                        <small v-if="errors.password" class="p-error">{{ errors.password[0] }}</small>
                    </div>
                    <div class="field col-12 m-0 text-center">
                        <!-- <small v-show="form.errors.auth !== null && form.submitted" id="" class="p-error">{{ form.errors.auth }}</small> -->
                        <!-- <inertia-link v-if="canResetPassword" :href="route('password.request')" class="underline text-sm text-gray-600 hover:text-gray-900"> Olvide mi contraseña </inertia-link> -->
                    </div>
                    <div class="p-fluid col-12 px-6 my-0">
                        <Button label="Ingresar" class="p-button-raised p-button-primary-theme" @click="login()" />
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
// import JetCheckbox from "@/Jetstream/Checkbox";
import { useForm } from "@inertiajs/inertia-vue3";
import { ref, onMounted, watch, toRefs } from "vue";
import { useToast } from "primevue/usetoast";

import { Inertia } from "@inertiajs/inertia";
import axios from "axios";

export default {
    // components: {
    //     JetCheckbox,
    // },
    props: {
        // errors: Object,
        // response: Object
    },
    setup(props, { emit }) {
        const toast = useToast();
        const errors = ref({
            email: "",
            password: "",
        });
        // const {response} = toRefs(props)
        const form = useForm({
            email: "",
            password: "",
        });
        const docente = ref({});
        const login = () => {
            axios
                .post(route("tramitePago.login"), form)
                .then((response) => {
                    // console.log(response);
                    if (response.data.status) {
                        toast.add({
                            severity: "success",
                            summary: "¡Exito...!",
                            detail: response.data.message,
                            life: 5000,
                        });

                        docente.value = response.data.docente;
                        emit("statusLogin", { status: true, docente: docente.value, datosExpediente: response.data.datosExpediente });
                    } else {
                        toast.add({
                            severity: "error",
                            summary: "¡Error!",
                            detail: response.data.message,
                            life: 5000,
                        });
                        emit("statusLogin", { status: false });
                    }
                })
                .catch((error) => {
                    errors.value = error.response.data.errors;
                });
        };
        return {
            form,
            login,
            errors,
            docente,
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
