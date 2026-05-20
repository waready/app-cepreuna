<template>
    <Toast />
    <div class="hidden sm:flex flex-column align-items-center panel-login">
        <Card style="margin-top: 50px; width: 28rem" class="">
            <template #header>
                <div class="layout-logo text-center">
                    <img class="mt-6" alt="logo" src="/assets/layout/images/logo.png" style="height: 60px; width: auto" />
                    <h6 class="font-bold my-2" style="color: #999">Centro de Estudios Pre Universitario</h6>
                </div>
            </template>
            <template #title>
                <div class="text-center mt-2">Iniciar Sesión</div>
                <div class="text-center">Administrativos</div>
            </template>
            <template #content>
                <div class="fluid">
                    <div class="field p-col-12 px-6 p-my-1">
                        <label for="usuario">Usuario</label>
                        <div class="p-inputgroup">
                            <span class="p-inputgroup-addon">
                                <i class="pi pi-user"></i>
                            </span>
                            <InputText id="inputgroup" type="text" v-model="form.email" />
                        </div>
                        <small v-show="errors.email !== null" class="p-error">{{ errors.email }}</small>
                    </div>

                    <div class="field col-12 px-6 my-1">
                        <label for="password">Contraseña</label>
                        <div class="p-inputgroup">
                            <span class="p-inputgroup-addon">
                                <i class="pi pi-lock"></i>
                            </span>
                            <Password id="password" v-model="form.password" toggleMask :feedback="false" @keydown.enter="submit()" />
                        </div>
                        <small v-show="errors.password !== null" class="p-error">{{ errors.password }}</small>
                    </div>
                    <div class="field col-12 m-0 text-center">
                        <!-- <small v-show="form.errors.auth !== null && form.submitted" id="" class="p-error">{{ form.errors.auth }}</small> -->
                        <!-- <inertia-link v-if="canResetPassword" :href="route('password.request')" class="underline text-sm text-gray-600 hover:text-gray-900"> Olvide mi contraseña </inertia-link> -->
                    </div>
                    <div class="p-fluid col-12 px-6 my-0">
                        <Button label="Ingresar" class="p-button-raised p-button-primary" @click="submit()" />
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
    <div class="hidden display-mobile flex-column align-items-center">
        <div class="panel-login-mobile">
            <div class="layout-logo text-center panel-login-mobile">
                <div style="margin: 0">
                    <img class="mt-6" alt="logo" src="/assets/layout/images/logo-mobile.png" style="height: 150px; width: auto" />
                    <h6 class="my-2" style="color: #fff">Centro de Estudios Pre Universitario</h6>
                </div>
            </div>
        </div>
        <div class="card mx-2 box shadow-5" style="margin: -40px">
            <div class="fluid">
                <div class="text-center text-2xl my-3 text-blue">Iniciar Sesión</div>
                <div class="text-center text-blue">Administrativos</div>
            </div>
            <div class="fluid">
                <div class="field p-col-12 px-2 p-my-1">
                    <label for="usuario">Usuario</label>
                    <div class="p-inputgroup">
                        <span class="p-inputgroup-addon">
                            <i class="pi pi-user"></i>
                        </span>
                        <InputText id="inputgroup" type="text" v-model="form.email" />
                    </div>
                    <small v-show="errors.email !== null" class="p-error">{{ errors.email }}</small>
                </div>

                <div class="field col-12 px-2 my-1">
                    <label for="password">Contraseña</label>
                    <div class="p-inputgroup">
                        <span class="p-inputgroup-addon">
                            <i class="pi pi-lock"></i>
                        </span>
                        <Password id="password" v-model="form.password" toggleMask :feedback="false" @keydown.enter="submit()" />
                    </div>
                    <small v-show="errors.password !== null" class="p-error">{{ errors.password }}</small>
                </div>
                <div class="field col-12 m-0 text-center">
                    <!-- <small v-show="form.errors.auth !== null && form.submitted" id="" class="p-error">{{ form.errors.auth }}</small> -->
                    <!-- <inertia-link v-if="canResetPassword" :href="route('password.request')" class="underline text-sm text-gray-600 hover:text-gray-900"> Olvide mi contraseña </inertia-link> -->
                </div>
                <div class="p-fluid col-12 px-2 my-0">
                    <Button label="Ingresar" class="p-button-raised p-button-primary" @click="submit()" />
                </div>
            </div>
            <div class="fluid">
                <div class="text-center mt-4">
                    <small class="text-xs"><em>APP CEPREUNA v. 1.0 © 2024 todos los derechos reservados </em></small>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import JetCheckbox from "@/Jetstream/Checkbox";

export default {
    components: {
        JetCheckbox,
    },
    props: {
        canResetPassword: Boolean,
        status: String,
        errors: Object,
    },

    data() {
        return {
            form: this.$inertia.form({
                email: "",
                password: "",
                remember: false,
            }),
        };
    },

    methods: {
        submit() {
            this.form.clearErrors();

            this.form
                .transform((data) => ({
                    ...data,
                    remember: this.form.remember ? "on" : "",
                }))
                .post(this.route("login"), {
                    onFinish: () => this.form.reset("password"),
                });
        },
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
