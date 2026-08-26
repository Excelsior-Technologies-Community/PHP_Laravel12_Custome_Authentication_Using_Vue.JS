import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';

import ToastNotification from './components/ToastNotification.vue';

import Login from './components/Login.vue';
import Register from './components/Register.vue';
import Dashboard from './components/Dashboard.vue';
import Profile from './components/Profile.vue';
import ForgotPassword from './components/ForgotPassword.vue';
import ResetPassword from './components/ResetPassword.vue';
import Security from './components/Security.vue';

const routes = [

    {
        path: '/',
        redirect: '/login'
    },

    {
        path: '/login',
        component: Login,
        meta: {
            guest: true
        }
    },

    {
        path: '/register',
        component: Register,
        meta: {
            guest: true
        }
    },

    {
        path: '/forgot-password',
        component: ForgotPassword,
        meta: {
            guest: true
        }
    },

    {
        path: '/reset-password',
        component: ResetPassword,
        meta: {
            guest: true
        }
    },

    {
        path: '/dashboard',
        component: Dashboard,
        meta: {
            requiresAuth: true
        }
    },

    {
        path: '/profile',
        component: Profile,
        meta: {
            requiresAuth: true
        }
    },

    {
        path: '/security',
        component: Security,
        meta: {
            requiresAuth: true
        }
    },

];

const router = createRouter({

    history: createWebHistory(),

    routes,

});


/*
|--------------------------------------------------------------------------
| Authentication Middleware
|--------------------------------------------------------------------------
*/

router.beforeEach((to, from, next) => {

    const isAuthenticated =
        !!window.authUser;

    if (
        to.meta.requiresAuth &&
        !isAuthenticated
    ) {

        next('/login');

    }

    else if (
        to.meta.guest &&
        isAuthenticated
    ) {

        next('/dashboard');

    }

    else {

        next();

    }

});


/*
|--------------------------------------------------------------------------
| Vue Application
|--------------------------------------------------------------------------
*/

const app = createApp({

    data() {

        return {

            toasts: [],

            toastId: 0,

        };

    },


    methods: {

        /*
        |--------------------------------------------------------------------------
        | SHOW TOAST
        |--------------------------------------------------------------------------
        */

        showToast(
            message,
            type = 'success'
        ) {

            const id =
                ++this.toastId;

            this.toasts.push({

                id,

                message,

                type

            });


            setTimeout(() => {

                this.toasts =
                    this.toasts.filter(
                        toast =>
                            toast.id !== id
                    );

            }, 3000);

        },


        /*
        |--------------------------------------------------------------------------
        | GET CURRENT CSRF TOKEN
        |--------------------------------------------------------------------------
        */

        getCsrfToken() {

            const meta =
                document.querySelector(
                    'meta[name="csrf-token"]'
                );


            if (!meta) {

                console.error(
                    'CSRF token meta tag not found.'
                );

                return '';

            }


            return meta.getAttribute(
                'content'
            );

        },


        /*
        |--------------------------------------------------------------------------
        | UPDATE CSRF TOKEN
        |--------------------------------------------------------------------------
        */

        setCsrfToken(token) {

            if (!token) {

                return;

            }


            const meta =
                document.querySelector(
                    'meta[name="csrf-token"]'
                );


            if (meta) {

                meta.setAttribute(
                    'content',
                    token
                );

            }

        },


        /*
        |--------------------------------------------------------------------------
        | CENTRALIZED API FETCH
        |--------------------------------------------------------------------------
        |
        | All authenticated API requests should use this method.
        |
        */

        async apiFetch(
            url,
            options = {}
        ) {

            const headers = {

                Accept:
                    'application/json',

                'X-Requested-With':
                    'XMLHttpRequest',

                ...(options.headers || {}),

            };


            /*
            |--------------------------------------------------------------------------
            | Add CSRF token automatically for non-GET requests
            |--------------------------------------------------------------------------
            */

            const method =
                (
                    options.method ||
                    'GET'
                ).toUpperCase();


            if (
                ![
                    'GET',
                    'HEAD',
                    'OPTIONS'
                ].includes(method)
            ) {

                headers['X-CSRF-TOKEN'] =
                    this.getCsrfToken();

            }


            const response =
                await fetch(
                    url,
                    {

                        ...options,

                        credentials:
                            'same-origin',

                        headers,

                    }
                );


            /*
            |--------------------------------------------------------------------------
            | CSRF TOKEN ERROR
            |--------------------------------------------------------------------------
            */

            if (
                response.status === 419
            ) {

                this.showToast(
                    'Your session has expired. Please refresh the page.',
                    'error'
                );


                return response;

            }


            /*
            |--------------------------------------------------------------------------
            | SESSION EXPIRED / REVOKED
            |--------------------------------------------------------------------------
            */

            if (
                response.status === 401
            ) {

                window.authUser =
                    null;


                this.showToast(
                    'Your session has expired or been revoked.',
                    'error'
                );


                setTimeout(() => {

                    if (
                        window.location.pathname !==
                        '/login'
                    ) {

                        window.location.href =
                            '/login';

                    }

                }, 500);


                return response;

            }


            return response;

        },

    },


    template: `

        <router-view></router-view>

        <ToastNotification
            :toasts="toasts"
        />

    `,

});


app.use(router);


app.component(
    'ToastNotification',
    ToastNotification
);


app.mount('#app');