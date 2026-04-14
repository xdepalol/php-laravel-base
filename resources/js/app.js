import { createApp } from 'vue';
import { createPinia } from 'pinia'
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate'
import router from './routes/index'
import VueSweetalert2 from "vue-sweetalert2";
import { abilitiesPlugin } from '@casl/vue';
import ability from './services/ability';
import useAuth from './composables/auth';
import { installI18n, loadMessages } from "./plugins/i18n";
import { langStore } from "@/store/lang";
import './plugins/axios.js';
import App from './main.vue'
import UtcFormatted from './components/datetime/UtcFormatted.vue'
import localDateDirective from './directives/localDate'

/*PRIMEVUE */
import PrimeVue from "primevue/config";
import Avatar from 'primevue/avatar';
import Badge from 'primevue/badge';
import Button from 'primevue/button';
import DatePicker from 'primevue/datepicker';
import Checkbox from 'primevue/checkbox';
import Column from 'primevue/column';
import ConfirmationService from 'primevue/confirmationservice';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import DialogService from 'primevue/dialogservice';
import Divider from 'primevue/divider';
import Select from 'primevue/select';
import Skeleton from 'primevue/skeleton';
import Textarea from 'primevue/textarea';
import Toast from 'primevue/toast';
import ToastService from 'primevue/toastservice';
import FileUpload from 'primevue/fileupload';
import Image from 'primevue/image';
import SplitButton from 'primevue/splitbutton';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Menu from 'primevue/menu';
import MultiSelect from 'primevue/multiselect';
import Panel from 'primevue/panel';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import Editor from 'primevue/editor';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Tooltip from 'primevue/tooltip';
import Ripple from 'primevue/ripple';
import FloatLabel from 'primevue/floatlabel';
import Password from 'primevue/password';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';

import "../css/app.css";

const app = createApp(App);
// {
//     created() {
//     useAuth().getUserSignIn()
// },
//     template: `<Toast /> <router-view />`
// }
const pinia = createPinia()
pinia.use(piniaPluginPersistedstate)

app.use(pinia)
app.use(router)
app.use(VueSweetalert2)
app.use(abilitiesPlugin, ability)
app.use(ToastService);
app.use(DialogService);
app.use(ConfirmationService);

const i18n = installI18n(app);
const l = langStore();
l.$subscribe((_, state) => {
    console.info('state: ' + state.locale);
    loadMessages(state.locale)
});

/**PRIMEVUE */
//import Noir from './presets/Noir.js';
import Aura from '@primevue/themes/aura';
app.use(PrimeVue, {
    ripple: true,
    theme: {
        preset: Aura,
        options: {
            prefix: 'p',
            darkModeSelector: '.app-dark',
            cssLayer: false
        }
    }
});


app.component('Avatar', Avatar);
app.component('Badge', Badge);
app.component('Button', Button);
app.component('DatePicker', DatePicker);
app.component('Checkbox', Checkbox);
app.component('DataTable', DataTable);
app.component('Column', Column);
app.component('Dialog', Dialog);
app.component('Divider', Divider);
app.component('Select', Select);
app.component('Skeleton', Skeleton);
app.component('Textarea', Textarea);
app.component('Toast', Toast);
app.component('FileUpload', FileUpload);
app.component('Image', Image);
app.component('InputNumber', InputNumber);
app.component('InputText', InputText);
app.component('Menu', Menu);
app.component('MultiSelect', MultiSelect);
app.component('Panel', Panel);
app.component('Card', Card);
app.component('Tag', Tag);
app.component('FloatLabel', FloatLabel);
app.component('Editor', Editor);
app.component('IconField', IconField);
app.component('InputIcon', InputIcon);
app.component('Password', Password);
app.component('SplitButton', SplitButton);
app.component('Tabs', Tabs);
app.component('TabList', TabList);
app.component('Tab', Tab);
app.component('TabPanels', TabPanels);
app.component('TabPanel', TabPanel);
app.component('UtcFormatted', UtcFormatted);

app.directive('tooltip', Tooltip);
app.directive('ripple', Ripple);
app.directive('local-date', localDateDirective);

// Inicializar modo oscuro desde localStorage antes de montar
// Esto asegura que el tema se aplique inmediatamente sin flash
(function initDarkMode() {
    try {
        // Pinia-plugin-persistedstate guarda con el nombre del store
        const savedTheme = localStorage.getItem('styleStore');
        if (savedTheme) {
            const themeData = JSON.parse(savedTheme);
            // El formato puede ser {darkTheme: true} directamente o anidado
            const isDark = themeData?.darkTheme === true || (themeData?.state && themeData.state.darkTheme === true);
            if (isDark) {
                document.documentElement.classList.add('app-dark', 'dark');
                document.body.classList.add('dark');
                return;
            }
        }
    } catch (e) {
        console.error('Error al leer tema:', e);
    }

    document.documentElement.classList.remove('app-dark', 'dark');
    document.body.classList.remove('dark');
})();

app.mount('#app')
