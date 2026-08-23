<template>
    <app-layout :title="title" :mode="2">
        <div class="card shadow-6 horario-panel">
            <div class="grid hidden sm:flex">
                <div class="col-12">
                    <h5 class="font-bold">Horarios</h5>
                </div>
            </div>
            <div class="grid">
                <div v-if="cargando" class="col-12 py-5 text-center text-600">Cargando horario...</div>
                <div v-else-if="errorHorario" class="col-12 p-3 border-round bg-red-50 text-red-700">
                    {{ errorHorario }}
                </div>
                <template v-else>
                    <div v-if="contactos.length" class="col-12 pb-0">
                        <h6 class="mb-2 text-700">Contactos por grupo</h6>
                        <div class="grid contacto-grid">
                            <div v-for="contacto in contactos" :key="contacto.grupo_aula_id" class="col-12 md:col-6 xl:col-4">
                                <article class="contacto-card">
                                    <div class="contacto-grupo">
                                        <i class="pi pi-users"></i>
                                        Grupo {{ contacto.grupo }}
                                    </div>
                                    <div class="contacto-fila">
                                        <span class="contacto-cargo">Auxiliar</span>
                                        <template v-if="contacto.auxiliar">
                                            <strong>{{ contacto.auxiliar.nombre }}</strong>
                                            <a v-if="contacto.auxiliar.telefono" :href="telefonoHref(contacto.auxiliar.telefono)">
                                                <i class="pi pi-phone"></i> {{ contacto.auxiliar.telefono }}
                                            </a>
                                            <small v-else>No registrado</small>
                                        </template>
                                        <strong v-else class="contacto-pendiente">Por asignar</strong>
                                    </div>
                                    <div class="contacto-fila">
                                        <span class="contacto-cargo">Coordinador</span>
                                        <template v-if="contacto.coordinador">
                                            <strong>{{ contacto.coordinador.nombre }}</strong>
                                            <a v-if="contacto.coordinador.telefono" :href="telefonoHref(contacto.coordinador.telefono)">
                                                <i class="pi pi-phone"></i> {{ contacto.coordinador.telefono }}
                                            </a>
                                            <small v-else>No registrado</small>
                                        </template>
                                        <strong v-else class="contacto-pendiente">Por asignar</strong>
                                    </div>
                                </article>
                            </div>
                        </div>
                    </div>
                    <div v-if="horariosVisibles.length === 0" class="col-12 py-5 text-center text-600">
                        No hay un horario asignado para el periodo actual.
                    </div>
                </template>
                <div class="col-12 turnos" v-for="horario in horariosVisibles" :key="horario.id">
                    <h5>{{ horario.turno }}</h5>
                    <Timeline :value="horario.dias" align="left">
                        <template #opposite="slotProps">
                            <small class="p-text-secondary">{{slotProps.item.dia}}</small>
                        </template>
                        <template #content="slotProps">
                            <Card>
                                <!-- <template #title>
                                    {{slotProps.item.status}}
                                </template>-->
                                <!-- <template #subtitle>
                                    Lunes
                                </template> -->
                                <template #content>
                                    <template v-for="dis in slotProps.item.disponibilidad" :key="dis.hora_inicio">
                                        <div class="horario-bloque grid border-500 shadow-2 py-1 my-1" v-if="dis.horario!=null" :style="'background:'+dis.horario.curso.color">
                                            <div class="horario-hora col-6 md:col-4 pb-0"><Tag icon="pi pi-clock" severity="Info" :value="dis.hora_inicio+' - '+dis.hora_fin"></Tag></div>
                                            <div class="horario-grupo col-6 md:col-3 pb-0"><Tag severity="warning" :value="'('+dis.horario.grupo+')'"></Tag></div>
                                            <div class="horario-curso col-6 md:col-5" >
                                                <b>{{dis.horario.curso.denominacion}}</b>
                                            </div>

                                        </div>
                                    </template>
                                </template>
                            </Card>
                        </template>
                    </Timeline>
                </div>
            </div>
        </div>
        <!-- <pre>{{ users }}</pre> -->
    </app-layout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout";

import { ref, onMounted, computed } from "vue";
import axios from "axios";
// import AppTopBarSocial from "../Sigma/AppTopbarSocial.vue";
export default {
    components: {
        AppLayout,
    },
    props: {
        data: Object,
    },
    setup() {
        const title = ref("Horarios");
        const horarios = ref([]);
        const contactos = ref([]);
        const cargando = ref(true);
        const errorHorario = ref("");
        const horariosVisibles = computed(() => {
            return horarios.value.filter((horario) => {
                return horario.dias.some((dia) => {
                    return dia.disponibilidad.some((bloque) => bloque.horario != null);
                });
            });
        });
        const telefonoHref = (telefono) => {
            return "tel:" + String(telefono).replace(/[^\d+]/g, "");
        };
        const getHorario = () => {
            cargando.value = true;
            errorHorario.value = "";
            axios
                .get(route("docentes.get-horario"), {
                    // params: {
                    //     area: form.area ? form.area.id : "",
                    //     turno: form.area ? form.turno.id : "",
                    //     sede: form.area ? form.sede.id : "",
                    // },
                })
                .then((response) => {
                    horarios.value = response.data.horario || [];
                    contactos.value = response.data.contactos || [];
                })
                .catch(() => {
                    horarios.value = [];
                    contactos.value = [];
                    errorHorario.value = "No se pudo cargar el horario. Intente nuevamente.";
                })
                .finally(() => {
                    cargando.value = false;
                });
        };

        onMounted(() => {
            getHorario();
        });
        return {
            title,
            horarios,
            horariosVisibles,
            contactos,
            cargando,
            errorHorario,
            telefonoHref,
            getHorario,
        };
    },
};
</script>
<style>
.turnos .p-timeline-event-opposite{
    min-width: 40px !important;
    flex: 0;
}

.contacto-grid {
    margin-top: 0;
}

.contacto-card {
    height: 100%;
    overflow: hidden;
    border: 1px solid #f0d3c2;
    border-radius: 12px;
    background: #fffaf7;
    box-shadow: 0 5px 16px rgba(108, 53, 25, 0.08);
}

.contacto-grupo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: #c94b1b;
    color: #fff;
    font-weight: 700;
}

.contacto-fila {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    padding: 0.75rem 1rem;
    color: #263238;
}

.contacto-fila + .contacto-fila {
    border-top: 1px solid #f0ded3;
}

.contacto-fila a {
    color: #b74816;
    font-weight: 600;
    text-decoration: none;
}

.contacto-fila a:hover {
    text-decoration: underline;
}

.contacto-fila small,
.contacto-pendiente {
    color: #78909c;
}

.contacto-cargo {
    color: #8d4d2f;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

@media (max-width: 640px) {
    .horario-panel.card {
        padding: 0.85rem;
    }

    .turnos .p-card-body {
        padding: 0.75rem;
    }

    .turnos .p-timeline-event-content {
        min-width: 0;
        padding-left: 0.5rem;
    }

    .turnos .p-card-content {
        padding: 0;
    }

    .horario-bloque {
        margin-right: 0;
        margin-left: 0;
        border-radius: 8px;
    }

    .horario-hora,
    .horario-grupo,
    .horario-curso {
        width: 100%;
        padding: 0.35rem 0.5rem;
    }

    .horario-curso {
        overflow-wrap: anywhere;
    }
}
</style>
