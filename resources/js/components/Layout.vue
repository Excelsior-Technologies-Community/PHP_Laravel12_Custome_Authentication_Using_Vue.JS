<template>

    <div
        :class="{
            'dark-mode':
                darkMode &&
                !isAuthPage &&
                !isDashboardPage
        }"
    >


        <!-- ========================================================= -->
        <!-- NAVBAR -->
        <!-- ========================================================= -->

        <nav
            class="navbar navbar-expand-lg"
            :class="
                darkMode &&
                !isAuthPage &&
                !isDashboardPage

                    ? 'navbar-dark bg-dark'

                    : 'navbar-light bg-light'
            "
        >

            <div class="container">


                <!-- Brand -->

                <router-link
                    class="navbar-brand"
                    to="/dashboard"
                >
                    CustomerApp
                </router-link>


                <!-- Mobile -->

                <button
                    class="navbar-toggler"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#navbarNav"
                >

                    <span
                        class="navbar-toggler-icon"
                    ></span>

                </button>


                <!-- Navigation -->

                <div
                    class="collapse navbar-collapse"
                    id="navbarNav"
                >

                    <div class="ms-auto">


                        <!-- ================================================= -->
                        <!-- AUTHENTICATED -->
                        <!-- ================================================= -->

                        <template
                            v-if="isAuthenticated()"
                        >


                            <!-- Dashboard -->

                            <router-link
                                to="/dashboard"
                                class="btn me-2"
                                :class="
                                    darkMode &&
                                    !isAuthPage &&
                                    !isDashboardPage

                                        ? 'btn-outline-light'

                                        : 'btn-outline-dark'
                                "
                            >
                                Dashboard
                            </router-link>


                            <!-- Profile -->

                            <router-link
                                to="/profile"
                                class="btn me-2"
                                :class="
                                    darkMode &&
                                    !isAuthPage &&
                                    !isDashboardPage

                                        ? 'btn-outline-light'

                                        : 'btn-outline-dark'
                                "
                            >
                                Profile
                            </router-link>


                            <!-- Security -->

                            <router-link
                                to="/security"
                                class="btn me-2"
                                :class="
                                    darkMode &&
                                    !isAuthPage &&
                                    !isDashboardPage

                                        ? 'btn-outline-light'

                                        : 'btn-outline-dark'
                                "
                            >
                                Security
                            </router-link>


                            <!-- Logout -->

                            <button
                                @click="logout"
                                class="btn btn-danger"
                                :disabled="loggingOut"
                            >

                                <span
                                    v-if="loggingOut"
                                    class="spinner-border spinner-border-sm me-1"
                                ></span>

                                {{
                                    loggingOut
                                        ? 'Logging out...'
                                        : 'Logout'
                                }}

                            </button>

                        </template>


                        <!-- ================================================= -->
                        <!-- GUEST -->
                        <!-- ================================================= -->

                        <template v-else>


                            <router-link
                                to="/login"
                                class="btn btn-outline-primary me-2"
                            >
                                Login
                            </router-link>


                            <router-link
                                to="/register"
                                class="btn btn-primary"
                            >
                                Register
                            </router-link>

                        </template>


                        <!-- Dark mode -->

                        <button
                            v-if="
                                !isAuthPage &&
                                !isDashboardPage
                            "
                            @click="toggleDarkMode"
                            class="btn btn-sm ms-2"
                            :class="
                                darkMode
                                    ? 'btn-outline-light'
                                    : 'btn-outline-dark'
                            "
                        >

                            {{
                                darkMode
                                    ? '☀️ Light'
                                    : '🌙 Dark'
                            }}

                        </button>

                    </div>

                </div>

            </div>

        </nav>


        <!-- ========================================================= -->
        <!-- CONTENT -->
        <!-- ========================================================= -->

        <div
            class="layout-content"
            :class="{
                'auth-layout':
                    isAuthPage
            }"
        >

            <slot></slot>

        </div>


        <!-- ========================================================= -->
        <!-- TOAST -->
        <!-- ========================================================= -->

        <ToastNotification
            :toasts="$root.toasts"
        />

    </div>

</template>


<script>

