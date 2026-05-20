<template>
    <div class="flex flex-column">
        <!-- Insertar Comentario Principal-->
        <div v-if="subcommentDialog != true" class="flex flex-row col-12 align-items-center">
            <div class="flex mr-2">
                <Avatar :image="datausuario.profile_photo_path == '' ? datausuario.profile_photo_url : datausuario.profile_photo_path" class="mr-3" size="xlarge" shape="circle" />
            </div>
            <div class="flex w-full flex-column">
                <Textarea v-model="comentario.texto" :autoResize="true" rows="1" class="w-full" placeholder="Escribe un comentario." />
                <small class="p-error" v-if="errors.texto">{{ errors.texto[0] }}</small>
            </div>
            <div class="flex h-2 px-1">
                <Button icon="pi pi-send" class="p-button-secondary" @click="comentar(datos.id)"></Button>
            </div>
        </div>

        <!-- <div v-if="comentarioExt != undefined" class="flex">
            <input v-if="comentarioExt != undefined" type="hidden" :v-model="(comentario.id = comentarioExt.id)" />
        </div> -->
        <!-- Lista de Comentarios -->
        <div class="flex flex-column p-3" style="background: #dee2e66e">
            <input type="hidden" :v-model="(a = datos.id)" />
            <SubComentarioComponent v-for="c in comentarios" :key="c" :comentarioRec="c" :datos="datos" :datausuario="datausuario" :usuario="usuario" />
        </div>
    </div>
</template>

<script>
import { useToast } from "primevue/usetoast";
import { ref, toRefs, onMounted } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";
import axios from "axios";
import SubComentarioComponent from "@/components/RedSocial/SubComentarioComponent.vue";
import { timeRange } from "../../utilities/timeRange";

export default {
    components: {
        SubComentarioComponent,
    },
    props: { datausuario: Object, datos: Object, usuario: Object },
    setup(props, { emit }) {
        // const { datos} = toRefs(props);
        const nombre = ref("");
        const foto = ref("");
        const estadoFotos = ref(false);
        const toast = useToast();
        const { usuario, datos } = toRefs(props);
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
            if (comentario.texto != null) {
                formData.append("texto", comentario.texto);
            }
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
                        getComentarios();
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

        const comentarios = ref([]);
        const getComentarios = () => {
            axios
                .get(route("comentario.get-comentarios", a.value))
                .then((response) => {
                    // console.log(response.data.comentarios);
                    comentarios.value = response.data.comentarios;
                    // console.log(statusForm);
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
                });

            getComentarios();
        });

        return {
            usuario,
            errors,
            comentario,
            comentar,
            a,
            comentarios,
            getComentarios,
            commentId,
            subcommentDialog,
            calculoFecha,
            nombre,
            foto,
            estadoFotos,
        };
    },
};
</script>
