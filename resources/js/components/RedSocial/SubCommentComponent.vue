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
        </div>
    </div>
</template>

<script>
import { useToast } from "primevue/usetoast";
import { ref, toRefs, onMounted } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";
import axios from "axios";
import { timeRange } from "../../utilities/timeRange";
export default {
    components: {},
    props: { datausuario: Object, comentarioRec: Object, datos: Object, usuario: Object },
    setup(props, { emit }) {
        // const { datos} = toRefs(props);
        const nombre = ref("");
        const foto = ref("");
        const estadoFotos = ref(false);
        const toast = useToast();
        const { usuario, datos, comentarioRec } = toRefs(props);
        const errors = ref({});

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
        });

        return {
            usuario,
            errors,
            nombre,
            foto,
            estadoFotos,
            comentarioRec,
            calculoFecha,
        };
    },
};
</script>

<style>
</style>
