<template>
    <Toast />
    <app-layout :title="title" :mode="2">
        <div class="card shadow-6">
            <div class="grid hidden sm:flex">
                <div class="col-12">
                    <h5 class="font-bold">Guias de Aprendizaje </h5>
                </div>
            </div>
            <div class="grid">
                <div class="col-12">
                    <a class="p-button p-component p-button-primary" href="https://drive.google.com/drive/folders/1npkWCdv84YZh4UKGJiXBLQrb72GB035Y?usp=sharing" target="_blank" v-if="area==1">
                        <i class="pi pi-book mr-2"></i>Cuadernillos de Biomédicas
                    </a>
                    <a class="p-button p-component p-button-primary" href="https://drive.google.com/drive/folders/1i5WJUGk0LOHRa-rYoMIast74UnhqwNUh?usp=sharing" target="_blank" v-if="area==2">
                        <i class="pi pi-book mr-2"></i>Cuadernillos de Ingenierías
                    </a>
                    <a class="p-button p-component p-button-primary" href="https://drive.google.com/drive/folders/1zm8Kq7eB8R3rvvgIAl6uAjV9HTmLqbJf?usp=sharing" target="_blank" v-if="area==3">
                        <i class="pi pi-book mr-2"></i>Cuadernillos de Sociales
                    </a>
                </div>
                <div class="col-12 acordion">
                    <Accordion :multiple="true" :activeIndex="[0]">
                        <AccordionTab v-for="curso in cursos" :key="curso.id">
                            <template #header>
                                <div class="curso-nombre"><i class="pi pi-bookmark-fill" :style="{ color: curso.color, fontSize: '19px' }"></i> {{ curso.denominacion }}</div>
                            </template>
                            <template v-if="curso.cuadernillos.length > 0" v-for="cuadernillo in curso.cuadernillos" :key="cuadernillo.id">
                                <a class="p-button p-component p-button-danger p-button-sm" :href="curso.base_path + '/storage/documentos/' + cuadernillo.path" target="_blank" download>
                                    <i class="pi pi-file-pdf mr-2"></i> Semana {{ cuadernillo.semana }}
                                </a>
                                <!-- <a class="p-button p-component p-button-danger" :href="route('auth-redirect')"> <i class="pi pi-google mr-1"></i> Ingresar con Google </a> -->
                            </template>
                            <template v-else>
                                <Tag class="mr-2 bg-gray-500">No existen cuadernillos para este curso </Tag>
                            </template>
                        </AccordionTab>
                    </Accordion>
                </div>
            </div>
        </div>
    </app-layout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout";
import { useToast } from "primevue/usetoast";

import { Inertia } from "@inertiajs/inertia";
import { useForm } from "@inertiajs/inertia-vue3";

import { ref, onMounted, watch, toRefs } from "vue";
import axios from "axios";

export default {
    // components: {
    //     AppTopBarMobile,
    // },
    props: {
        errors: Object,
        data: Object,
        response: Object,
    },
    setup(props) {
        const title = ref("Cuadernillos");
        const toast = useToast();
        const { response,data } = toRefs(props);

        onMounted(() => {
            getCursos();
        });
        const area = ref(data.value.area);
        const cargas = ref([]);
        const getCarga = () => {
            axios.get(route("estudiantes.get-carga")).then(
                function (response) {
                    // console.log(response.data);
                    cargas.value = response.data.carga;
                }.bind(this)
            );
        };

        const fields = ref({
            id: "",
        });
        const idCarga = (id) => {
            fields.value.id = id;
        };

        const cursos = ref([]);
        const getCursos = () => {
            axios.get(route("estudiantes.get-cursos-estudiante")).then(
                function (response) {
                    cursos.value = response.data.cuadernillos;
                }.bind(this)
            );
        };

        return {
            title,
            cargas,
            getCarga,
            fields,
            idCarga,
            cursos,
            getCursos,
            area
        };
    },
    components: {
        AppLayout,
    },
};
</script>
<style scoped>
.acordion .curso-nombre {
    min-width: 0;
    overflow-wrap: anywhere;
    line-height: 1.35;
}
table th {
    float: right;
}
</style>
