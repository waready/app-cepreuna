<template>
    <div class="grid">
        <Toast />
        <div class="col-12 text-center">
            <Button class="p-button-sm" label="Añadir Voucher" icon="pi pi-plus" @click="openModal = true" />
        </div>

        <Dialog v-model:visible="openModal" :style="{ width: '500px' }" header="Validar Pago" :modal="true" position="top" class="bg-info">
            <div class="grid" style="margin-top: -20px">
                <Message severity="info" :closable="false">
                    <small> validar el pago debe transcurrir un día desde el deposito ó <b>realizar el pago con un día de anticipación.</b> </small>
                </Message>
                <div class="col-12 md:12" role="alert">
                    <small class=""
                        ><i class="fa fa-info-circle"></i><b> Usted realizo el pago mediante <a href="https://pagalo.pe/">pagalo.pe</a></b>
                        <input type="checkbox" v-model="fields.pagadoConPagalo" name="pagadoConPagalo" id="pagadoConPagalo" />
                    </small>
                </div>
                <div class="col-12 md:col-6">
                    <div class="grid">
                        <div class="col-12">
                            <label for="">N° de Documento</label>
                            <div class="p-inputgroup">
                                <InputText type="text" v-model="fields.dni" :value="$page.props.usuario.dni" disabled />
                            </div>
                            <small class="p-error" v-if="errors.dni">{{ errors.dni[0] }}</small>
                        </div>
                        <div class="col-12">
                            <label for="">Secuencia</label>
                            <div class="p-inputgroup">
                                <InputText type="text" v-model="fields.secuencia" />
                            </div>
                            <small class="p-error" v-if="errors.secuencia">{{ errors.secuencia[0] }}</small>
                        </div>
                        <div class="col-12">
                            <label for="">Monto</label>
                            <div class="p-inputgroup">
                                <InputText type="text" v-model="fields.monto" />
                            </div>
                            <small class="p-error" v-if="errors.monto">{{ errors.monto[0] }}</small>
                        </div>
                        <div class="col-12">
                            <label for="">Fecha</label>
                            <div class="p-inputgroup">
                                <Calendar id="basic" v-model="fields.fecha" autocomplete="off" dateFormat="dd-mm-yy" />
                            </div>
                            <small class="p-error" v-if="errors.fecha">{{ errors.fecha[0] }}</small>
                        </div>
                        <div class="col-12">
                            <label for="">Voucher Adjunto</label>
                            <div class="p-inputgroup" v-if="this.fields.pagadoConPagalo">
                                <FileInput :size="6" is-pdf placeholder-button-text="Buscar" v-model="file" placeholder-input-text="Seleccione Archivo" />
                            </div>
                            <div class="p-inputgroup" v-else>
                                <FileInput :size="6" is-image placeholder-button-text="Buscar" v-model="file" placeholder-input-text="Seleccione Archivo" />
                            </div>
                            <small class="p-error" v-if="errors.file">{{ errors.file[0] }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-12 md:col-6 text-center" v-if="this.fields.pagadoConPagalo">
                    <Image src="/images/pagalo.jpg" alt="Image" width="240" preview />
                </div>
                <div class="col-12 md:col-6 text-center" v-else>
                    <Image src="/images/voucher.jpg" alt="Image" width="240" preview />
                </div>
            </div>
            <template #footer>
                <Button label="Cerrar" icon="pi pi-times" @click="openModal = false" class="p-button-secondary p-button-sm" />
                <Button label="Guardar" icon="pi pi-check" @click="submit" autofocus :loading="saveLoading" class="p-button-success p-button p-button-sm" />
            </template>
        </Dialog>
    </div>
</template>

<script>
import FileInput from "../FileInput.vue";
export default {
    components: { FileInput },
    props: ["dni", "url"],
    data() {
        return {
            fields: {},
            errors: {},
            result: {
                pago: [],
            },
            selectAdjunto: "Selecione",
            estudiante: "",
            openModal: false,
            saveLoading: false,
            file: null,
        };
    },
    // props: ['tarifa','documento'],
    methods: {
        submit() {
            const currentDate = new Date(this.fields.fecha);

            // let fecha = currentDate.getDate() + "-" + currentDate.getMonth() + "-" + currentDate.getFullYear();
            const fecha = currentDate.getFullYear() + "-" + ("0" + (currentDate.getMonth() + 1)).slice(-2) + "-" + ("0" + currentDate.getDate()).slice(-2);
            // console.log(fecha);
            this.saveLoading = true;
            this.fields.tarifa = this.tarifa;
            let formData = new FormData();

            // Pagalo.pe (checkbox)
            formData.append("pagarEnPagalo", typeof this.fields.pagadoConPagalo !== "undefined" ? this.fields.pagadoConPagalo : "");

            formData.append("secuencia", typeof this.fields.secuencia !== "undefined" ? this.fields.secuencia : "");
            formData.append("monto", typeof this.fields.monto !== "undefined" ? this.fields.monto : "");
            formData.append("fecha", typeof this.fields.fecha !== "undefined" ? fecha : "");
            // formData.append('tarifa',this.fields.tarifa);
            formData.append("documento", this.$page.props.usuario.dni);
            // console.log(this.file);
            formData.append("file", this.file !== null ? this.file.file : "");

            this.errors = {};
            axios
                .post(this.url + "/api/pagos/validar-pago-cuota/" + this.$page.props.user.id, formData)
                .then((response) => {
                    if (response.data.status) {
                        this.result.pago.push(response.data);
                        this.result.pago = this.result.pago.filter((pago, index, self) => index === self.findIndex((t) => t.secuencia === pago.secuencia));
                        // $("#modalPago").modal("hide");
                        this.openModal = false;

                        this.$emit("result", this.result);
                        this.fields = {};
                        this.$toast.add({ severity: "success", summary: "¡Exito!", detail: response.data.message, life: 3000 });
                    } else {
                        this.$toast.add({ severity: "error", summary: "Error", detail: response.data.message, life: 3000 });
                    }
                    this.saveLoading = false;
                })
                .catch((error) => {
                    this.saveLoading = false;
                    if (error.response.status === 422) {
                        this.errors = error.response.data.errors || {};
                    }
                });
        },
        filesChange(e) {
            let file = e.target.files[0];
            this.selectAdjunto = file.name;
            this.fields.file = file;
        },
        getEstudiante: function () {
            axios.get("get-estudiante").then(
                function (response) {
                    // console.log(response.data);
                    this.estudiante = response.data.estudiante;
                    this.fields.documento = response.data.estudiante.nro_documento;
                }.bind(this)
            );
        },
    },
    mounted() {
        // this.fields.dni = this.dni;
        // console.log(this.$page.props.user.id);
        // this.getEstudiante();
        // console.log(this.tarifa);
        // this.fields.documento = this.documento;
    },
};
</script>
