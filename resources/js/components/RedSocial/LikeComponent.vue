<template>
    <input type="hidden" :v-model="(datosLike._userid = usuario.id)" />
    <input type="hidden" :v-model="(datosLike.role_id = usuario.roles[0].id)" />
    <input type="hidden" :v-model="(datosLike.publicacion_id = publicacion.id)" />

    <Button v-if="likeExists == 0" @click="like(publicacion.id)" class="p-button-raised p-button-secondary p-button-text flex align-content-center justify-content-center col-6">
        <i class="pi pi-thumbs-up px-1"></i>
        <span class="px-1">Me gusta</span>
    </Button>
    <Button v-if="likeExists == 1" @click="dislike(publicacion.id)" class="p-button-raised p-button-primary flex align-content-center justify-content-center col-6">
        <i class="pi pi-thumbs-up px-1"></i>
        <span class="px-1">Me gusta</span>
    </Button>
</template>
<script>
import { useToast } from "primevue/usetoast";
import { ref, toRefs, onMounted } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";
import axios from "axios";

export default {
    components: {},
    props: { usuario: Object, publicacion: Object },
    setup(props, { emit }) {
        const toast = useToast();
        const { usuario, publicacion } = toRefs(props);
        const errors = ref({});
        const likeExists = ref(null);

        const datosLike = useForm({
            user_id: null,
            role_id: null,
            publicacion_id: null,
        });

        const getLike = () => {
            axios
                .get(route("publicacion.get-like", datosLike.publicacion_id))
                .then((response) => {
                    likeExists.value = response.data.likestatus;
                })
                .catch((error) => {
                    console.log(error.response);
                    if (error.response.status === 422) {
                        errors.value = error.response.data.errors;
                    }
                });
        };
        const like = (id) => {
            axios
                .post(route("publicacion.like", id))
                .then((response) => {
                    if (response.data.status) {
                        emit("statusForm", { status: true });
                        getLike();
                    } else {
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
        const dislike = (id) => {
            axios
                .post(route("publicacion.dislike", id))
                .then((response) => {
                    if (response.data.status) {
                        emit("statusForm", { status: true });
                        getLike();
                    } else {
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

        onMounted(() => {
            getLike();
        });

        return {
            usuario,
            publicacion,
            errors,
            likeExists,
            datosLike,
            getLike,
            like,
            dislike,
        };
    },
};
</script>
