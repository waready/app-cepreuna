<template>
    <Toast />
    <NavbarComponent />
    <LoginComponent v-if="!isLogin" @statusLogin="statusLogin" />
    <DocenteInicioComponent v-else @statusForm="statusForm" :docente="docente" :datosExpediente="datosExpediente" />
    <!-- <FormComponent v-else @statusForm="statusForm" :docente="docente" :datosTramite="datosTramite" :tipoDocumentos="tipoDocumentos" /> -->
    <!-- <CorreccionDocumentosComponent /> -->
</template>

<script>
import NavbarComponent from "@/components/Docente/NavbarComponent.vue";
import LoginComponent from "@/components/Docente/LoginComponent.vue";
import FormComponent from "@/components/Docente/FormComponent.vue";
import DocenteInicioComponent from "@/components/Docente/DocenteInicioComponent.vue";
import CorreccionDocumentosComponent from "@/components/Docente/CorreccionDocumentosComponent.vue";
import { ref, onMounted, watch, toRefs } from "vue";
import { useToast } from "primevue/usetoast";
import axios from "axios";

export default {
    components: {
        NavbarComponent,
        LoginComponent,
        FormComponent,
        DocenteInicioComponent,
        CorreccionDocumentosComponent,
    },
    props: {},
    setup(props) {
        const toast = useToast();
        const isLogin = ref(false);
        const { response, errors } = toRefs(props);
        // const errors = ref({});
        const docente = ref("");
        const datosExpediente = ref({});
        // const docsExpediente = ref({});

        const statusLogin = (data) => {
            isLogin.value = data.status;
            docente.value = data.docente;
            datosExpediente.value = data.datosExpediente;
            // docsExpediente.value = data.docsExpediente;
        };

        const statusForm = (data) => {
            console.log(data);
        };

        // const tipoDocumentos = ref([]);

        // const getTipoDocumentos = () => {
        //     axios
        //         .get(route("docente.get-tipo-documentos"))
        //         .then((response) => {
        //             console.log(response.data);
        //             tipoDocumentos.value = response.data;
        //         })
        //         .catch((err) => alert(err));
        // };

        // onMounted(() => {
        //     getTipoDocumentos();
        // });

        return {
            errors,
            isLogin,
            docente,
            datosExpediente,
            // docsExpediente,
            // tipoDocumentos,
            statusForm,
            statusLogin,
        };
    },
};
</script>
