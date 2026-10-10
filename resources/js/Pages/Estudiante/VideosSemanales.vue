<template>
    <Toast />
    <app-layout :title="titulo" :mode="2">
        <div class="card shadow-6">
            <div class="grid hidden sm:flex">
                <div class="col-12">
                    <h5 class="font-bold">{{ titulo }}</h5>
                </div>
            </div>
            <div class="grid">
                <div class="col-12" v-if="descripcion">
                    <Tag class="mr-2 bg-gray-500">{{ descripcion }}</Tag>
                </div>
                <div class="col-12 acordion">
                    <Accordion :multiple="true" :activeIndex="[0]">
                        <AccordionTab v-for="grupo in grupos" :key="grupo.clave">
                            <template #header>
                                <div class="grupo-nombre">
                                    <i class="pi pi-bookmark-fill" :style="{ color: grupo.color, fontSize: '19px' }"></i>
                                    {{ grupo.nombre }}
                                    <small class="ml-2 text-500">{{ grupo.publicadas }} de {{ totalSemanas }} publicadas</small>
                                </div>
                            </template>
                            <div class="grid">
                                <div class="col-12 md:col-6 lg:col-4" v-for="semana in grupo.semanas" :key="semana.semana">
                                    <div class="semana" :class="{ 'semana-pendiente': !semana.disponible }">
                                        <div class="font-bold mb-2">Semana {{ semana.semana }}</div>
                                        <template v-if="semana.disponible">
                                            <a
                                                v-if="semana.video"
                                                class="p-button p-component p-button-primary p-button-sm mr-2 mb-1"
                                                :href="semana.video"
                                                target="_blank"
                                                rel="noopener"
                                            >
                                                <i class="pi pi-youtube mr-2"></i> Ver video
                                            </a>
                                            <a
                                                v-if="semana.pdf"
                                                class="p-button p-component p-button-danger p-button-sm mb-1"
                                                :href="semana.pdf"
                                                target="_blank"
                                                rel="noopener"
                                            >
                                                <i class="pi pi-file-pdf mr-2"></i> Cuadernillo
                                            </a>
                                            <Tag v-if="conCuadernillo && semana.video && !semana.pdf" class="bg-gray-500">
                                                Cuadernillo proximamente
                                            </Tag>
                                        </template>
                                        <template v-else>
                                            <Tag class="bg-gray-500">Proximamente</Tag>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </AccordionTab>
                    </Accordion>
                </div>
                <div class="col-12" v-if="grupos.length === 0">
                    <Tag class="bg-gray-500">Todavia no hay contenido publicado</Tag>
                </div>
            </div>
        </div>
    </app-layout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout";

import { ref, toRefs } from "vue";

export default {
    props: {
        errors: Object,
        data: Object,
    },
    setup(props) {
        const { data } = toRefs(props);

        const titulo = ref(data.value.titulo ?? "Videos");
        const descripcion = ref(data.value.descripcion ?? "");
        const grupos = ref(data.value.grupos ?? []);
        const totalSemanas = ref(data.value.totalSemanas ?? 8);
        const conCuadernillo = ref(data.value.conCuadernillo ?? false);

        return {
            titulo,
            descripcion,
            grupos,
            totalSemanas,
            conCuadernillo,
        };
    },
    components: {
        AppLayout,
    },
};
</script>
<style scoped>
.acordion .grupo-nombre {
    min-width: 0;
    overflow-wrap: anywhere;
    line-height: 1.35;
}
.semana {
    border: 1px solid var(--surface-border);
    border-radius: 6px;
    padding: 1rem;
    height: 100%;
}
.semana-pendiente {
    opacity: 0.6;
}
</style>
