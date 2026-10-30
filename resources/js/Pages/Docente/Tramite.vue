<template>
    <Toast />
    <app-layout :title="title" :mode="2">
        <div class="card shadow-6">
            <div class="grid hidden sm:flex">
                <div class="col-12">
                    <h5 class="font-bold">Trámite documentario</h5>
                </div>
            </div>

            <div class="grid" v-if="error">
                <div class="col-12">
                    <Tag class="bg-red-500">{{ error }}</Tag>
                </div>
            </div>

            <div class="grid" v-else-if="!informes.length">
                <div class="col-12">
                    <Tag class="bg-gray-500">Todavía no hay informes configurados para este ciclo.</Tag>
                </div>
            </div>

            <div class="grid" v-else>
                <div class="col-12" v-for="informe in informes" :key="informe.periodo.id">
                    <div class="informe">
                        <div class="flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                            <div>
                                <span class="font-bold text-lg">{{ informe.periodo.nombre }}</span>
                                <span class="text-500 ml-2">{{ informe.periodo.desde }} al {{ informe.periodo.hasta }}</span>
                            </div>
                            <Tag :class="informe.periodo.habilitado ? 'bg-green-600' : 'bg-gray-500'">
                                {{ informe.periodo.habilitado ? 'Habilitado' : informe.periodo.sin_horas ? 'Sin horas' : 'Aún no habilitado' }}
                            </Tag>
                        </div>

                        <!-- Sin expediente: todavía no se ha generado -->
                        <template v-if="!informe.expediente">
                            <!-- El backend avisa por qué no se puede generar (p. ej. sin horas en el periodo). -->
                            <p v-if="informe.periodo.aviso" class="text-600 mb-3">
                                {{ informe.periodo.aviso }}
                            </p>
                            <p v-else class="text-600 mb-3">
                                Tu informe de este periodo aún no se ha generado.
                            </p>
                            <Button
                                v-if="informe.periodo.habilitado"
                                label="Generar mi informe"
                                icon="pi pi-file"
                                :loading="generando === informe.periodo.id"
                                @click="generar(informe.periodo.id)"
                            />
                            <Tag v-else-if="!informe.periodo.aviso" class="bg-gray-500">
                                Podrás generarlo cuando administración habilite el periodo.
                            </Tag>
                        </template>

                        <!-- Ya entregado: la lista de documentos ya no le sirve,
                             porque no puede cambiar nada. Queda el comprobante,
                             lo que presentó y por dónde va. -->
                        <template v-else-if="presentado(informe)">
                            <div class="flex align-items-center gap-3 flex-wrap mb-2">
                                <span class="codigo">{{ informe.expediente.codigo }}</span>
                                <Tag :class="claseExpediente(informe.expediente)">
                                    {{ informe.expediente.estado_texto }}
                                </Tag>
                            </div>

                            <p class="recepcion mb-3">
                                <i class="pi pi-check-circle mr-1"></i>
                                Lo entregaste en la oficina el
                                <strong>{{ fecha(informe.expediente.recepcionado_en) }}</strong>.
                            </p>
                            <p v-if="informe.expediente.nota" class="text-sm text-600 mb-3">
                                Nota de administración: {{ informe.expediente.nota }}
                            </p>

                            <div class="flex align-items-center gap-3 flex-wrap mb-4">
                                <div v-if="informe.horas" class="dato">
                                    <div class="text-500 text-sm">Total a pagar</div>
                                    <div class="text-xl font-bold text-green-700">{{ informe.horas.total_con_descuento }} h</div>
                                </div>
                                <a
                                    class="p-button p-component p-button-danger"
                                    :href="urlDocumento(informe.expediente.codigo, 'completo')"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    <i class="pi pi-download mr-2"></i> Descargar lo que presentaste
                                </a>
                            </div>

                            <div class="font-bold mb-2">Seguimiento de tu informe</div>
                            <ol class="seguimiento">
                                <li v-for="(paso, i) in informe.seguimiento" :key="i" :class="paso.estado">
                                    <i :class="iconoPaso(paso)"></i>
                                    <div>
                                        <div><strong>{{ paso.oficina }}</strong> — {{ paso.accion }}</div>
                                        <div v-if="paso.fecha" class="text-500 text-sm">
                                            {{ paso.estado === "actual" ? "Desde el" : "El" }} {{ fecha(paso.fecha) }}
                                        </div>
                                    </div>
                                </li>
                            </ol>
                            <p class="text-500 text-sm mt-2 mb-0">
                                Los siguientes pasos aparecerán aquí a medida que tu informe avance por las oficinas.
                            </p>
                        </template>

                        <template v-else>
                            <div class="flex align-items-center gap-3 flex-wrap mb-2">
                                <span class="codigo">{{ informe.expediente.codigo }}</span>
                                <Tag :class="claseExpediente(informe.expediente)">
                                    {{ informe.expediente.estado_texto }}
                                </Tag>
                                <Tag
                                    v-if="enPreparacion(informe)"
                                    :class="informe.listo ? 'bg-green-600' : 'bg-orange-500'"
                                >
                                    {{ informe.listo ? 'Documentos completos' : 'Te falta documentación' }}
                                </Tag>
                            </div>

                            <!-- El comprobante de que lo entregó -->
                            <p v-if="informe.expediente.recepcionado_en" class="recepcion mb-3">
                                <i class="pi pi-check-circle mr-1"></i>
                                Presentaste este informe en la oficina el
                                <strong>{{ fecha(informe.expediente.recepcionado_en) }}</strong>.
                            </p>
                            <p v-if="informe.expediente.nota" class="text-sm text-600 mb-3">
                                Nota de administración: {{ informe.expediente.nota }}
                            </p>

                            <!-- Horas, que es lo primero que el docente mira -->
                            <div class="grid mb-2" v-if="informe.horas">
                                <div class="col-6 md:col-3" v-for="h in resumenHoras(informe.horas)" :key="h.etiqueta">
                                    <div class="dato">
                                        <div class="text-500 text-sm">{{ h.etiqueta }}</div>
                                        <div class="text-xl font-bold" :class="h.clase">{{ h.valor }}</div>
                                    </div>
                                </div>
                            </div>

                            <p v-if="informe.horas && informe.horas.tardanzas > 0" class="text-sm text-600 mb-3">
                                Se registraron {{ informe.horas.tardanzas }} tardanza(s).
                                Cada tres equivalen al descuento de una hora académica:
                                se descontaron {{ informe.horas.descuento }} hora(s).
                                <template v-if="informe.horas.arrastre > 0">
                                    Las {{ informe.horas.arrastre }} tardanza(s) restante(s) no completan una hora
                                    y se arrastran al informe siguiente.
                                </template>
                            </p>

                            <!-- Lista de verificación -->
                            <table class="verificacion">
                                <thead>
                                    <tr>
                                        <th>Documento</th>
                                        <th class="col-estado">Estado</th>
                                        <th class="col-accion"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="doc in informe.documentos" :key="doc.codigo">
                                        <td>
                                            {{ doc.nombre }}
                                            <div class="text-500 text-sm">{{ comoSeObtiene(doc.origen) }}</div>
                                            <div v-if="doc.observacion" class="text-sm text-red-600">
                                                {{ doc.observacion }}
                                            </div>

                                            <!-- Hay documentos que no todos presentan -->
                                            <label v-if="doc.declarable" class="no-corresponde">
                                                <input
                                                    type="checkbox"
                                                    :checked="doc.declarado_no_aplica"
                                                    :disabled="marcando === doc.codigo"
                                                    @change="declarar(informe.expediente.codigo, doc.codigo, $event.target.checked)"
                                                />
                                                No me corresponde
                                            </label>
                                            <div v-else-if="doc.bloqueo" class="text-500 text-sm">
                                                <i class="pi pi-lock mr-1"></i>{{ doc.bloqueo }}
                                            </div>
                                        </td>
                                        <td class="col-estado">
                                            <Tag :class="claseEstado(doc)">{{ textoEstado(doc) }}</Tag>
                                        </td>
                                        <td class="col-accion">
                                            <a
                                                v-if="doc.descargable"
                                                class="p-button p-component p-button-sm p-button-outlined"
                                                :href="urlDocumento(informe.expediente.codigo, doc.codigo)"
                                                target="_blank"
                                                rel="noopener"
                                            >
                                                <i class="pi pi-file-pdf mr-1"></i> Ver
                                            </a>
                                            <Button
                                                v-else-if="doc.subible"
                                                class="p-button-sm p-button-outlined"
                                                :label="doc.tiene_archivo ? 'Cambiar' : 'Subir'"
                                                icon="pi pi-upload"
                                                :loading="subiendo === doc.codigo"
                                                @click="elegirArchivo(informe.expediente.codigo, doc.codigo)"
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="mt-3 flex gap-2 flex-wrap">
                                <a
                                    class="p-button p-component p-button-danger"
                                    :href="urlDocumento(informe.expediente.codigo, 'completo')"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    <i class="pi pi-download mr-2"></i> Descargar expediente completo
                                </a>
                                <Button
                                    v-if="informe.periodo.habilitado && enPreparacion(informe)"
                                    label="Recalcular mis horas"
                                    icon="pi pi-refresh"
                                    class="p-button-outlined"
                                    :loading="generando === informe.periodo.id"
                                    @click="generar(informe.periodo.id)"
                                />
                            </div>

                            <div v-if="!informe.listo && enPreparacion(informe)" class="mt-3">
                                <Tag class="bg-orange-500">
                                    Antes de llevarlo a la oficina, debes completar:
                                    {{ informe.le_falta_subir.join(', ') }}
                                </Tag>
                            </div>
                            <p v-else-if="enPreparacion(informe)" class="text-600 text-sm mt-3">
                                Imprime estas hojas, fírmalas y entrégalas en la oficina de
                                administración. Cuando las reciban lo verás aquí.
                            </p>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Oculto: lo abre el botón de cada fila. Un solo campo para toda
                 la tabla, porque solo se sube un documento a la vez. -->
            <input
                ref="campoArchivo"
                type="file"
                class="hidden"
                accept=".pdf,.jpg,.jpeg,.png"
                @change="enviarArchivo"
            />
        </div>
    </app-layout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout";
