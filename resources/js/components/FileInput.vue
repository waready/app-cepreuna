<template>
    <div class="file-input-control">
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
import { defineComponent, ref, computed, onMounted, onBeforeUnmount, watch } from "vue";
export default defineComponent({
    name: "FileInput",
    emits: ["update:modelValue"],
    props: {
        modelValue: {
            default: null,
        },
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
        let objectUrl = null;
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
        const releaseObjectUrl = () => {
            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
                objectUrl = null;
            }
        };
        const resetInput = () => {
            releaseObjectUrl();
            FileName.value = props.placeholderInputText;
            if (file.value) {
                file.value.value = "";
            }
        };
        const emitFileChange = () => {
            if (file.value.files.length > 0) {
                if (file.value.files[0].size > 1024 * (1024 * props.size) && props.size != 0) {
                    errorSize.value = `El tamaño del archivo excede el límite de ${props.size} MB permitido.`;
                    context.emit("update:modelValue", null);
                    resetInput();
                } else {
                    errorSize.value = false;
                    const fileAux = file.value.files[0];
                    FileName.value = fileAux.name;
                    releaseObjectUrl();
                    objectUrl = URL.createObjectURL(fileAux);
                    const response = {
                        file: fileAux,
                        fileName: fileAux.name,
                        fileBlob: objectUrl,
                        fileType: fileAux.type,
                    };
                    context.emit("update:modelValue", response);
                }
            } else {
                errorSize.value = false;
                context.emit("update:modelValue", null);
                resetInput();
            }
        };
        watch(
            () => props.modelValue,
            (value) => {
                if (value === null) {
                    errorSize.value = false;
                    resetInput();
                }
            }
        );
        onMounted(() => {
            FileName.value = props.placeholderInputText;
        });
        onBeforeUnmount(releaseObjectUrl);
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

.file-input-control,
.file-input-control .p-inputgroup {
    min-width: 0;
}

.buttonFileStyle {
    display: inline-flex;
    flex: 0 0 auto;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
}

.file-input-control :deep(.p-inputtext) {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
}

@media (max-width: 360px) {
    .file-input-control .p-inputgroup {
        align-items: stretch;
        flex-direction: column;
    }

    .file-input-control :deep(.p-inputtext),
    .buttonFileStyle {
        width: 100%;
        border-radius: 6px;
    }

    .buttonFileStyle {
        min-height: 2.75rem;
        margin-top: 0.5rem;
    }
}
</style>
