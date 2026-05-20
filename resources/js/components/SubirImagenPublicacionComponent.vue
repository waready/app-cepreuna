<template>
    <div>
        <div class="p-inputgroup">
            <InputText type="text" v-model="FileName" readonly />
            <label class="custom-file-upload p-button buttonFileStyle">
                <span class="pi pi-image p-button-icon mr-2"></span>
                <input class="file" type="file" ref="file" v-on:change="emitFileChange" v-show="false" :accept="acceptType" />
                {{ placeholderButtonText }}
            </label>
        </div>
        <!-- <FileInput :size="2" is-pdf placeholder-button-text="Buscar" v-model="form.file1" placeholder-input-text="Seleccione Archivo" /> -->
        <small class="p-error" v-if="errorSize">{{ errorSize }}</small>
    </div>
</template>

<script>
import { defineComponent, ref, computed, onMounted } from "vue";
export default defineComponent({
    name: "FileInput",
    props: {
        size: {
            type: Number,
            default: 0,
        },
        isImage: {
            type: Boolean,
            default: false,
        },
        isPdf: {
            type: Boolean,
            default: false,
        },
        isWord: {
            type: Boolean,
            default: false,
        },
        isExcel: {
            type: Boolean,
            default: false,
        },
        isVideo: {
            type: Boolean,
            default: false,
        },
        isAudio: {
            type: Boolean,
            default: false,
        },
        isPdfImage: {
            type: Boolean,
            default: false,
        },
        buttonBackgroundColor: {
            type: String,
            default: () => "#003e70",
        },
        buttonTextColor: {
            type: String,
            default: () => "#FFF",
        },
        placeholderInputText: {
            type: String,
            default: () => "Select a file",
        },
        placeholderButtonText: {
            type: String,
            default: () => "Select a file",
        },
    },
    setup(props, context) {
        const file = ref(null);
        const FileName = ref(null);
        const acceptType = computed(() => {
            if (props.isExcel) {
                return ".xlsx, .xls";
            } else if (props.isWord) {
                return ".doc, .docx";
            } else if (props.isImage) {
                return "image/*";
            } else if (props.isVideo) {
                return "video/*";
            } else if (props.isPdf) {
                return "application/pdf";
            } else if (props.isAudio) {
                return "audio/*";
            } else if (props.isPdfImage) {
                return "application/pdf,image/*";
            } else {
                return "*";
            }
        });
        const errorSize = ref(false);
        const buttonStyle = computed(() => {
            return `background-color: ${props.buttonBackgroundColor}; color: ${props.buttonTextColor};`;
        });
        const emitFileChange = () => {
            if (file.value.files.length > 0) {
                if (file.value.files[0].size > 1024 * (1024 * props.size) && props.size != 0) {
                    errorSize.value = `El tamaño del archivo excede el limite de ${props.size} MB permitido.`;
                    context.emit("update:modelValue", null);
                    FileName.value = props.placeholderInputText;
                } else {
                    errorSize.value = false;
                    const fileAux = file.value.files[0];
                    FileName.value = fileAux.name;
                    const FileBlob = new Blob([file], {
                        name: FileName.value,
                        type: fileAux.type,
                    });
                    const fileURL = URL.createObjectURL(FileBlob);
                    const response = {
                        file: file.value.files[0],
                        fileName: file.value.files[0].name,
                        fileBlob: fileURL,
                        fileType: fileAux.type,
                    };
                    context.emit("update:modelValue", response);
                }
            } else {
                errorSize.value = false;
                context.emit("update:modelValue", null);
                FileName.value = props.placeholderInputText;
            }
        };
        onMounted(() => {
            FileName.value = props.placeholderInputText;
        });
        return {
            FileName,
            acceptType,
            buttonStyle,
            emitFileChange,
            file,
            errorSize,
        };
    },
});
</script>

