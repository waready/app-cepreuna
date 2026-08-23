<template>
    <Toast />
    <app-layout :title="title" :mode="2" class="pagos-page">
        <div class="card shadow-6 pagos-panel">
            <div class="grid hidden sm:flex">
                <div class="col-12">
                    <h5 class="font-semibold">Pagos</h5>
                </div>
            </div>
            <template v-if="!isDesktop()">
                <!-- vistas del menu inferior -->
                <div :class="menuTabs[0].isActive == false ? 'hidden' : 'grid'">
                    <div class="col-12">
                        <div class="grid">
                            <div class="col-12 text-center pb-0">
                                <span class="text-base font-bold">Deuda Actual </span>
                                <span class="text-base font-bold">(Cuota N° {{ datos.cronograma.nro_cuota }})</span>
                            </div>
                            <div class="col-12 text-center pt-0">
                                <em class="text-sm">del {{ datos.cronograma.inicio }} al {{ datos.cronograma.fin }}</em>
                            </div>
                            <div class="col-12 py-7 text-center font-bold text-orange-500">
                                S/ <span class="text-5xl"> {{ datos.deuda }}</span>
                            </div>
                            <div class="col-12 text-center">
                                <span class="text-base">{{ datos.tipo_descuento }}</span>
                            </div>
                            <Divider />
                            <div class="col-12">
                                <div class="p-datatable p-component p-datatable-responsive-scroll p-datatable-sm p-datatable-striped" data-scrollselectors=".p-datatable-wrapper" pv_id_8="">
                                    <div class="p-datatable-header text-center">Lista de Pagos</div>
                                    <div class="p-datatable-wrapper">
                                        <table role="table" class="p-datatable-table">
                                            <thead class="p-datatable-thead" role="rowgroup">
                                                <tr role="row">
                                                    <th class="" role="cell" style="width: 50px">
                                                        <div class="p-column-header-content">
                                                            <span class="p-column-title">Cuota</span>
                                                        </div>
                                                    </th>
                                                    <th class="" role="cell">
                                                        <div class="p-column-header-content">
                                                            <span class="p-column-title">Tarifa</span>
                                                        </div>
                                                    </th>
                                                    <th class="" role="cell">
                                                        <div class="p-column-header-content">
                                                            <span class="p-column-title">Pagado</span>
                                                        </div>
                                                    </th>
                                                    <th class="" role="cell">
                                                        <div class="p-column-header-content">
                                                            <span class="p-column-title">Mora</span>
                                                        </div>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="p-datatable-tbody" role="rowgroup">
                                                <tr v-for="(tarifa, i) in datos.tarifario" :key="i" role="row" draggable="false">
                                                    <td role="cell">{{ tarifa.nro_cuota == 0 ? "Ins." : tarifa.nro_cuota }}</td>
                                                    <td role="cell">S/ {{ tarifa.monto }}</td>
                                                    <td role="cell">S/ {{ tarifa.pagado }}</td>
                                                    <td role="cell">S/ {{ tarifa.mora }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 text-center pt-2">
                                <Button label="Agregar voucher" icon="pi pi-upload" class="p-button-sm" @click="activeMenu(1)" />
                            </div>
                        </div>
                    </div>
                </div>
                <div :class="menuTabs[1].isActive == false ? 'hidden' : 'grid'">
                    <div class="font-semibold col-12 text-center">
                        {{ menuTabs[1].title }}
                        <hr class="mb-0" />
                    </div>
                    <div class="col-12">
                        <Message severity="info" :closable="false">Recuerde que al pagar en el banco de la nación debe aumentar el monto de <b>S/ 1.00</b> de comisión por voucher.</Message>
                        <InlineMessage v-if="parseFloat(datos.deuda || 0) <= 0" class="text-sm mb-3" severity="info">
                            Puede adjuntar su comprobante para que sea validado.
                        </InlineMessage>
                        <AgregarPago :dni="$page.props.usuario.dni" :url="datos.url" @result="resultPago = $event"></AgregarPago>
                        <div class="p-datatable p-component p-datatable-responsive-scroll p-datatable-sm p-datatable-striped" data-scrollselectors=".p-datatable-wrapper" pv_id_8="">
                            <div class="p-datatable-wrapper">
                                <table role="table" class="p-datatable-table">
                                    <thead class="p-datatable-thead" role="rowgroup">
                                        <tr role="row">
                                            <th class="" role="cell" style="width: 30px">
                                                <div class="p-column-header-content">
                                                    <span class="p-column-title">N°</span>
                                                </div>
                                            </th>
                                            <th class="" role="cell">
                                                <div class="p-column-header-content">
                                                    <span class="p-column-title">Secuencia</span>
                                                </div>
                                            </th>
                                            <th class="" role="cell">
                                                <div class="p-column-header-content">
                                                    <span class="p-column-title">Monto</span>
                                                </div>
                                            </th>
                                            <th class="" role="cell">
                                                <div class="p-column-header-content">
                                                    <span class="p-column-title">Fecha</span>
                                                </div>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="p-datatable-tbody" role="rowgroup">
                                        <tr v-for="(result, i) in resultPago.pago" :key="i" role="row" draggable="false">
                                            <td role="cell">{{ i + 1 }}</td>
                                            <td role="cell">{{ result.secuencia }}</td>
                                            <td role="cell">S/ {{ result.monto }}</td>
                                            <td role="cell">{{ result.fecha }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <Divider />
                        <div class="text-center">
                            <Button v-if="resultPago.pago.length != 0" :loading="saveLoading" class="p-button-sm p-button-success" label="Guardar Pago" icon="pi pi-save" @click="submit" />
                        </div>
                        <!-- <table class="table table-sm">
                        <tbody>
                            <tr v-for="result in resultPago.pago" :key="result.secuencia">
                                <td>
                                    <div class="alert alert-secondary" role="alert">
                                        <b>Secuencia</b>: {{ result.secuencia }} | <b>Monto</b>: {{ result.monto }} | <b> fecha</b>: {{ result.fecha }}
                                    </div>
                                </td>

                                <td>
                                    <div class="alert alert-success" role="alert">{{ result.message }}</div>
                                </td>
                            </tr>
                        </tbody>
                    </table> -->
                    </div>
                </div>
                <div :class="menuTabs[2].isActive == false ? 'hidden' : 'grid'">
                    <div class="font-semibold col-12 text-center">
                        {{ menuTabs[2].title }}
                        <hr class="mb-0" />
                    </div>
                    <div class="col-12">
                        <InlineMessage class="text-sm mb-2" severity="info">Pagos con comisión al Banco de la Nacion S/ 1.00</InlineMessage>

                        <TabView :scrollable="true">
                            <TabPanel v-for="(voucher, i) in vouchers" :key="voucher.id" :header="'V' + i">
                                <div class="grid">
                                    <div class="col-6">
                                        <b>Secuencia</b>
                                        <p class="m-0">{{ voucher.secuencia }}</p>
                                        <b>Fecha</b>
                                        <p class="m-0">{{ voucher.fecha.split("-")[2] + "-" + voucher.fecha.split("-")[1] + "-" + voucher.fecha.split("-")[0] }}</p>
                                        <div v-if="voucher.folio != null && voucher.folio != ''">
                                            <b>Folio</b>
                                            <p class="m-0">{{ voucher.folio }}</p>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <b>Monto</b>
                                        <p class="m-0">{{ voucher.monto }}</p>
                                        <b>Tipo de Pago</b>
                                        <p class="m-0">{{ voucher.tipo_pago == 1 ? "Deposito Normal" : "Por Descuento" }}</p>
                                    </div>
                                </div>
                            </TabPanel>
                        </TabView>
                    </div>
                </div>
            </template>
            <template v-else>
                <div class="grid">
                    <div class="col-6">
                        <div class="grid">
                            <div class="col-12 text-center pb-0">
                                <span class="text-base font-bold">Deuda Actual </span>
                                <span class="text-base font-bold">(Cuota N° {{ datos.cronograma.nro_cuota }})</span>
                            </div>
                            <div class="col-12 text-center pt-0">
                                <em class="text-sm">del {{ datos.cronograma.inicio }} al {{ datos.cronograma.fin }}</em>
                            </div>
                            <div class="col-12 py-7 text-center font-bold text-orange-500">
                                S/ <span class="text-5xl"> {{ datos.deuda }}</span>
                            </div>
                            <div class="col-12 text-center">
                                <span class="text-base">{{ datos.tipo_descuento }}</span>
                            </div>
                            <Divider />
                            <div class="col-12">
                                <div class="p-datatable p-component p-datatable-responsive-scroll p-datatable-sm p-datatable-striped" data-scrollselectors=".p-datatable-wrapper" pv_id_8="">
                                    <div class="p-datatable-header text-center">Lista de Pagos</div>
                                    <div class="p-datatable-wrapper">
                                        <table role="table" class="p-datatable-table">
                                            <thead class="p-datatable-thead" role="rowgroup">
                                                <tr role="row">
                                                    <th class="" role="cell" style="width: 50px">
                                                        <div class="p-column-header-content">
                                                            <span class="p-column-title">Cuota</span>
                                                        </div>
                                                    </th>
                                                    <th class="" role="cell">
                                                        <div class="p-column-header-content">
                                                            <span class="p-column-title">Tarifa</span>
                                                        </div>
                                                    </th>
                                                    <th class="" role="cell">
                                                        <div class="p-column-header-content">
                                                            <span class="p-column-title">Pagado</span>
                                                        </div>
                                                    </th>
                                                    <th class="" role="cell">
                                                        <div class="p-column-header-content">
                                                            <span class="p-column-title">Mora</span>
                                                        </div>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="p-datatable-tbody" role="rowgroup">
                                                <tr v-for="(tarifa, i) in datos.tarifario" :key="i" role="row" draggable="false">
                                                    <td role="cell">{{ tarifa.nro_cuota == 0 ? "Ins." : tarifa.nro_cuota }}</td>
                                                    <td role="cell">S/ {{ tarifa.monto }}</td>
                                                    <td role="cell">S/ {{ tarifa.pagado }}</td>
                                                    <td role="cell">S/ {{ tarifa.mora }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="grid">
                            <div class="col-12">
                                <Panel header="Vouchers Registrados">
                                    <InlineMessage class="text-sm mb-2" severity="info">Pagos con comisión al Banco de la Nacion S/ 1.00</InlineMessage>

                                    <TabView :scrollable="true">
                                        <TabPanel v-for="(voucher, i) in vouchers" :key="voucher.id" :header="'V' + i">
                                            <div class="grid">
                                                <div class="col-6">
                                                    <b>Secuencia</b>
                                                    <p class="m-0">{{ voucher.secuencia }}</p>
                                                    <b>Fecha</b>
                                                    <p class="m-0">{{ voucher.fecha.split("-")[2] + "-" + voucher.fecha.split("-")[1] + "-" + voucher.fecha.split("-")[0] }}</p>
                                                    <div v-if="voucher.folio != null && voucher.folio != ''">
                                                        <b>Folio</b>
                                                        <p class="m-0">{{ voucher.folio }}</p>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <b>Monto</b>
                                                    <p class="m-0">{{ voucher.monto }}</p>
                                                    <b>Tipo de Pago</b>
                                                    <p class="m-0">{{ voucher.tipo_pago == 1 ? "Deposito Normal" : "Por Descuento" }}</p>
                                                </div>
                                            </div>
                                        </TabPanel>
                                    </TabView>
                                </Panel>
                            </div>
                        </div>
                        <div class="grid">
                            <div class="col-12">
                                <Panel header="Añadir Voucher">
                                    <Message severity="info" :closable="false"
                                        >Recuerde que al pagar en el banco de la nación debe aumentar el monto de <b>S/ 1.00</b> de comisión por voucher.</Message
                                    >
                                    <InlineMessage v-if="parseFloat(datos.deuda || 0) <= 0" class="text-sm mb-3" severity="info">
                                        Puede adjuntar su comprobante para que sea validado.
                                    </InlineMessage>
                                    <AgregarPago :dni="$page.props.usuario.dni" :url="datos.url" @result="resultPago = $event"></AgregarPago>
                                    <div class="p-datatable p-component p-datatable-responsive-scroll p-datatable-sm p-datatable-striped" data-scrollselectors=".p-datatable-wrapper" pv_id_8="">
                                        <div class="p-datatable-wrapper">
                                            <table role="table" class="p-datatable-table">
                                                <thead class="p-datatable-thead" role="rowgroup">
                                                    <tr role="row">
                                                        <th class="" role="cell" style="width: 30px">
                                                            <div class="p-column-header-content">
                                                                <span class="p-column-title">N°</span>
                                                            </div>
                                                        </th>
                                                        <th class="" role="cell">
                                                            <div class="p-column-header-content">
                                                                <span class="p-column-title">Secuencia</span>
                                                            </div>
                                                        </th>
                                                        <th class="" role="cell">
                                                            <div class="p-column-header-content">
                                                                <span class="p-column-title">Monto</span>
                                                            </div>
                                                        </th>
                                                        <th class="" role="cell">
                                                            <div class="p-column-header-content">
                                                                <span class="p-column-title">Fecha</span>
                                                            </div>
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody class="p-datatable-tbody" role="rowgroup">
                                                    <tr v-for="(result, i) in resultPago.pago" :key="i" role="row" draggable="false">
                                                        <td role="cell">{{ i + 1 }}</td>
                                                        <td role="cell">{{ result.secuencia }}</td>
                                                        <td role="cell">S/ {{ result.monto }}</td>
                                                        <td role="cell">{{ result.fecha }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <Divider />
                                    <div class="text-center">
                                        <Button v-if="resultPago.pago.length != 0" :loading="saveLoading" class="p-button-sm p-button-success" label="Guardar Pago" icon="pi pi-save" @click="submit" />
                                    </div>
                                </Panel>
                            </div>
                            <div class="col-12 md:col-12" v-if="simulacro">
                                <div class="grid">
                                    <div class="col-12">
                                        <Fieldset legend="Puntaje del examen de simulacro">
                                            <div class="grid">
                                                <div class="col-6">
                                                    <b>Usuario : </b>
                                                    <span>{{ usuario_si }}</span>
                                                    <br />
                                                    <b>Carrera : </b>
                                                    <span>{{ carrera_si }}</span>
                                                    <hr />
                                                    <b>Puntaje : </b>
                                                    <b
                                                        ><h2>{{ puntaje_si }}</h2></b
                                                    >
                                                </div>
                                                <div class="col-6">
                                                    <div
                                                        class="p-datatable p-component p-datatable-responsive-scroll p-datatable-sm p-datatable-striped"
                                                        data-scrollselectors=".p-datatable-wrapper"
                                                        pv_id_8=""
                                                    >
                                                        <div class="p-datatable-header text-center">Puntaje de aprobación</div>
                                                        <div class="p-datatable-wrapper">
                                                            <table role="table" class="p-datatable-table">
                                                                <thead class="p-datatable-thead" role="rowgroup">
                                                                    <tr role="row">
                                                                        <th class="" role="cell">
                                                                            <div class="p-column-header-content">
                                                                                <span class="p-column-title">Area</span>
                                                                            </div>
                                                                        </th>
                                                                        <th class="" role="cell">
                                                                            <div class="p-column-header-content">
                                                                                <span class="p-column-title">Mínimo</span>
                                                                            </div>
                                                                        </th>
                                                                        <th class="" role="cell">
                                                                            <div class="p-column-header-content">
                                                                                <span class="p-column-title">Máximo</span>
                                                                            </div>
                                                                        </th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody class="p-datatable-tbody" role="rowgroup">
                                                                    <tr>
                                                                        <th role="cell" class="mt-3 text-left">Biomédicas</th>
                                                                        <td role="cell" class="mt-3">918.5</td>
                                                                        <td role="cell" class="mt-3 instock">1670</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th role="cell" class="mt-3 text-left">Ingenierías</th>
                                                                        <td role="cell" class="mt-3">924</td>
                                                                        <td role="cell" class="mt-3 instock">1680</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th role="cell" class="mt-3 text-left">Sociales</th>
                                                                        <td role="cell" class="mt-3">913</td>
                                                                        <td role="cell" class="mt-3 instock">1660</td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </Fieldset>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
        <template v-if="!isDesktop()">
            <!-- menu inferior -->
            <div class="menu-footer">
                <span class="p-fluid flex flex-row">
                    <div v-for="(tab, index) in menuTabs" :key="tab.title" class="flex-1 flex align-items-center justify-content-center bg-white font-bold">
                        <Button
                            :class="tab.isActive ? 'menu-tab-active' : ''"
                            class="p-button-text p-button-plain pb-2"
                            style="display: grid"
                            :label="tab.menuTitle"
                            :icon="tab.icon"
                            @click="activeMenu(index)"
                        />
                    </div>
                </span>
            </div>
        </template>
    </app-layout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout";
import QrCodeComponent from "@/components/QrCodeComponent";
import { useToast } from "primevue/usetoast";

import { Inertia } from "@inertiajs/inertia";
import { useForm } from "@inertiajs/inertia-vue3";

import { ref, onMounted, onBeforeUnmount, watch, toRefs, computed } from "vue";
import axios from "axios";

import AgregarPago from "../../components/Estudiante/AgregarPagoComponent.vue";
export default {
    components: {
        AppLayout,
        QrCodeComponent,
        AgregarPago,
    },
    props: {
        errors: Object,
        response: Object,
        data: Object,
    },
    setup(props) {
        const title = ref("Pagos");
        const toast = useToast();
        const { response, data } = toRefs(props);

        const menuTabs = ref([
            {
                title: "Estado",
                icon: "pi pi-th-large",
                isActive: true,
                menuTitle: "Estado",
            },
            {
                title: "Registrar Pago",
                icon: "pi pi-dollar",
                isActive: false,
                menuTitle: "Pagar",
            },
            {
                title: "Vouchers de Pago Registrados",
                icon: "pi pi-ticket",
                isActive: false,
                menuTitle: "Vouchers",
            },
        ]);
        const activeMenu = (i) => {
            menuTabs.value.forEach((m) => {
                m.isActive = false;
            });
            menuTabs.value[i].isActive = true;
        };

        const usuario = ref();
        // mounted
        const desktop = ref(window.innerWidth > 1024);
        const syncViewport = () => {
            desktop.value = window.innerWidth > 1024;
        };

        onMounted(() => {
            window.addEventListener("resize", syncViewport, { passive: true });
        });
        onBeforeUnmount(() => window.removeEventListener("resize", syncViewport));

        // vouchers
        const vouchers = ref(data.value.vouchers);
        const datos = ref(data.value);
        // Aperturar Asistencia
        const form = useForm({
            area: null,
            turno: null,
            sede: null,
            grupo: null,
            observacion: "",
        });
        const actionForm = ref();
        const saveLoading = ref(false);

        // resultado de pago
        const resultPago = ref({
            pago: [],
        });
        const capitalize = (value) => {
            if (!value) return "";
            value = value.toLowerCase();
            value = value.toString();
            return value.charAt(0).toUpperCase() + value.slice(1);
        };
        const isDesktop = () => {
            return desktop.value;
        };
        // SUBMIT
        const fields = ref({ tokens: [] });
        const submit = () => {
            saveLoading.value = true;
            let tokens = [];

            resultPago.value.pago.sort((a, b) => new Date(a.fecha).getTime() - new Date(b.fecha).getTime());

            resultPago.value.pago.map(function (pago) {
                tokens.push(pago.token);
            });

            fields.value.tokens = tokens;
            axios
                .post(route("estudiantes.registrar-pago"), fields.value)
                .then((response) => {
                    saveLoading.value = false;
                    if (response.data.status) {
                        toast.add({ severity: "success", summary: "¡Exito!", detail: response.data.message, life: 3000 });
                        // window.location.reload();
                        Inertia.visit(route("estudiantes.pagos"), {
                            only: ["data"],
                        });
                    } else {
                        toast.add({ severity: "error", summary: "Error", detail: response.data.message, life: 3000 });
                    }
                })
                .catch((error) => {
                    saveLoading.value = false;
                    if (error.response.status === 422) {
                        // this.errors = error.response.data.errors || {};
                    }
                });
        };
        const simulacro = ref(data.value.simulacro);
        const usuario_si = ref(data.value.usuario);
        const carrera_si = ref(data.value.carrera);
        const puntaje_si = ref(data.value.puntaje);
        return {
            title,
            menuTabs,
            activeMenu,
            form,
            saveLoading,
            capitalize,
            resultPago,
            vouchers,
            datos,
            submit,
            fields,
            isDesktop,
            simulacro,
            usuario_si,
            carrera_si,
            puntaje_si,
        };
    },
};
</script>
<style scoped>
.p-avatar.p-avatar-xl {
    width: 7rem;
    height: auto;
    font-size: 2rem;
}

@media (max-width: 576px) {
    .pagos-panel {
        overflow: hidden;
    }

    .pagos-panel :deep(.p-message) {
        width: 100%;
        margin-right: 0;
        margin-left: 0;
    }

    .pagos-panel :deep(.p-datatable-table) {
        font-size: 0.86rem;
    }

    .pagos-panel :deep(.p-datatable-thead > tr > th),
    .pagos-panel :deep(.p-datatable-tbody > tr > td) {
        padding: 0.55rem 0.5rem;
        white-space: nowrap;
    }
}
</style>
