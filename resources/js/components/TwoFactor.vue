<template>
    <div class="container mt-5">

        <div class="row justify-content-center">

            <div class="col-md-5">

                <div class="card shadow">

                    <div class="card-body">

                        <h3 class="text-center mb-4">
                            Two-Factor Authentication
                        </h3>

                        <p class="text-muted text-center">
                            Enter the 6-digit OTP sent to your email.
                        </p>

                        <div
                            v-if="error"
                            class="alert alert-danger"
                        >
                            {{ error }}
                        </div>

                        <div
                            v-if="message"
                            class="alert alert-success"
                        >
                            {{ message }}
                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                OTP
                            </label>

                            <input
                                v-model="otp"
                                type="text"
                                maxlength="6"
                                class="form-control"
                                placeholder="Enter 6 digit OTP"
                            >

                        </div>

                        <button
                            @click="verifyOtp"
                            class="btn btn-primary w-100"
                            :disabled="loading"
                        >

                            {{
                                loading
                                    ? 'Verifying...'
                                    : 'Verify OTP'
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

const otp = ref('');

const loading = ref(false);

const error = ref('');

const message = ref('');

const verifyOtp = async () => {

    error.value = '';
    message.value = '';

    if (otp.value.length !== 6) {

        error.value =
            'Please enter a valid 6 digit OTP.';

        return;
    }

    loading.value = true;

    try {

        const response =
            await axios.post(
                '/verify-2fa',
                {
                    otp: otp.value
                }
            );

        if (response.data.success) {

            window.location.href =
                '/dashboard';
        }

    } catch (err) {

        error.value =
            err.response?.data?.message ||
            'Invalid OTP.';

    } finally {

        loading.value = false;
    }
};

</script>