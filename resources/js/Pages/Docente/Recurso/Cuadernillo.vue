<template>
    <Toast />
    <app-layout :title="title" :mode="2">
        <div class="card shadow-6">
            <div class="grid hidden sm:flex">
                <div class="col-12">
                    <h5 class="font-bold">Cuadernillos</h5>
                </div>
            </div>
            <div class="grid">
                <div class="col-12">
                    <Tag class="mr-2" value="Docentes"></Tag>
                    <Tag class="mr-2" value="Estudiantes" severity="danger"></Tag>

                </div>
                <div class="col-12 acordion">
                    <Accordion :multiple="true" :activeIndex="[0]">
                        <AccordionTab v-for="curso in cursos" :key="curso.id">
                            <template #header>
                                <div class="curso-nombre"><i class="pi pi-bookmark-fill" :style="{ color: curso.color, fontSize: '19px' }"></i><b>{{ curso.area }}</b> - {{ curso.curso }}</div>
                            </template>
                            <template v-if="curso.cuadernillos.length > 0" v-for="cuadernillo in curso.cuadernillos" :key="cuadernillo.id">
                                <a class="p-button p-component p-button-sm ml-1" :href="curso.base_path + '/storage/documentos/' + cuadernillo.path" target="_blank" download>
                                    <i class="pi pi-file-pdf mr-2"></i> Semana {{ cuadernillo.semana }}
                                </a>
                            </template>
                            <template v-else>
                                <Tag class="mr-2 bg-gray-500">No existen cuadernillos estudiantes para este curso </Tag>
                            </template>
                            <hr>
                            <template v-if="curso.cuadernillosEstudiante.length > 0" v-for="cuadernillo in curso.cuadernillosEstudiante" :key="cuadernillo.id">
                                <a class="p-button p-component p-button-danger p-button-sm ml-1" :href="curso.base_path + '/storage/documentos/' + cuadernillo.path" target="_blank" download>
                                    <i class="pi pi-file-pdf mr-2"></i> Semana {{ cuadernillo.semana }}
                                </a>
                            </template>
                            <template v-else>
                                <Tag class="mr-2 bg-gray-500">No existen cuadernillos estudiantes para este curso </Tag>
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
        response: Object,
    },
    setup(props) {
        const title = ref("Cuadernillos");
        const toast = useToast();
        const { response } = toRefs(props);

        onMounted(() => {
            getCursos();
        });

        // const cargas = ref([]);
        // const getCarga = () => {
        //     axios.get(route("estudiantes.get-carga")).then(
        //         function (response) {
        //             // console.log(response.data);
        //             cargas.value = response.data.carga;
        //         }.bind(this)
        //     );
        // };

        const fields = ref({
            id: "",
        });
        const idCarga = (id) => {
            fields.value.id = id;
        };

        const cursos = ref([]);
        const getCursos = () => {
            axios.get(route("docentes.recursos.get-cursos-docente")).then(
                function (response) {
                    cursos.value = response.data.cuadernillos;
                }.bind(this)
            );
        };

        return {
            title,
            // cargas,
            // getCarga,
            fields,
            idCarga,
            cursos,
            getCursos,
        };
    },
    components: {
        AppLayout,
    },
};
</script>
<style scoped>
.acordion .curso-nombre {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0px;
    padding: 11px;
}
table th {
    float: right;
}
</style>
