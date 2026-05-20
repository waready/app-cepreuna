<template>
    <Toast />
    <app-layout :title="title" :mode="2">
        <div class="card shadow-6">
            <Fieldset legend="Test Completado" v-if="validationForm">
                <p class="m-0">
                    <Message severity="success" :sticky="sticky" :closable="false" :life="1000">
                        ¡Gracias por llenar o completar el test vocacional! Estamos felices de que hayas dedicado tiempo
                        para conocerte mejor y explorar tus opciones profesionales.
                    </Message>
                </p>

                <Card v-if="areaSugerida" class="mt-4 border-round-xl shadow-3">
                    <template #title>
                        <div class="flex align-items-center gap-2">
                            <i class="pi pi-star-fill text-yellow-500"></i>
                            <span class="text-xl font-semibold">Resultado del Test Vocacional</span>
                        </div>
                    </template>
                    <template #content>
                        <h3 class="text-center text-teal-700 mt-2">
                            Tu test vocacional sugiere el área de: 
                            <span class="font-bold">{{ areaSugerida }}</span>
                        </h3>
                       
                    </template>
                </Card>

                <Button 
                    label="Generar Constancia" 
                    icon="pi pi-file-pdf" 
                    class="p-button-success mt-4" 
                    @click="generarConstancia" 
                />
            </Fieldset>

            <template v-else>
                <template v-if="estadoTest === 'proximamente'">
                    <Message severity="info" :sticky="sticky" :closable="false" :life="5000">
                        El test vocacional estará disponible el sábado 10 y domingo 11 de mayo.
                    </Message>
                </template>
                <template v-if="estadoTest === 'activo'">
                    <Fieldset legend="Test Vocacional">
                    <form-wizard @on-complete="submit"
                        v-if="totalQuestions != 0"
                        nextButtonText="Siguiente"
                        backButtonText="Anterior"
                        finishButtonText="Enviar"
                        shape="tab"
                        color="#2196F3">
                        <tab-content title="Etapa 1" icon="pi pi-th-large" :before-change="() => validateTab(stage1Questions)">
                            <div class="p-fluid">
                                <div v-for="(question, index) in stage1Questions" :key="index" class="mb-5">
                                    <p>{{ question.id }} - {{ question.denominacion }}</p>
                                    <div class="flex align-items-center gap-4">
                                        <div class="flex align-items-center gap-2">
                                            <RadioButton v-model="answers[question.id]" :value="true" :inputId="'yes-' + question.id" class="p-radiobutton-success" />
                                            <label :for="'yes-' + question.id">Sí</label>
                                        </div>
                                        <div class="flex align-items-center gap-2">
                                            <RadioButton v-model="answers[question.id]" :value="false" :inputId="'no-' + question.id" class="p-radiobutton-danger" />
                                            <label :for="'no-' + question.id">No</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </tab-content>
                        <tab-content title="Etapa 2" icon="pi pi-th-large" :before-change="() => validateTab(stage2Questions)">
                            <div class="p-fluid">
                                <div v-for="(question, index) in stage2Questions" :key="index" class="mb-5">
                                    <p>{{ question.id }} - {{ question.denominacion }}</p>
                                    <div class="flex align-items-center gap-4">
                                        <div class="flex align-items-center gap-2">
                                            <RadioButton v-model="answers[question.id]" :value="true" :inputId="'yes-' + question.id" class="p-radiobutton-success" />
                                            <label :for="'yes-' + question.id">Sí</label>
                                        </div>
                                        <div class="flex align-items-center gap-2">
                                            <RadioButton v-model="answers[question.id]" :value="false" :inputId="'no-' + question.id" class="p-radiobutton-danger" />
                                            <label :for="'no-' + question.id">No</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </tab-content>
                        <tab-content title="Etapa 3" icon="pi pi-th-large" :before-change="() => validateTab(stage3Questions)">
                            <div class="p-fluid">
                                <div v-for="(question, index) in stage3Questions" :key="index" class="mb-5">
                                    <p>{{ question.id }} - {{ question.denominacion }}</p>
                                    <div class="flex align-items-center gap-4">
                                        <div class="flex align-items-center gap-2">
                                            <RadioButton v-model="answers[question.id]" :value="true" :inputId="'yes-' + question.id" class="p-radiobutton-success" />
                                            <label :for="'yes-' + question.id">Sí</label>
                                        </div>
                                        <div class="flex align-items-center gap-2">
                                            <RadioButton v-model="answers[question.id]" :value="false" :inputId="'no-' + question.id" class="p-radiobutton-danger" />
                                            <label :for="'no-' + question.id">No</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </tab-content>
                        <tab-content title="Etapa 4" icon="pi pi-th-large" :before-change="() => validateTab(stage4Questions)">
                            <div class="p-fluid">
                                <div v-for="(question, index) in stage4Questions" :key="index" class="mb-5">
                                    <p>{{ question.id }} - {{ question.denominacion }}</p>
                                    <div class="flex align-items-center gap-4">
                                        <div class="flex align-items-center gap-2">
                                            <RadioButton v-model="answers[question.id]" :value="true" :inputId="'yes-' + question.id" class="p-radiobutton-success" />
                                            <label :for="'yes-' + question.id">Sí</label>
                                        </div>
                                        <div class="flex align-items-center gap-2">
                                            <RadioButton v-model="answers[question.id]" :value="false" :inputId="'no-' + question.id" class="p-radiobutton-danger" />
                                            <label :for="'no-' + question.id">No</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </tab-content>
                        <tab-content title="Resumen" icon="pi pi-check">
                            <div class="confirmation">
                                <h5>Resumen de tus Respuestas</h5>
                                <div v-for="(value, questionId) in answers" :key="questionId" class="mb-3">
                                    <p>
                                        <b>{{ questionId }} - {{ getQuestionText(questionId) }}:</b>&nbsp;
                                        {{ value ? "Sí" : "No" }}
                                    </p>
                                </div>
                            </div>
                        </tab-content>
                    </form-wizard>
                    <Message severity="warn" v-else :sticky="sticky" :closable="false" :life="1000">
                        No se Encontraron Preguntas!
                    </Message>
                </Fieldset>
                </template>
                <template v-else-if="estadoTest === 'cerrado'">
                    <Message severity="warn" :sticky="sticky" :closable="false" :life="5000">
                        El test vocacional estuvo disponible el sábado 10 y domingo 11 de mayo.
                        Actualmente el test está cerrado.
                    </Message>
                </template>
               
            </template>
        </div>
    </app-layout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout";
