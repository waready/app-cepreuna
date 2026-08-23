<template>
    <Toast />
    <app-layout>
        <div class="p-grid">
            <div class="p-col-12 p-md-3">
                <div class="p-card p-component">
                    <div class="p-card-header p-px-5 p-pt-5">
                        <img :src="$page.props.user.profile_photo_url" />
                    </div>
                    <div class="p-card-body">
                        <div class="p-card-title p-text-center">{{ $page.props.user.name }}</div>
                        <div class="p-card-subtitle p-text-center">Rol: {{ perfil.model_has_role.role.name }}</div>
                    </div>
                </div>
                <div class="p-card p-component p-mt-4">
                    <div class="p-card-body">
                        <div class="p-card-subtitle p-text-center">
                            <Divider align="center">
                                <span class="p-text-bold">Datos Personales</span>
                            </Divider>
                        </div>
                        <div class="p-card-content">
                            <table>
                                <tbody>
                                <tr>
                                    <th>Nombres</th>
                                    <td>:</td>
                                    <td>{{ perfil.name }}</td>
                                </tr>
                                <tr>
                                    <th>Apellidos</th>
                                    <td>:</td>
                                    <td>{{ perfil.paterno + " " + perfil.materno }}</td>
                                </tr>
                                <tr>
                                    <th>DNI</th>
                                    <td>:</td>
                                    <td>{{ perfil.dni }}</td>
                                </tr>
                                <tr>
                                    <th>Correo</th>
                                    <td>:</td>
                                    <td>{{ perfil.email }}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-col-12 p-md-9">
                <div class="p-card p-component">
                    <div class="p-card-body">
                        <div class="p-card-content">
                            <div class="p-grid p-mx-4">
                                <div class="p-col-12">
                                    <h5 class="p-mb-0 p-text-bold">Editar información personal</h5>
                                </div>
                            </div>
                            <Divider />
                            <form @submit.prevent="" action="" autocomplete="off">
                                <div class="p-grid p-mx-6">
                                    <div class="p-col-12 p-md-12">
                                        <div class="p-fluid">
                                            <div class="p-field p-grid">
                                                <label for="nombre" class="p-col-12 p-mb-2 p-md-4 p-mb-md-0">Nombres</label>
                                                <div class="p-col-12 p-md-8">
                                                    <InputText id="nombre" type="text" v-model.trim="usuarioForm.name" autofocus @keyup.enter="saveUsuario" />
                                                    <small class="p-error" v-if="errors.name">{{ errors.name }}</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="p-fluid">
                                            <div class="p-field p-grid">
                                                <label for="paterno" class="p-col-12 p-mb-2 p-md-4 p-mb-md-0">Apellido Paterno</label>
                                                <div class="p-col-12 p-md-8">
                                                    <InputText id="paterno" type="text" v-model.trim="usuarioForm.paterno" @keyup.enter="saveUsuario" />
                                                    <small class="p-error" v-if="errors.paterno">{{ errors.paterno }}</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="p-fluid">
                                            <div class="p-field p-grid">
                                                <label for="materno" class="p-col-12 p-mb-2 p-md-4 p-mb-md-0">Apellido Materno</label>
                                                <div class="p-col-12 p-md-8">
                                                    <InputText id="materno" type="text" v-model.trim="usuarioForm.materno" @keyup.enter="saveUsuario" />
                                                    <small class="p-error" v-if="errors.materno">{{ errors.materno }}</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="p-fluid">
                                            <div class="p-field p-grid">
                                                <label for="dni" class="p-col-12 p-mb-2 p-md-4 p-mb-md-0">DNI</label>
                                                <div class="p-col-12 p-md-8">
                                                    <InputText id="dni" type="text" v-model.trim="usuarioForm.dni" @keyup.enter="saveUsuario" />
                                                    <small class="p-error" v-if="errors.dni">{{ errors.dni }}</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="p-fluid">
                                            <div class="p-field p-grid">
                                                <label for="email" class="p-col-12 p-mb-2 p-md-4 p-mb-md-0">Email</label>
                                                <div class="p-col-12 p-md-8">
                                                    <InputText id="email" type="text" v-model.trim="usuarioForm.email" @keyup.enter="saveUsuario" />
                                                    <small class="p-error" v-if="errors.email">{{ errors.email }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="p-card-footer">
                            <div class="p-mx-6 p-text-right">
                                <Button label="Guardar" icon="pi pi-check" class="p-button p-button-success" :loading="saveLoading" @click="saveUsuario" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-card p-component p-mt-4">
                    <div class="p-card-body">
                        <div class="p-card-content">
                            <div class="p-grid p-mx-4">
                                <div class="p-col-12">
                                    <h5 class="p-mb-0 p-text-bold">Cambiar Contraseña</h5>
                                </div>
                            </div>
                            <Divider />
                            <form @submit.prevent="" action="" autocomplete="off">
                                <div class="p-grid p-mx-6">
                                    <div class="p-col-12 p-md-12">
                                        <div class="p-fluid">
                                            <div class="p-field p-grid">
                                                <label for="old_password" class="p-col-12 p-mb-2 p-md-4 p-mb-md-0">Contraseña Actual</label>
                                                <div class="p-col-12 p-md-8">
                                                    <Password id="old_password" v-model="passwordForm.old_password" toggleMask :feedback="false" @keydown.enter="changePassword" />
                                                    <small class="p-error" v-if="errors.old_password">{{ errors.old_password }}</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="p-fluid">
                                            <div class="p-field p-grid">
                                                <label for="password" class="p-col-12 p-mb-2 p-md-4 p-mb-md-0">Nueva Contraseña</label>
                                                <div class="p-col-12 p-md-8">
                                                    <Password id="password" v-model="passwordForm.password" toggleMask @keydown.enter="changePassword">
                                                        <template #footer="sp">
                                                            {{ sp.level }}
                                                            <Divider />
                                                            <p class="p-mt-2">Sugerencias</p>
                                                            <ul class="p-pl-2 p-ml-2 p-mt-0" style="line-height: 1.5">
                                                                <li>Al menos una minúscula</li>
                                                                <li>Al menos una mayúscula</li>
                                                                <li>Al menos un número</li>
                                                                <li>Minimo 8 caracteres</li>
                                                            </ul>
                                                        </template>
                                                    </Password>
                                                    <small class="p-error" v-if="errors.password">{{ errors.password }}</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="p-fluid">
                                            <div class="p-field p-grid">
                                                <label for="password_confirmation" class="p-col-12 p-mb-2 p-md-4 p-mb-md-0">Repetir Nueva Contraseña</label>
                                                <div class="p-col-12 p-md-8">
                                                    <Password id="password_confirmation" v-model="passwordForm.password_confirmation" toggleMask :feedback="false" @keydown.enter="changePassword" />
                                                    <small class="p-error" v-if="errors.password_confirmation">{{ errors.password_confirmation }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="p-card-footer">
                            <div class="p-mx-6 p-text-right">
                                <Button label="Guardar" icon="pi pi-check" class="p-button p-button-success" :loading="saveLoading" @click="changePassword" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </app-layout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout";
import { Inertia } from "@inertiajs/inertia";
import { useForm } from "@inertiajs/inertia-vue3";

import { useToast } from "primevue/usetoast";
import { onMounted, ref, toRefs } from "vue";

export default {
    props: {
        perfil: Object,
        errors: Object,
        response: Object,
        user: Object,
    },
    setup(props) {
        const toast = useToast();
        const { response } = toRefs(props);
        const { user, perfil } = toRefs(props);
        const usuarioForm = useForm({
            profile_photo_url: "",
            name: "",
            paterno: "",
            materno: "",
            dni: "",
            email: "",
        });
        const passwordForm = useForm({
            password: "",
            password_confirmation: "",
            old_password: "",
        });
        const saveLoading = ref(false);
        const saveUsuario = () => {
            saveLoading.value = true;

            Inertia.put(route("perfil.update", user.value.id), usuarioForm, {
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
        const changePassword = () => {
            saveLoading.value = true;

            Inertia.put(route("perfil.password", user.value.id), passwordForm, {
                onSuccess: () => {
                    if (response.value.status) {
                        toast.add({
                            severity: "success",
                            summary: "¡Exito...!",
                            detail: response.value.message,
                            life: 5000,
                        });
                        passwordForm.reset();
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
        onMounted(() => {
            for (const key in perfil.value) {
                usuarioForm[key] = perfil.value[key];
            }
        });
        return {
            usuarioForm,
            saveUsuario,
            changePassword,
            saveLoading,
            passwordForm,
        };
    },
    components: {
        AppLayout,
    },
};
</script>
<style scoped>
table tr {
    text-align: left;
}
.p-card-footer button {
    margin: 0 5px;
}
</style>