import { Inertia } from "@inertiajs/inertia";
import { computed, ref, toRefs } from "vue";

export default {
    props: {
        errors: Object,
        data: Object,
    },
    setup(props) {
        const title = ref("Trámite");
        const { data } = toRefs(props);

        // Calculados, no copiados: con un ref la pantalla se quedaba con los
        // datos de la primera carga y no reflejaba ni el recalculo de horas ni
        // las marcas de documentos, aunque el servidor ya los hubiera guardado.
        const informes = computed(() => data.value.informes ?? []);
        const error = computed(() => data.value.error ?? null);
        const generando = ref(null);

        const generar = (periodoId) => {
            generando.value = periodoId;
            Inertia.post(route("docentes.tramite.generar", periodoId), {}, {
                preserveScroll: true,
                onFinish: () => { generando.value = null; },
            });
        };

        const urlDocumento = (codigo, tipo) => route("docentes.tramite.documento", [codigo, tipo]);

        // Mientras está en preparación el docente puede tocarlo; después ya hay
        // un papel firmado en la oficina y el expediente queda como está.
        const enPreparacion = (informe) => informe.expediente?.estado === "preparacion";

        // Entregado y en camino. Un expediente observado no entra: ahí sí tiene
        // que ver la lista para saber qué subsanar.
        const presentado = (informe) => !!informe.expediente
            && !["preparacion", "observado"].includes(informe.expediente.estado)
            && !!informe.expediente.recepcionado_en;

        const iconoPaso = (paso) => ({
            hecho: "pi pi-check-circle",
            actual: "pi pi-circle-fill",
        }[paso.estado] ?? "pi pi-circle");

        const claseExpediente = (expediente) => ({
            preparacion: "bg-gray-500",
            recepcionado: "bg-green-600",
            observado: "bg-red-500",
            presidencia: "bg-blue-500",
            rectorado: "bg-blue-500",
            pendiente_recibo: "bg-orange-500",
            finalizado: "bg-green-700",
        }[expediente.estado] ?? "bg-gray-500");

        const fecha = (valor) => {
            if (!valor) return "";
            // Viene "2026-10-28 09:15:00"; al docente le basta el día.
            const [anio, mes, dia] = valor.slice(0, 10).split("-");
            return `${dia}/${mes}/${anio}`;
        };

        // Subida de anexos: el botón abre el campo oculto y al elegir archivo
        // se envía solo, sin un modal de por medio.
        const campoArchivo = ref(null);
        const subiendo = ref(null);
        const destino = ref(null);

        const elegirArchivo = (codigo, tipo) => {
            destino.value = { codigo, tipo };
            campoArchivo.value.value = null;   // permite reelegir el mismo archivo
            campoArchivo.value.click();
        };

        const marcando = ref(null);

        const declarar = (codigo, tipo, noAplica) => {
            marcando.value = tipo;
            Inertia.post(
                route("docentes.tramite.declarar", [codigo, tipo]),
                { no_aplica: noAplica },
                {
                    preserveScroll: true,
                    onFinish: () => { marcando.value = null; },
                },
            );
        };

        const enviarArchivo = (evento) => {
            const archivo = evento.target.files[0];
            if (!archivo || !destino.value) return;

            const { codigo, tipo } = destino.value;
            subiendo.value = tipo;

            Inertia.post(
                route("docentes.tramite.subir", [codigo, tipo]),
                { archivo },
                {
                    forceFormData: true,
                    preserveScroll: true,
                    onFinish: () => {
                        subiendo.value = null;
                        destino.value = null;
                    },
                },
            );
        };

        // Cómo llega cada documento: así el docente entiende de un vistazo
        // qué tiene que hacer él y qué ya está resuelto.
        const comoSeObtiene = (origen) => ({
            generado: "Lo genera el sistema",
            ficha: "Se toma de tu ficha",
            carga_docente: "Debes subirlo tú",
            carga_admin: "Lo adjunta administración",
            fisico: "Lo entregas en físico",
        }[origen] ?? origen);

        const textoEstado = (doc) => {
            if (doc.estado === "no_aplica") return "No aplica";
            if (doc.origen === "generado") return "Listo";
            if (doc.origen === "fisico") return "En físico";
            // Lo que pone administración, como la copia del contrato, está
            // listo cuando lo da por cumplido: no hay nada que validar del docente.
            if (doc.origen === "carga_admin" && doc.estado === "validado") return "Listo";
            return {
                disponible: "Disponible",
                pendiente: "Pendiente",
                presentado: "Presentado",
                en_revision: "En revisión",
                validado: "Validado",
                observado: "Observado",
            }[doc.estado] ?? doc.estado;
        };

        const claseEstado = (doc) => {
            if (doc.estado === "observado") return "bg-red-500";
            if (doc.estado === "no_aplica") return "bg-gray-400";
            if (doc.origen === "generado" || doc.estado === "disponible" || doc.estado === "validado") return "bg-green-600";
            if (doc.origen === "carga_admin" || doc.origen === "fisico") return "bg-blue-500";
            return "bg-orange-500";
        };

        const resumenHoras = (h) => [
            { etiqueta: "Horas presencial", valor: h.presencial, clase: "" },
            { etiqueta: "Horas virtual", valor: h.virtual, clase: "" },
            { etiqueta: "Descuento", valor: h.descuento, clase: h.descuento > 0 ? "text-red-600" : "" },
            { etiqueta: "Total a pagar", valor: h.total_con_descuento, clase: "text-green-700" },
        ];

        return {
            title, informes, error, generando, campoArchivo, subiendo, marcando,
            generar, urlDocumento, comoSeObtiene, textoEstado, claseEstado, resumenHoras,
            elegirArchivo, enviarArchivo, declarar, enPreparacion, claseExpediente, fecha,
            presentado, iconoPaso,
        };
    },
    components: { AppLayout },
};
</script>

