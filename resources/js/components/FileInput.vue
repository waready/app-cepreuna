<template>
    <div>
        <div class="p-inputgroup">
            <InputText type="text" v-model="FileName" readonly />
            <label class="custom-file-upload p-button buttonFileStyle">
                <span class="pi pi-paperclip p-button-icon mr-2"></span>
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
                    errorSize.value = `El tamaño del archivo excede el limite de 2 MB permitido.`;
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
