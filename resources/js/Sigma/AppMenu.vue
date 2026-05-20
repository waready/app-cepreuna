<template>
    <div class="layout-menu-container">
        <AppSubmenu :activeDefault="menuDefault" :items="model" class="layout-menu" :root="true" @menuitem-click="onMenuItemClick" />
    </div>
</template>

<script>
import AppSubmenu from "./AppSubmenu";

export default {
    props: {
        model: Array,
    },
    methods: {
        onMenuItemClick(event) {
            this.$emit("menuitem-click", event);
        },
    },
    computed: {
        menuDefault() {
            return this.model.findIndex((m) => {
                return m.path.toLowerCase() == route().l.pathname.split("/")[1];
            });
        },
        // let default;
        // default = this.model.find((m)=>{
        //     return
        // })
    },
    components: {
        AppSubmenu: AppSubmenu,
    },
};
</script>

<style scoped></style>
