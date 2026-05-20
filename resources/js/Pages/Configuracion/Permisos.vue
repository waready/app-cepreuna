<template>
    <Toast />
    <app-layout :title="title" :mode="2">
        <div class="card shadow-6">
            <div class="grid hidden sm:flex">
                <div class="col-12">
                    <h5 class="font-bold">Permisos</h5>
                </div>
            </div>
            <Toolbar class="mb-4">
                <template #start>
                    <Button label="Nuevo" icon="pi pi-plus" class="p-button-success mr-2" @click="openNew" />
                </template>
            </Toolbar>
            <DataTable
                :value="permisos"
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
                <Column :exportable="false" header="Op.">
                    <template #body="slotProps">
                        <Button icon="pi pi-pencil" class="p-button-rounded p-button-info mr-2" @click="openEdit(slotProps.data)" />
                        <!-- <Button icon="pi pi-trash" class="p-button-rounded p-button-danger" @click="confirmDeletePermiso(slotProps.data)" /> -->
                    </template>
                </Column>
            </DataTable>
        </div>
        <!-- Agregar y Editar Registro -->
        <Dialog v-model:visible="permisoDialog" :style="{ width: '500px' }" header="Detalle de Permiso" :modal="true" class="fluid bg-primary">
            <form @submit.prevent="" action="" autocomplete="off">
                <div class="grid mx-4">
                    <div class="col-12 md:col-12">
                        <div class="field grid p-fluid">
                            <label for="nombre" class="col-12 mb-2 md:col-3 md:mb-0">Nombre</label>
                            <div class="col-12 md:col-9">
                                <InputText id="nombre" type="text" v-model.trim="permisoForm.name" autofocus @keyup.enter="savePermiso" />
                                <small class="p-error" v-if="errors.name">{{ errors.name }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <template #footer>
                <Button label="Cancelar" icon="pi pi-times" class="p-button-secondary" @click="permisoDialog = false" />
                <Button label="Guardar" icon="pi pi-check" class="p-button-success" :loading="saveLoading" @click="savePermiso" />
            </template>
        </Dialog>
        <!-- Eliminar Registro -->
        <Dialog v-model:visible="deletePermisoDialog" :style="{ width: '450px' }" header="Confirmar" :modal="true" class="bg-warning">
            <div class="confirmation-content mt-4 text-center">
                <i class="pi pi-exclamation-triangle mr-3" style="font-size: 3rem" />
                <span v-if="permisoForm"
                    >¿Esta seguro de eliminar el permiso <b>{{ permisoForm.name }}</b
                    >?</span
                >
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" class="p-button-secondary" @click="deletePermisoDialog = false" />
                <Button label="Si" icon="pi pi-check" class="p-button-success" @click="deletePermiso" />
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
    },
    setup(props) {
        const title = ref("Permisos");
        const toast = useToast();
        const { response } = toRefs(props);
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
        const permisos = ref();

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
                .get(route("permisos.tabla"), { params: { lazyEvent: JSON.stringify(lazyParams.value), page: page.value } })
                .then((response) => {
                    permisos.value = response.data.data;
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

        // crud permisos
        const permisoDialog = ref(false);
        const permisoForm = useForm({
            name: "",
        });
        const actionForm = ref();
        const saveLoading = ref(false);
        const deletePermisoDialog = ref(false);

        const openNew = () => {
            permisoForm.clearErrors();
            permisoForm.reset();
            actionForm.value = "new";

            permisoDialog.value = true;
        };
        const openEdit = (data) => {
            permisoForm.clearErrors();
            permisoForm.reset();
            actionForm.value = "edit";
            for (const key in data) {
                permisoForm[key] = data[key];
            }
            permisoDialog.value = true;
        };
        // Agregar y Editar registro
        const savePermiso = () => {
            saveLoading.value = true;

            if (actionForm.value == "new") {
                permisoForm.clearErrors();
                Inertia.post(route("permisos.store"), permisoForm, {
                    onSuccess: () => {
                        if (response.value.status) {
                            loadLazyData();
                            toast.add({
                                severity: "success",
                                summary: "¡Exito...!",
                                detail: response.value.message,
                                life: 5000,
                            });
                            permisoForm.reset();
                            permisoDialog.value = false;
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
                Inertia.put(route("permisos.update", permisoForm.id), permisoForm, {
                    onSuccess: () => {
                        if (response.value.status) {
                            loadLazyData();
                            toast.add({
                                severity: "success",
                                summary: "¡Exito...!",
                                detail: response.value.message,
                                life: 5000,
                            });
                            permisoForm.reset();
                            permisoDialog.value = false;
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
        const confirmDeletePermiso = (data) => {
            for (const key in data) {
                permisoForm[key] = data[key];
            }
            deletePermisoDialog.value = true;
        };
        const deletePermiso = () => {
            deletePermisoDialog.value = false;
            Inertia.delete(route("permisos.destroy", permisoForm.id), {
                onSuccess: () => {
                    if (response.value.status) {
                        loadLazyData();
                        toast.add({
                            severity: "success",
                            summary: "¡Exito...!",
                            detail: response.value.message,
                            life: 5000,
                        });
                        permisoForm.reset();
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
        return {
            title,
            dt,
            loading,
            totalRecords,
            permisos,
            filters,
            lazyParams,
            columns,
            loadLazyData,
            onPage,
            onSort,
            onFilter,
            permisoDialog,
            permisoForm,
            openNew,
            openEdit,
            saveLoading,
            savePermiso,
            confirmDeletePermiso,
            deletePermisoDialog,
            deletePermiso,
        };
    },
    components: {
        AppLayout,
    },
};
</script>
