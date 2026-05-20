<template>
    <Toast />
    <div class="flex lg:flex-row px-8 py-2 justify-content-center">
        <div class="card col-10 px-6">
            <div class="grid">
                <div class="flex flex-column col-12 text-center">
                    <h4>
                        <b> BIENVENIDO {{ docente.nombres }} {{ docente.paterno }} {{ docente.materno }}</b>
                    </h4>

                    <div class="flex flex-row justify-content-end">
                        <p><b>DNI:</b> {{ docente.dni }}</p>
                    </div>
                </div>
                <div class="text-justify mb-2 px-4">
                    <p>
                        A continuación observará la evaluación de sus documentos, modifique los documentos observados de acuerdo a lo indicado y vuelva a cargarlos a la plataforma, cuando todos sus documentos sean aprobados podrá descargar el expediente para imprimirlo y presentarlo de forma física en oficina.
                    </p>
                </div>
                <div class="col-12 text-center">
                    <h4>Sus Documentos</h4>
                </div>
            </div>
            <div>
                <DataTable showGridlines>
                    <Column header="Documento"></Column>
                    <Column header="Archivo"></Column>
                    <Column header="Fecha de Carga"></Column>
                    <Column header="Estado"></Column>
                    <Column header="Fecha de Revisión"></Column>
                    <Column header="Observación"></Column>
                </DataTable>
                <div class="flex justify-content-center">
                    <Button label="Enviar Nuevos Documentos" class="p-button-raised p-button-primary-theme mt-3" @click="asd()" />
                </div>
            </div>
        </div>
    </div>
</template>
