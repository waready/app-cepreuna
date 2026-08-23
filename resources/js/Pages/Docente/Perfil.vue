<template>
    <app-layout title="Mis datos" :mode="2">
        <section class="docente-profile">
            <header class="profile-hero">
                <img :src="foto" :alt="nombreCompleto" class="profile-avatar" />
                <div class="profile-heading">
                    <span class="profile-eyebrow">Perfil docente</span>
                    <h1>{{ nombreCompleto }}</h1>
                    <p>{{ cuenta.usuario }}</p>
                </div>
                <span class="period-badge">
                    <i class="pi pi-calendar"></i>
                    {{ periodoNombre }}
                </span>
            </header>

            <div class="profile-grid">
                <article class="profile-panel">
                    <div class="panel-title">
                        <i class="pi pi-id-card"></i>
                        <h2>Datos personales</h2>
                    </div>
                    <dl class="data-list">
                        <div>
                            <dt>Documento</dt>
                            <dd>{{ docente.nro_documento || "No registrado" }}</dd>
                        </div>
                        <div>
                            <dt>Correo institucional</dt>
                            <dd>{{ cuenta.usuario || "No registrado" }}</dd>
                        </div>
                        <div>
                            <dt>Grado principal</dt>
                            <dd>{{ relacionNombre(docente.grado_academico) }}</dd>
                        </div>
                        <div>
                            <dt>Programa</dt>
                            <dd>{{ relacionNombre(docente.programa) }}</dd>
                        </div>
                    </dl>
                </article>

                <article class="profile-panel">
                    <div class="panel-title">
                        <i class="pi pi-book"></i>
                        <h2>Formación registrada</h2>
                    </div>
                    <div v-if="grados.length" class="degree-list">
                        <div v-for="grado in grados" :key="grado.id" class="degree-item">
                            <i class="pi pi-check-circle"></i>
                            <div>
                                <strong>{{ relacionNombre(grado.grado_academico) }}</strong>
                                <span>{{ relacionNombre(grado.programa) }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="empty-state">
                        <i class="pi pi-info-circle"></i>
                        <p>No hay grados adicionales registrados para este período.</p>
                    </div>
                </article>
            </div>
        </section>
    </app-layout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout";
import { computed } from "vue";

export default {
    components: { AppLayout },
    props: {
        docente: { type: Object, required: true },
        cuenta: { type: Object, required: true },
        grados: { type: Array, default: () => [] },
        periodo: { type: Object, required: true },
        fotoPerfil: { type: String, default: "" },
    },
    setup(props) {
        const nombreCompleto = computed(() =>
            [props.docente.nombres, props.docente.paterno, props.docente.materno]
                .filter(Boolean)
                .join(" ")
        );
        const foto = computed(
            () =>
                props.fotoPerfil ||
                `https://ui-avatars.com/api/?name=${encodeURIComponent(nombreCompleto.value)}`
        );
        const periodoNombre = computed(
            () =>
                props.periodo.denominacion ||
                props.periodo.nombre ||
                props.periodo.descripcion ||
                `Período ${props.periodo.id}`
        );
        const relacionNombre = (relacion) =>
            relacion?.denominacion || relacion?.nombre || "No registrado";

        return { foto, nombreCompleto, periodoNombre, relacionNombre };
    },
};
</script>

<style scoped>
.docente-profile {
    --profile-ink: #23313d;
    --profile-muted: #667787;
    --profile-accent: #c74717;
    display: grid;
    gap: 1.25rem;
}

.profile-hero {
    position: relative;
    display: flex;
    align-items: center;
    gap: 1.25rem;
    overflow: hidden;
    padding: 1.5rem;
    border: 1px solid #ead8cf;
    border-radius: 18px;
    background: linear-gradient(120deg, #fff8f3 0%, #ffffff 56%, #f4e9df 100%);
    box-shadow: 0 12px 32px rgba(69, 45, 31, 0.08);
}

.profile-hero::after {
    content: "";
    position: absolute;
    right: -55px;
    bottom: -80px;
    width: 190px;
    height: 190px;
    border-radius: 50%;
    background: rgba(199, 71, 23, 0.09);
}

.profile-avatar {
    width: 88px;
    height: 88px;
    flex: 0 0 auto;
    border: 4px solid #fff;
    border-radius: 22px;
    object-fit: cover;
    box-shadow: 0 8px 22px rgba(91, 49, 28, 0.16);
}

.profile-heading {
    min-width: 0;
    flex: 1;
}

.profile-heading h1 {
    margin: 0.2rem 0 0.35rem;
    color: var(--profile-ink);
    font-size: clamp(1.35rem, 3vw, 2rem);
    line-height: 1.15;
}

.profile-heading p {
    overflow-wrap: anywhere;
    margin: 0;
    color: var(--profile-muted);
}

.profile-eyebrow {
    color: var(--profile-accent);
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.period-badge {
    position: relative;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.65rem 0.85rem;
    border-radius: 999px;
    background: #71391f;
    color: #fff;
    font-size: 0.82rem;
    font-weight: 700;
    white-space: nowrap;
}

.profile-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1.25rem;
}

.profile-panel {
    min-width: 0;
    padding: 1.25rem;
    border: 1px solid #e3e8ec;
    border-radius: 16px;
    background: #fff;
    box-shadow: 0 8px 24px rgba(35, 49, 61, 0.06);
}

.panel-title {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    margin-bottom: 1rem;
    color: var(--profile-accent);
}

.panel-title h2 {
    margin: 0;
    color: var(--profile-ink);
    font-size: 1.05rem;
}

.data-list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
    margin: 0;
}

.data-list div {
    min-width: 0;
    padding: 0.85rem;
    border-radius: 12px;
    background: #f7f9fa;
}

.data-list dt {
    margin-bottom: 0.35rem;
    color: var(--profile-muted);
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
}

.data-list dd {
    overflow-wrap: anywhere;
    margin: 0;
    color: var(--profile-ink);
    font-weight: 600;
}

.degree-list {
    display: grid;
    gap: 0.75rem;
}

.degree-item {
    display: flex;
    align-items: flex-start;
    gap: 0.7rem;
    padding: 0.85rem;
    border-left: 3px solid var(--profile-accent);
    border-radius: 10px;
    background: #fff8f3;
}

.degree-item i {
    margin-top: 0.15rem;
    color: var(--profile-accent);
}

.degree-item strong,
.degree-item span {
    display: block;
}

.degree-item span {
    margin-top: 0.2rem;
    color: var(--profile-muted);
    font-size: 0.85rem;
}

.empty-state {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    padding: 1rem;
    border-radius: 12px;
    background: #f7f9fa;
    color: var(--profile-muted);
}

.empty-state p {
    margin: 0;
}

@media (max-width: 767px) {
    .profile-hero {
        align-items: flex-start;
        flex-wrap: wrap;
        padding: 1rem;
    }

    .profile-avatar {
        width: 70px;
        height: 70px;
        border-radius: 18px;
    }

    .period-badge {
        width: 100%;
        justify-content: center;
    }

    .profile-grid,
    .data-list {
        grid-template-columns: 1fr;
    }

    .profile-panel {
        padding: 1rem;
    }
}
</style>
