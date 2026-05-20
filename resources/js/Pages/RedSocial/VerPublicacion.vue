<template lang="html">
    <app-layout :mode="2" :title="title" class="dashboard">
        <div class="card shadow-6 p-0">
            <div class="grid">
                <div class="col-12 pt-0">
                    <PublicacionComponent :datos="publicacion" :usuario="usuario" :datausuario="$attrs.usuario"></PublicacionComponent>
                </div>
            </div>
        </div>
    </app-layout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout";
import { ref, onMounted, reactive,toRefs } from "vue";
import PublicacionComponent from "@/components/RedSocial/PublicacionComponent.vue";
import { timeRange } from "../../utilities/timeRange";


import axios from "axios";

export default {
    components: {
        AppLayout,
        PublicacionComponent,
    },
    props: {
        data:Object,
        user: Object,
    },
    setup(props) {
        const { data,user} = toRefs(props);
        const publicacion = data.value.publicacion;
        const comentarDialog = ref(false);
        const usuario = user.value;
        const title = ref("CEPREUNA");
        const msg = ref("Bienvenido al Panel Principal");
        const calculoFecha = (date) => {
            const fecha = new Date(date);
            return timeRange(fecha);
        };
        onMounted(() => {
            // getAlertNotificaciones();
        });
        return {
            msg,
            title,
            comentarDialog,
            calculoFecha,
            usuario,
            publicacion
        };
    },
};
</script>
<style scoped>
/* .layout-main {
    z-index: 0;
} */
</style>
