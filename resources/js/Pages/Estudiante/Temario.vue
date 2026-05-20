<template>
    <Toast />
    <app-layout :title="title" :mode="2">
        <div class="card shadow-6">
            <div class="grid hidden sm:flex">
                <div class="col-12">
                    <h5 class="font-bold">Temarios</h5>
                </div>
            </div>
            <div class="grid">
                <div class="col-12">
                    <Tag class="mr-2" value="Temarios"></Tag>
                </div>
                <div class="col-12 acordion">
                    <Accordion :multiple="true" :activeIndex="[0]">
                        <AccordionTab v-for="curso in cursos" :key="curso.id">
                            <template #header>
                                <div class="curso-nombre"><i class="pi pi-bookmark-fill" :style="{ color: curso.color, fontSize: '19px' }"></i><b>{{ curso.area }}</b> - {{ curso.curso }}</div>
                            </template>
                            <template v-if="curso.temarios!=null">
                                    <a class="p-button p-component p-button-sm ml-1" :href="curso.base_path + '/storage/documentos/' + curso.temarios.path" target="_blank" download>
                                        <i class="pi pi-file-pdf mr-2"></i> Descargar Temario
                                    </a>
                            </template>
                            <template v-else>
                                <Tag class="mr-2 bg-gray-500">No existen Temarios para este curso </Tag>
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

import { ref, onMounted, toRefs } from "vue";
import axios from "axios";

export default {
    props: {
        errors: Object,
        response: Object,
    },
    setup(props) {
        const title = ref("Temarios");
        const toast = useToast();
        const { response } = toRefs(props);

        onMounted(() => {
            getCursos();
        });

        const cursos = ref([]);
        const getCursos = () => {
            axios.get(route("estudiantes.get-cursos-estudiante-temario")).then(
                function (response) {
                    cursos.value = response.data.temarios;
                }.bind(this)
            );
        };

        return {
            title,
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
