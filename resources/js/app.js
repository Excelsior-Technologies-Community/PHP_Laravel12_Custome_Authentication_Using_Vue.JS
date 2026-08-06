import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import ToastNotification from './components/ToastNotification.vue';

import Login from './components/Login.vue';
import Register from './components/Register.vue';
import Dashboard from './components/Dashboard.vue';
import Profile from './components/Profile.vue';
import ForgotPassword from './components/ForgotPassword.vue';
import ResetPassword from './components/ResetPassword.vue';

const routes = [
    { path: '/', redirect: '/login' },
    { path: '/login', component: Login, meta: { guest: true } },
    { path: '/register', component: Register, meta: { guest: true } },
    { path: '/forgot-password', component: ForgotPassword, meta: { guest: true } },
    { path: '/reset-password', component: ResetPassword, meta: { guest: true } },
    { path: '/dashboard', component: Dashboard, meta: { requiresAuth: true } },
    { path: '/profile', component: Profile, meta: { requiresAuth: true } },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to, from, next) => {
    const isAuthenticated = !!window.authUser;

    if (to.meta.requiresAuth && !isAuthenticated) {
        next('/login');
    } else if (to.meta.guest && isAuthenticated) {
        next('/dashboard');
    } else {
        next();
    }
});

const app = createApp({
    data() {
        return {
            toasts: [],
            toastId: 0,
        };
    },
    methods: {
        showToast(message, type = 'success') {
            const id = ++this.toastId;
            this.toasts.push({ id, message, type });
            setTimeout(() => {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }, 3000);
        },
    },
    template: `
        <router-view></router-view>
        <ToastNotification :toasts="toasts" />
    `,
});

app.use(router);
app.component('ToastNotification', ToastNotification);
app.mount('#app');
