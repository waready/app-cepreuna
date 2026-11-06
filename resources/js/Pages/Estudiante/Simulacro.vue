<template>
    <app-layout title="Simulacro" :mode="2">
        <section class="card simulacro-card">
            <div class="simulacro-heading">
                <div class="flex align-items-center gap-3">
                    <span class="simulacro-icon"><i class="pi pi-file-pdf"></i></span>
                    <div>
                        <span class="text-sm text-500">Ciclo {{ data.ciclo }}</span>
                        <h2 class="mt-1 mb-0 text-2xl font-bold">{{ data.titulo }}</h2>
                    </div>
                </div>
                <span class="simulacro-confirmado"><i class="pi pi-check-circle mr-2"></i>Inscripción confirmada</span>
            </div>
            <p class="mt-3 text-600">Ya estás inscrito en el simulacro de este ciclo. Puedes ver tu ficha y descargarla para presentarla el día del examen.</p>
            <div class="simulacro-datos">
                <div><span>Fecha del examen</span><strong>{{ data.fecha_examen }}</strong></div>
                <div><span>Horario de ingreso</span><strong>{{ data.ingreso }}</strong></div>
                <div><span>Lugar</span><strong>{{ data.lugar }}</strong></div>
                <div><span>Total registrado</span><strong>S/ {{ data.monto }}</strong></div>
            </div>
            <div class="simulacro-acciones">
                <a :href="data.url_descarga" class="p-button p-component"><i class="pi pi-download mr-2"></i>Descargar mi ficha</a>
                <a :href="data.url_ficha" target="_blank" rel="noopener" class="p-button p-component p-button-outlined"><i class="pi pi-external-link mr-2"></i>Abrir ficha</a>
                <small v-if="data.fecha_registro" class="text-500">Inscripción: {{ data.fecha_registro }}</small>
            </div>
        </section>
        <section class="card simulacro-card">
            <h3 class="text-lg font-bold mt-0">Mi ficha de inscripción</h3>
            <iframe :src="data.url_ficha" title="Ficha de inscripción al simulacro" class="simulacro-pdf"></iframe>
            <p class="text-sm text-500 mb-0">Si tu dispositivo no muestra el PDF, utiliza «Abrir ficha» o «Descargar mi ficha».</p>
        </section>
    </app-layout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout";

export default {
    components: { AppLayout },
    props: { data: { type: Object, required: true } },
};
</script>

<style scoped>
.simulacro-card { border-radius: 16px; }
.simulacro-heading { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.simulacro-icon { display: grid; place-items: center; width: 48px; height: 48px; border-radius: 14px; background: #e8f2ff; color: #2563eb; }
.simulacro-icon i { font-size: 24px; }
.simulacro-confirmado { padding: 9px 13px; border-radius: 30px; background: #e8f8ee; color: #166534; font-size: 13px; font-weight: 700; }
.simulacro-datos { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin: 24px 0; }
.simulacro-datos div { display: flex; flex-direction: column; gap: 7px; padding: 16px; border: 1px solid var(--surface-border, #dce3eb); border-radius: 12px; background: var(--surface-ground, #f8fafc); }
.simulacro-datos span { font-size: 12px; color: var(--text-color-secondary, #64748b); }
.simulacro-datos strong { overflow-wrap: anywhere; }
.simulacro-acciones { display: flex; align-items: center; flex-wrap: wrap; gap: 12px; }
.simulacro-acciones small { margin-left: auto; }
.simulacro-pdf { width: 100%; height: 760px; border: 1px solid var(--surface-border, #dce3eb); border-radius: 10px; background: #f1f5f9; }
@media (max-width: 960px) { .simulacro-datos { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 576px) { .simulacro-datos { grid-template-columns: 1fr; } .simulacro-acciones a { width: 100%; justify-content: center; } .simulacro-acciones small { margin-left: 0; } .simulacro-pdf { height: 560px; } }
</style>
