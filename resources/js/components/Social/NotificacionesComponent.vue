<template>
    <div class="grid">
        <Notificacion v-for="(item,key) in notificaciones" :key="key" :item="item" />
        <div class="col-12 text-center" v-if="total>pagina">
             <Button label="Ver más notificaciones" class="p-button-link" @click="moreNoticaciones" />
        </div>
    </div>
</template>

<script>
import { ref,onMounted,toRefs } from "vue";
import axios from "axios";
import Notificacion from "./NotificacionComponent.vue";
export default {

    components:{
        Notificacion,
    },
    setup() {
        // const { item} = toRefs(props);
        // console.log(item.value);
        const notificaciones = ref([]);
        const total = ref(0);
        const pagina = ref(1);
        const getNotificaciones = () =>{
            // console.log(item.value);
            axios
            .get(route("recursos.get-notificaciones"), {
                params: {
                    page: pagina.value,
                },
            })
            .then((response) =>{
                notificaciones.value.push(...response.data['data']);
                total.value = (response.data['total']/5);
                console.log(response.data.total);
            });
        }
        onMounted(() => {
            getNotificaciones();
        });
        const moreNoticaciones = () => {
            if(total.value>pagina.value){
                pagina.value+=1;
            }
            getNotificaciones();
        }
        return {
            notificaciones,
            moreNoticaciones,
            total,
            pagina,
        };
    },
};
</script>

<style lang="scss" scoped>

</style>
