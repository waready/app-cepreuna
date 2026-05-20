<template>
    <Toast />
    <app-layout :title="title" :mode="2">
        <div class="card">
            <div class="grid hidden sm:flex">
                <div class="col-12">
                    <h5 class="font-bold">Usuarios</h5>
                </div>
                <!-- <pre>
                    {{ $page.props.permissions }}
                </pre> -->
            </div>
            <Toolbar class="mb-4">
                <template #start>
                    <Button label="Nuevo" icon="pi pi-plus" class="p-button-success mr-2" @click="openNew" />
                </template>
            </Toolbar>
            <DataTable
                :value="usuarios"
                :lazy="true"
                :paginator="true"
                :rows="10"
                v-model:filters="filters"
                ref="dt"
                :totalRecords="totalRecords"
                :loading="loading"
                @page="onPage($event)"
                @sort="onSort($event)"
                filterDisplay="row"
                :globalFilterFields="['id', 'name', 'dni', 'email', 'model_has_role.role.name']"
                responsiveLayout="stack"
                stripedRows
                showGridlines
                class="p-datatable-sm"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                :rowsPerPageOptions="[5, 10, 25]"
                currentPageReportTemplate="{first} a {last} de {totalRecords} registros"
            >
                <Column field="id" header="ID" filterMatchMode="contains" :showFilterMenu="false" ref="id" :sortable="true" style="min-width: 20px">
                    <template #filter="{ filterModel, filterCallback }">
                        <InputText type="text" v-model="filterModel.value" @keydown.enter="filterCallback()" class="p-column-filter" placeholder="Buscar por ID" />
                    </template>
                </Column>
                <Column field="name" header="Nombres" filterMatchMode="contains" :showFilterMenu="false" ref="name" :sortable="true">
                    <template #filter="{ filterModel, filterCallback }">
                        <InputText type="text" v-model="filterModel.value" @keydown.enter="filterCallback()" class="p-column-filter" placeholder="Buscar por nombre" />
                    </template>
                </Column>
                <Column field="dni" header="DNI" filterMatchMode="contains" :showFilterMenu="false" ref="dni" :sortable="true" style="width: 150px">
                    <template #filter="{ filterModel, filterCallback }">
                        <InputText type="text" v-model="filterModel.value" @keydown.enter="filterCallback()" class="p-column-filter" placeholder="Buscar por dni" />
                    </template>
                </Column>
                <Column field="email" header="Email" filterMatchMode="contains" :showFilterMenu="false" ref="email" :sortable="true">
                    <template #filter="{ filterModel, filterCallback }">
                        <InputText type="text" v-model="filterModel.value" @keydown.enter="filterCallback()" class="p-column-filter" placeholder="Buscar por email" />
                    </template>
                </Column>
                <Column field="model_has_role.role.name" header="Rol" filterMatchMode="contains" :showFilterMenu="false" ref="model_has_role.role.name" :sortable="false" style="width: 200px">
                    <template #body="{ data }">
                        {{ data.model_has_role ? data.model_has_role.role.name : "" }}
                    </template>
                </Column>
                <Column :exportable="false" header="Op." style="width: 70px">
                    <template #body="slotProps">
                        <Button icon="pi pi-pencil" class="p-button-rounded p-button-info mr-2" @click="openEdit(slotProps.data)" />
                        <Button icon="pi pi-trash" class="p-button-rounded p-button-danger" @click="confirmDeleteUsuario(slotProps.data)" />
                    </template>
                </Column>
            </DataTable>
        </div>
        <!-- Agregar y Editar Registro -->
        <Dialog v-model:visible="usuarioDialog" :style="{ width: '500px' }" header="Detalle de Usuario" :modal="true" class="p-fluid bg-primary">
            <form @submit.prevent="" action="" autocomplete="off">
                <div class="grid mx-4">
                    <div class="col-12 md:col-12">
                        <div class="field grid p-fluid">
                            <div class="col-12 md:col-12 text-center">
                                <Avatar v-if="usuarioForm.profile_photo_url" :image="usuarioForm.profile_photo_url" class="mr-2 shadow-3" style="width: 100px; height: 100px" shape="circle" />
                            </div>
                        </div>

                        <div class="field grid p-fluid">
                            <label for="nombre" class="col-12 mb-2 md:col-4 md:mb-0">Nombre</label>
                            <div class="col-12 md:col-8">
                                <InputText id="nombre" type="text" v-model.trim="usuarioForm.name" autofocus @keyup.enter="saveUsuario" />
                                <small class="p-error" v-if="errors.name">{{ errors.name }}</small>
                            </div>
                        </div>

                        <div class="field grid p-fluid">
                            <label for="dni" class="col-12 mb-2 md:col-4 md:mb-0">DNI</label>
                            <div class="col-12 md:col-8">
                                <InputText id="dni" type="text" v-model.trim="usuarioForm.dni" @keyup.enter="saveUsuario" />
                                <small class="p-error" v-if="errors.dni">{{ errors.dni }}</small>
                            </div>
                        </div>

                        <div class="field grid p-fluid">
                            <label for="rol" class="col-12 mb-2 md:col-4 md:mb-0">Rol</label>
                            <div class="col-12 md:col-8">
                                <AutoComplete v-model="usuarioForm.rol" :suggestions="filteredRol" @complete="searchRol($event)" :dropdown="true" field="name" forceSelection id="rol"> </AutoComplete>
                                <small class="p-error" v-if="errors.rol">{{ errors.rol }}</small>
                            </div>
                        </div>

                        <div class="field grid p-fluid">
                            <label for="email" class="col-12 mb-2 md:col-4 md:mb-0">Email</label>
                            <div class="col-12 md:col-8">
                                <InputText id="email" type="text" v-model.trim="usuarioForm.email" @keyup.enter="saveUsuario" />
                                <small class="p-error" v-if="errors.email">{{ errors.email }}</small>
                            </div>
                        </div>

                        <div class="field grid p-fluid">
                            <label for="password" class="col-12 mb-2 md:col-4 md:mb-0">Contraseña</label>
                            <div class="col-12 md:col-8">
                                <Password id="password" v-model="usuarioForm.password" toggleMask :feedback="false" @keydown.enter="saveUsuario" />
                                <small class="p-error" v-if="errors.password">{{ errors.password }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <template #footer>
                <Button label="Cancelar" icon="pi pi-times" class="p-button-secondary" @click="usuarioDialog = false" />
                <Button label="Guardar" icon="pi pi-check" class="p-button-success" :loading="saveLoading" @click="saveUsuario" />
            </template>
        </Dialog>
        <!-- Eliminar Registro -->
        <Dialog v-model:visible="deleteUsuarioDialog" :style="{ width: '450px' }" header="Confirmar" :modal="true" class="bg-warning">
            <div class="confirmation-content mt-4 text-center">
                <i class="pi pi-exclamation-triangle mr-3" style="font-size: 3rem" />
                <span v-if="usuarioForm"
                    >¿Esta seguro de eliminar el usuario <b>{{ usuarioForm.name }}</b
                    >?</span
                >
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" class="p-button-secondary" @click="deleteUsuarioDialog = false" />
                <Button label="Si" icon="pi pi-check" class="p-button-success" @click="deleteUsuario" />
            </template>
        </Dialog>
    </app-layout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout";

import { useToast } from "primevue/usetoast";

import { Inertia } from "@inertiajs/inertia";
import { useForm } from "@inertiajs/inertia-vue3";

import { ref, onMounted, watch, toRefs } from "vue";
import axios from "axios";
export default {
    props: {
        errors: Object,
        response: Object,
        data: Object,
    },
    setup(props) {
        const title = ref("Usuarios");
        const toast = useToast();
        const { response } = toRefs(props);
        const { data } = toRefs(props);
        // mounted
        onMounted(() => {
            loading.value = true;

            lazyParams.value = {
                first: 0,
                rows: dt.value.rows,
                sortField: null,
                sortOrder: null,
                filters: filters.value,
            };
            loadLazyData();
        });
        // datatable
        const dt = ref();
        const loading = ref(false);
        const totalRecords = ref(0);
        const page = ref(1);
        const usuarios = ref();

        const filters = ref({
            id: { value: "", matchMode: "contains" },
            name: { value: "", matchMode: "contains" },
            paterno: { value: "", matchMode: "contains" },
            materno: { value: "", matchMode: "contains" },
            dni: { value: "", matchMode: "contains" },
            email: { value: "", matchMode: "contains" },
            "model_has_role.role.name": { value: "", matchMode: "contains" },
        });
        const lazyParams = ref({});
        const columns = ref([
            { field: "id", header: "ID" },
            { field: "name", header: "Nombre" },
            { field: "dni", header: "DNI" },
            { field: "email", header: "Email" },
            { field: "model_has_role.role.name", header: "Rol" },
        ]);
        const loadLazyData = () => {
            loading.value = true;

            axios
                .get(route("usuarios.tabla"), {
                    params: {
                        lazyEvent: JSON.stringify(lazyParams.value),
                        page: page.value,
                    },
                })
                .then((response) => {
                    usuarios.value = response.data.data;
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

        // crud usuarios
        const usuarioDialog = ref(false);
        const usuarioForm = useForm({
            profile_photo_url: "",
            name: "",
            dni: "",
            rol: "",
            email: "",
            password: "",
        });
        const actionForm = ref();
        const saveLoading = ref(false);
        const deleteUsuarioDialog = ref(false);

        const openNew = () => {
            usuarioForm.clearErrors();
            usuarioForm.reset();
            actionForm.value = "new";

            usuarioDialog.value = true;
        };
        const openEdit = (data) => {
            usuarioForm.clearErrors();
            usuarioForm.reset();
            actionForm.value = "edit";
            for (const key in data) {
                usuarioForm[key] = data[key];
            }

            usuarioForm.rol = data.model_has_role.role;
            usuarioDialog.value = true;
        };
        // Agregar y Editar registro
        const saveUsuario = () => {
            saveLoading.value = true;

            if (actionForm.value == "new") {
                usuarioForm.clearErrors();
                Inertia.post(route("usuarios.store"), usuarioForm, {
                    onSuccess: () => {
                        if (response.value.status) {
                            loadLazyData();
                            toast.add({
                                severity: "success",
                                summary: "¡Exito...!",
                                detail: response.value.message,
                                life: 5000,
                            });
                            usuarioForm.reset();
                            usuarioDialog.value = false;
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
            } else {
                Inertia.put(route("usuarios.update", usuarioForm.id), usuarioForm, {
                    onSuccess: () => {
                        if (response.value.status) {
                            loadLazyData();
                            toast.add({
                                severity: "success",
                                summary: "¡Exito...!",
                                detail: response.value.message,
                                life: 5000,
                            });
                            usuarioForm.reset();
                            usuarioDialog.value = false;
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
            }
        };
        // Eliminar registro
        const confirmDeleteUsuario = (data) => {
            for (const key in data) {
                usuarioForm[key] = data[key];
            }
            deleteUsuarioDialog.value = true;
        };
        const deleteUsuario = () => {
            deleteUsuarioDialog.value = false;
            Inertia.delete(route("usuarios.destroy", usuarioForm.id), {
                onSuccess: () => {
                    if (response.value.status) {
                        loadLazyData();
                        toast.add({
                            severity: "success",
                            summary: "¡Exito...!",
                            detail: response.value.message,
                            life: 5000,
                        });
                        usuarioForm.reset();
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

        // select roles
        const filteredRol = ref();
        const roles = ref(data.value.roles);
        const searchRol = (event) => {
            if (!event.query.trim().length) {
                filteredRol.value = [...roles.value];
            } else {
                filteredRol.value = roles.value.filter((rol) => {
                    return rol.name.toLowerCase().startsWith(event.query.toLowerCase());
                });
            }
        };
        return {
            title,
            dt,
            loading,
            totalRecords,
            usuarios,
            filters,
            lazyParams,
            columns,
            loadLazyData,
            onPage,
            onSort,
            onFilter,
            usuarioDialog,
            usuarioForm,
            openNew,
            openEdit,
            saveLoading,
            saveUsuario,
            confirmDeleteUsuario,
            deleteUsuarioDialog,
            deleteUsuario,
            filteredRol,
            roles,
            searchRol,
        };
    },
    components: {
        AppLayout,
    },
};
</script>
