import './bootstrap';
import { createApp } from 'vue';
import router from './router';
import App from './App.vue';
import { initTheme } from './composables/useDarkMode';

// Apply the saved light/dark theme before the first render
initTheme();

const app = createApp(App);

app.use(router);

app.mount('#app');
