<template>
    <div class="container mt-5">

        <div class="row justify-content-center">

            <div class="col-md-5">

                <div class="card shadow">

                    <div class="card-body p-4">

                        <h3 class="text-center mb-4">
                            Customer Login
                        </h3>

                        <!-- Success Message -->
                        <div
                            v-if="successMessage"
                            class="alert alert-success"
                        >
                            {{ successMessage }}
                        </div>

                        <!-- Error -->
                        <div
                            v-if="error"
                            class="alert alert-danger"
                        >
                            {{ error }}
                        </div>

                        <!-- Email Not Verified -->
                        <div
                            v-if="emailNotVerified"
                            class="alert alert-warning"
                        >
                            <div class="mb-2">
                                Your email address is not verified.
                            </div>

                            <button
                                type="button"
                                class="btn btn-sm btn-warning"
                                @click="resendVerification"
                                :disabled="resending"
                            >
                                {{
                                    resending
                                        ? 'Sending...'
                                        : 'Resend Verification Email'
                                }}
                            </button>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">

                            <label class="form-label">
                                Email
                            </label>

                            <input
                                v-model="form.email"
                                type="email"
                                class="form-control"
                                placeholder="Enter email"
                                autocomplete="email"
                            >

                            <small
                                v-if="errors.email"
                                class="text-danger"
                            >
                                {{ errors.email[0] }}
                            </small>

                        </div>

                        <!-- Password -->
                        <div class="mb-3">

                            <label class="form-label">
                                Password
                            </label>

                            <div class="input-group">

                                <input
                                    v-model="form.password"
                                    :type="
                                        showPassword
                                            ? 'text'
                                            : 'password'
                                    "
                                    class="form-control"
                                    placeholder="Enter password"
                                    autocomplete="current-password"
                                >

                                <button
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    @click="
                                        showPassword =
                                            !showPassword
                                    "
                                >

                                    <i
                                        :class="
                                            showPassword
                                                ? 'bi bi-eye-slash'
                                                : 'bi bi-eye'
                                        "
                                    ></i>

                                </button>

                            </div>

                            <small
                                v-if="errors.password"
                                class="text-danger"
                            >
                                {{ errors.password[0] }}
                            </small>

                        </div>

                        <!-- Remember -->
                        <div class="form-check mb-3">

                            <input
                                id="remember"
                                v-model="form.remember"
                                type="checkbox"
                                class="form-check-input"
                            >

                            <label
                                for="remember"
                                class="form-check-label"
                            >
                                Remember me
                            </label>

                        </div>

                        <!-- Login -->
                        <button
                            type="button"
                            class="btn btn-primary w-100"
                            @click="login"
                            :disabled="loading"
                        >

                            {{
                                loading
                                    ? 'Logging in...'
                                    : 'Login'
                            }}

                        </button>

                        <!-- Forgot Password -->
                        <div class="text-center mt-3">

                            <a
                                href="/forgot-password"
                            >
                                Forgot Password?
                            </a>

                        </div>

                        <!-- Register -->
                        <div class="text-center mt-2">

                            Don't have an account?

                            <a href="/register">
                                Register
                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>
</template>


<script setup>

import { ref } from 'vue';
import axios from 'axios';


/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

const form = ref({
    email: '',
    password: '',
    remember: false,
});


/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const loading = ref(false);

const resending = ref(false);

const error = ref('');

const successMessage = ref('');

const emailNotVerified = ref(false);

const showPassword = ref(false);

const errors = ref({});


/*
|--------------------------------------------------------------------------
| Login
|--------------------------------------------------------------------------
*/

const login = async () => {

    error.value = '';

    successMessage.value = '';

    emailNotVerified.value = false;

    errors.value = {};

    if (!form.value.email) {

        error.value = 'Email is required.';

        return;
    }

    if (!form.value.password) {

        error.value = 'Password is required.';

        return;
    }

    loading.value = true;

    try {

        const response = await axios.post(
            '/login',
            form.value
        );

        /*
        |--------------------------------------------------------------------------
        | 2FA Required
        |--------------------------------------------------------------------------
        */

        if (
            response.data.two_factor_required
        ) {

            window.location.href =
                '/two-factor';

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Normal Login
        |--------------------------------------------------------------------------
        */

        if (response.data.success) {

            window.location.href =
                '/dashboard';

            return;
        }

    } catch (err) {

        const data =
            err.response?.data;

        /*
        |--------------------------------------------------------------------------
        | Email Not Verified
        |--------------------------------------------------------------------------
        */

        if (
            data?.email_not_verified
        ) {

            emailNotVerified.value =
                true;

            error.value =
                data.message ||
                'Please verify your email.';

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Validation Errors
        |--------------------------------------------------------------------------
        */

        if (data?.errors) {

            errors.value =
                data.errors;

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | General Error
        |--------------------------------------------------------------------------
        */

        error.value =
            data?.message ||
            'Login failed. Please try again.';

    } finally {

        loading.value = false;
    }
};


/*
|--------------------------------------------------------------------------
| Resend Verification
|--------------------------------------------------------------------------
*/

const resendVerification = async () => {

    error.value = '';

    successMessage.value = '';

    resending.value = true;

    try {

        const response =
            await axios.post(
                '/resend-verification'
            );

        if (response.data.success) {

            successMessage.value =
                response.data.message;
        }

    } catch (err) {

        error.value =
            err.response?.data?.message ||
            'Unable to resend verification email.';

    } finally {

        resending.value = false;
    }
};

</script>