<style scoped>
.buttonFileStyle {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0px;
    background-color: var(--surface-b);
    border: 1px solid #ced4da;
    color: var(--text-color);
}
.buttonFileStyle:active {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0px;
    background: #ced4da;
    border: 1px solid #ced4da;
    color: var(--text-color);
}
.buttonFileStyle:hover {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0px;
    background: #ced4da;
    border: 1px solid #ced4da;
    color: var(--text-color);
}
</style>


    <!-- <div class="grid" style="margin-top: 1px">
        <div class="col-12 md:col-12">
            <Button label="Agregar Imagen" @click="dialogImage = true" icon="pi pi-image" />
        </div>
        <div class="col-12 text-center mt-2">
            <canvas v-if="imgState" id="canvas" style="width: 130px"></canvas>
        </div>
    </div>
    <Dialog v-model:visible="dialogImage" :style="{ width: '90%' }" header="Subir Imagen" :modal="true" class="fluid bg-primary">
        <form @submit.prevent="" action="" autocomplete="off">
            <div class="grid mx-4">
                <div class="col-12 md:col-12">
                    <div class="field grid p-fluid">
                        <div class="col-12 md:col-12">
                            <FileInput :size="10" is-image placeholder-button-text="Seleccionar archivo" placeholder-input-text="Sin archivos seleccionados" @input="getImage" />
                            <InlineMessage severity="info">El recorte de la imagen debe ser superior a 540 px de ancho y 540 px de alto.</InlineMessage>
                            <div v-if="modal" class="p-error" role="alert">
                                <small><li>El recorte de la imagen es muy pequeña.</li></small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="grid">
                        <div class="col-5">
                            <label>2. Recorta</label>
                            <Cropper
                                class="cropper"
                                :src="avatar"
                                ref="cropper"
                                :auto-zoom="true"
                                :transitions="true"
                                :resize-image="{
                                    adjustStencil: true,
                                }"
                                image-restriction="fit-area"
                                default-boundaries="fill"
                                :stencil-props="{
                                    aspectRatio: 10 / 10,
                                }"
                                :min-height="limitations.minHeight"
                                :min-width="limitations.minWidth"
                                :size-restrictions-algorithm="pixelsRestriction"
                                @change="change"
                            />
                        </div>
                        <div class="col-7 text-center">
                            <label>3. Previsualización </label>
                            <div v-if="image" class="flex justify-content-center">
                                <img style="border: 1px solid #ddd" :src="image" class="md:w-5 sm:w-9" />
                            </div>
                        </div>
                        <div class="col-12 text-center">
                            <label class="font-bold" for="">Ancho: {{ coordinatesImage.width }}px</label>
                            <label class="font-bold ml-3" for="">Alto: {{ coordinatesImage.height }}px</label>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" class="p-button-secondary" @click="closeModal" />
            <Button label="Guardar Recorte" icon="pi pi-check" class="p-button-success" @click="guardarRecorte" />
        </template>
    </Dialog> 
</template>

/*<script>
import FileInput from "@/components/FileInput.vue";
import { Cropper } from "vue-advanced-cropper";
import "vue-advanced-cropper/dist/style.css";
import { ref, onMounted, watch, toRefs } from "vue";
export default {
    setup(props, context) {
        const dialogImage = ref(false);
        const image = ref(null);
        const imgState = ref(false);
        const modal = ref(false);
        const avatar = ref(null);
        const final = ref(null);
        const fin = ref(null);
        const coordinatesImage = ref({
            width: 0,
            height: 0,
            left: 0,
            top: 0,
        });
        const limitations = ref({
            minWidth: 539,
            minHeight: 539,
            maxWidth: 540 + 50,
            maxHeight: 540 + 50,
        });

        // metodos
        const pixelsRestriction = ({ minWidth, minHeight, maxWidth, maxHeight, imageWidth, imageHeight }) => {
            return {
                minWidth: minWidth,
                minHeight: minHeight,
                maxWidth: maxWidth,
                maxHeight: maxHeight,
            };
        };
        const getImage = (e) => {
            let image = e.target.files[0];
            let reader = new FileReader();
            reader.readAsDataURL(image);
            reader.onload = (e) => {
                avatar.value = e.target.result;
                //this.NuevoRedic(this.avatar, 413, 531)
            };
        };

        const closeModal = () => {
            avatar.value = null;
            image.value = null;
            coordinatesImage.value.width = null;
            coordinatesImage.value.height = null;
            coordinatesImage.value.top = null;
            coordinatesImage.value.left = null;
            modal.value = false;
            dialogImage.value = false;
        };
        const cropper = ref(null);
        const guardarRecorte = () => {
            console.log(cropper.value.getResult());
            const { canvas } = cropper.value.getResult();
            if (canvas) {
                if (coordinatesImage.value.height < 531 || coordinatesImage.value.width < 413) {
                    modal.value = true;
                } else {
                    final.value = canvas.toDataURL();
                    dialogImage.value = false;
                    // $(".modal-backdrop").remove();
                    imgState.value = true;
                    var img = document.createElement("img");
                    let reader = new FileReader();

                    img.onload = () => {
                        var canva = document.getElementById("canvas");
                        var ctx = canva.getContext("2d");
                        canva.width = 413;
                        canva.height = 531;
                        ctx.drawImage(img, 0, 0, 413, 531);
                        var dataURI = canva.toDataURL();
                        fin.value = dataURI;
                        // console.log('tagdasdasd', fin.value=dataURI)
                        //console.log("pre", final.value)
                        //console.log("pos", fin.value)
                        context.emit("imagen64", fin.value);
                    };
                    img.src = final.value;
                }
            } else {
                modal.value = true;
            }
        };
        const change = ({ coordinates, canvas }) => {
            coordinatesImage.value = coordinates;
            image.value = canvas.toDataURL();
        };
        return {
            dialogImage,
            image,
            imgState,
            modal,
            avatar,
            final,
            fin,
            closeModal,
            coordinatesImage,
            limitations,
            pixelsRestriction,
            getImage,
            guardarRecorte,
            change,
            cropper,
        };
    },
    components: {
        FileInput,
        Cropper,
    },
};
</script>

<style lang="scss" scoped></style>
*/-->