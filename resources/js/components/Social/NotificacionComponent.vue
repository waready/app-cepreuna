<template>
    <div class="col-12">
        <a :href="'/ver-publicacion/'+idCrypt" class="notification-item">
            <div class="image-container">
                <img :src="foto" v-if="estadoFotos"/>
                <Avatar v-else icon="pi pi-user" class="mb-3 mr-3" size="xlarge" shape="circle" />
                <span :class="['icono',iconoColor]">
                    <i :class="['pi',icono,'notification-category-icon','text-0']" style="font-size: 1.7rem"></i>
                </span>
            </div>
            <div class="notification-list-detail">
                <h6 class="mb-2"><strong>{{nombre}}</strong> {{descripcion}} </h6>
                <span class="notification-category">Hace {{tiempo}}</span>
            </div>
            <div class="notification-list-action">
                <img v-if="imagenTumb" :src="imagenTumb" />
                <!-- <Avatar icon="pi pi-user" class="mr-2" size="xlarge" shape="circle" /> -->
            </div>
        </a>
    </div>
</template>

<script>
import { ref,onMounted,toRefs } from "vue";
import axios from "axios";
import { timeRange } from "../../utilities/timeRange.js";
import Avatar from 'primevue/avatar';

export default {
    props:{
        item: Object,
    },
    components:{
        Avatar,
    },
    setup(props) {
        const { item} = toRefs(props);

        const nombre = ref("");
        const foto = ref("");
        const estadoFotos = ref(false);
        const descripcion = ref("");
        const tiempo = ref("");
        const imagenTumb = ref("");
        const idCrypt = ref("");
        const icono = ref("pi-clock");
        const iconoColor = ref("bg-yellow-400");
        const getNotificacion = () =>{
            descripcion.value = item.value.descripcion;
            tiempo.value = timeRange(item.value.created_at);
            if(item.value.tipo=="1"){
                // console.log("publicacion")
                imagenTumb.value = item.value.publicacion.imagen_tumb_url || publicacionMediaUrl(item.value.publicacion.imagen_tumb);
                icono.value = "pi-id-card";
                iconoColor.value = "bg-blue-500";
                axios
                .get(route("recursos.get-data-user"), {
                    params: {
                        id: item.value.publicacion.id ? item.value.publicacion.id : "",
                        idUser: item.value.publicacion.user_id ? item.value.publicacion.user_id : "",
                        rolName: item.value.publicacion.rol?.name || "",
                    },
                })
                .then((response) => {
                    // datos.value =response.data;
                    nombre.value = response.data.datos.nombres;
                    foto.value = response.data.datos.path_foto;
                    estadoFotos.value = response.data.datos.estado_foto;
                    idCrypt.value = response.data.id;
                });
            }
            if(item.value.tipo=="2"){
                // console.log("comentario")
                imagenTumb.value = item.value.comentario.publicacion.imagen_tumb_url || publicacionMediaUrl(item.value.comentario.publicacion.imagen_tumb);
                icono.value = "pi-comments";
                iconoColor.value = "bg-green-500";
                // console.log("publicacion")
                axios
                .get(route("recursos.get-data-user"), {
                    params: {
                        id: item.value.publicacion.id ? item.value.publicacion.id : "",
                        idUser: item.value.comentario.user_id ? item.value.comentario.user_id : "",
                        rolName: item.value.comentario.rol?.name || "",
                    },
                })
                .then((response) => {
                    // datos.value =response.data;
                    nombre.value = response.data.datos.nombres;
                    foto.value = response.data.datos.path_foto;
                    estadoFotos.value = response.data.datos.estado_foto;
                    idCrypt.value = response.data.id;
                });
            }
        }
        const publicacionMediaUrl = (path) => {
            if (!path) return "";
            if (/^https?:\/\//i.test(path)) return path;

            const cleanPath = String(path).replace(/\\/g, "/").replace(/^\/+/, "");
            if (cleanPath.startsWith("storage/")) return `/${cleanPath}`;
            if (cleanPath.startsWith("publicaciones/")) return `/storage/${cleanPath}`;

            return `/storage/publicaciones/${cleanPath}`;
        };
        onMounted(() => {
            // cacularTiempo();
            getNotificacion();
            // console.log(item);
        });
        return {
            nombre,
            foto,
            descripcion,
            tiempo,
            imagenTumb,
            icono,
            iconoColor,
            estadoFotos,
            idCrypt,
            publicacionMediaUrl
        };
    },
};
</script>

<style lang="scss" scoped>
.notification-item {
	display: flex;
	align-items: center;
	padding: .5rem;
	width: 100%;
    color:#000;

	img {
		width: 70px;
		height: 70px;
        //box-shadow: 0 3px 6px rgba(0, 0, 0, 0.16), 0 3px 6px rgba(0, 0, 0, 0.23);
        margin-right: 1rem;
        border:solid rgba(0, 0, 0, 0.185) 1px;
	}
	.image-container img {
        border-radius: 50%;


	}
    .image-container{
        position: relative;
    }
    .image-container .icono{
        position: absolute;
        border-radius: 50%;
        bottom: 2px;
        right: 7px;
        height: 35px;
        width: 35px;

    }
    .image-container .icono i{
        margin-top: 6px;
        margin-left: 6px;
    }

	.notification-list-detail {
		flex: 1 1 0;
	}

	.notification-list-action {
		display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    .notification-category-icon {
        vertical-align: middle;
        margin-right: .5rem;
        font-size: .875rem;
    }

    .notification-category {
        vertical-align: middle;
        line-height: 1;
        font-size: .875rem;
    }
}

.notification-item:hover{
    background: #e4e6eb;
}

</style>
