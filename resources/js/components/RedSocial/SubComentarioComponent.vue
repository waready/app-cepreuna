<template>
    <div class="flex flex-row mb-1">
        <div class="flex mr-2">
            <Avatar v-if="estadoFotos" class="mr-3" :image="foto" size="xlarge" shape="circle" />
            <Avatar v-else icon="pi pi-user" class="mr-3" size="xlarge" shape="circle" />
        </div>
        <div class="flex w-full flex-column">
            <div class="flex w-full flex-column p-card p-2 mb-2">
                <p class="font-medium mb-0 pb-0">
                    <b style="font-size: 12px">{{ nombre }}</b> <small>{{ calculoFecha(comentarioRec.created_at) }}</small>
                </p>
                <p style="font-size: 13px">{{ comentarioRec.descripcion }}</p>
                <!-- <Textarea disabled type="text" :autoResize="true" class="pt-0 mt-0" :placeholder="comentarioRec.descripcion" rows="1" /> -->
            </div>
            <div :v-if="comentarioRec.tipo == '1'" class="flex w-full flex-column">
                <div class="flex flex-row justify-content-end">
                    <input type="hidden" :v-model="(a = comentarioRec.id)" />
                    <Button class="mr-2 text-bluegray-900 p-0 p-button-text text-xs" @click="(subcommentDialog = true), (commentId = comentarioRec.id)">
                        <b style="font-size: 10px">Responder</b>
                    </Button>
                </div>
                <div v-if="subcommentDialog == true && commentId == comentarioRec.id" class="flex flex-row align-items-center mt-1">
                    <input v-if="subcommentDialog == true" type="hidden" :v-model="(comentario.id = comentarioRec.id)" />
                    <div class="flex flex-row col-12 align-items-center">
                        <div class="flex mr-2">
                            <Avatar :image="datausuario.profile_photo_path == '' ? datausuario.profile_photo_url : datausuario.profile_photo_path" class="mr-3" size="large" shape="circle" />
                        </div>
                        <div class="flex w-full">
                            <Textarea v-model="comentario.texto" :autoResize="true" rows="1" class="w-full" placeholder="Escribe un comentario." />
                            <small class="p-error" v-if="errors.texto">{{ errors.texto[0] }}</small>
                        </div>
                        <div class="flex h-2 px-1">
                            <Button icon="pi pi-send" class="p-button-secondary" @click="comentar(datos.id)"></Button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-column p-3" style="background: #dee2e66e">
                <SubCommentComponent v-for="sc in subcomentarios" :key="sc" :comentarioRec="sc" :datos="datos" :datausuario="datausuario" :usuario="usuario" />
            </div>
        </div>
    </div>
</template>

<script>
import { useToast } from "primevue/usetoast";
import { ref, toRefs, onMounted } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";
import axios from "axios";
import { timeRange } from "../../utilities/timeRange";
import SubCommentComponent from "@/components/RedSocial/SubCommentComponent.vue";
export default {
    components: {
        SubCommentComponent,
    },
    props: { datausuario: Object, comentarioRec: Object, datos: Object, usuario: Object },
    setup(props, { emit }) {
        // const { datos} = toRefs(props);
        const nombre = ref("");
        const foto = ref("");
        const estadoFotos = ref(false);
        const toast = useToast();
        const { usuario, datos, comentarioRec } = toRefs(props);
        const errors = ref({});
        const a = ref("");

        const comentario = useForm({
            texto: null,
            id: null,
        });
        const comentar = (id) => {
            let formData = new FormData();
            let formUsuario = JSON.stringify(usuario.value);

            formData.append("usuario", formUsuario);
            formData.append("texto", comentario.texto);
            formData.append("id", id);
            if (comentario.id != null) {
                formData.append("comentario", comentario.id);
            }

            axios
                .post(route("comentario.crear"), formData)
                .then((response) => {
                    if (response.data.status) {
                        toast.add({
                            severity: "success",
                            summary: "¡Exito...!",
                            detail: response.data.message,
                            life: 5000,
                        });
                        emit("statusForm", { status: true });
                        comentario.reset();
                        getSubComentarios();
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

        const subcomentarios = ref([]);
        const getSubComentarios = () => {
            // console.log("subcoment");
            axios
                .get(route("comentario.get-subcomentarios", a.value))
                .then((response) => {
                    subcomentarios.value = response.data.subcomentarios;
                })
                .catch((error) => {
                    console.log(error.response);
                    if (error.response.status === 422) {
                        errors.value = error.response.data.errors;
                    }
                });
        };

        const subcommentDialog = ref(false);
        const commentId = ref(null);

        const calculoFecha = (date) => {
            const fecha = new Date(date);
            return timeRange(fecha);
        };

        onMounted(() => {
            axios
                .get(route("recursos.get-data-user"), {
                    params: {
                        id: comentarioRec.value.id ? comentarioRec.value.id : "",
                        idUser: comentarioRec.value.user_id ? comentarioRec.value.user_id : "",
                        rolName: comentarioRec.value.rol.name ? comentarioRec.value.rol.name : "",
                    },
                })
                .then((response) => {
                    // datos.value =response.data;
                    nombre.value = response.data.datos.nombres;
                    foto.value = response.data.datos.path_foto;
                    estadoFotos.value = response.data.datos.estado_foto;
                });

            getSubComentarios();
        });

        return {
            usuario,
            errors,
            comentario,
            comentar,
            a,
            subcomentarios,
            getSubComentarios,
            commentId,
            subcommentDialog,
            calculoFecha,
            nombre,
            foto,
            estadoFotos,
            comentarioRec,
        };
    },
};
</script>

<style>
</style>
