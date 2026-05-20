<template>
    <div class="grid">
        <div class="col-12">
            <div class="mx-3 shadow-2 flex flex-column">
                <TabView>
                    <TabPanel>
                        <template #header>
                            <span style="font-size: 12px">Nuestros Directivos</span>
                        </template>
                        <div class="flex flex-wrap justify-content-around">
                            <div class="col card mx-3 shadow-2 p-0 flex-grow-1 md:flex-grow-0" v-for="d in directivos" :key="d">
                                <div class="flex mx-3 flex-column mt-1">
                                    <div class="flex h-20rem overflow-hidden align-items-center justify-content-center">
                                        <Image :src="datausuario.url + '/storage/directivos/' + d.foto_path" class="h-full" imageStyle="height:100%;" imageClass="body-directivo" alt="directivo" />
                                    </div>
                                    <div class="my-1">
                                        <div class="h5 text-center font-bold">{{ d.sigla_grado_academico + " " + d.nombres + " " + d.paterno + " " + d.materno }}</div>
                                        <div class="h3 text-center" v-if="d.tipo == '1'">Presidente</div>
                                        <div class="h3 text-center" v-if="d.tipo == '2'">Miembro CEPREUNA</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </TabPanel>
                    <TabPanel>
                        <template #header>
                            <span style="font-size: 12px">Misión y Visión</span>
                        </template>
                        <div class="flex flex-column">
                            <div class="flex flex-column mx-3" v-for="mv in misionVision" :key="mv">
                                <div class="col-12 flex md:flex-row flex-column">
                                    <div class="md:col-3 col-12">
                                        <h5 class="font-bold uppercase" v-if="mv.nosotros_tipo_dato_id == 1">MISIÓN</h5>
                                        <h5 class="font-bold uppercase" v-if="mv.nosotros_tipo_dato_id == 2">VISIÓN</h5>
                                    </div>
                                    <div class="md:col-9 col-12">
                                        <p class="text-justify">
                                            {{ mv.descripcion }}
                                        </p>
                                    </div>
                                </div>
                                <Divider />
                            </div>
                        </div>
                    </TabPanel>
                    <TabPanel>
                        <template #header>
                            <span style="font-size: 12px">Nuestros Objetivos</span>
                        </template>
                        <div class="flex flex-column">
                            <div class="flex md:flex-row flex-column mx-3">
                                <div class="md:col-3 col-12">
                                    <h5 class="font-bold uppercase">OBJETIVO GENERAL</h5>
                                </div>
                                <div class="md:col-9 col-12 flex flex-column">
                                    <div class="flex mb-1" v-for="o in objetivos" :key="o">
                                        <p class="" v-if="o.nosotros_tipo_dato_id == 3">
                                            {{ o.descripcion }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <Divider />
                            <div class="flex md:flex-row flex-column mx-3">
                                <div class="md:col-3 col-12">
                                    <h5 class="font-bold uppercase">OBJETIVOS ESPECÍFICOS</h5>
                                </div>
                                <div class="md:col-9 col-12 flex flex-column">
                                    <div class="flex mb-1" v-for="o in objetivos" :key="o">
                                        <p class="flex flex-row vertical-align-middle" v-if="o.nosotros_tipo_dato_id == 4">&#8594; {{ o.descripcion }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </TabPanel>
                    <TabPanel>
                        <template #header>
                            <span style="font-size: 12px">Nuestra Historia</span>
                        </template>
                        <div class="flex flex-column">
                            <div class="flex md:flex-row flex-column mx-3">
                                <div class="md:col-3 col-12">
                                    <h5 class="font-bold uppercase">HISTORIA</h5>
                                </div>
                                <div class="md:col-9 col-12">
                                    <p class="text-justify">
                                        {{ historia.descripcion }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </TabPanel>
                </TabView>
            </div>
        </div>
        <!-- <div class="col-12 md:col-6">
            <div class="card mx-3 shadow-2 p-0">
                <div class="grid">
                    <div class="col-12">
                        <Image imageClass="body-ciclo" style="width: 100%" src="https://via.placeholder.com/400x560" alt="ciclo abril-julio" preview />
                    </div>
                    <div class="col-12">
                        <div class="h5 text-center font-bold">CICLO ABRIL - JULIO 2022</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 md:col-6">
            <div class="card">
                Lorem ipsum dolor sit amet consectetur adipisicing elit. Quia asperiores architecto, quos doloremque cum nostrum nulla ipsam ab eveniet itaque. Quam eius praesentium ullam perspiciatis
                aperiam molestiae commodi temporibus cum.
            </div>
        </div> -->
    </div>
</template>

<script>
import { useToast } from "primevue/usetoast";
import { ref, toRefs, onMounted } from "vue";
import axios from "axios";
import TabView from "primevue/tabview";
import TabPanel from "primevue/tabpanel";
import Image from "primevue/image";
import Divider from "primevue/divider";

export default {
    components: {
        TabView,
        TabPanel,
        Image,
        Divider,
    },
    props: {
        datausuario: Object,
    },
    setup(props, { emit }) {
        const toast = useToast();
        const errors = ref({});
        const directivos = ref([]);
        const getDirectivos = () => {
            axios
                .get(route("nosotros.get-directivos"))
                .then((response) => {
                    directivos.value = response.data.directivos;
                })
                .catch((error) => {
                    console.log(error.response);
                    if (error.response.status === 422) {
                        errors.value = error.response.data.errors;
                    }
                });
        };
        const misionVision = ref([]);
        const getMisionVision = () => {
            axios
                .get(route("nosotros.get-mision-vision"))
                .then((response) => {
                    misionVision.value = response.data.misionvision;
                })
                .catch((error) => {
                    console.log(error.response);
                    if (error.response.status === 422) {
                        errors.value = error.response.data.errors;
                    }
                });
        };
        const objetivos = ref([]);
        const getObjetivos = () => {
            axios
                .get(route("nosotros.get-objetivos"))
                .then((response) => {
                    objetivos.value = response.data.objetivos;
                })
                .catch((error) => {
                    console.log(error.response);
                    if (error.response.status === 422) {
                        errors.value = error.response.data.errors;
                    }
                });
        };
        const historia = ref([]);
        const getHistoria = () => {
            axios
                .get(route("nosotros.get-historia"))
                .then((response) => {
                    historia.value = response.data.historia;
                })
                .catch((error) => {
                    console.log(error.response);
                    if (error.response.status === 422) {
                        errors.value = error.response.data.errors;
                    }
                });
        };

        onMounted(() => {
            getDirectivos();
            getMisionVision();
            getObjetivos();
            getHistoria();
        });

        return {
            errors,
            directivos,
            getDirectivos,
            misionVision,
            getMisionVision,
            objetivos,
            getObjetivos,
            historia,
            getHistoria,
        };
    },
};
</script>

<style lang="scss" scoped>
</style>