<style scoped>
.informe {
    border: 1px solid var(--surface-border);
    border-radius: 8px;
    padding: 1.25rem;
    margin-bottom: 1rem;
}
.codigo {
    font-family: monospace;
    font-size: 1.05rem;
    font-weight: 700;
    /* El gap de la fila no siempre llega, y el codigo quedaba pegado a la
       etiqueta de estado: "EXP-2026II-1-0110Documentos completos". */
    margin-right: 0.75rem;
}
.dato {
    border: 1px solid var(--surface-border);
    border-radius: 6px;
    padding: 0.6rem 0.8rem;
}
.verificacion {
    width: 100%;
    border-collapse: collapse;
}
.verificacion th,
.verificacion td {
    border-bottom: 1px solid var(--surface-border);
    padding: 0.55rem 0.4rem;
    text-align: left;
    vertical-align: top;
}
.verificacion th {
    font-size: 0.85rem;
    color: var(--text-color-secondary);
}
.col-estado {
    width: 9.5rem;
}
.col-accion {
    width: 6rem;
    text-align: right;
}
.recepcion {
    color: var(--green-700, #15803d);
    font-size: 0.95rem;
    margin: 0;
}
/* Como el seguimiento de un envío: una línea que une los pasos, verde lo
   que ya pasó, azul donde está ahora y gris lo que falta. */
.seguimiento {
    list-style: none;
    margin: 0;
    padding: 0;
}
.seguimiento li {
    position: relative;
    display: flex;
    gap: 0.75rem;
    padding: 0 0 1rem 0;
}
.seguimiento li:last-child {
    padding-bottom: 0;
}
.seguimiento li:not(:last-child)::before {
    content: "";
    position: absolute;
    left: 0.6rem;
    top: 1.4rem;
    bottom: 0.1rem;
    border-left: 2px solid var(--surface-border);
}
.seguimiento li > i {
    font-size: 1.25rem;
    line-height: 1.4rem;
    color: var(--gray-400, #9ca3af);
}
.seguimiento li.hecho > i {
    color: var(--green-600, #16a34a);
}
.seguimiento li.actual > i {
    color: var(--blue-500, #3b82f6);
}
.seguimiento li.pendiente {
    color: var(--text-color-secondary);
}
.no-corresponde {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    margin-top: 0.35rem;
    font-size: 0.85rem;
    color: var(--text-color-secondary);
    cursor: pointer;
}
</style>
