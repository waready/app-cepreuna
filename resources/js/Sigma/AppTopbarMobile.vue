<template>
    <header class="layout-topbar topbar-mobile">
        <div class="topbar-mobile-content flex justify-content-between align-items-center">
            <div class="flex align-items-center justify-content-center">
                <button type="button" class="p-link layout-menu-button" aria-label="Volver" @click="goBack">
                    <span class="pi pi-angle-left"></span>
                </button>
            </div>
            <div class="topbar-mobile-title flex align-items-center justify-content-center font-bold">{{ title }}</div>
            <div class="flex align-items-center justify-content-center">
                <button type="button" class="p-link layout-menu-button" aria-label="Abrir menú" @click="onMenuToggle">
                    <span class="pi pi-bars"></span>
                </button>
            </div>
        </div>
        <!-- <div class="layout-topbar-icons">
            <button class="p-link" @click.prevent="logout"><i class="pi pi-fw pi-power-off"></i><span>Salir</span></button>
        </div> -->
    </header>
</template>

<script>
export default {
    props: {
        title: String,
    },
    methods: {
        onMenuToggle(event) {
            this.$emit("menu-toggle", event);
        },
        logout() {
            this.$inertia.post(route("logout"));
        },
        goBack() {
            window.history.back();
        },
    },
};
</script>
