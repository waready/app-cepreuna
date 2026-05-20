<template>
    <Toast />
    <app-layout :title="title" :mode="2">
        <div class="card shadow-6">
            <div class="grid hidden sm:flex">
                <div class="col-12">
                    <h5 class="font-semibold text-center">Asistencia de Estudiantes</h5>
                </div>
            </div>
            <!-- vistas del menu inferior -->
            <div :class="menuTabs[0].isActive == false ? 'hidden' : 'grid'">
                <div class="font-semibold col-12 text-center">
                    {{ menuTabs[0].title }}
                    <hr />
                </div>
                <div class="col-12">
                    <form action="" autocomplete="off">
                        <div class="grid">
                            <div class="col-12 md:col-3">
                                <label for="documento">Area</label>
                                <div class="p-inputgroup">
                                    <AutoComplete
                                        :dropdown="true"
                                        v-model="form.area"
                                        :suggestions="filteredAreas"
                                        @complete="searchAreas($event)"
                                        @item-select="changeArea"
                                        forceSelection
                                        field="denominacion"
                                        placeholder="Seleccione..."
                                    />
                                </div>
                            </div>
                            <div class="col-12 md:col-3">
                                <label for="documento">Turno</label>
                                <div class="p-inputgroup">
                                    <AutoComplete
                                        :dropdown="true"
                                        v-model="form.turno"
                                        :suggestions="filteredTurnos"
                                        @complete="searchTurnos($event)"
                                        @item-select="changeTurno"
                                        forceSelection
                                        field="denominacion"
                                        placeholder="Seleccione..."
                                    />
                                </div>
                            </div>
                            <div class="col-12 md:col-3">
                                <label for="documento">Sede</label>
                                <div class="p-inputgroup">
                                    <AutoComplete
                                        :dropdown="true"
                                        v-model="form.sede"
                                        :suggestions="filteredSedes"
                                        @complete="searchSedes($event)"
                                        @item-select="changeSede"
                                        forceSelection
                                        field="denominacion"
                                        placeholder="Seleccione..."
                                    />
                                </div>
                            </div>
                            <div class="col-12 md:col-3">
                                <label for="documento">Grupo</label>
                                <div class="p-inputgroup">
                                    <AutoComplete
                                        :dropdown="true"
                                        v-model="form.grupo"
                                        :suggestions="filteredGrupos"
                                        @complete="searchGrupos($event)"
                                        forceSelection
                                        field="grupo"
                                        placeholder="Seleccione..."
                                    />
                                </div>
                                <small class="p-error" v-if="errors.grupo">{{ errors.grupo }}</small>
                            </div>
                            <div class="col-12 md:col-6">
                                <label for="">Observaciones</label>
                                <div class="p-inputgroup">
                                    <Textarea v-model="form.observacion" rows="3" cols="30" />
                                </div>
                            </div>
                            <div class="col-12 md:col-6 text-center">
                                <Button label="Aperturar Asistencia" @click="aperturarAsistencia" class="p-button-primary-theme" />
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div :class="menuTabs[1].isActive == false ? 'hidden' : 'grid'">
                <div class="font-semibold col-12 text-center">
                    {{ menuTabs[1].title }}
                    <hr class="mb-0" />
                </div>
                <div class="font-semibold col-12 text-center">
                    <div class="card m-0 p-0">
                        <div class="grid" v-if="activeGroup">
                            <div class="col-12">
                                <Tag class="mr-2 mb-1" severity="success" :value="'Grupo Actual: ' + activeGroup"></Tag>
                                <div class="grid p-fluid">
                                    <div class="col-12">
                                        <div class="p-inputgroup">
                                            <InputText v-model.trim="dni" placeholder="Ingrese DNI del estudiante" />
                                            <Button icon="pi pi-search" class="p-button-warning" @click="scanQr(dni)" />
                                        </div>
                                    </div>
                                </div>
                                <qr-code-component @scan-qr="scanQr"></qr-code-component>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 text-center">
                    <Button
                        v-for="aperturado in aperturados"
                        :key="aperturado.id"
                        :label="aperturado.denominacion"
                        class="p-button-rounded p-button-secondary p-button-sm m-1"
                        :disabled="aperturado.estado == '1' ? false : true"
                        @click="onActiveGroup(aperturado)"
                    />
                </div>
            </div>
            <div :class="menuTabs[2].isActive == false ? 'hidden' : 'grid'">
                <div class="font-semibold col-12 text-center">
                    {{ menuTabs[2].title }} {{ activeGroup }}
                    <hr class="mb-0" />
                </div>
                <div class="col-12">
                    <div class="p-datatable p-component p-datatable-responsive-scroll p-datatable-sm" data-scrollselectors=".p-datatable-wrapper">
                        <div class="p-datatable-wrapper">
                            <table class="p-datatable">
                                <tbody class="p-datatable-tbody" role="rowgroup">
                                    <tr v-for="(lista, index) in listaActual">
                                        <td>{{ index + 1 }}.-</td>
                                        <td>{{ capitalize(lista.estudiante.paterno) }} {{ capitalize(lista.estudiante.materno) }} {{ capitalize(lista.estudiante.nombres) }}</td>
                                        <td>
                                            <span style="font-size: 12px" class="mr-2 mb-1" :class="lista.estado == '1' ? 'instock' : 'lowstock'">{{
                                                lista.estado == "1" ? "Presente" : "Tarde"
                                            }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-12 text-center">
                        <Button v-if="activeGroup" label="Cerrar Asistencia" class="p-button-primary-theme p-button-sm m-1" @click="cerrarAsistencia(aperturado)" :loading="saveLoading" />
                    </div>
                </div>
            </div>
        </div>

        <!-- menu inferior -->
        <div class="menu-footer">
            <span class="p-fluid flex flex-row">
                <div v-for="(tab, index) in menuTabs" :key="tab.title" class="flex-1 flex align-items-center justify-content-center bg-white font-bold">
                    <Button
                        :class="tab.isActive ? 'menu-tab-active' : ''"
                        class="p-button-text p-button-plain pb-2"
                        style="display: grid"
                        :label="tab.menuTitle"
                        :icon="tab.icon"
                        @click="activeMenu(index)"
                    />
                </div>
            </span>
        </div>

        <!-- Agregar y Editar Registro -->
        <Dialog v-model:visible="searchDialog" :style="{ width: '500px' }" header="Detalle de Asistencia" @hide="hideDialogAsistencia" :modal="true" class="p-fluid bg-success">
            <form @submit.prevent="" action="" autocomplete="off">
                <div v-if="errorSearch" class="grid">
                    <Message severity="error">{{ errorSearch }}</Message>
                </div>
                <div v-else class="grid">
                    <div class="col-5 text-center">
                        <Avatar :image="baseUrl + '/storage/fotos/' + avatar" class="mr-2 shadow-3" size="xlarge" />
                    </div>
                    <div class="col-7">
                        <p class="m-0">Apellidos:</p>
                        <p class="m-0 text-base font-bold">{{ apellidos }}</p>
                        <p class="m-0">Nombres:</p>
                        <p class="m-0 text-base font-bold">{{ nombres }}</p>
                        <p class="m-0">Grupo:</p>
                        <p class="m-0 text-base font-bold">{{ grupoSede }}</p>
                    </div>
                    <div v-if="!existeAsistencia" class="col-12 text-center">
                        <hr class="m-0" />
                        <label for="">Estado de Asistencia</label>
                        <!-- <Dropdown v-model="estado" :options="estados" optionLabel="denominacion" optionValue="id" placeholder="Seleccione..." /> -->
                        <!-- <div class="p-formgroup-inline">
                            <div class="p-field-radiobutton">
                                <RadioButton id="input_outlined" v-model="estado" name="inputstyle" value="1" />
                                <label class="mr-3 text-green-500" for="input_outlined"> Presente</label>
                                <RadioButton id="input_filled" v-model="estado" name="inputstyle" value="2" />
                                <label class="mr-3 text-orange-500" for="input_filled"> Tarde</label>
                            </div>
                        </div> -->
                    </div>
                    <div v-if="!existeAsistencia" class="col-6">
                        <Button label="Presente" icon="pi pi-check" class="p-button-success" :loading="saveLoading" @click="saveAsistencia(1)" />
                    </div>
                    <div v-if="!existeAsistencia" class="col-6">
                        <Button label="Tarde" icon="pi pi-check" class="p-button-warning" :loading="saveLoading" @click="saveAsistencia(2)" />
                    </div>
                    <div v-if="existeAsistencia" class="col-12">
                        <Message severity="success"
                            >El estudiante ya fue registrado con estado <b>{{ existeAsistencia }}</b></Message
                        >
                    </div>
                </div>
            </form>
        </Dialog>
    </app-layout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout";
import QrCodeComponent from "@/components/QrCodeComponent";
import { useToast } from "primevue/usetoast";

import { Inertia } from "@inertiajs/inertia";
import { useForm } from "@inertiajs/inertia-vue3";

import { ref, onMounted, watch, toRefs, computed } from "vue";
import axios from "axios";

export default {
    components: {
        AppLayout,
        QrCodeComponent,
    },
    props: {
        errors: Object,
        response: Object,
        data: Object,
    },
    setup(props) {
        const title = ref("Asistencia de Estudiantes");
        const toast = useToast();
        const { response, data } = toRefs(props);

        const aperturados = ref(data.value.aperturados);
        const menuTabs = ref([
            {
                title: "Iniciar Asistencia",
                icon: "pi pi-check-square",
                isActive: false,
                menuTitle: "Iniciar",
            },
            {
                title: "Escanear Carnet",
                icon: "pi pi-qrcode",
                isActive: true,
                menuTitle: "Escanear",
            },
            {
                title: "Lista Actual",
                icon: "pi pi-list",
                isActive: false,
                menuTitle: "Lista",
            },
        ]);
        const activeMenu = (i) => {
            menuTabs.value.forEach((m) => {
                m.isActive = false;
            });
            menuTabs.value[i].isActive = true;
        };
        const estados = ref([
            { denominacion: "Presente", id: "1" },
            { denominacion: "Tarde", id: "2" },
        ]);
        const tipoAsistencia = ref(true);
        const usuario = ref();
        // mounted
        onMounted(() => {
            getAreas();
            getTurnos();
            getGrupoAulas();
            getSedes();
        });

        // recursos
        const areas = ref([]);
        const turnos = ref([]);
        const grupos = ref([]);
        const sedes = ref([]);

        const area = ref("");
        const turno = ref("");
        const grupo = ref("");
        const sede = ref("");

        const getSedes = () => {
            axios
                .get(route("recursos.get-sedes"), {
                    params: {
                        all: true,
                    },
                })
                .then((response) => {
                    sedes.value = response.data;
                });
        };
        const filteredSedes = ref();
        const searchSedes = (event) => {
            if (!event.query.trim().length) {
                filteredSedes.value = [...sedes.value];
            } else {
                filteredSedes.value = sedes.value.filter((sede) => {
                    return sede.denominacion.toLowerCase().includes(event.query.toLowerCase());
                });
            }
        };

        const getAreas = () => {
            axios.get(route("recursos.get-areas")).then((response) => {
                areas.value = response.data;
            });
        };
        const filteredAreas = ref();

        const searchAreas = (event) => {
            if (!event.query.trim().length) {
                filteredAreas.value = [...areas.value];
            } else {
                filteredAreas.value = areas.value.filter((area) => {
                    return area.denominacion.toLowerCase().includes(event.query.toLowerCase());
                });
            }
        };

        const getTurnos = () => {
            axios.get(route("recursos.get-turnos")).then((response) => {
                turnos.value = response.data;
            });
        };
        const filteredTurnos = ref();
        const searchTurnos = (event) => {
            if (!event.query.trim().length) {
                filteredTurnos.value = [...turnos.value];
            } else {
                filteredTurnos.value = turnos.value.filter((turno) => {
                    return turno.denominacion.toLowerCase().includes(event.query.toLowerCase());
                });
            }
        };

        const getGrupoAulas = () => {
            axios
                .get(route("recursos.get-grupo-aulas-auxiliar"), {
                    params: {
                        area: form.area ? form.area.id : "",
                        turno: form.area ? form.turno.id : "",
                        sede: form.area ? form.sede.id : "",
                    },
                })
                .then((response) => {
                    // this.grupo = response.data[0].id;
                    grupos.value = response.data;
                });
        };
        const filteredGrupos = ref();
        const searchGrupos = (event) => {
            if (!event.query.trim().length) {
                filteredGrupos.value = [...grupos.value];
            } else {
                filteredGrupos.value = grupos.value.filter((grupo) => {
                    return grupo.denominacion.toLowerCase().includes(event.query.toLowerCase());
                });
            }
        };

        const changeArea = () => {
            getGrupoAulas();
            getTurnos();
        };
        const changeTurno = () => {
            getGrupoAulas();
        };
        const changeSede = () => {
            getGrupoAulas();
        };
        // Aperturar Asistencia
        const form = useForm({
            area: null,
            turno: null,
            sede: null,
            grupo: null,
            observacion: "",
        });
        const actionForm = ref();
        const saveLoading = ref(false);
        const aperturarAsistencia = () => {
            saveLoading.value = true;
            form.clearErrors();
            Inertia.post(route("asistencias.aperturar-asistencias"), form, {
                onSuccess: () => {
                    if (response.value.status) {
                        toast.add({
                            severity: "success",
                            summary: "¡Exito...!",
                            detail: response.value.message,
                            life: 5000,
                        });
                        form.reset();

                        aperturados.value = response.value.aperturados;
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

        // QR Scan
        const searchDialog = ref(false);
        const avatar = ref("");
        const baseUrl = ref("");
        const nombres = ref("");
        const dni = ref("");
        const apellidos = ref("");
        const estudiante = ref("");
        const grupoSede = ref("");
        const tmpActiveGroup = ref("");
        const errorSearch = ref("");
        const existeAsistencia = ref("");
        const scanQr = (item) => {
            dni.value = item;
            tmpActiveGroup.value = activeGroup.value;
            activeGroup.value = "";
            errorSearch.value = "";
            axios
                .get(route("asistencias.buscar-estudiante"), {
                    params: {
                        dni: dni.value,
                        grupo: idActiveGroup.value,
                        asistencia: idActiveAsistencia.value,
                        grupoActual: tmpActiveGroup.value,
                    },
                })
                .then((response) => {
                    if (response.data.status) {
                        estudiante.value = response.data.estudiante.id;
                        avatar.value = response.data.estudiante.foto;
                        baseUrl.value = response.data.baseUrl;
                        nombres.value = response.data.estudiante.nombres;
                        apellidos.value = response.data.estudiante.apellidos;
                        grupoSede.value = response.data.estudiante.grupo;
                        existeAsistencia.value = response.data.exist;
                    } else {
                        errorSearch.value = response.data.message;
                    }

                    searchDialog.value = true;

                    // this.grupo = response.data[0].id;
                    // grupos.value = response.data;
                });
        };
        // interaccion al seleccionar grupo
        const activeGroup = ref("");
        const idActiveGroup = ref("");
        const idActiveAsistencia = ref("");
        const listaActual = ref([]);
        const onActiveGroup = (item) => {
            activeGroup.value = item.denominacion;
            idActiveGroup.value = item.grupo;
            idActiveAsistencia.value = item.id;

            axios
                .get(route("asistencias.lista-asistencia"), {
                    params: {
                        asistencia: idActiveAsistencia.value,
                    },
                })
                .then((response) => {
                    listaActual.value = response.data.asistencia.asistencia_estudiante_detalle;
                });
        };

        const hideDialogAsistencia = () => {
            activeGroup.value = tmpActiveGroup.value;
        };
        const estado = ref("");
        const saveAsistencia = (e) => {
            saveLoading.value = true;
            estado.value = e;
            axios
                .post(route("asistencias.guardar-asistencia"), {
                    estudiante: estudiante.value,
                    asistencia: idActiveAsistencia.value,
                    estado: estado.value,
                })
                .then((response) => {
                    if (response.data.status) {
                        toast.add({
                            severity: "success",
                            summary: "¡Exito...!",
                            detail: response.data.message,
                            life: 5000,
                        });
                        searchDialog.value = false;
                        dni.value = "";
                    } else {
                        toast.add({
                            severity: "error",
                            summary: "¡Error!",
                            detail: response.data.message,
                            life: 5000,
                        });
                    }
                    saveLoading.value = false;
                });
        };
        // cerrar asistencia
        const cerrarAsistencia = () => {
            saveLoading.value = true;
            axios
                .post(route("asistencias.cerrar-asistencia"), {
                    asistencia: idActiveAsistencia.value,
                })
                .then((response) => {
                    if (response.data.status) {
                        toast.add({
                            severity: "success",
                            summary: "¡Exito...!",
                            detail: response.data.message,
                            life: 5000,
                        });
                        activeGroup.value = "";
                        listaActual.value = [];
                        aperturados.value = response.data.aperturados;
                    } else {
                        toast.add({
                            severity: "error",
                            summary: "¡Error!",
                            detail: response.data.message,
                            life: 5000,
                        });
                    }
                    saveLoading.value = false;
                });
        };
        const capitalize = (value) => {
            if (!value) return "";
            value = value.toLowerCase();
            value = value.toString();
            return value.charAt(0).toUpperCase() + value.slice(1);
        };

        return {
            title,
            menuTabs,
            activeMenu,
            estados,
            estado,
            searchDialog,
            form,
            saveLoading,
            aperturarAsistencia,
            tipoAsistencia,
            usuario,
            getSedes,
            getAreas,
            getTurnos,
            getGrupoAulas,
            areas,
            filteredAreas,
            searchAreas,
            changeArea,
            turnos,
            filteredTurnos,
            searchTurnos,
            changeTurno,
            grupos,
            filteredGrupos,
            searchGrupos,
            sedes,
            filteredSedes,
            searchSedes,
            changeSede,
            aperturados,
            activeGroup,
            scanQr,
            onActiveGroup,
            idActiveGroup,
            avatar,
            nombres,
            apellidos,
            idActiveAsistencia,
            errorSearch,
            grupoSede,
            hideDialogAsistencia,
            saveAsistencia,
            existeAsistencia,
            dni,
            listaActual,
            capitalize,
            cerrarAsistencia,
            baseUrl,
        };
    },
};
</script>
<style scoped>
.p-avatar.p-avatar-xl {
    width: 7rem;
    height: auto;
    font-size: 2rem;
}
</style>
