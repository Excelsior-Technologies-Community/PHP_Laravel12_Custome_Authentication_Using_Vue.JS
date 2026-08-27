<template>

    <div class="container mt-4">

        <h2 class="mb-4">
            Security
        </h2>


        <!-- Loading -->
        <div
            v-if="loading"
            class="text-center"
        >
            Loading security information...
        </div>


        <template v-else>

            <!-- Security Status -->
            <div class="card shadow-sm mb-4">

                <div class="card-header">
                    <h5 class="mb-0">
                        Security Status
                    </h5>
                </div>

                <div class="card-body">

                    <div class="row">

                        <!-- Email -->
                        <div class="col-md-4 mb-3">

                            <strong>
                                Email Verification
                            </strong>

                            <div class="mt-2">

                                <span
                                    class="badge"
                                    :class="
                                        security.email_verified
                                            ? 'bg-success'
                                            : 'bg-warning text-dark'
                                    "
                                >

                                    {{
                                        security.email_verified
                                            ? 'Verified'
                                            : 'Not Verified'
                                    }}

                                </span>

                            </div>

                        </div>


                        <!-- 2FA -->
                        <div class="col-md-4 mb-3">

                            <strong>
                                Two-Factor Authentication
                            </strong>

                            <div class="mt-2">

                                <span
                                    class="badge"
                                    :class="
                                        security.two_factor_enabled
                                            ? 'bg-success'
                                            : 'bg-secondary'
                                    "
                                >

                                    {{
                                        security.two_factor_enabled
                                            ? 'Enabled'
                                            : 'Disabled'
                                    }}

                                </span>

                            </div>

                        </div>


                        <!-- Account -->
                        <div class="col-md-4 mb-3">

                            <strong>
                                Account Status
                            </strong>

                            <div class="mt-2">

                                <span
                                    class="badge"
                                    :class="
                                        security.account_active
                                            ? 'bg-success'
                                            : 'bg-danger'
                                    "
                                >

                                    {{
                                        security.account_active
                                            ? 'Active'
                                            : 'Inactive'
                                    }}

                                </span>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- Two Factor -->
            <div class="card shadow-sm mb-4">

                <div class="card-header">

                    <h5 class="mb-0">
                        Two-Factor Authentication
                    </h5>

                </div>

                <div class="card-body">

                    <p class="text-muted">

                        Add an extra layer of security.
                        When enabled, you will receive
                        an OTP by email during login.

                    </p>


                    <div class="mb-3">

                        <span
                            class="badge me-2"
                            :class="
                                security.two_factor_enabled
                                    ? 'bg-success'
                                    : 'bg-secondary'
                            "
                        >

                            {{
                                security.two_factor_enabled
                                    ? '2FA Enabled'
                                    : '2FA Disabled'
                            }}

                        </span>

                    </div>


                    <button
                        type="button"
                        class="btn"
                        :class="
                            security.two_factor_enabled
                                ? 'btn-danger'
                                : 'btn-success'
                        "
                        @click="toggleTwoFactor"
                        :disabled="twoFactorLoading"
                    >

                        {{
                            twoFactorLoading
                                ? 'Processing...'
                                : security.two_factor_enabled
                                    ? 'Disable 2FA'
                                    : 'Enable 2FA'
                        }}

                    </button>

                </div>

            </div>


            <!-- Password -->
            <div class="card shadow-sm mb-4">

                <div class="card-header">

                    <h5 class="mb-0">
                        Password Security
                    </h5>

                </div>

                <div class="card-body">

                    <p>
                        Last changed:
                        <strong>
                            {{
                                formatDate(
                                    security.password_changed_at
                                )
                            }}
                        </strong>
                    </p>

                </div>

            </div>


            <!-- Login Information -->
            <div class="card shadow-sm mb-4">

                <div class="card-header">

                    <h5 class="mb-0">
                        Login Information
                    </h5>

                </div>

                <div class="card-body">

                    <p>
                        Last Login:
                        <strong>
                            {{
                                formatDate(
                                    security.last_login_at
                                )
                            }}
                        </strong>
                    </p>

                    <p>
                        Last Login IP:
                        <strong>
                            {{
                                security.last_login_ip ||
                                'N/A'
                            }}
                        </strong>
                    </p>

                    <p>
                        Failed Login Attempts:
                        <strong>
                            {{
                                security.failed_login_attempts
                            }}
                        </strong>
                    </p>

                    <p>
                        Failed Login Attempts
                        Last 30 Days:
                        <strong>
                            {{
                                security.failed_login_attempts_30_days
                            }}
                        </strong>
                    </p>

                </div>

            </div>


            <!-- Active Sessions -->
            <div class="card shadow-sm mb-4">

                <div class="card-header">

                    <h5 class="mb-0">
                        Active Sessions
                    </h5>

                </div>

                <div class="card-body">

                    <p>

                        Active Sessions:

                        <strong>
                            {{
                                security.active_sessions_count
                            }}
                        </strong>

                    </p>

                    <p>

                        Total Sessions:

                        <strong>
                            {{
                                security.total_sessions_count
                            }}
                        </strong>

                    </p>

                </div>

            </div>


            <!-- Deactivate -->
            <div class="card border-warning shadow-sm mb-4">

                <div class="card-header bg-warning">

                    <h5 class="mb-0">
                        Deactivate Account
                    </h5>

                </div>

                <div class="card-body">

                    <p class="text-muted">

                        Deactivating your account will
                        prevent you from logging in.

                    </p>

                    <button
                        type="button"
                        class="btn btn-warning"
                        @click="deactivateAccount"
                        :disabled="accountLoading"
                    >

                        {{
                            accountLoading
                                ? 'Processing...'
                                : 'Deactivate Account'
                        }}

                    </button>

                </div>

            </div>


            <!-- Delete -->
            <div class="card border-danger shadow-sm mb-4">

                <div class="card-header bg-danger text-white">

                    <h5 class="mb-0">
                        Delete Account
                    </h5>

                </div>

                <div class="card-body">

                    <p class="text-danger">

                        <strong>
                            Warning:
                        </strong>

                        This action permanently deletes
                        your account and cannot be undone.

                    </p>

                    <button
                        type="button"
                        class="btn btn-danger"
                        @click="deleteAccount"
                        :disabled="deleteLoading"
                    >

                        {{
                            deleteLoading
                                ? 'Deleting...'
                                : 'Permanently Delete Account'
                        }}

                    </button>

                </div>

            </div>

        </template>

    </div>

