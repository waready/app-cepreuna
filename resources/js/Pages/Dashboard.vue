<template lang="html">
    <app-layout :mode="2" :title="title" class="dashboard">
        <div class="card shadow-6 p-0">
            <div class="grid">
                <div class="col-12 pt-0">
                    <TabsMenu :notificacion="notificacion">
                        <TabSubmenu title="Inicio" icon="pi-home">
                            <PublicationComponent :usuario="$attrs.user" :datausuario="$attrs.usuario" :permisos="$page.props.permissions"/>
                        </TabSubmenu>
                        <TabSubmenu title="Comunicados" icon="pi-book">
                            <ComunicadoComponent :usuario="$attrs.user" :datausuario="$attrs.usuario" :permisos="$page.props.permissions" />

                        </TabSubmenu>
                        <TabSubmenu title="Notificaciones" icon="pi-bell">
                            <NotificacionesComponent />
                        </TabSubmenu>
                    </TabsMenu>
                </div>
            </div>
        </div>
    </app-layout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout";
import { ref, onMounted, reactive } from "vue";

import AppTopBarSocial from "../Sigma/AppTopbarSocial.vue";
import TabsMenu from "../components/TabsMenu.vue";
import TabSubmenu from "../components/TabSubmenu.vue";

import axios from "axios";
// componentes submenu
import NotificacionesComponent from "../components/Social/NotificacionesComponent.vue";
import ComunicadoComponent from "@/components/RedSocial/ComunicadoComponent.vue";
import PublicationComponent from "@/components/RedSocial/PublicationComponent.vue";
export default {
    components: {
        AppTopBarSocial,
        TabsMenu,
        TabSubmenu,
        AppLayout,
        PublicationComponent,
        NotificacionesComponent,
        ComunicadoComponent,
    },
    props: {
        users: Object,
    },
    setup() {
        const title = ref("CEPREUNA");
        const msg = ref("Bienvenido al Panel Principal");
        const usuario = ref("");

        // const alertNotificaciones = ref(false);
        // const countNotificaciones = ref(false);
        const notificacion = ref({
            alert: false,
            count: 0,
            index: 0,
        });
        const getAlertNotificaciones = () => {
            // console.log(item.value);
            axios.get(route("recursos.alert-notificaciones"), {}).then((response) => {
                notificacion.value.alert = response.data.status;
                notificacion.value.count = response.data.count;
                notificacion.value.index = 2;
            });
        };
        onMounted(() => {
            getAlertNotificaciones();
        });
        return {
            msg,
            title,
            usuario,
            notificacion,
        };
    },
};
</script>
<style scoped>
/* .layout-main {
    z-index: 0;
} */
</style>
