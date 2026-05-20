<template>
    <div class="layout-topbar">
        <div class="flex justify-content-between flex-wrap card-container purple-container">
            <div class="flex align-items-center justify-content-center">
                <!-- <button class="p-link layout-menu-button">
                    <span class="pi pi-home"></span>
                </button> -->
            </div>
            <div class="flex align-items-center justify-content-center">
                <!-- <button class="p-link layout-menu-button">
                    <span class="pi pi-bell"></span>
                </button> -->
            </div>
            <div class="flex align-items-center justify-content-center">
                <!-- <button class="p-link layout-menu-button">
                    <span class="pi pi-sitemap"></span>
                </button> -->
            </div>
            <div class="flex align-items-center justify-content-center">
                <button class="p-link layout-menu-button" @click="onMenuToggle">
                    <span class="pi pi-bars"></span>
                </button>
            </div>
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
