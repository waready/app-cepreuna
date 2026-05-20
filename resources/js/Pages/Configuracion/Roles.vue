<template>
    <Toast />
    <app-layout :title="title" :mode="2">
        <div class="card">
            <div class="grid hidden sm:flex">
                <div class="col-12">
                    <h5 class="font-bold">Roles</h5>
                </div>
            </div>
            <Toolbar class="mb-4">
                <template #start>
                    <Button label="Nuevo" icon="pi pi-plus" class="p-button-success mr-2" @click="openNew" />
                </template>
            </Toolbar>
            <DataTable
                :value="roles"
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
                :globalFilterFields="['id', 'name']"
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
                <Column field="name" header="Nombre" filterMatchMode="contains" :showFilterMenu="false" ref="name" :sortable="true" style="min-width: 13rem">
                    <template #filter="{ filterModel, filterCallback }">
                        <InputText type="text" v-model="filterModel.value" @keydown.enter="filterCallback()" class="p-column-filter" placeholder="Buscar por nombre" />
                    </template>
                </Column>
                <Column :exportable="false" header="Permisos">
                    <template #body="slotProps">
                        <Badge v-for="permission in slotProps.data.permissions" :value="permission.name" severity="success" class="mr-2"></Badge>
                    </template>
                </Column>
                <Column :exportable="false" header="Op.">
                    <template #body="slotProps">
                        <Button icon="pi pi-pencil" class="p-button-rounded p-button-info mr-2" @click="openEdit(slotProps.data)" />
                        <!-- <Button icon="pi pi-trash" class="p-button-rounded p-button-danger" @click="confirmDeleteRol(slotProps.data)" /> -->
                    </template>
                </Column>
            </DataTable>
        </div>
        <!-- Agregar y Editar Registro -->
        <Dialog v-model:visible="rolDialog" :style="{ width: '500px' }" header="Detalle de Rol" :modal="true" class="p-fluid bg-primary">
            <form @submit.prevent="" action="" autocomplete="off">
                <div class="grid mx-4">
                    <div class="col-12 md:col-12">
                        <div class="field grid p-fluid">
                            <label for="nomre" class="col-12 mb-2 md:col-3 md:mb-0">Nombre</label>
                            <div class="col-12 md:col-9">
                                <InputText id="nombre" type="text" v-model.trim="rolForm.name" autofocus @keyup.enter="saveRol" />
                                <small class="p-error" v-if="errors.name">{{ errors.name }}</small>
                            </div>
                        </div>
                        <div class="field grid p-fluid">
                            <label for="permiso" class="col-12 mb-2 md:col-3 md:mb-0">Permisos</label>
                            <div class="col-12 md:col-9">
                                <AutoComplete id="permiso" :multiple="true" v-model="selectedPermisos" :suggestions="filteredPermisos" @complete="searchPermisos($event)" field="name" />
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <template #footer>
                <Button label="Cancelar" icon="pi pi-times" class="p-button-secondary" @click="rolDialog = false" />
                <Button label="Guardar" icon="pi pi-check" class="p-button-success" :loading="saveLoading" @click="saveRol" />
            </template>
        </Dialog>
        <Dialog v-model:visible="deleteRolDialog" :style="{ width: '450px' }" header="Confirmar" :modal="true" class="bg-warning">
            <div class="confirmation-content mt-4 text-center">
                <i class="pi pi-exclamation-triangle mr-3" style="font-size: 3rem" />
                <span v-if="rolForm"
                    >¿Esta seguro de eliminar el rol <b>{{ rolForm.name }}</b
                    >?</span
                >
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" class="p-button-secondary" @click="deleteRolDialog = false" />
                <Button label="Si" icon="pi pi-check" class="p-button-success" @click="deleteRol" />
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
        permisos: Array,
    },
    setup(props) {
        const title = ref("Roles");
        const toast = useToast();
        const { response } = toRefs(props);
        const { permisos } = toRefs(props);
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
        const roles = ref();

        const filters = ref({
            id: { value: "", matchMode: "contains" },
            name: { value: "", matchMode: "contains" },
        });
        const lazyParams = ref({});
        const columns = ref([
            { field: "id", header: "ID" },
            { field: "name", header: "Nombre" },
        ]);
        const loadLazyData = () => {
            loading.value = true;

            axios
                .get(route("roles.tabla"), { params: { lazyEvent: JSON.stringify(lazyParams.value), page: page.value } })
                .then((response) => {
                    roles.value = response.data.data;
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

        // crud roles
        const rolDialog = ref(false);
        const rolForm = useForm({
            name: "",
        });
        const actionForm = ref();
        const saveLoading = ref(false);
        const deleteRolDialog = ref(false);

        const openNew = () => {
            rolForm.clearErrors();
            rolForm.reset();
            actionForm.value = "new";
            rolForm.value = {};
            rolDialog.value = true;
        };
        const openEdit = (data) => {
            rolForm.clearErrors();
            rolForm.reset();
            actionForm.value = "edit";
            for (const key in data) {
                rolForm[key] = data[key];
            }
            selectedPermisos.value = data.permissions;
            rolDialog.value = true;
        };
        // Agregar y Editar registro
        const saveRol = () => {
            saveLoading.value = true;
            let permisos = new Array();
            permisos.push(
                selectedPermisos.value.map((permiso) => {
                    return permiso.id;
                })
            );
            // console.log(permisos);
            rolForm.permisos = permisos;

            if (actionForm.value == "new") {
                rolForm.clearErrors();
                Inertia.post(route("roles.store"), rolForm, {
                    onSuccess: () => {
                        if (response.value.status) {
                            loadLazyData();
                            toast.add({
                                severity: "success",
                                summary: "¡Exito...!",
                                detail: response.value.message,
                                life: 5000,
                            });
                            rolForm.reset();
                            rolDialog.value = false;
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
                Inertia.put(route("roles.update", rolForm.id), rolForm, {
                    onSuccess: () => {
                        if (response.value.status) {
                            loadLazyData();
                            toast.add({
                                severity: "success",
                                summary: "¡Exito...!",
                                detail: response.value.message,
                                life: 5000,
                            });
                            rolForm.reset();
                            rolDialog.value = false;
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
        const confirmDeleteRol = (data) => {
            for (const key in data) {
                rolForm[key] = data[key];
            }
            deleteRolDialog.value = true;
        };
        const deleteRol = () => {
            deleteRolDialog.value = false;
            Inertia.delete(route("roles.destroy", rolForm.id), {
                onSuccess: () => {
                    if (response.value.status) {
                        loadLazyData();
                        toast.add({
                            severity: "success",
                            summary: "¡Exito...!",
                            detail: response.value.message,
                            life: 5000,
                        });
                        rolForm.reset();
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
        // autocomplete permisos
        const selectedPermisos = ref();
        const filteredPermisos = ref();
        const searchPermisos = (event) => {
            if (!event.query.trim().length) {
                filteredPermisos.value = [...permisos.value];
            } else {
                filteredPermisos.value = permisos.value.filter((country) => {
                    return country.name.toLowerCase().startsWith(event.query.toLowerCase());
                });
            }
        };
        return {
            title,
            dt,
            loading,
            totalRecords,
            roles,
            filters,
            lazyParams,
            columns,
            loadLazyData,
            onPage,
            onSort,
            onFilter,
            rolDialog,
            rolForm,
            openNew,
            openEdit,
            saveLoading,
            saveRol,
            confirmDeleteRol,
            deleteRolDialog,
            deleteRol,
            searchPermisos,
            selectedPermisos,
            filteredPermisos,
        };
    },
    components: {
        AppLayout,
    },
};
</script>
