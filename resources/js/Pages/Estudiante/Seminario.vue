<template>
    <Toast />
    <app-layout :title="title" :mode="2">
        <div class="card shadow-6">
            <div class="grid hidden sm:flex">
                <div class="col-12">
                    <h5 class="font-bold">Seminarios de Idiomas</h5>
                </div>
            </div>
            <div class="grid">
                <div class="col-12">
                    <Tag class="mr-2 bg-gray-500">
                        {{ totalSemanas }} semanas por idioma. Cada semana incluye el video de la sesion y su cuadernillo.
                    </Tag>
                </div>
                <div class="col-12 acordion">
                    <Accordion :multiple="true" :activeIndex="[0]">
                        <AccordionTab v-for="idioma in idiomas" :key="idioma.clave">
                            <template #header>
                                <div class="idioma-nombre">
                                    <i class="pi pi-bookmark-fill" :style="{ color: idioma.color, fontSize: '19px' }"></i>
                                    {{ idioma.nombre }}
                                    <small class="ml-2 text-500">{{ idioma.publicadas }} de {{ totalSemanas }} publicadas</small>
                                </div>
                            </template>
                            <div class="grid">
                                <div class="col-12 md:col-6 lg:col-4" v-for="semana in idioma.semanas" :key="semana.semana">
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
                                            <Tag v-if="semana.video && !semana.pdf" class="bg-gray-500">
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
        const title = ref("Seminarios");
        const { data } = toRefs(props);

        const idiomas = ref(data.value.idiomas ?? []);
        const totalSemanas = ref(data.value.totalSemanas ?? 8);

        return {
            title,
            idiomas,
            totalSemanas,
        };
    },
    components: {
        AppLayout,
    },
};
</script>
<style scoped>
.acordion .idioma-nombre {
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
