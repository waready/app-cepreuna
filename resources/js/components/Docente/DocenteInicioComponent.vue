<template>
    <Toast />
    <div class="flex lg:flex-row px-8 py-2 justify-content-center">
        <div class="card col-10 px-6">
            <div class="grid">
                <div class="flex flex-column col-12 text-center">
                    <h4>
                        <b> BIENVENIDO {{ docente.nombres }} {{ docente.paterno }} {{ docente.materno }}</b>
                    </h4>

                    <div class="flex flex-row justify-content-end">
                        <p><b>DNI:</b> {{ docente.dni }}</p>
                    </div>
                </div>
                <div class="text-justify mb-2 px-4">
                    <p>A continuación, observará sus expedientes.</p>
                </div>
            </div>
            <div class="grid">
                <DataTable stripedRows class="col-12" :value="datos">
                    <Column field="id" header="Nro"></Column>
                    <Column header="Estado">
                        <template #body="slotProps">
                            <!-- <div v-for="e in datosExpediente" :key="e"> -->
                            <p v-if="slotProps.data.t_estado == '0'"><Tag severity="info" icon="pi pi-folder-open">Pendiente</Tag></p>
                            <p v-if="slotProps.data.t_estado == '1'"><Tag severity="warning" icon="pi pi-inbox">En revisión</Tag></p>
                            <p v-if="slotProps.data.t_estado == '2'"><Tag severity="danger" icon="pi pi-exclamation-circle">Observado</Tag></p>
                            <p v-if="slotProps.data.t_estado == '3'"><Tag severity="success" icon="pi pi-check">Aprobado</Tag></p>
                            <p v-if="slotProps.data.t_estado == '4'"><Tag severity="warning" icon="pi pi-building">Derivado</Tag></p>
                            <!-- </div> -->
                        </template>
                    </Column>
                    <Column field="fecha_inicio" header="Fecha de Inicio"></Column>
                    <Column field="fecha_fin" header="Fecha de Finalización"></Column>
                    <Column field="inicio_ciclo" header="Periodo Inicio"></Column>
                    <Column field="fin_ciclo" header="Periodo Fin"></Column>
                    <Column style="min-width: 2rem" header="Horas">
                        <template #body="slotProps">
                            <Button icon="pi pi-clock" class="p-button-raised p-button-secondary" label="Ver Detalles" @click="verDetallesHoras(slotProps.data.id)" />
                        </template>
                    </Column>
                    <Column style="min-width: 2rem" header="Acciones">
                        <template #body="slotProps">
                            <Button
                                v-if="slotProps.data.t_estado == '0'"
                                icon="pi pi-upload"
                                class="p-button-raised p-button-warning"
                                label="Cargar Documentos"
                                @click="cargarDocumentos(slotProps.data.id)"
                            />
                            <Button
                                v-if="slotProps.data.t_estado == '2'"
                                icon="pi pi-exclamation-circle"
                                class="p-button-raised p-button-danger"
                                label="Actualizar Documentos"
                                @click="actualizarDocumentos(slotProps.data.id)"
                            />
                            <!-- <Button v-if="slotProps.data.t_estado == '2'" icon="pi pi-comment" class="p-button-raised p-button-secondary" label="Ver mensaje" @click="displayMensaje = true" /> -->
                            <Button v-if="slotProps.data.t_estado == '3'" icon="pi pi-check" class="p-button-raised p-button-success" label="Documentos Aprobados" />
                            <Button v-if="slotProps.data.t_estado == '3'" icon="pi pi-comment" class="p-button-raised p-button-secondary" label="Ver mensaje" @click="displayMensaje = true" />
                            <!-- <Button v-if="slotProps.data.t_estado == '4'" icon="pi pi-sitemap" class="p-button-raised p-button-success" label="Ver oficina derivada" /> -->
                            <!-- <Button v-if="slotProps.data.t_estado == '4'" icon="pi pi-comment" class="p-button-raised p-button-secondary" label="Ver mensaje" @click="displayMensaje = true" /> -->
                        </template>
                    </Column>
                </DataTable>
                <!-- Ver Detalles de Horas -->
                <Dialog header="Detalles de Horas" v-model:visible="displayHoras" :style="{ width: '50vw' }">
                    <DataTable stripedRows class="col-12" :value="horasDetalle">
                        <Column field="tp_denominacion" header="Denominación"></Column>
                        <Column field="hd_cantidad" header="Total"></Column>
                    </DataTable>
                </Dialog>
                <!-- Cargar Documentos -->
                <Dialog header="Documentos Requeridos para el Tramite de Pago Docente" v-model:visible="display" :style="{ width: '50vw' }">
                    <div v-for="me in mensajeExp" :key="me">
                        <Message>{{ me.t_mensaje }}</Message>
                    </div>
                    <div v-for="a in datos" :key="a">
                        <input type="hidden" :v-model="(form.id_expediente = a.id)" />
                    </div>
                    <div v-for="f in documentos" :key="f" class="grid mx-4 align-items-end mb-2 mb-2">
                        <div v-if="f.tipo_documento_id == '1'" class="col-12 md:col-12 px-5">
                            <div class="field grid p-fluid">
                                <label for="dni" class="col-12 font-bold">{{ f.denominacion }}</label>
                                <div class="col-12">
                                    <FileInput :size="2" is-pdf placeholder-button-text="Buscar" v-model="form.dni" placeholder-input-text="Seleccione Archivo" />
                                    <input type="hidden" :v-model="(form.id_dni = f.d_id)" />
                                    <small class="p-error" v-if="errors.dni">{{ errors.dni[0] }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-for="f in documentos" :key="f" class="grid mx-4 align-items-end mb-2">
                        <div v-if="f.tipo_documento_id == '2'" class="col-12 md:col-12 px-5">
                            <div class="field grid p-fluid">
                                <label for="suspencion" class="col-12 font-bold">{{ f.denominacion }}</label>
                                <div class="col-12">
                                    <FileInput :size="2" is-pdf placeholder-button-text="Buscar" v-model="form.suspencion" placeholder-input-text="Seleccione Archivo" />
                                    <input type="hidden" :v-model="(form.id_suspencion = f.d_id)" />
                                    <small class="p-error" v-if="errors.suspencion">{{ errors.suspencion[0] }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-for="f in documentos" :key="f" class="grid mx-4 align-items-end mb-2">
                        <div v-if="f.tipo_documento_id == '3'" class="col-12 md:col-12 px-5">
                            <div class="field grid p-fluid">
                                <label for="osce" class="col-12 font-bold">{{ f.denominacion }}</label>
                                <div class="col-12">
                                    <FileInput :size="2" is-pdf placeholder-button-text="Buscar" v-model="form.osce" placeholder-input-text="Seleccione Archivo" />
                                    <input type="hidden" :v-model="(form.id_osce = f.d_id)" />
                                    <small class="p-error" v-if="errors.osce">{{ errors.osce[0] }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-for="f in documentos" :key="f" class="grid mx-4 align-items-end mb-2">
                        <div v-if="f.tipo_documento_id == '4'" class="col-12 md:col-12 px-5">
                            <div class="field grid p-fluid">
                                <label for="formato1" class="col-12 font-bold">{{ f.denominacion }}</label>
                                <div class="col-12">
                                    <FileInput :size="2" is-pdf placeholder-button-text="Buscar" v-model="form.formato1" placeholder-input-text="Seleccione Archivo" />
                                    <input type="hidden" :v-model="(form.id_formato1 = f.d_id)" />
                                    <small class="p-error" v-if="errors.formato1">{{ errors.formato1[0] }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-for="f in documentos" :key="f" class="grid mx-4 align-items-end mb-2">
                        <div v-if="f.tipo_documento_id == '5'" class="col-12 md:col-12 px-5">
                            <div class="field grid p-fluid">
                                <label for="declaracion" class="col-12 font-bold">{{ f.denominacion }}</label>
                                <div class="col-12">
                                    <FileInput :size="2" is-pdf placeholder-button-text="Buscar" v-model="form.declaracion" placeholder-input-text="Seleccione Archivo" />
                                    <input type="hidden" :v-model="(form.id_declaracion = f.d_id)" />
                                    <small class="p-error" v-if="errors.declaracion">{{ errors.declaracion[0] }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-for="f in documentos" :key="f" class="grid mx-4 align-items-end mb-2">
                        <div v-if="f.tipo_documento_id == '6'" class="col-12 md:col-12 px-5">
                            <div class="field grid p-fluid">
                                <label for="informe" class="col-12 font-bold">{{ f.denominacion }}</label>
                                <div class="col-12">
                                    <FileInput :size="2" is-pdf placeholder-button-text="Buscar" v-model="form.informe" placeholder-input-text="Seleccione Archivo" />
                                    <input type="hidden" :v-model="(form.id_informe = f.d_id)" />
                                    <small class="p-error" v-if="errors.informe">{{ errors.informe[0] }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-for="f in documentos" :key="f" class="grid mx-4 align-items-end mb-2">
                        <div v-if="f.tipo_documento_id == '7'" class="col-12 md:col-12 px-5">
                            <div class="field grid p-fluid">
                                <label for="reciboHonorarios" class="col-12 font-bold">{{ f.denominacion }}</label>
                                <div class="col-12">
                                    <FileInput :size="2" is-pdf placeholder-button-text="Buscar" v-model="form.reciboHonorarios" placeholder-input-text="Seleccione Archivo" />
                                    <input type="hidden" :v-model="(form.id_reciboHonorarios = f.d_id)" />
                                    <small class="p-error" v-if="errors.reciboHonorarios">{{ errors.reciboHonorarios[0] }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- <div class="grid mx-4 align-items-end mb-2">
                        <div class="col-12 md:col-12 px-5">
                            <div class="field grid p-fluid">
                                <label for="Mensaje" class="col-12 font-bold">Mensaje</label>
                                <div class="col-12">
                                    <InputText type="text" id="mensaje" placeholder="Escriba aqui sus comentarios u observaciones." v-model="form.mensaje" />
                                    <small class="p-error" v-if="errors.mensaje">{{ errors.mensaje[0] }}</small>
                                </div>
                            </div>
                        </div>
                    </div> -->
                    <div class="flex justify-content-center">
                        <Button label="Enviar Documentos" class="p-button-raised p-button-primary-theme mt-3" @click="subirArchivos()" />
                    </div>
                    <!-- </div> -->
                </Dialog>
                <!-- Actualizar Documentos -->
                <Dialog header="Documentos Observados para el Tramite de Pago Docente" v-model:visible="displayAct" :style="{ width: '50vw' }">
                    <div v-for="a in datos" :key="a">
                        <input type="hidden" :v-model="(form.id_expediente = a.id)" />
                    </div>
                    <div v-for="f in documentos" :key="f" class="grid mx-4 align-items-end mb-2 mb-2">
                        <div v-if="f.tipo_documento_id == '1'" class="col-12 md:col-12 px-5">
                            <div class="field grid p-fluid">
                                <label for="dni" class="col-12 font-bold">{{ f.denominacion }}</label>
                                <div class="col-12" v-if="f.estado == '2'">
                                    <Message severity="error" :closable="false">Documento Observado</Message>
                                    <Accordion :activeIndex="0">
                                        <AccordionTab>
                                            <template #header>
                                                <i class="pi pi-exclamation-circle"></i>
                                                <span>Observaciones</span>
                                            </template>
                                            {{ f.observacion }}
                                        </AccordionTab>
                                    </Accordion>
                                    <FileInput :size="2" is-pdf placeholder-button-text="Buscar" v-model="form.dni" placeholder-input-text="Seleccione Archivo" />
                                    <input type="hidden" :v-model="(form.id_dni = f.d_id)" />
                                    <small class="p-error" v-if="errors.dni">{{ errors.dni[0] }}</small>
                                </div>
                                <div class="col-12" v-if="f.estado == '1'"><Message severity="success" :closable="false">Documento Aprobado</Message></div>
                            </div>
                        </div>
                    </div>
                    <div v-for="f in documentos" :key="f" class="grid mx-4 align-items-end mb-2">
                        <div v-if="f.tipo_documento_id == '2'" class="col-12 md:col-12 px-5">
                            <div class="field grid p-fluid">
                                <label for="suspencion" class="col-12 font-bold">{{ f.denominacion }}</label>
                                <div class="col-12" v-if="f.estado == '2'">
                                    <Message severity="error" :closable="false">Documento Observado</Message>
                                    <Accordion :activeIndex="0" class="mb-2">
                                        <AccordionTab>
                                            <template #header>
                                                <i class="pi pi-exclamation-circle"></i>
                                                <span>Observaciones</span>
                                            </template>
                                            {{ f.observacion }}
                                        </AccordionTab>
                                    </Accordion>
                                    <FileInput :size="2" is-pdf placeholder-button-text="Buscar" v-model="form.suspencion" placeholder-input-text="Seleccione Archivo" />
                                    <input type="hidden" :v-model="(form.id_suspencion = f.d_id)" />
                                    <small class="p-error" v-if="errors.suspencion">{{ errors.suspencion[0] }}</small>
                                </div>
                                <div class="col-12" v-if="f.estado == '1'"><Message severity="success" :closable="false">Documento Aprobado</Message></div>
                            </div>
                        </div>
                    </div>
                    <div v-for="f in documentos" :key="f" class="grid mx-4 align-items-end mb-2">
                        <div v-if="f.tipo_documento_id == '3'" class="col-12 md:col-12 px-5">
                            <div class="field grid p-fluid">
                                <label for="osce" class="col-12 font-bold">{{ f.denominacion }}</label>
                                <div class="col-12" v-if="f.estado == '2'">
                                    <Message severity="error" :closable="false">Documento Observado</Message>
                                    <Accordion :activeIndex="0" class="mb-2">
                                        <AccordionTab>
                                            <template #header>
                                                <i class="pi pi-exclamation-circle"></i>
                                                <span>Observaciones</span>
                                            </template>
                                            {{ f.observacion }}
                                        </AccordionTab>
                                    </Accordion>
                                    <FileInput :size="2" is-pdf placeholder-button-text="Buscar" v-model="form.osce" placeholder-input-text="Seleccione Archivo" />
                                    <input type="hidden" :v-model="(form.id_osce = f.d_id)" />
                                    <small class="p-error" v-if="errors.osce">{{ errors.osce[0] }}</small>
                                </div>
                                <div class="col-12" v-if="f.estado == '1'"><Message severity="success" :closable="false">Documento Aprobado</Message></div>
                            </div>
                        </div>
                    </div>
                    <div v-for="f in documentos" :key="f" class="grid mx-4 align-items-end mb-2">
                        <div v-if="f.tipo_documento_id == '4'" class="col-12 md:col-12 px-5">
                            <div class="field grid p-fluid">
                                <label for="formato1" class="col-12 font-bold">{{ f.denominacion }}</label>
                                <div class="col-12" v-if="f.estado == '2'">
                                    <Message severity="error" :closable="false">Documento Observado</Message>
                                    <Accordion :activeIndex="0" class="mb-2">
                                        <AccordionTab>
                                            <template #header>
                                                <i class="pi pi-exclamation-circle"></i>
                                                <span>Observaciones</span>
                                            </template>
                                            {{ f.observacion }}
                                        </AccordionTab>
                                    </Accordion>
                                    <FileInput :size="2" is-pdf placeholder-button-text="Buscar" v-model="form.formato1" placeholder-input-text="Seleccione Archivo" />
                                    <input type="hidden" :v-model="(form.id_formato1 = f.d_id)" />
                                    <small class="p-error" v-if="errors.formato1">{{ errors.formato1[0] }}</small>
                                </div>
                                <div class="col-12" v-if="f.estado == '1'"><Message severity="success" :closable="false">Documento Aprobado</Message></div>
                            </div>
                        </div>
                    </div>
                    <div v-for="f in documentos" :key="f" class="grid mx-4 align-items-end mb-2">
                        <div v-if="f.tipo_documento_id == '5'" class="col-12 md:col-12 px-5">
                            <div class="field grid p-fluid">
                                <label for="declaracion" class="col-12 font-bold">{{ f.denominacion }}</label>
                                <div class="col-12" v-if="f.estado == '2'">
                                    <Message severity="error" :closable="false">Documento Observado</Message>
                                    <Accordion :activeIndex="0" class="mb-2">
                                        <AccordionTab>
                                            <template #header>
                                                <i class="pi pi-exclamation-circle"></i>
                                                <span>Observaciones</span>
                                            </template>
                                            {{ f.observacion }}
                                        </AccordionTab>
                                    </Accordion>
                                    <FileInput :size="2" is-pdf placeholder-button-text="Buscar" v-model="form.declaracion" placeholder-input-text="Seleccione Archivo" />
                                    <input type="hidden" :v-model="(form.id_declaracion = f.d_id)" />
                                    <small class="p-error" v-if="errors.declaracion">{{ errors.declaracion[0] }}</small>
                                </div>
                                <div class="col-12" v-if="f.estado == '1'"><Message severity="success" :closable="false">Documento Aprobado</Message></div>
                            </div>
                        </div>
                    </div>
                    <div v-for="f in documentos" :key="f" class="grid mx-4 align-items-end mb-2">
                        <div v-if="f.tipo_documento_id == '6'" class="col-12 md:col-12 px-5">
                            <div class="field grid p-fluid">
                                <label for="informe" class="col-12 font-bold">{{ f.denominacion }}</label>
                                <div class="col-12" v-if="f.estado == '2'">
                                    <Message severity="error" :closable="false">Documento Observado</Message>
                                    <Accordion :activeIndex="0" class="mb-2">
                                        <AccordionTab>
                                            <template #header>
                                                <i class="pi pi-exclamation-circle"></i>
                                                <span>Observaciones</span>
                                            </template>
                                            {{ f.observacion }}
                                        </AccordionTab>
                                    </Accordion>
                                    <FileInput :size="2" is-pdf placeholder-button-text="Buscar" v-model="form.informe" placeholder-input-text="Seleccione Archivo" />
                                    <input type="hidden" :v-model="(form.id_informe = f.d_id)" />
                                    <small class="p-error" v-if="errors.informe">{{ errors.informe[0] }}</small>
                                </div>
                                <div class="col-12" v-if="f.estado == '1'"><Message severity="success" :closable="false">Documento Aprobado</Message></div>
                            </div>
                        </div>
                    </div>
                    <div v-for="f in documentos" :key="f" class="grid mx-4 align-items-end mb-2">
                        <div v-if="f.tipo_documento_id == '7'" class="col-12 md:col-12 px-5">
                            <div class="field grid p-fluid">
                                <label for="reciboHonorarios" class="col-12 font-bold">{{ f.denominacion }}</label>
                                <div class="col-12" v-if="f.estado == '2'">
                                    <Message severity="error" :closable="false">Documento Observado</Message>
                                    <Accordion :activeIndex="0" class="mb-2">
                                        <AccordionTab>
                                            <template #header>
                                                <i class="pi pi-exclamation-circle"></i>
                                                <span>Observaciones</span>
                                            </template>
                                            {{ f.observacion }}
                                        </AccordionTab>
                                    </Accordion>
                                    <FileInput :size="2" is-pdf placeholder-button-text="Buscar" v-model="form.reciboHonorarios" placeholder-input-text="Seleccione Archivo" />
                                    <input type="hidden" :v-model="(form.id_reciboHonorarios = f.d_id)" />
                                    <small class="p-error" v-if="errors.reciboHonorarios">{{ errors.reciboHonorarios[0] }}</small>
                                </div>
                                <div class="col-12" v-if="f.estado == '1'"><Message severity="success" :closable="false">Documento Aprobado</Message></div>
                            </div>
                        </div>
                    </div>
                    <!-- <div class="grid mx-4 align-items-end mb-2">
                        <div class="col-12 md:col-12 px-5">
                            <div class="field grid p-fluid">
                                <label for="Mensaje" class="col-12 font-bold">Mensaje</label>
                                <div class="col-12">
                                    <InputText type="text" id="mensaje" placeholder="Escriba aqui sus comentarios u observaciones." v-model="form.mensaje" />
                                    <small class="p-error" v-if="errors.mensaje">{{ errors.mensaje[0] }}</small>
                                </div>
                            </div>
                        </div>
                    </div> -->
                    <div class="flex justify-content-center">
                        <Button label="Actualizar Documentos" class="p-button-raised p-button-primary-theme mt-3" @click="actualizarArchivos()" />
                    </div>
                </Dialog>
                <Dialog header="Mensaje" v-model:visible="displayMensaje" :style="{ width: '50vw' }" class="flex flex-column col-12 text-center">
                    <div v-for="a in datos" :key="a">
                        <div v-if="(a.t_estado = '3')">
                            <p>Imprima sus documentos y presentelos en físico en las oficinas de administracion.</p>
                        </div>
                        <div v-else>
                            <div v-for="f in datos" :key="f">
                                <p>{{ f.mensaje }}</p>
                            </div>
                        </div>
                    </div>
                </Dialog>
            </div>
        </div>
    </div>
</template>

<script>
import { useToast } from "primevue/usetoast";
import { useForm } from "@inertiajs/inertia-vue3";
import { ref, onMounted, watch, toRefs } from "vue";
import { Inertia } from "@inertiajs/inertia";
import Dialog from "primevue/dialog";
import Accordion from "primevue/accordion";
import AccordionTab from "primevue/accordiontab";
import Tag from "primevue/tag";
import InputText from "primevue/inputtext";
import FileInput from "@/components/FileInput.vue";
import axios from "axios";

export default {
    components: {
        FileInput,
        Dialog,
        Accordion,
        AccordionTab,
        Tag,
        InputText,
    },
    props: { docente: Object, datosExpediente: Object },
    setup(props, { emit }) {
        const toast = useToast();
        const { docente, datosExpediente } = toRefs(props);
        const datos = ref(datosExpediente.value);
        // const datosDocs = ref(docsExpediente.value);
        const errors = ref({});
        const form = useForm({
            dni: null,
            suspencion: null,
            osce: null,
            formato1: null,
            declaracion: null,
            informe: null,
            reciboHonorarios: null,
            docente: {},
            datosExpediente: {},
            id_expediente: null,
            // mensaje: null,
            id_dni: null,
            id_suspencion: null,
            id_osce: null,
            id_formato1: null,
            id_informe: null,
            id_reciboHonorarios: null,
            id_declaracion: null,
            // docsExpediente: {},
        });
        // const cargarArchivos = () => {
        //     axios
        //         .get(route("tramitePago.docsRequeridos"))
        //         .then((response) => {
        //             console.log(response.data);
        //         })
        //         .catch((e) => {
        //             console.log(e.response);
        //         });
        // };

        const displayHoras = ref(false);
        const horasDetalle = ref([]);
        const verDetallesHoras = (id) => {
            displayHoras.value = true;

            axios
                .get(route("tramitePago.get-detalles-horas", id))
                .then((response) => {
                    // console.log(response.data);
                    horasDetalle.value = response.data.eDetalles;
                    console.log(horasDetalle.value);
                })
                .catch((error) => {
                    console.log(error.response);
                    if (error.response.status === 422) {
                        errors.value = error.response.data.errors;
                    }
                });
        };

        const display = ref(false);
        const documentos = ref([]);
        const mensajeExp = ref();

        const cargarDocumentos = (id) => {
            display.value = true;

            axios
                .get(route("tramitePago.get-documentos-expediente", id))
                .then((response) => {
                    // console.log(response.data);
                    documentos.value = response.data.documentos;
                    mensajeExp.value = response.data.mensaje;
                })
                .catch((error) => {
                    console.log(error.response);
                    if (error.response.status === 422) {
                        errors.value = error.response.data.errors;
                    }
                });
        };

        const displayAct = ref(false);
        // const documentosAct = ref([]);
        const actualizarDocumentos = (id) => {
            displayAct.value = true;

            axios
                .get(route("tramitePago.get-actualizar-documentos-expediente", id))
                .then((response) => {
                    // console.log(response.data);
                    documentos.value = response.data.documentos;
                })
                .catch((error) => {
                    console.log(error.response);
                    if (error.response.status === 422) {
                        errors.value = error.response.data.errors;
                    }
                });
        };
        const displayMensaje = ref(false);
        // const displayMensaje1 = ref(false);
        // const mensaje = ref();
        // const verMensaje = (id) => {
        //     displayMensaje1.value = true;
        //     axios
        //         .get(route("tramitePago.get-mensaje", id))
        //         .then((response) => {
        //             // console.log(response.data);
        //             mensaje.value = response.data.mensaje;
        //             console.log(mensaje.value);
        //         })
        //         .catch((error) => {
        //             console.log(error.response);
        //             if (error.response.status === 422) {
        //                 errors.value = error.response.data.errors;
        //             }
        //         });
        // };

        const subirArchivos = () => {
            // form.docente = docente.value;

            let formData = new FormData();
            let formDocente = JSON.stringify(docente.value);
            let formExpediente = JSON.stringify(form.id_expediente);
            let formMensaje = JSON.stringify(form.mensaje);
            let formDni = JSON.stringify(form.dni);
            let formSuspencion = JSON.stringify(form.suspencion);
            let formOSCE = JSON.stringify(form.osce);
            let formFormato1 = JSON.stringify(form.formato1);
            let formDeclaracion = JSON.stringify(form.declaracion);
            let formInforme = JSON.stringify(form.informe);
            let formReciboHonorarios = JSON.stringify(form.reciboHonorarios);
            let id_dni = JSON.stringify(form.id_dni);
            let id_suspencion = JSON.stringify(form.id_suspencion);
            let id_declaracion = JSON.stringify(form.id_declaracion);
            let id_informe = JSON.stringify(form.id_informe);
            let id_formato1 = JSON.stringify(form.id_formato1);
            let id_reciboHonorarios = JSON.stringify(form.id_reciboHonorarios);
            let id_osce = JSON.stringify(form.id_osce);

            formData.append("docente", formDocente);
            formData.append("id_expediente", form.id_expediente);
            if (form.mensaje != null) {
                formData.append("mensaje", form.mensaje);
            }
            if (form.dni != null) {
                formData.append("dni", form.dni.file);
                formData.append("id_dni", form.id_dni);
            }
            if (form.suspencion != null) {
                formData.append("suspencion", form.suspencion.file);
                formData.append("id_suspencion", form.id_suspencion);
            }
            if (form.reciboHonorarios != null) {
                formData.append("reciboHonorarios", form.reciboHonorarios.file);
                formData.append("id_reciboHonorarios", form.id_reciboHonorarios);
            }
            if (form.osce != null) {
                formData.append("osce", form.osce.file);
                formData.append("id_osce", form.id_osce);
            }
            if (form.formato1 != null) {
                formData.append("formato1", form.formato1.file);
                formData.append("id_formato1", form.id_formato1);
            }
            if (form.declaracion != null) {
                formData.append("declaracion", form.declaracion.file);
                formData.append("id_declaracion", form.id_declaracion);
            }
            if (form.informe != null) {
                formData.append("informe", form.informe.file);
                formData.append("id_informe", form.id_informe);
            }

            axios
                .post(route("tramitePago.subir"), formData)
                .then((response) => {
                    if (response.data.status) {
                        toast.add({
                            severity: "success",
                            summary: "¡Exito...!",
                            detail: response.data.message,
                            life: 5000,
                        });
                        // docente.value = response.data.docente;
                        emit("statusForm", { status: true, docente: docente.value });
                        datos.value = response.data.datosExpediente;
                        display.value = false;
                        form.reset();
                    } else {
                        toast.add({
                            severity: "error",
                            summary: "¡Error!",
                            detail: response.data.message,
                            life: 5000,
                        });
                        emit("statusForm", { status: false });
                    }
                })
                .catch((error) => {
                    console.log(error.response);
                    if (error.response.status === 422) {
                        errors.value = error.response.data.errors;
                    }
                });
        };

        const actualizarArchivos = () => {
            let formData = new FormData();
            let formDocente = JSON.stringify(docente.value);
            let formExpediente = JSON.stringify(form.id_expediente);
            let formMensaje = JSON.stringify(form.mensaje);
            let formDni = JSON.stringify(form.dni);
            let formSuspencion = JSON.stringify(form.suspencion);
            let formOSCE = JSON.stringify(form.osce);
            let formFormato1 = JSON.stringify(form.formato1);
            let formDeclaracion = JSON.stringify(form.declaracion);
            let formInforme = JSON.stringify(form.informe);
            let formReciboHonorarios = JSON.stringify(form.reciboHonorarios);
            let id_dni = JSON.stringify(form.id_dni);
            let id_suspencion = JSON.stringify(form.id_suspencion);
            let id_declaracion = JSON.stringify(form.id_declaracion);
            let id_informe = JSON.stringify(form.id_informe);
            let id_formato1 = JSON.stringify(form.id_formato1);
            let id_reciboHonorarios = JSON.stringify(form.id_reciboHonorarios);
            let id_osce = JSON.stringify(form.id_osce);

            formData.append("docente", formDocente);
            formData.append("id_expediente", form.id_expediente);
            if (form.mensaje != null) {
                formData.append("mensaje", form.mensaje);
            }
            if (form.dni != null) {
                formData.append("dni", form.dni.file);
                formData.append("id_dni", form.id_dni);
            }
            if (form.suspencion != null) {
                formData.append("suspencion", form.suspencion.file);
                formData.append("id_suspencion", form.id_suspencion);
            }
            if (form.reciboHonorarios != null) {
                formData.append("reciboHonorarios", form.reciboHonorarios.file);
                formData.append("id_reciboHonorarios", form.id_reciboHonorarios);
            }
            if (form.osce != null) {
                formData.append("osce", form.osce.file);
                formData.append("id_osce", form.id_osce);
            }
            if (form.formato1 != null) {
                formData.append("formato1", form.formato1.file);
                formData.append("id_formato1", form.id_formato1);
            }
            if (form.declaracion != null) {
                formData.append("declaracion", form.declaracion.file);
                formData.append("id_declaracion", form.id_declaracion);
            }
            if (form.informe != null) {
                formData.append("informe", form.informe.file);
                formData.append("id_informe", form.id_informe);
            }

            axios
                .post(route("tramitePago.actualizar-documentos-expediente"), formData)
                .then((response) => {
                    if (response.data.status) {
                        toast.add({
                            severity: "success",
                            summary: "¡Exito...!",
                            detail: response.data.message,
                            life: 5000,
                        });
                        // docente.value = response.data.docente;
                        emit("statusForm", { status: true, docente: docente.value });
                        datos.value = response.data.datosExpediente;
                        displayAct.value = false;
                        form.reset();
                    } else {
                        toast.add({
                            severity: "error",
                            summary: "¡Error!",
                            detail: response.data.message,
                            life: 5000,
                        });
                        emit("statusForm", { status: false });
                    }
                })
                .catch((error) => {
                    console.log(error.response);
                    if (error.response.status === 422) {
                        errors.value = error.response.data.errors;
                    }
                });
        };

        return {
            form,
            errors,
            subirArchivos,
            datos,
            cargarDocumentos,
            actualizarDocumentos,
            actualizarArchivos,
            verDetallesHoras,
            // verMensaje,
            displayHoras,
            horasDetalle,
            display,
            documentos,
            mensajeExp,
            displayAct,
            // displayMensaje1,
            displayMensaje,
        };
    },
};
</script>
