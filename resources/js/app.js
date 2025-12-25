import { createApp } from 'vue';

import Login from './components/Login.vue';
import Register from './components/Register.vue';
import Dashboard from './components/Dashboard.vue';

createApp({
    components: {
        Login,
        Register,
        Dashboard,
    },
    data() {
        return {
            path: window.location.pathname,
        };
    },
    template: `
        <Login v-if="path === '/login'" />
        <Register v-else-if="path === '/register'" />
        <Dashboard v-else-if="path === '/dashboard'" />
        <Login v-else />
    `
}).mount('#app');
