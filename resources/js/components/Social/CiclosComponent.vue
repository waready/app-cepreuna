<template>
    <div class="grid">
        <div v-for="ciclo in ciclos" class="col-12 md:col-6">
            <div class="card mx-3 shadow-2 p-0">
                <div class="grid">
                    <div class="col-12 pb-0">
                        <Image imageClass="body-ciclo" style="width: 100%" :src="$page.props.usuario.url + '/storage/ciclos/' + ciclo.path" alt="ciclo abril-julio" preview />
                    </div>
                    <div class="col-12 pt-0">
                        <div class="h5 text-center font-bold bg-cyan-500 text-white py-2">{{ ciclo.inicio_ciclo }} - {{ ciclo.fin_ciclo }}</div>
                    </div>
                    <div class="col-12 text-center">
                        <u class="font-bold">Inicio de Inscripciones</u>
                        <br />
                        <span>{{ ciclo.inicio_inscripciones }}</span>
                        <hr />
                        <u class="font-bold">Finalización de Inscripciones</u>
                        <br />
                        <span>{{ ciclo.fin_inscripciones }}</span>
                        <hr />
                        <u class="font-bold">Inicio de Clases</u>
                        <br />
                        <span>{{ ciclo.inicio_clases }}</span>
                        <hr />
                        <Chip :label="ciclo.estado == 1 ? 'HABILITADO' : 'CERRADO'" :style="ciclo.estado == 1 ? 'background-color:#4CAF50' : 'background-color:#f95050'" class="text-white mr-2 mb-2" />
                    </div>
                </div>
            </div>
        </div>
        <!-- <div class="col-12 md:col-6">
            <div class="card text-center mb-0">
                <u class="font-bold">Inicio de Inscripciones</u>
                <br />
                <span>{{ ciclo.inicio_inscripciones }}</span>
                <hr />
                <u class="font-bold">Finalización de Inscripciones</u>
                <br />
                <span>{{ ciclo.fin_inscripciones }}</span>
                <hr />
                <u class="font-bold">Inicio de Clases</u>
                <br />
                <span>{{ ciclo.inicio_clases }}</span>
                <hr />
                <Chip :label="ciclo.estado == 1 ? 'HABILITADO' : 'CERRADO'" class="bg-green-500 text-white mr-2 mb-2" />
            </div>
        </div> -->
    </div>
</template>

<script>
import { ref, onMounted, watch, toRefs } from "vue";
export default {
    setup() {
        onMounted(() => {
            getCiclos();
        });
        const ciclos = ref([]);
        const getCiclos = () => {
            // recursos.get-ciclos
            axios
                .get(route("recursos.get-ciclos"))
                .then((response) => {
                    ciclos.value = response.data;
                })
                .catch((errors) => {
                    console.log(errors);
                });
        };
        return {
            getCiclos,
            ciclos,
        };
    },
};
</script>

<style lang="scss" scoped></style>
