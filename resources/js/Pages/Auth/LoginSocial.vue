<template>
    <Toast ref="toast" :html="true"/>
    <div class="hidden sm:flex flex-column align-items-center panel-login">
        <Card style="margin-top: 50px; width: 28rem" class="">
            <template #header>
                <div class="layout-logo text-center">
                    <img class="mt-6" alt="logo" src="/assets/layout/images/logo.png"
                        style="height: 60px; width: auto" />
                    <h6 class="font-bold my-2" style="color: #999">Centro de Estudios Pre Universitario</h6>
                </div>
            </template>
            <template #title>
                <div class="fluid">
                    <div class="grid">
                        <div class="col-12 text-center">
                            <div class="text-center text-2xl my-0 text-orange font-bold">Iniciar Sesión</div>
                            <small class="text-gray-600" style="font-size: 14px">Ingresa solo con tu cuenta
                                institucional.</small>
                        </div>
                    </div>
                </div>
            </template>
            <template #content>
                <div class="fluid mb-3 text-center">
                    <img class="mt-3" alt="logo" src="/images/logo-login.png" style="height: 150px; width: auto" />
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
                            <Password id="password" v-model="form.password" toggleMask :feedback="false"
                                @keydown.enter="submit()" />
                        </div>
                        <small v-show="errors.password !== null" class="p-error">{{ errors.password }}</small>
                    </div>

                    <div class="p-fluid col-12 px-2 my-0">
                        <Button label="Ingresar" class="p-button-raised p-button-primary-theme" @click="submit()" />
                    </div>
                </div>
                <div class="field col-12 m-0 text-center">
                    <!-- <small v-show="form.errors.auth !== null && form.submitted" id="" class="p-error">{{ form.errors.auth }}</small> -->
                    <!-- <inertia-link v-if="canResetPassword" :href="route('password.request')" class="underline text-sm text-gray-600 hover:text-gray-900"> Olvide mi contraseña </inertia-link> -->
                </div>
                <div class="grid">
                    <div class="col-12 text-center">
                        <a :href="route('auth-redirect')">
                            <Button type="button" class="p-button-outlined p-button-info">
                                <img alt="logo" src="/images/google-logo.svg" style="width: 1.5rem" />
                                <span class="ml-2 font-bold">Ingresar con Google</span>
                            </Button>
                        </a>
                    </div>
                </div>
            </template>
            <template #footer>
                <div class="text-center">
                    <small><em>APP CEPREUNA v. 1.0 © 2024 todos los derechos reservados </em></small>
                </div>
            </template>
        </Card>
    </div>
    <div class="mobile-login-page hidden display-mobile flex-column align-items-center">
        <div class="mobile-login-hero panel-login-mobile">
            <div class="mobile-login-hero-content layout-logo text-center">
                <div style="margin: 0">
                    <img class="mobile-login-logo mt-5" alt="CEPREUNA" src="/assets/layout/images/logo-mobile.png" />
                    <h6 class="my-2" style="color: #fff">Centro de Estudios Pre Universitario</h6>

                    <inertia-link style="color: #ff9c56; position: absolute; top: 15px; right: 15px"
                        :href="route('login')">
                        <i class="pi pi-lock" style="font-size: 1.2rem"></i>
                    </inertia-link>
                </div>
            </div>
        </div>
        <div class="mobile-login-card card box shadow-5">
            <div class="fluid">
                <div class="grid">
                    <div class="col-12 text-center">
                        <div class="text-center text-2xl my-0 text-orange font-bold">Iniciar Sesión</div>
                        <small class="text-gray-600">Ingresa solo con tu cuenta institucional.</small>
                    </div>
                </div>
            </div>
            <div class="fluid mb-1 text-center">
                <img class="mobile-login-illustration mt-1" alt="Acceso institucional" src="/images/logo-login.png" />
            </div>
            <div class="fluid">
                <div class="field p-col-12 px-2 p-my-1">
                    <label for="usuario">Usuario</label>
                    <div class="p-inputgroup">
                        <span class="p-inputgroup-addon">
                            <i class="pi pi-user"></i>
                        </span>
                    <InputText id="mobile-email" type="text" v-model="form.email" autocomplete="username" autocapitalize="none" />
                    </div>
                    <small v-show="errors.email !== null" class="p-error">{{ errors.email }}</small>
                </div>

                <div class="field col-12 px-2 my-1">
                    <label for="password">Contraseña</label>
                    <div class="p-inputgroup">
                        <span class="p-inputgroup-addon">
                            <i class="pi pi-lock"></i>
                        </span>
                        <Password id="mobile-password" v-model="form.password" toggleMask :feedback="false" autocomplete="current-password"
                            @keydown.enter="submit()" />
                    </div>
                    <small v-show="errors.password !== null" class="p-error">{{ errors.password }}</small>
                </div>

                <div class="p-fluid col-12 my-0">
                    <Button label="Ingresar" class="p-button-raised p-button-primary-theme" @click="submit()" />
                </div>
            </div>
            <div class="field col-12 m-0 text-center">
                <!-- <small v-show="form.errors.auth !== null && form.submitted" id="" class="p-error">{{ form.errors.auth }}</small> -->
                <!-- <inertia-link v-if="canResetPassword" :href="route('password.request')" class="underline text-sm text-gray-600 hover:text-gray-900"> Olvide mi contraseña </inertia-link> -->
            </div>
            <div class="grid">
                <div class="col-12 text-center">
                    <a :href="route('auth-redirect')">
                        <Button type="button" class="p-button-outlined p-button-info">
                            <img alt="logo" src="/images/google-logo.svg" style="width: 1.5rem" />
                            <span class="ml-2 font-bold">Ingresar con Google</span>
                        </Button>
                    </a>
                </div>
            </div>
            <!-- <div class="fluid">
                <div class="field p-col-12 px-6 p-my-1 text-center">
                    <a class="p-button p-component p-button-danger" :href="route('auth-redirect')"> <i class="pi pi-google mr-1"></i> Ingresar con Google </a>
                </div>
            </div> -->
            <div class="fluid">
                <div class="text-center mt-4">
                    <small class="mobile-login-footer text-xs"><em>APP CEPREUNA v. 1.0 © 2024 todos los derechos reservados </em></small>
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
                .post(this.route("login-singsuit.login"), {
                    onFinish: () => this.form.reset("password"),
                    onSuccess: (response) => {
                        const authResponse = response.props?.response;
                        if (authResponse?.status === false) {
                            const detail = authResponse.message ? `${authResponse.message}, apersonarse al CEPREUNA.` : "Accesos incorrectos.";
                            this.$refs.toast.add({ severity: "error", summary: "Error", detail, life: 3000 });
                            this.form.reset("password");
                        }
                    },
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

    .mobile-login-page {
        width: 100%;
        min-height: 100vh;
        min-height: 100dvh;
        padding-bottom: max(1rem, env(safe-area-inset-bottom));
        overflow-x: hidden;
        background: var(--surface-b);
    }

    .mobile-login-hero {
        flex: 0 0 auto;
    }

    .mobile-login-hero-content {
        padding-top: env(safe-area-inset-top);
    }

    .mobile-login-logo {
        width: auto;
        height: clamp(7.5rem, 34vw, 9.5rem);
        max-width: 70vw;
    }

    .mobile-login-card {
        position: relative;
        width: calc(100% - 2rem);
        max-width: 28rem;
        margin: -2.5rem auto 0 !important;
        padding: 1rem;
        z-index: 1;
    }

    .mobile-login-illustration {
        width: auto;
        height: 5rem;
        max-width: 50%;
    }

    .mobile-login-footer {
        display: block;
        overflow-wrap: anywhere;
        line-height: 1.35;
    }

    .mobile-login-card :deep(.p-password),
    .mobile-login-card :deep(.p-password-input) {
        width: 100%;
    }
}
</style>
