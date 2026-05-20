<template>
    <div class="layout-topbar topbar-mobile">
        <div class="flex justify-content-between flex-wrap card-container purple-container">
            <div class="flex align-items-center justify-content-center">
                <button class="p-link layout-menu-button" @click="goBack">
                    <span class="pi pi-angle-left"></span>
                </button>
            </div>
            <div class="flex align-items-center justify-content-center font-bold">{{ title }}</div>
            <div class="flex align-items-center justify-content-center">
                <button class="p-link layout-menu-button" @click="onMenuToggle">
                    <span class="pi pi-bars"></span>
                </button>
            </div>
        </div>
        <!-- <div class="layout-topbar-icons">
            <button class="p-link" @click.prevent="logout"><i class="pi pi-fw pi-power-off"></i><span>Salir</span></button>
        </div> -->
    </div>
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
