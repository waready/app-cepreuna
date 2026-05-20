<template>
    <div class="layout-topbar">
        <button class="p-link layout-menu-button" @click="onMenuToggle">
            <span class="pi pi-bars"></span>
        </button>
        <div class="layout-topbar-icons">
            <button class="p-link" @click.prevent="logout"><i class="pi pi-fw pi-power-off"></i><span>Salir</span></button>
            <!-- <button class="p-link">
                <span class="layout-topbar-item-text">Events</span>
                <span class="layout-topbar-icon pi pi-calendar"></span>
                <span class="layout-topbar-badge">5</span>
            </button>
            <button class="p-link">
                <span class="layout-topbar-item-text">Settings</span>
                <span class="layout-topbar-icon pi pi-cog"></span>
            </button>
            <button class="p-link">
                <span class="layout-topbar-item-text">User</span>
                <span class="layout-topbar-icon pi pi-user"></span>
            </button> -->
        </div>
    </div>
</template>

<script>
export default {
    methods: {
        onMenuToggle(event) {
            this.$emit("menu-toggle", event);
        },
        logout() {
            this.$inertia.post(route("logout"));
        },
    },
};
</script>
