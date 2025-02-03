import "../css/app.css";
import "./bootstrap";

import 'primeicons/primeicons.css'

import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { createApp, DefineComponent, h } from "vue";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import PrimeVue from "primevue/config";
import { Omega } from "./Omega";

import Button from "primevue/button";
import Menu from 'primevue/menu';
import Tag from 'primevue/tag';
import Tooltip from 'primevue/tooltip';
import InputText from 'primevue/inputtext';
import InputGroup from 'primevue/inputgroup';
import InputGroupAddon from 'primevue/inputgroupaddon';
import Dropdown from 'primevue/dropdown';
import MultiSelect from 'primevue/multiselect';
import ToggleButton from 'primevue/togglebutton';
import Paginator from 'primevue/paginator';
import SelectButton from 'primevue/selectbutton';
import InputNumber from 'primevue/inputnumber';
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';
import Checkbox from 'primevue/checkbox';
import Chip from 'primevue/chip';
import Textarea from 'primevue/textarea';
import Toast from 'primevue/toast';
import ToastService from 'primevue/toastservice';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

const appName = import.meta.env.VITE_APP_NAME || "Laravel";

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<DefineComponent>("./Pages/**/*.vue")
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(PrimeVue, {
                ripple: true,
                theme: {
                    preset: Omega,
                    options: {
                        darkModeSelector: false || "none",
                    },
                },
            })
            .use(ToastService)
            .component("Button", Button)
            .component("Menu", Menu)
            .component("Tag", Tag)
            .component("InputText", InputText)
            .component("InputGroup", InputGroup)
            .component("InputGroupAddon", InputGroupAddon)
            .component("Dropdown", Dropdown)
            .component("MultiSelect", MultiSelect)
            .component("ToggleButton", ToggleButton)
            .component("Paginator", Paginator)
            .component("SelectButton", SelectButton)
            .component("InputNumber", InputNumber)
            .component("Accordion", Accordion)
            .component("AccordionTab", AccordionTab)
            .component("Checkbox", Checkbox)
            .component("Chip", Chip)
            .component("Textarea", Textarea)
            .component("Toast", Toast)
            .component("DataTable", DataTable)
            .component("Column", Column)
            .directive('tooltip', Tooltip)
            .mount(el);
    },
    progress: {
        color: "#4B5563",
    },
});
