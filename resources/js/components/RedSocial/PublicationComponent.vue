<template>
    <Toast />
    <div class="grid px-2">
        <div class="col-12 flex flex-column surface-100">
            <Card class="mb-3 md:col-offset-2 md:col-8">
                <template #content>
                    <div class="col-12 flex flex-column pt-0" style="margin-top: -15px">
                        <div class="col-12 flex flex-row">
                            <div class="flex">
                                <Avatar :image="datausuario.profile_photo_path == '' ? datausuario.profile_photo_url : datausuario.profile_photo_path" class="mr-3" size="xlarge" shape="circle" />
                            </div>
                            <div class="flex flex-column">
                                <b>{{ datausuario.nombres }} </b>
                                <!-- <p class="text-xs">HORA Y FECHA</p> -->
                            </div>
                        </div>
                        <div class="col-12 flex flex-column justify-content-center mb-0 pb-0">
                            <Textarea :autoResize="true" rows="3" class="col-12" v-model="publicacion.texto" :placeholder="'¿Qué estas pensando ' + datausuario.nombres + '?'" />
                            <small class="p-error" v-if="errors.texto">{{ errors.texto[0] }}</small>
                        </div>
                        <div class="flex flex-column">
                            <div class="col-12 flex justify-content-between md:flex-row sm:flex-column mt-0 pt-0">
                                <SubirImagenPublicacion
                                    :size="2"
                                    is-image
                                    placeholder-button-text="Buscar"
                                    v-model="publicacion.imagen"
                                    placeholder-input-text="Agregar Imagen"
                                    class="col-12 md:col-6"
                                />
                                <!-- <FileInput :size="2" is-pdf placeholder-button-text="Buscar" v-model="publicacion.archivo" placeholder-input-text="Agregar Archivo" class="col-6" /> -->
                            </div>
                        </div>
                        <div class="col-12 flex justify-content-center">
                            <Button label="Publicar" class="col-12" @click="crearPublicacion()" />
                        </div>
                    </div>
                </template>
            </Card>
            <PublicacionComponent v-for="p in publicaciones" :key="p" :datos="p" :usuario="usuario" :datausuario="datausuario" :permisos="permisos"></PublicacionComponent>
            <div class="col-12 text-center" v-if="total > pagina">
                <Button label="Ver más publicaciones" class="p-button-link" @click="morePublicaciones" />
            </div>

            <Dialog header="Crear Publicación" v-model:visible="displayNewPost" :style="{ width: '50vw' }" class="flex flex-column col-12 text-center">
                <div class="col-12 flex flex-column mt-0 pt-0">
                    <div class="col-12 flex flex-row">
                        <div class="flex">
                            <Avatar :image="datausuario.profile_photo_path" class="mr-3" size="xlarge" shape="circle" />
                        </div>
                        <div class="flex flex-column">
                            <b>{{ datausuario.nombres }} </b>
                            <!-- <p class="text-xs">HORA Y FECHA</p> -->
                        </div>
                    </div>
                    <div class="col-12 flex flex-column justify-content-center mb-0 pb-0">
                        <Textarea :autoResize="true" rows="5" class="col-12" v-model="publicacion.texto" :placeholder="'¿Qué estas pensando ' + datausuario.nombres + '?'" />
                        <small class="p-error" v-if="errors.texto">{{ errors.texto[0] }}</small>
                    </div>
                    <div class="flex flex-column">
                        <div class="col-12 flex justify-content-between md:flex-row sm:flex-column mt-0 pt-0">
                            <SubirImagenPublicacion :size="2" is-image placeholder-button-text="Buscar" v-model="publicacion.imagen" placeholder-input-text="Agregar Imagen" class="col-12 md:col-6" />
                            <!-- <FileInput :size="2" is-pdf placeholder-button-text="Buscar" v-model="publicacion.archivo" placeholder-input-text="Agregar Archivo" class="col-6" /> -->
                        </div>
                    </div>
                    <div class="col-12 flex justify-content-center">
                        <Button label="Publicar" class="col-12" @click="crearPublicacion()" />
                    </div>
                </div>
            </Dialog>
        </div>
    </div>
