<template>
    <Toast />
    <NavbarComponent />
    <DocenteAptoComponent />
</template>

<script>
import NavbarComponent from "@/components/Docente/NavbarComponent.vue";
import DocenteAptoComponent from "@/components/Docente/DocenteAptoComponent.vue";
import { useToast } from "primevue/usetoast";
import { ref, onMounted, watch, toRefs } from "vue";

export default {
    components: {
        NavbarComponent,
        DocenteAptoComponent,
    },
    setup(props) {
        const toast = useToast();
        const { response, errors } = toRefs(props);

        return {
            errors,
        };
    },
};
</script>

<style>
</style>