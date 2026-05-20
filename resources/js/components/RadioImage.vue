<template>
    <label :for="link" class="custom-radio-label">
        <img 
            :src="src" 
            :style="[isChecked ? { filter: 'grayscale(0)', width: '40px' } : { filter: 'grayscale(1)', width: '35px' }]" 
            alt="" 
        />
        <span v-if="text" class="radio-text">{{ text }}</span>
        <input 
            v-show="false" 
            type="radio" 
            :checked="isChecked" 
            :id="link" 
            :value="value" 
            @input="$emit('update:modelValue', $event.target.value)" 
        />
    </label>
</template>

<script>
export default {
    model: {
        prop: "modelValue",
        event: "update",
    },
    props: {
        src: { type: String, default: "", required: true },
        link: { type: String, default: "", required: true },
        modelValue: { default: "" },
        value: { type: String, default: undefined },
        text: { type: String, default: "" },
    },
    computed: {
        isChecked() {
            return this.modelValue == this.value;
        },
    },
};
</script>

<style lang="scss" scoped>
.custom-radio-label {
    display: inline-flex;
    flex-direction: row;       /* Cambiado de column a row */
    align-items: center;       /* Centra verticalmente los elementos */
    gap: 8px;                  /* Espacio entre imagen y texto */
    margin: 0 8px;
    cursor: pointer;
    
    &:hover {
        opacity: 0.8;
    }
}

.radio-text {
    font-size: 0.875rem;       /* Equivalente a text-sm en algunos sistemas */
    white-space: nowrap;       /* Evita que el texto se divida en múltiples líneas */
}

img {
    transition: all 0.2s ease; /* Suaviza los cambios de tamaño/filtro */
}
</style>