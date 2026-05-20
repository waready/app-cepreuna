<template>
    <Toast />
    <div class="flex lg:flex-row px-8 py-2">
        <!-- Menu Lateral -->
        <div class="flex flex-column col-4 align-items-center">
            <Menu :model="items" class="w-6" />
        </div>
        <!-- Principal -->
        <div class="card col-8">
            <DataTable :value="cars">
                <template #header>
                    <div>
                        <Button icon="pi pi-refresh" style="float: left" />
                    </div>
                </template>
                <Column field="nombres" header="Nombres"></Column>
                <Column field="paterno" header="Paterno"></Column>
                <Column field="materno" header="Materno"></Column>
                <Column field="dni" header="DNI"></Column>
                <Column field="condicion" header="Condicion"></Column>
                <Column field="celular" header="Celular"></Column>
                <Column field="email" header="Email"></Column>
                <Column field="periodo_id" header="Periodo"></Column>
                <Column headerStyle="width: 8em" bodyStyle="text-align: center">
                    <template #header>
                        <Button type="button" icon="pi pi-cog"></Button>
                    </template>
                    <template #body="">
                        <Button type="button" icon="pi pi-search" class="p-button-success" style="margin-right: 0.5em"></Button>
                        <Button type="button" icon="pi pi-pencil" class="p-button-warning"></Button>
                    </template>
                </Column>
                <template #footer> In total there are {{ cars ? cars.length : 0 }} cars. </template>
            </DataTable>
        </div>
    </div>
</template>

<script>
import Menu from "primevue/menu";
import FileInput from "@/components/FileInput.vue";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import ColumnGroup from "primevue/columngroup"; //optional for column grouping
import Row from "primevue/row"; //optional for row
import axios from "axios";

export default {
    components: {
        Menu,
        FileInput,
        DataTable,
        Column,
        ColumnGroup,
        Row,
    },
    props: {
        status: String,
        errors: Object,
    },

    data() {
        return {
            docentes: [],

            form: this.$inertia.form({
                dni: "",
                suspencion: "",
                reciboHonorarios: "",
                osce: "",
                formato1: "",
                declaracion: "",
                informe: "",
            }),
        };
    },

    // created(){
    //     axios.get('docentes')
    // }

    methods: {
        submit() {
            this.form.clearErrors();

            // this.form
            //     .transform((data) => ({
            //         ...data,
            //         remember: this.form.remember ? "on" : "",
            //     }))
            //     .post(this.route("login"), {
            //         onFinish: () => this.form.reset("password"),
            //     });
        },
    },
};
</script>

<style scoped>
@media screen and (max-width: 576px) {
    .display-mobile {
        display: flex !important;
    }
}
</style>
