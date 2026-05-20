require("./bootstrap");

// Import modules...
import { createApp, h, reactive } from "vue";
import { App, App as InertiaApp, plugin as InertiaPlugin } from "@inertiajs/inertia-vue3";
import { InertiaProgress } from "@inertiajs/progress";

// componentes de PrimeVue
import PrimeVue from "primevue/config";
import ConfirmationService from "primevue/confirmationservice";
import ToastService from "primevue/toastservice";
// import CodeHighlight from "./AppCodeHighlight";
import Ripple from "primevue/ripple";
import BadgeDirective from "primevue/badgedirective";
import Toast from "primevue/toast";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Password from "primevue/password";
import Card from "primevue/card";
import Checkbox from "primevue/checkbox";
import Toolbar from "primevue/toolbar";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import ColumnGroup from "primevue/columngroup";
import Dialog from "primevue/dialog";
import Badge from "primevue/badge";
import AutoComplete from "primevue/autocomplete";
import Avatar from "primevue/avatar";
import Divider from "primevue/divider";
import Calendar from "primevue/calendar";
import Dropdown from "primevue/dropdown";
import RadioButton from "primevue/radiobutton";
import FileUpload from "primevue/fileupload";
import InputNumber from "primevue/inputnumber";
import InlineMessage from "primevue/inlinemessage";
import Message from "primevue/message";
import Textarea from "primevue/textarea";
import Tag from "primevue/tag";
import Panel from "primevue/panel";
import SelectButton from "primevue/selectbutton";
import Fieldset from "primevue/fieldset";
import Timeline from "primevue/timeline";
import Accordion from "primevue/accordion";
import AccordionTab from "primevue/accordiontab";
import Image from "primevue/image";
import TabView from "primevue/tabview";
import TabPanel from "primevue/tabpanel";
import Skeleton from "primevue/skeleton";
import Chip from "primevue/chip";

const el = document.getElementById("app");

const app = createApp({
    render: () =>
        h(InertiaApp, {
            initialPage: JSON.parse(el.dataset.page),
            resolveComponent: (name) => require(`./Pages/${name}`).default,
        }),
});

app.config.globalProperties.$appState = reactive({ inputStyle: "outlined" });
app.use(PrimeVue, { ripple: true });
app.use(ConfirmationService);
app.use(ToastService);

app.mixin({ methods: { route } });
app.use(InertiaPlugin);

// app.directive("code", CodeHighlight);
app.directive("ripple", Ripple);
app.directive("badge", BadgeDirective);

// componentes de PrimeVue
app.component("Button", Button);
app.component("Toast", Toast);
app.component("InputText", InputText);
app.component("Password", Password);
app.component("Card", Card);
app.component("Checkbox", Checkbox);
app.component("Toolbar", Toolbar);
app.component("DataTable", DataTable);
app.component("Column", Column);
app.component("ColumnGroup", ColumnGroup);
app.component("Dialog", Dialog);
app.component("Badge", Badge);
app.component("AutoComplete", AutoComplete);
app.component("Avatar", Avatar);
app.component("Divider", Divider);
app.component("Calendar", Calendar);
app.component("Dropdown", Dropdown);
app.component("RadioButton", RadioButton);
app.component("FileUpload", FileUpload);
app.component("InputNumber", InputNumber);
app.component("InlineMessage", InlineMessage);
app.component("Textarea", Textarea);
app.component("Tag", Tag);
app.component("Message", Message);
app.component("Panel", Panel);
app.component("SelectButton", SelectButton);
app.component("Fieldset", Fieldset);
app.component("Timeline", Timeline);
app.component("Accordion", Accordion);
app.component("AccordionTab", AccordionTab);
app.component("Image", Image);
app.component("TabView", TabView);
app.component("TabPanel", TabPanel);
app.component("Skeleton", Skeleton);
app.component("Chip", Chip);

app.mount("#app");

InertiaProgress.init({ color: "#4B5563" });
