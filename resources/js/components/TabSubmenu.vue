<template>
    <div class="tab-content" v-if="title == selectedTitle">
        <slot :alert="alert" />
    </div>
</template>

<script>
import { inject } from "vue";

export default {
    props: {
        title: String,
        alert: Boolean,
        count: {
            type: Number,
            default: 0,
        },
        alert: {
            type: Boolean,
            default: false,
        },
    },
    setup() {
        const selectedTitle = inject("selectedTitle");

        return {
            selectedTitle,
        };
    },
};
</script>

<style lang="scss" scoped></style>
