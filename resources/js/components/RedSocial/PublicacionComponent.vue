<template>
    <Toast />
    <Card class="mb-3 px-2 md:col-offset-2 md:col-8">
        <template #content>
            <div class="col-12 flex flex-row">
                <div class="col-10 flex flex-row">
                    <div class="flex">
                        <Avatar v-if="estadoFotos" class="mr-3" :image="foto" size="xlarge" shape="circle" />
                        <Avatar v-else icon="pi pi-user" class="mr-3" size="xlarge" shape="circle" />
                    </div>
                    <div class="flex flex-column">
                        <b>
                            <span style="font-size: 15px">{{ nombre }}</span>
                            <!-- <Tag class="mr-2 tag-secondary" :value="rol"></Tag> -->
                            <br />
                            <small class="tag-secondary">{{ rol }}</small>
                        </b>
                        <p class="text-xs">{{ calculoFecha(datos.created_at) }}</p>
                    </div>
                </div>
                <div class="flex col-2 justify-content-end" v-if="permisos.includes('app ocultar publicacion')">
                    <Button icon="pi pi-times" label="" class="p-button-rounded p-button-danger" @click="ocultarPublicacion(datos.id)" />
                </div>
            </div>
            <!-- Contenido -->
            <div class="flex flex-column col-12">
                <div class="mb-2 overflow-hidden text-overflow-ellipsis">
                    <span style="white-space: pre-line"
                        ><p>{{ datos.descripcion }}</p></span
                    >
                </div>
                <div v-if="datos.imagen_pub != null" class="flex col-12 h-30rem overflow-hidden align-items-center justify-content-center" style="background: #dee2e66e">
                    <Image class="h-full" imageStyle="height:100%;" :src="'/storage/publicaciones/' + datos.imagen_pub" alt="imagen publicacion" preview />
                </div>
                <div v-if="datos.archivo != null" class="flex flex-row align-items-center align-content-center col-12 surface-300">
                    <a
                        :href="'/storage/publicaciones/' + datos.archivo"
                        target="_blank"
                        style="color: black; text-decoration: none"
                        class="flex flex-row align-items-center align-content-center col-12 surface-300"
                    >
                        <Avatar icon="pi pi-file-pdf" style="color: #c62828" size="large" />
                        <div class="flex flex-column pl-2 overflow-hidden text-overflow-ellipsis">
                            <p class="my-0 py-0">PDF</p>
                            <b>{{ datos.archivo }}</b>
                        </div>
                    </a>
                </div>
                <div class="flex flex-row justify-content-between">
                    <div class="flex flex-row col-6 align-items-center">
                        <Avatar icon="pi pi-thumbs-up" shape="circle" style="background-color: #2196f3; color: #ffffff" />
                        <p class="ml-2">{{ datos.like }}</p>
                    </div>
                    <div class="flex flex-row col-6 align-items-center justify-content-end">
                        <Avatar icon="pi pi-comments" shape="circle" style="background-color: #696969; color: #ffffff" />
                        <p class="ml-2">{{ countComentarios }}</p>
                    </div>
                </div>
            </div>
            <!-- Botones -->
            <div class="flex flex-column">
                <div class="flex flex-row col-12 p-buttonset">
                    <LikeComponent :publicacion="datos" :usuario="usuario" />
                    <Button
                        @click="(comentarDialog = true), (publicationId = datos.id)"
                        class="p-button-raised p-button-secondary p-button-text flex align-content-center justify-content-center col-6"
                    >
                        <i class="pi pi-comment px-1"></i>
                        <span class="px-1">Comentar</span>
                    </Button>
                    <!-- </span> -->
                </div>
            </div>
        </template>
        <template #footer v-if="comentarDialog == true && publicationId == datos.id">
            <CommentComponent :datausuario="datausuario" :usuario="usuario" :datos="datos" />
        </template>
    </Card>
</template>

<script>
import { useToast } from "primevue/usetoast";
import { ref, onMounted, toRefs } from "vue";
import { timeRange } from "../../utilities/timeRange.js";
import axios from "axios";
import CommentComponent from "@/components/RedSocial/CommentComponent.vue";
import LikeComponent from "@/components/RedSocial/LikeComponent.vue";

export default {
    components: {
        LikeComponent,
        CommentComponent,
    },
    props: {
        datos: Object,
        usuario: Object,
        datausuario: Object,
        permisos: Object,
    },
    setup(props, { emit }) {
        const toast = useToast();
        const { datos } = toRefs(props);
        const comentarDialog = ref(false);
        const nombre = ref("");
        const foto = ref("");
        const estadoFotos = ref(false);
        const rol = ref("");
        const countComentarios = ref(null);
        // console.log(item.value);

        const cantidadComentarios = () => {
            axios
                .get(route("comentario.get-countcomentarios", datos.value.id))
                .then((response) => {
                    countComentarios.value = response.data.countComentarios;
                })
                .catch((error) => {
                    console.log(error.response);
                    if (error.response.status === 422) {
                        errors.value = error.response.data.errors;
                    }
                });
        };

        const ocultarPublicacion = (id) => {
            axios
                .post(route("publicacion.ocultar", id))
                .then((response) => {
                    console.log(response.data.status);
                    if (response.data.status) {
                        toast.add({
                            severity: "success",
                            summary: "¡Exito...!",
                            detail: response.data.message,
                            life: 5000,
                        });
                        emit("statusForm", { status: true });
                    } else {
                        toast.add({
                            severity: "error",
                            summary: "¡Error!",
                            detail: response.data.message,
                            life: 5000,
                        });
                       
                    }
                })
                .catch((error) => {
                    console.log(error.response);
                    if (error.response.status === 422) {
                        errors.value = error.response.data.errors;
                    }
                });
        };

        onMounted(() => {
            // console.log(datos.value);
            cantidadComentarios();
            axios
                .get(route("recursos.get-data-user"), {
                    params: {
                        id: datos.value.id ? datos.value.id : "",
                        idUser: datos.value.user_id ? datos.value.user_id : "",
                        rolName: datos.value.rol.name ? datos.value.rol.name : "",
                    },
                })
                .then((response) => {
                    // datos.value =response.data;
                    nombre.value = response.data.datos.nombres;
                    foto.value = response.data.datos.path_foto;
                    estadoFotos.value = response.data.datos.estado_foto;
                    rol.value = response.data.datos.rol;
                });
        });
        const calculoFecha = (date) => {
            const fecha = new Date(date);
            return timeRange(fecha);
        };
        return {
            calculoFecha,
            cantidadComentarios,
            comentarDialog,
            nombre,
            foto,
            estadoFotos,
            rol,
            countComentarios,
            ocultarPublicacion,
        };
    },
};
</script>

<style lang="scss" scoped>
.tag-secondary {
    color: #fd8043 !important;
}
</style>
