<template>
    <div class="grid">
        <label for="fotogreafia" class="col-12 mb-2 md:col-4 md:mb-0">Fotografia</label>
        <div class="col-12 md:col-8">
            <Button label="Subir Imagen" @click="dialogImage = true" icon="pi pi-image" />
        </div>
        <div class="col-12 text-center mt-2">
            <canvas v-if="imgState" id="canvas" style="width: 130px"></canvas>
        </div>
    </div>
    <Dialog v-model:visible="dialogImage" :style="{ width: '500px' }" header="Subir Imagen" :modal="true" class="fluid bg-primary">
        <form @submit.prevent="" action="" autocomplete="off">
            <div class="grid mx-4">
                <div class="col-12 md:col-12">
                    <div class="field grid p-fluid">
                        <div class="col-12 md:col-12">
                            <FileInput :size="10" is-image placeholder-button-text="Seleccionar archivo" placeholder-input-text="Sin archivos seleccionados" @input="getImage" />
                            <InlineMessage severity="info">El recorte de la imagen debe ser superior a 413 px de ancho y 531 px de alto.</InlineMessage>
                            <div v-if="modal" class="p-error" role="alert">
                                <small><li>El recorte de la imagen es muy pequeña</li></small>
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
                                    aspectRatio: 10 / 12,
                                }"
                                :min-height="limitations.minHeight"
                                :min-width="limitations.minWidth"
                                :size-restrictions-algorithm="pixelsRestriction"
                                @change="change"
                            />
                        </div>
                        <div class="col-7 text-center">
                            <label>3. Previsualización </label>
                            <div v-if="image">
                                <img style="border: 1px solid #ddd" :src="image" width="130" />
                            </div>
                        </div>
                        <div class="col-12 text-center">
                            <label class="font-bold" for="">Ancho: {{ coordinatesImage.width }}px</label>
                            <label class="font-bold ml-3" for="">Alto: {{ coordinatesImage.height }}px</label>
                        </div>
                    </div>
                </div>
                <div class="col-12 md:col-12 text-center">
                    <hr />
                    <b>Realice el recorte de acuerdo al siguiente ejemplo.</b>
                    <br />
                    <img style="border: 1px solid #ddd" class="img-thumbnail rounded" src="/images/ejemplo-foto.jpg" width="130" />
                </div>
            </div>
        </form>
        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" class="p-button-secondary" @click="closeModal" />
            <Button label="Guardar Recorte" icon="pi pi-check" class="p-button-success" @click="guardarRecorte" />
        </template>
    </Dialog>
</template>

<script>
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
            minWidth: 412,
            minHeight: 430,
            maxWidth: 413 + 50,
            maxHeight: 531 + 50,
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