</template>

<script>
import { useToast } from "primevue/usetoast";
import { ref, toRefs, onMounted } from "vue";
import Dialog from "primevue/dialog";
import Textarea from "primevue/textarea";
import ToggleButton from "primevue/togglebutton";
import SubirImagenPublicacion from "@/components/SubirImagenPublicacionComponent.vue";
import FileInput from "@/components/FileInput.vue";
import CommentComponent from "@/components/RedSocial/CommentComponent.vue";
import LikeComponent from "@/components/RedSocial/LikeComponent.vue";
import PublicacionComponent from "@/components/RedSocial/PublicacionComponent.vue";
import { useForm } from "@inertiajs/inertia-vue3";
import axios from "axios";
import { timeRange } from "../../utilities/timeRange";

export default {
    components: {
        Dialog,
        Textarea,
        SubirImagenPublicacion,
        FileInput,
        ToggleButton,
        LikeComponent,
        CommentComponent,
        PublicacionComponent,
    },
    props: {
        usuario: Object,
        datausuario: Object,
        permisos: Object,
    },
    setup(props, { emit }) {
        const toast = useToast();
        const { usuario } = toRefs(props);
        const errors = ref({});
        const publicationId = ref(null);
        // const checked = ref(false);
        const total = ref(0);
        const pagina = ref(1);

        const publicacion = useForm({
            imagen: null,
            archivoname: null,
            texto: null,
            archivo: null,
        });

        const displayNewPost = ref(false);
        const newPost = () => {
            displayNewPost.value = true;
        };

        const crearPublicacion = () => {
            let formData = new FormData();
            let formUsuario = JSON.stringify(usuario.value);

            formData.append("usuario", formUsuario);
            // console.log(formUsuario);
            if (publicacion.texto != null) {
                formData.append("texto", publicacion.texto);
            }
            if (publicacion.imagen != null) {
                formData.append("imagen", publicacion.imagen.file);
                // formData.append("imgt", publicacion.imagen);
            }
            if (publicacion.archivo != null) {
                formData.append("archivo", publicacion.archivo.file);
                formData.append("archivoname", publicacion.archivo.fileName);
            }
            formData.append("tipo", "1");
            axios
                .post(route("publicacion.crear"), formData)
                .then((response) => {
                    if (response.data.status) {
                        toast.add({
                            severity: "success",
                            summary: "¡Exito...!",
                            detail: response.data.message,
                            life: 5000,
                        });
                        emit("statusForm", { status: true });
                        displayNewPost.value = false;
                        publicacion.reset();
                        publicaciones.value = [];
                        pagina.value = 1;
                        verPublicaciones();
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

        const publicaciones = ref([]);
        const verPublicaciones = () => {
            axios
                .get(route("publicacion.get-publicaciones"), {
                    params: {
                        page: pagina.value,
                        tipo: "1",
                    },
                })
                .then((response) => {
                    // console.log(response.data.publicaciones);
                    // publicaciones.value = response.data.publicaciones;
                    // console.log(publicaciones.value);
                    publicaciones.value.push(...response.data["data"]);
                    total.value = response.data["total"] / 5;
                    console.log(response.data.total);
                })
                .catch((error) => {
                    console.log(error.response);
                    if (error.response.status === 422) {
                        errors.value = error.response.data.errors;
                    }
                });
        };

        const morePublicaciones = () => {
            if (total.value > pagina.value) {
                pagina.value += 1;
            }
            verPublicaciones();
        };

        const comentarDialog = ref(false);

        const calculoFecha = (date) => {
            const fecha = new Date(date);
            return timeRange(fecha);
        };

        onMounted(() => {
            verPublicaciones();
        });

        return {
            usuario,
            errors,
            displayNewPost,
            publicacion,
            newPost,
            crearPublicacion,
            publicaciones,
            verPublicaciones,
            calculoFecha,
            // cropImg,
            publicationId,
            comentarDialog,
            total,
            pagina,
            morePublicaciones,
            // checked,
        };
    },
};
</script>
