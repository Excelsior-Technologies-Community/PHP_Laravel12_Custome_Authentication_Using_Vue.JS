<template>

    <div class="container mt-4">

        <div class="row justify-content-center">

            <div class="col-md-6">

                <div class="card shadow">

                    <div class="card-body p-4">

                        <h4 class="mb-4">
                            Change Password
                        </h4>


                        <!-- Success -->
                        <div
                            v-if="success"
                            class="alert alert-success"
                        >
                            {{ success }}
                        </div>


                        <!-- Error -->
                        <div
                            v-if="error"
                            class="alert alert-danger"
                        >
                            {{ error }}
                        </div>


                        <!-- Current Password -->
                        <div class="mb-3">

                            <label class="form-label">
                                Current Password
                            </label>

                            <div class="input-group">

                                <input
                                    v-model="
                                        form.current_password
                                    "
                                    :type="
                                        showCurrentPassword
                                            ? 'text'
                                            : 'password'
                                    "
                                    class="form-control"
                                    placeholder="Current password"
                                >

                                <button
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    @click="
                                        showCurrentPassword =
                                            !showCurrentPassword
                                    "
                                >

                                    <i
                                        :class="
                                            showCurrentPassword
                                                ? 'bi bi-eye-slash'
                                                : 'bi bi-eye'
                                        "
                                    ></i>

                                </button>

                            </div>

                        </div>


                        <!-- New Password -->
                        <div class="mb-3">

                            <label class="form-label">
                                New Password
                            </label>

                            <div class="input-group">

                                <input
                                    v-model="
                                        form.password
                                    "
                                    :type="
                                        showPassword
                                            ? 'text'
                                            : 'password'
                                    "
                                    class="form-control"
                                    placeholder="New password"
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

                        </div>


                        <!-- Confirm Password -->
                        <div class="mb-3">

                            <label class="form-label">
                                Confirm Password
                            </label>

                            <div class="input-group">

                                <input
                                    v-model="
                                        form.password_confirmation
                                    "
                                    :type="
                                        showConfirmPassword
                                            ? 'text'
                                            : 'password'
                                    "
                                    class="form-control"
                                    placeholder="Confirm password"
                                >

                                <button
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    @click="
                                        showConfirmPassword =
                                            !showConfirmPassword
                                    "
                                >

                                    <i
                                        :class="
                                            showConfirmPassword
                                                ? 'bi bi-eye-slash'
                                                : 'bi bi-eye'
                                        "
                                    ></i>

                                </button>

                            </div>

                        </div>


                        <!-- Submit -->
                        <button
                            type="button"
                            class="btn btn-primary w-100"
                            @click="changePassword"
                            :disabled="loading"
                        >

                            {{
                                loading
                                    ? 'Changing...'
                                    : 'Change Password'
                            }}

                        </button>

                    </div>

                </div>

            </div>

        </div>

    </div>

</template>


<script setup>

import { ref } from 'vue';
import axios from 'axios';


const form = ref({
    current_password: '',
    password: '',
    password_confirmation: '',
});


const loading = ref(false);

const error = ref('');

const success = ref('');


const showCurrentPassword =
    ref(false);

const showPassword =
    ref(false);

const showConfirmPassword =
    ref(false);


const changePassword = async () => {

    error.value = '';

    success.value = '';

    loading.value = true;

    try {

        const response =
            await axios.post(
                '/api/password/change',
                form.value
            );

        if (response.data.success) {

            success.value =
                response.data.message;

            form.value = {
                current_password: '',
                password: '',
                password_confirmation: '',
            };
        }

    } catch (err) {

        error.value =
            err.response?.data?.message ||
            'Unable to change password.';

    } finally {

        loading.value = false;
    }
};

</script>