import { FormWizard, TabContent } from "vue3-form-wizard";
import RadioButton from "primevue/radiobutton";
import Button from "primevue/button";
import { useToast } from "primevue/usetoast";
import { ref, toRefs, computed, onMounted } from "vue";
import axios from "axios";
import { Inertia } from "@inertiajs/inertia";
import "vue3-form-wizard/dist/style.css";

export default {
    components: {
        AppLayout,
        FormWizard,
        TabContent,
        RadioButton,
        Button,
    },
    props: {
        errors: Object,
        response: Object,
        data: Object,
    },
    setup(props) {
        const { data } = toRefs(props);
        const title = ref("Test Vocacional");
        const toast = useToast();

        const formattedData = ref(JSON.stringify(data.value.estudiante, null, 2));
        const validationForm = data.value.validacion;
        const totalQuestions = data.value.preguntas.length;

        const puntajes = ref(null);
        const areaSugerida = ref(null);
        const answers = ref({});

        const estadoTest = ref(data.value.estado_test);

        const questionsPerStage = Math.ceil(totalQuestions / 4);
        const stage1Questions = computed(() => data.value.preguntas.slice(0, questionsPerStage));
        const stage2Questions = computed(() => data.value.preguntas.slice(questionsPerStage, questionsPerStage * 2));
        const stage3Questions = computed(() => data.value.preguntas.slice(questionsPerStage * 2, questionsPerStage * 3));
        const stage4Questions = computed(() => data.value.preguntas.slice(questionsPerStage * 3));

        const validateTab = (questions) => {
            const unanswered = questions.find(q => answers.value[q.id] === undefined);
            if (unanswered) {
                toast.add({
                    severity: "error",
                    summary: "Pregunta sin responder",
                    detail: `Por favor, responde la pregunta: \"${unanswered.denominacion}\"`,
                    life: 3000,
                });
                return false;
            }
            return true;
        };

        const calcularAreaSugerida = (validationForm) => {
            const puntajesCalculados = {
                ingenieria: validationForm.puntaje_ingeneria,
                biomedicas: validationForm.puntaje_biomedicas,
                sociales: validationForm.puntaje_sociales,
            };
            puntajes.value = puntajesCalculados;
            const [areaMayor] = Object.entries(puntajesCalculados).reduce((a, b) => (b[1] > a[1] ? b : a));
            areaSugerida.value = areaMayor.charAt(0).toUpperCase() + areaMayor.slice(1);
        };

        const generarConstancia = () => {
            const estudianteId = data.value.estudiante.id; // ID del estudiante
            const url = route('estudiantes.test.constancia', { id: estudianteId }); // Genera la URL usando el nombre de la ruta
            window.open(url, '_blank'); // Abre el PDF en una nueva pestaña
        };

        const submit = () => {
            const payload = {
                estudianteId: data.value.estudiante.id,
                respuestas: answers.value,
            };
            axios.post(route("estudiantes.test.validar"), payload)
                .then(() => {
                    toast.add({ severity: "success", summary: "¡Éxito!", detail: "Tus respuestas han sido enviadas correctamente.", life: 3000 });
                    Inertia.visit(route("estudiantes.test"), { only: ["data"] });
                })
                .catch(() => {
                    toast.add({ severity: "error", summary: "Error", detail: "Hubo un problema al enviar las respuestas.", life: 3000 });
                });
        };

        const getQuestionText = (id) => {
            const allQuestions = [...stage1Questions.value, ...stage2Questions.value, ...stage3Questions.value, ...stage4Questions.value];
            const question = allQuestions.find((q) => q.id === parseInt(id));
            return question ? question.denominacion : "";
        };

        onMounted(() => {
            if (validationForm) {
                calcularAreaSugerida(validationForm);
            }
        });



        return {
            title,
            validationForm,
            totalQuestions,
            formattedData,
            stage1Questions,
            stage2Questions,
            stage3Questions,
            stage4Questions,
            answers,
            validateTab,
            submit,
            getQuestionText,
            areaSugerida,
            puntajes,
            generarConstancia,
            estadoTest,
        };
    },
};
</script>

<style scoped>
.mb-5 {
    margin-bottom: 1.5rem;
}
.ml-2 {
    margin-left: 0.5rem;
}
.gap-4 {
    gap: 1rem;
}
.gap-2 {
    gap: 0.5rem;
}
pre {
    background: #f4f4f4;
    padding: 15px;
    border-radius: 5px;
    overflow-x: auto;
}
</style>