export default {

    props: ['path'],


    data() {

        return {

            darkMode:
                localStorage.getItem(
                    'darkMode'
                ) === 'true',

            loggingOut: false,

        };

    },


    computed: {


        isAuthPage() {

            return [

                '/login',

                '/register',

                '/forgot-password',

                '/reset-password'

            ].includes(
                this.path
            );

        },


        isDashboardPage() {

            return [

                '/dashboard',

                '/profile',

                '/security'

            ].includes(
                this.path
            );

        },

    },


    mounted() {

        this.applyDarkMode();

    },


    methods: {


        /*
        |--------------------------------------------------------------------------
        | AUTHENTICATION
        |--------------------------------------------------------------------------
        */

        isAuthenticated() {

            return !!window.authUser;

        },


        /*
        |--------------------------------------------------------------------------
        | GET FRESH CSRF TOKEN
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
        | LOGOUT
        |--------------------------------------------------------------------------
        */

async logout() {

    if (this.loggingOut) {

        return;

    }


    this.loggingOut =
        true;


    try {

        const response =
            await this.$root.apiFetch(
                '/logout',
                {
                    method: 'POST',

                    headers: {
                        'Content-Type':
                            'application/json',

                        'X-CSRF-TOKEN':
                            this.$root.getCsrfToken(),

                        'X-Requested-With':
                            'XMLHttpRequest',
                    },

                    body: JSON.stringify({}),

                }
            );


        /*
        |--------------------------------------------------------------------------
        | Session Already Expired
        |--------------------------------------------------------------------------
        */

        if (
            response.status === 401
        ) {

            return;

        }


        /*
        |--------------------------------------------------------------------------
        | CSRF Expired
        |--------------------------------------------------------------------------
        */

        if (
            response.status === 419
        ) {

            throw new Error(
                'CSRF token expired. Please refresh the page and try again.'
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Read Response
        |--------------------------------------------------------------------------
        */

        const text =
            await response.text();


        let data = {};


        try {

            data =
                text
                    ? JSON.parse(text)
                    : {};

        } catch (error) {

            console.error(
                'Invalid JSON response:',
                text
            );

        }


        /*
        |--------------------------------------------------------------------------
        | HTTP Error
        |--------------------------------------------------------------------------
        */

        if (!response.ok) {

            throw new Error(

                data.message ||
                `Logout failed. HTTP ${response.status}`

            );

        }


        /*
        |--------------------------------------------------------------------------
        | Application Error
        |--------------------------------------------------------------------------
        */

        if (!data.success) {

            throw new Error(

                data.message ||
                'Logout failed.'

            );

        }


        /*
        |--------------------------------------------------------------------------
        | Clear Authentication
        |--------------------------------------------------------------------------
        */

        window.authUser =
            null;


        this.$root.sessionExpired =
            false;


        /*
        |--------------------------------------------------------------------------
        | Success Message
        |--------------------------------------------------------------------------
        */

        this.$root.showToast(

            data.message ||
            'Logged out successfully',

            'success'

        );


        /*
        |--------------------------------------------------------------------------
        | Redirect
        |--------------------------------------------------------------------------
        */

        await this.$router.push(
            '/login'
        );


        /*
        |--------------------------------------------------------------------------
        | Reload
        |--------------------------------------------------------------------------
        |
        | Laravel will generate a fresh session
        | and CSRF token.
        |
        */

        window.location.reload();


    } catch (error) {

        console.error(
            'Logout error:',
            error
        );


        this.$root.showToast(

            error.message ||
            'Logout failed. Please try again.',

            'error'

        );

    } finally {

        this.loggingOut =
            false;

    }

},


        /*
        |--------------------------------------------------------------------------
        | DARK MODE
        |--------------------------------------------------------------------------
        */

        toggleDarkMode() {

            this.darkMode =
                !this.darkMode;


            localStorage.setItem(

                'darkMode',

                this.darkMode

            );


            this.applyDarkMode();

        },


        applyDarkMode() {

            if (

                this.darkMode &&
                !this.isAuthPage &&
                !this.isDashboardPage

            ) {

                document.body.classList.add(
                    'dark-mode'
                );


                document.body.style.backgroundColor =
                    '#0f0f23';


                document.body.style.color =
                    '#e0e0e0';

            } else {

                document.body.classList.remove(
                    'dark-mode'
                );


                document.body.style.backgroundColor =
                    '';


                document.body.style.color =
                    '';

            }

        },

    },

};

</script>


<style scoped>

.layout-content {

    min-height:
        calc(100vh - 80px);

}


.layout-content.auth-layout {

    min-height: 100vh;

    padding: 0;

    margin-top: -80px;

    padding-top: 80px;

}


.dark-mode .card {

    background-color:
        #1a1a2e !important;

    color:
        #e0e0e0 !important;

    border-color:
        #16213e !important;

}


.dark-mode .form-control {

    background-color:
        #16213e !important;

    border-color:
        #0f3460 !important;

    color:
        #e0e0e0 !important;

}


.dark-mode .form-control:focus {

    background-color:
        #16213e !important;

    color:
        #e0e0e0 !important;

}


.dark-mode .form-label {

    color:
        #e0e0e0 !important;

}


.dark-mode .input-group-text {

    background-color:
        #0f3460 !important;

    border-color:
        #0f3460 !important;

    color:
        #e0e0e0 !important;

}


.dark-mode .btn-close {

    filter:
        invert(1);

}


.dark-mode .text-muted {

    color:
        #b0b0b0 !important;

}


.dark-mode table {

    color:
        #e0e0e0 !important;

}


.dark-mode thead {

    background-color:
        #0f3460 !important;

}


.dark-mode .badge {

    filter:
        brightness(1.2);

}

</style>