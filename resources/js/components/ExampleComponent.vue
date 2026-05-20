<template>
    <div>
        <h5>{{ msg }}</h5>
        <Button label="Secondary" class="p-button-secondary" />
        <Button label="Primary" />
        <Button
            type="button"
            label="Messages"
            icon="pi pi-users"
            class="p-button-warning"
            badge="8"
            badgeClass="p-badge-info"
        />
    </div>
</template>

<script>
import { ref } from "vue";
export default {
    setup() {
        const msg = ref("hola mundo");

        return { msg };
    },
};
</script>

<style lang="scss" scoped></style>
