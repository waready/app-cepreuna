<template>
    <Toast />
    <app-layout :title="title" :mode="2">
        <div class="card shadow-6">
            <div class="grid hidden sm:flex">
                <div class="col-12">
                    <h5 class="font-semibold text-center">Asistencia de Docentes</h5>
                </div>
            </div>
            <!-- vistas del menu inferior -->
            <div :class="menuTabs[0].isActive == false ? 'hidden' : 'grid'">
                <div class="font-semibold col-12 text-center">
                    {{ menuTabs[0].title }}
                    <hr />
                </div>
                <div class="col-12">
                    <!-- <h5>Regular</h5> -->
                    <Panel :header="sede.sede" :toggleable="true" v-for="(sede, index) in sedes" :key="index">
                        <template #header>
                            {{sede.sede}}
                            <button class="p-panel-header-icon p-link mr-2" @click="toggle">
                                <!-- <span class="pi pi-cog"></span> -->
                            </button>
                        </template>
                        <!-- <p> -->
                            <DataTable :value="sede.grupos" responsiveLayout="scroll">
                                <Column field="grupo" header="Grupo"></Column>
                                <Column header="Opciones">
                                    <template #body="slotProps">
                                        <!-- <span :class="'product-badge status-' + slotProps.data.inventoryStatus.toLowerCase()">{{slotProps.data.inventoryStatus}}</span> -->
                                        <!-- <a :href="slotProps.data.id">Descargar Parte</a> -->
                                        <a :href="route('reportes.parte-docente-pdf',{grupo:slotProps.data.id})" download><Button label="Exportar pdf" class="p-button-outlined p-button-danger p-mr-2" icon="pi pi-file-pdf" /></a>
                                    </template>
                                </Column>
                            </DataTable>
                        <!-- </p> -->
                    </Panel>
                </div>
            </div>
            <div :class="menuTabs[1].isActive == false ? 'hidden' : 'grid'">
                <div class="font-semibold col-12 text-center">
                    {{ menuTabs[1].title }}
                    <hr class="mb-0" />
                </div>
                <div class="font-semibold col-12 text-center">
                    <div class="card m-0 p-0">
                        <div class="grid">
                            <div class="col-12">
                                <div class="grid p-fluid">
                                    <div class="col-12">
                                        <div class="p-inputgroup">
                                            <InputText v-model.trim="ids" placeholder="Ingrese Codigos" autofocus />
                                            <Button icon="pi pi-search" class="p-button-warning" @click="scanQr(ids)" />
                                        </div>
                                    </div>
                                </div>
                                <qr-code-component @scan-qr="scanQr"></qr-code-component>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 text-center">
                    <!-- <Button
                        v-for="aperturado in aperturados"
                        :key="aperturado.id"
                        :label="aperturado.denominacion"
                        class="p-button-rounded p-button-secondary p-button-sm m-1"
                        :disabled="aperturado.estado == '1' ? false : true"
                        @click="onActiveGroup(aperturado)"
                    /> -->
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
        <Dialog v-model:visible="asistenciaDialog" :style="{ width: '700px' }"  header="Detalle de Asistencia" position="top"  :modal="true" class="p-fluid bg-info">
            <form @submit.prevent="" action="" autocomplete="off">
                <!-- <div v-if="errorSearch" class="grid">
                    <Message severity="error">{{ errorSearch }}</Message>
                </div> -->
                <div class="grid" style="margin-top:-20px">
                    <div class="col-3 pb-0">
                        <p class="m-0 font-bold">Docente:</p>
                    </div>
                    <div class="col-9 pb-0">
                        <p class="m-0 text-base line-height-1">{{docente}}</p>
                    </div>
                    <div class="col-3 pb-0">
                        <p class="m-0 font-bold">Telefono:</p>
                    </div>
                    <div class="col-9 pb-0">
                        <p class="m-0 text-base">{{telefono}}</p>
                    </div>
                    <!-- <div class="col-4">
                        <p class="m-0 font-bold">{{docente}}</p>
                        <p class="m-0 text-base">{{telefono}}</p>
                    </div> -->
                    <!-- <h6 class="font-bold col-12 my-0">Link de Meet</h6> -->
                    <!-- <a :href="link" target="_blank" class="col-12"><Button label="Ver Clase Meet" class="p-button-outlined" icon="pi pi-desktop" iconPos="left" /></a> -->
                    <h6 class="font-bold col-12 my-0">Tema</h6>
                    <div class="col-12">
                        <div class="col-12 md:col-12" v-if="statusTema">
                            <InlineMessage severity="success">{{sesion.tema}}</InlineMessage>
                        </div>
                        <div class="grid" v-if="!statusTema">
                            <div class="field col-12 mb-0 py-0">
                                <!-- <label for="username1">Fecha Tema</label> -->
                                <Textarea v-model="form.tema" :autoResize="true" rows="2" cols="30" />
                                <div v-if="errors && errors.tema" class="p-error">
                                    {{ errors.tema }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- <h6 class="font-bold col-12 my-0">Estado</h6> -->
                    <SelectButton class="col-12" v-model="form.status" :options="optionsEstado" dataKey="value">
                        <template #option="slotProps">
                            {{slotProps.option.name}}
                        </template>
                    </SelectButton>
                    <div class="col-12">
                        <div class="grid pt-3">
                            <div class="col-6">
                                <span class="p-float-label">
                                    <InputNumber id="cantidadHoras"  v-model="form.horasAsistidas"  />
                                    <label for="horasAsistidas">Cantidad Horas</label>
                                </span>
                                <div v-if="errors && errors.horasAsistidas" class="p-error">
                                    {{ errors.horasAsistidas }}
                                </div>
                                <!-- <h6 class="font-bold my-1">Cantidad Horas</h6>
                                <InputText  v-model="form.horasAsistidas" /> -->
                            </div>
                            <div class="col-6">
                                <span class="p-float-label">
                                    <InputNumber id="cantidadEstudiantes"  v-model="form.cantidadEstudiantes" />
                                    <label for="cantidadEstudiantes">Cantidad Estudiantes</label>
                                </span>
                                <div v-if="errors && errors.cantidadEstudiantes" class="p-error">
                                    {{ errors.cantidadEstudiantes }}
                                </div>
                                <!-- <h6 class="font-bold my-1">Cantidad Estudiantes</h6>
                                <InputText type="text" v-model="form.cantidadEstudiantes" /> -->
                            </div>
                        </div>
                    </div>
                    <!-- <h6 class="font-bold col-12 my-0">Archivo</h6> -->
                    <!-- <div class="col-12">
                        <FileUpload
                            name="imagen"
                            url="https://test.cepreuna.edu.pe/asistencia/docente/externo/image"
                            @upload="onUpload"
                            accept="image/*"
                            :maxFileSize="1000000"
                            fileLimit="1"
                            chooseLabel="Cargar"
                            uploadLabel="Enviar"
                            cancelLabel=" "
                            invalidFileSizeMessage="{0}: la imagen pesa mas de {1}."
                        >
                            <template #empty>
                                <p>Drag and drop files to here to upload.</p>
                            </template>
                        </FileUpload>

                    </div> -->
                    <!-- <div class="col-6">
                        <input id="imagen" type="file" name="imagen" style="display: none" @change="changeImage" accept="image/png, image/jpeg" />
                        <label for="imagen" class="p-button p-button-warning"
                            ><span class="pi pi-plus" style="margin-right: 5px"></span> Imagen
                        </label>
                    </div>
                    <div class="col-6">
                        <div class="grid">
                            <div class="col-12">
                                <div style="position: relative">
                                    <Button
                                        :class="buttonDelete ? '' : 'display-button'"
                                        @click="removeImage"
                                        icon="pi pi-times"
                                        class="p-button-rounded p-button-danger"
                                        style="position: absolute; top: -10px; right: 0"
                                    />
                                    <img class="p-col-12" :src="previewImage" style="max-width: 100%" />
                                </div>
                            </div>
                        </div>
                    </div> -->
                    <div class="col-12">
                        <div class="grid">
                            <div class="col-12">
                                <span class="p-float-label">
                                    <Textarea id="Observacion" v-model="form.observacion" :autoResize="true" rows="2" cols="30" />
                                    <label for="Observacion">Observación</label>
                                </span>
                                <!-- <h6 class="font-bold my-1">Observación</h6>
                                <Textarea v-model="form.observacion" :autoResize="true" rows="2" cols="30" /> -->
                            </div>
                        </div>
                    </div>

                </div>
            </form>
            <template #footer>
                <Button label="Ver Meet" icon="pi pi-desktop" iconPos="left" @click="showMeet" class="p-button-sm md:p-button" />
                <Button label="Cerrar" icon="pi pi-times" @click="closeBasic" class="p-button-outlined p-button-secondary p-button-sm md:p-button"/>
                <Button label="Guardar" icon="pi pi-check" @click="saveAsistencia" autofocus :loading="saveLoading"  class="p-button-success p-button-sm md:p-button lg:p-button xl:p-button"/>
            </template>
        </Dialog>
        <!-- ver registro-->
        <Dialog v-model:visible="checkAsistenciaDialog" :style="{ width: '700px' }" header="Detalle de Asistencia" position="top" :modal="true" class="p-fluid bg-success">
            <form @submit.prevent="" action="" autocomplete="off">
                <div class="grid" style="margin-top:-20px">
                    <div class="col-3 pb-0">
                        <p class="m-0 font-bold">Docente:</p>
                    </div>
                    <div class="col-9 pb-0">
                        <p class="m-0 text-base line-height-1">{{docente}}</p>
                    </div>
                    <div class="col-3 pb-0">
                        <p class="m-0 font-bold">Telefono:</p>
                    </div>
                    <div class="col-9 pb-0">
                        <p class="m-0 text-base">{{telefono}}</p>
                    </div>
                    <!-- <div class="col-4">
                        <p class="m-0 font-bold">{{docente}}</p>
                        <p class="m-0 text-base">{{telefono}}</p>
                    </div> -->
                    <!-- <h6 class="font-bold col-12 my-0">Link de Meet</h6> -->
                    <!-- <a :href="link" target="_blank" class="col-12"><Button label="Ver Clase Meet" class="p-button-outlined" icon="pi pi-desktop" iconPos="left" /></a> -->
                    <h6 class="font-bold col-12 my-0">Tema</h6>
                    <div class="col-12 md:col-12">
                        <InlineMessage severity="success">{{asistencia.sesiones.tema}}</InlineMessage>
                    </div>
                    <!-- <h6 class="font-bold col-12 my-0">Estado</h6> -->
                    <div class="col-6 col-offset-3" v-if="asistencia.estado=='1'">
                        <InlineMessage severity="success">Prensente</InlineMessage>
                    </div>
                    <div class="col-6 col-offset-3" v-if="asistencia.estado=='2'">
                        <InlineMessage severity="warn">Tarde</InlineMessage>
                    </div>
                    <div class="col-6 col-offset-3" v-if="asistencia.estado=='3'">
                        <InlineMessage severity="error">Falta</InlineMessage>
                    </div>

                    <div class="col-12">
                        <div class="grid">
                            <div class="col-6">
                                <h6 class="font-bold my-1">Cantidad Horas</h6>
                                <p>{{asistencia.horas_pago}}</p>
                            </div>
                            <div class="col-6">
                                <h6 class="font-bold my-1">Cantidad Estudiantes</h6>
                                <p>{{asistencia.cantidad_estudiantes}}</p>
                            </div>
                        </div>
                    </div>
                    <!-- <h6 class="font-bold col-12 my-0">Archivo</h6> -->
                    <!-- <div class="col-6">
                        <div class="grid">
                            <div class="col-12">
                                <div style="position: relative">
                                    <Button
                                        :class="buttonDelete ? '' : 'display-button'"
                                        @click="removeImage"
                                        icon="pi pi-times"
                                        class="p-button-rounded p-button-danger"
                                        style="position: absolute; top: -10px; right: 0"
                                    />
                                    <img class="p-col-12" :src="previewImage" style="max-width: 100%" />
                                </div>
                            </div>
                        </div>

                    </div> -->
                    <div class="col-12">
                        <div class="grid">
                            <div class="col-12">
                                <label for="Observacion">Observación</label>
                                <p>{{asistencia.observacion}}</p>
                                <!-- <h6 class="font-bold my-1">Observación</h6>
                                <Textarea v-model="form.observacion" :autoResize="true" rows="2" cols="30" /> -->
                            </div>
                        </div>
                    </div>

                </div>
            </form>
            <template #footer>
                <Button label="Ver Meet" icon="pi pi-desktop" iconPos="left" @click="showMeet" class="p-button-sm md:p-button" />
                <Button label="Cerrar" icon="pi pi-times" @click="closeBasic" class="p-button-secondary p-button-sm md:p-button"/>
            </template>
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

// import { QrcodeStream, QrcodeDropZone, QrcodeCapture } from "vue-qrcode-reader";
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
        let today = new Date();

        const title = ref("Asistencia de Docentes");
        const toast = useToast();
        const menu = ref(null);
        const { response, data } = toRefs(props);
        const menuTabs = ref([
            {
                title: "Iniciar Asistencia",
                icon: "pi pi-check-square",
                isActive: false,
                menuTitle: "Iniciar",
            },
            {
                title: "Escanear QR",
                icon: "pi pi-qrcode",
                isActive: true,
                menuTitle: "Escanear",
            },

        ]);
        const activeMenu = (i) => {
            menuTabs.value.forEach((m) => {
                m.isActive = false;
            });
            menuTabs.value[i].isActive = true;
        };

        const sedes = ref([]);
        const getGrupoAulas = () => {
            axios
                .get(route("recursos.get-grupo-aulas-auxiliar-agrupado"), {
                    // params: {
                    //     area: form.area ? form.area.id : "",
                    //     turno: form.area ? form.turno.id : "",
                    //     sede: form.area ? form.sede.id : "",
                    // },
                })
                .then((response) => {
                    // this.grupo = response.data[0].id;
                    sedes.value = response.data.sedes;
                });
        };
        const form = useForm({
            status: {name:"Presente",value:1},
            horasAsistidas:0,
            cantidadEstudiantes:0,
            status_tema:2,
            tema:"",
            sesion:"",
            imagen:"",
            estado:1,
            observacion:"",
        });
        // const errors = [];
        const optionsEstado = ref([
            {name: 'Presente', value: 1},
            {name: 'Tarde', value: 2},
            {name: 'Falta', value: 3},
        ]);
        // QR Scan
        const asistenciaDialog = ref(false);
        const checkAsistenciaDialog = ref(false);
        const asistencia = ref([]);
        const ids = ref("");
        const docente = ref("");
        const telefono = ref("");
        const link = ref("");
        const sesion = ref("");
        const statusTema = ref(false);
        const tema = ref("");
        const horasAsistidas = ref(0);
        const items = ref([]);
        // const estudiante = ref("");
        // const grupoSede = ref("");
        // const tmpActiveGroup = ref("");
        // const errorSearch = ref("");
        // const existeAsistencia = ref("");
        const scanQr = (item) => {
            const items = item.split("-");
            form.carga = items[0];
            form.docente = items[1];
            // console.log(items)

            axios
            .get(route("recursos.get-sesiones"), {
                params: {
                    carga: items[0],
                    docente: items[1]
                    // fecha: idActiveAsistencia.value,
                },
            })
            .then((response) => {


                if(response.data.status_carga){
                    docente.value = response.data.carga.docente.paterno + " " + response.data.carga.docente.materno + " " + response.data.carga.docente.nombres;
                    telefono.value = response.data.carga.docente.celular;
                    link.value = response.data.carga.link;
                    // horasAsistidas.value = response.data.horasAsistidas;
                    sesion.value = response.data.sesion;
                    if (sesion.value) {
                        statusTema.value = true;
                        form.tema = sesion.value.tema;
                        form.sesion = sesion.id;
                    } else {
                        form.tema = "";
                        statusTema.value = false;
                        form.sesion = "";
                    }
                    form.horasAsistidas = response.data.cantidadHoras;

                    if(response.data.status){
                        checkAsistenciaDialog.value = true;
                        asistencia.value = response.data.asistencia;
                    }else{

                        asistenciaDialog.value = true;
                    }
                }else{
                    toast.add({
                        severity: "error",
                        summary: "¡Aviso...!",
                        detail: 'La carga Academica no corresponde al dia',
                        life: 5000,
                    });
                }

            });

        };
        // imagen
        const buttonDelete = ref(false);
        const previewImage = ref("/assets/layout/images/default-image.svg");

        const changeImage = (event) => {
            previewImage.value = "/assets/layout/images/default-image.svg";
            previewImage.value = URL.createObjectURL(event.target.files[0]);
            buttonDelete.value = true;
            form.imagen = event.target.files[0];
        };
        const removeImage = () => {
            buttonDelete.value = false;
            previewImage.value = "/assets/layout/images/default-image.svg";
            form.imagen = "";
        };
        //*
        const closeBasic = () => {
            asistenciaDialog.value = false;
            checkAsistenciaDialog.value = false;
        }
        const showMeet = () => {
            const win = window.open(link.value, '_blank');
            win.focus();
        }
        //guardar Asistentecia
        const saveLoading = ref(false);

        const saveAsistencia = (e) => {
            saveLoading.value = true;
            if(!statusTema.value){
                form.status_tema = 2;
            }else{
                form.status_tema = 1;
            }
            form.estado = form.status.value;
            // console.log(form.status.value);
            // estado.value = e;
            Inertia.post(route("asistencias-docentes.store"), form, {
                onSuccess: () => {
                    // saveLoading.value = true;
                    // console.log(response);
                    if (response.value.status) {
                        // loadLazyData();
                        toast.add({
                            severity: "success",
                            summary: "¡Exito...!",
                            detail: response.value.message,
                            life: 5000,
                        });
                        // previewImage.value = "/assets/layout/images/default-image.svg";
                        // buttonDelete.value = false;

                        // cart.value = data.value.recursos;
                        asistenciaDialog.value = false;
                        // errors.value = [];
                        form.reset();
                        ids.value='';

                        saveLoading.value = false;
                        // buyForm.voucher = vouchers.value[0];
                        // registerBuyDialog.value = false;
                        // submitted.value = false;
                    } else {
                        toast.add({
                            severity: "error",
                            summary: "¡Error!",
                            detail: response.value.message,
                            life: 5000,
                        });
                        saveLoading.value = false;
                    }
                },
                onError: () => {
                    // console.log(errors);
                    // errors.value = errors;
                    saveLoading.value = false;
                    // registerBuyDialog.value = false;
                    // submitted.value = false;
                },
            });
        };
        onMounted(() => {
            getGrupoAulas();
        });
        const toggle = (event) => {
            menu.value.toggle(event);
        };

        const onUpload = (response) => {
            // toast.add({severity: 'info', summary: 'Success', detail: 'File Uploaded', life: 3000});
            console.log(response);
        }
        return {
            title,
            menuTabs,
            activeMenu,
            toggle,
            sedes,
            scanQr,
            closeBasic,
            ids,
            // estados,
            // estado,
            checkAsistenciaDialog,
            asistenciaDialog,
            form,
            asistencia,
            // errors,
            optionsEstado,
            docente,
            telefono,
            link,
            sesion,
            statusTema,
            tema,
            horasAsistidas,

            changeImage,
            previewImage,
            buttonDelete,
            removeImage,

            showMeet,
            saveLoading,
            saveAsistencia,
            onUpload,
            // aperturarAsistencia,
            // tipoAsistencia,

        };
    },
};
</script>
<style>
</style>