</template>


<script setup>

import {
    ref,
    onMounted
} from 'vue';

import axios from 'axios';


/*
|--------------------------------------------------------------------------
| State
|--------------------------------------------------------------------------
*/

const loading = ref(true);

const twoFactorLoading =
    ref(false);

const accountLoading =
    ref(false);

const deleteLoading =
    ref(false);

const error = ref('');


/*
|--------------------------------------------------------------------------
| Security
|--------------------------------------------------------------------------
*/

const security = ref({

    email_verified: false,

    two_factor_enabled: false,

    account_active: true,

    password_changed_at: null,

    last_login_at: null,

    last_login_ip: null,

    failed_login_attempts: 0,

    failed_login_attempts_30_days: 0,

    active_sessions_count: 0,

    total_sessions_count: 0,
});


/*
|--------------------------------------------------------------------------
| Load Security
|--------------------------------------------------------------------------
*/

const loadSecurity = async () => {

    loading.value = true;

    try {

        const response =
            await axios.get(
                '/api/security'
            );

        if (response.data.success) {

            security.value =
                response.data.security;
        }

    } catch (err) {

        error.value =
            err.response?.data?.message ||
            'Unable to load security information.';

        alert(error.value);

    } finally {

        loading.value = false;
    }
};


/*
|--------------------------------------------------------------------------
| Toggle 2FA
|--------------------------------------------------------------------------
*/

const toggleTwoFactor = async () => {

    const action =
        security.value.two_factor_enabled
            ? 'disable'
            : 'enable';

    if (
        !confirm(
            `Are you sure you want to ${action} two-factor authentication?`
        )
    ) {

        return;
    }

    twoFactorLoading.value = true;

    try {

        const response =
            await axios.post(
                '/api/2fa/toggle'
            );

        if (response.data.success) {

            security.value.two_factor_enabled =
                response.data.two_factor_enabled;

            alert(
                response.data.message
            );
        }

    } catch (err) {

        alert(
            err.response?.data?.message ||
            'Unable to update 2FA.'
        );

    } finally {

        twoFactorLoading.value = false;
    }
};


/*
|--------------------------------------------------------------------------
| Deactivate Account
|--------------------------------------------------------------------------
*/

const deactivateAccount = async () => {

    const confirmed =
        confirm(
            'Are you sure you want to deactivate your account?'
        );

    if (!confirmed) {

        return;
    }

    const secondConfirm =
        confirm(
            'You will be logged out and will not be able to login until the account is activated again. Continue?'
        );

    if (!secondConfirm) {

        return;
    }

    accountLoading.value = true;

    try {

        const response =
            await axios.post(
                '/api/account/deactivate'
            );

        if (response.data.success) {

            alert(
                response.data.message
            );

            window.location.href =
                '/login';
        }

    } catch (err) {

        alert(
            err.response?.data?.message ||
            'Unable to deactivate account.'
        );

    } finally {

        accountLoading.value = false;
    }
};


/*
|--------------------------------------------------------------------------
| Delete Account
|--------------------------------------------------------------------------
*/

const deleteAccount = async () => {

    const confirmed =
        confirm(
            'WARNING: This will permanently delete your account. Are you sure?'
        );

    if (!confirmed) {

        return;
    }

    const secondConfirm =
        confirm(
            'This action cannot be undone. Delete your account permanently?'
        );

    if (!secondConfirm) {

        return;
    }

    deleteLoading.value = true;

    try {

        const response =
            await axios.delete(
                '/api/account'
            );

        if (response.data.success) {

            alert(
                response.data.message
            );

            window.location.href =
                '/register';
        }

    } catch (err) {

        alert(
            err.response?.data?.message ||
            'Unable to delete account.'
        );

    } finally {

        deleteLoading.value = false;
    }
};


/*
|--------------------------------------------------------------------------
| Format Date
|--------------------------------------------------------------------------
*/

const formatDate = (date) => {

    if (!date) {

        return 'Never';
    }

    return new Date(date)
        .toLocaleString();
};


/*
|--------------------------------------------------------------------------
| Load
|--------------------------------------------------------------------------
*/

onMounted(() => {

    loadSecurity();

});

</script>