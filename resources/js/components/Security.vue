<template>

    <div class="security-page">

        <div class="container py-5">


            <!-- ========================================================= -->
            <!-- HEADER -->
            <!-- ========================================================= -->

            <div
                class="d-flex justify-content-between align-items-center mb-4"
            >

                <div>

                    <h2 class="fw-bold mb-1">
                        Account Security
                    </h2>

                    <p class="text-muted mb-0">
                        Monitor your account security and manage active devices.
                    </p>

                </div>


                <router-link
                    to="/dashboard"
                    class="btn btn-outline-primary"
                >
                    ← Dashboard
                </router-link>

            </div>


            <!-- ========================================================= -->
            <!-- LOADING -->
            <!-- ========================================================= -->

            <div
                v-if="loading"
                class="text-center py-5"
            >

                <div
                    class="spinner-border text-primary"
                    role="status"
                ></div>

                <p class="text-muted mt-3">
                    Loading security information...
                </p>

            </div>


            <!-- ========================================================= -->
            <!-- ERROR -->
            <!-- ========================================================= -->

            <div
                v-else-if="error"
                class="alert alert-danger"
            >

                <strong>Error:</strong>

                {{ error }}


                <button
                    @click="loadSecurityData"
                    class="btn btn-sm btn-danger ms-3"
                >
                    Retry
                </button>

            </div>


            <!-- ========================================================= -->
            <!-- SECURITY CONTENT -->
            <!-- ========================================================= -->

            <template v-else>


                <!-- ===================================================== -->
                <!-- SECURITY STATISTICS -->
                <!-- ===================================================== -->

                <div class="row g-4 mb-4">


                    <!-- Account Created -->

                    <div class="col-md-3">

                        <div class="card security-card h-100">

                            <div class="card-body">

                                <div class="security-icon">
                                    👤
                                </div>

                                <p class="text-muted mb-1">
                                    Account Created
                                </p>

                                <h5 class="fw-bold">
                                    {{ formatDate(
                                        security.account_created_at
                                    ) }}
                                </h5>

                            </div>

                        </div>

                    </div>


                    <!-- Last Login -->

                    <div class="col-md-3">

                        <div class="card security-card h-100">

                            <div class="card-body">

                                <div class="security-icon">
                                    🔐
                                </div>

                                <p class="text-muted mb-1">
                                    Last Successful Login
                                </p>

                                <h5 class="fw-bold">
                                    {{ formatDate(
                                        security.last_login_at
                                    ) }}
                                </h5>

                            </div>

                        </div>

                    </div>


                    <!-- Password -->

                    <div class="col-md-3">

                        <div class="card security-card h-100">

                            <div class="card-body">

                                <div class="security-icon">
                                    🔑
                                </div>

                                <p class="text-muted mb-1">
                                    Password Last Changed
                                </p>

                                <h5 class="fw-bold">
                                    {{ formatDate(
                                        security.password_changed_at
                                    ) }}
                                </h5>

                            </div>

                        </div>

                    </div>


                    <!-- Active Sessions -->

                    <div class="col-md-3">

                        <div class="card security-card h-100">

                            <div class="card-body">

                                <div class="security-icon">
                                    💻
                                </div>

                                <p class="text-muted mb-1">
                                    Active Sessions
                                </p>

                                <h5 class="fw-bold">
                                    {{ security.active_sessions_count }}
                                </h5>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- ===================================================== -->
                <!-- LAST LOGIN INFORMATION -->
                <!-- ===================================================== -->

                <div class="card security-card mb-4">

                    <div class="card-body">

                        <div class="row">

                            <div class="col-md-6">

                                <strong>
                                    Last Login IP
                                </strong>

                                <div class="text-muted mt-1">

                                    {{
                                        security.last_login_ip ||
                                        'Unknown'
                                    }}

                                </div>

                            </div>


                            <div class="col-md-6">

                                <strong>
                                    Failed Login Attempts
                                </strong>

                                <div class="mt-1">

                                    <span class="badge bg-danger fs-6">

                                        {{
                                            security.failed_login_attempts
                                        }}

                                    </span>

                                    <small class="text-muted ms-2">

                                        {{
                                            security.failed_login_attempts_30_days
                                        }}
                                        in the last 30 days

                                    </small>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- ===================================================== -->
                <!-- ACTIVE SESSIONS -->
                <!-- ===================================================== -->

                <div class="card security-card mb-4">


                    <div class="card-header bg-transparent py-3">

                        <div
                            class="d-flex justify-content-between align-items-center"
                        >

                            <div>

                                <h5 class="fw-bold mb-1">
                                    Active Devices / Sessions
                                </h5>

                                <small class="text-muted">
                                    Devices currently logged into your account.
                                </small>

                            </div>


                            <button
                                v-if="sessions.length > 1"
                                @click="logoutOtherSessions"
                                class="btn btn-danger"
                                :disabled="processing"
                            >

                                <span
                                    v-if="processing"
                                    class="spinner-border spinner-border-sm me-1"
                                ></span>

                                Logout All Other Devices

                            </button>

                        </div>

                    </div>


                    <div class="card-body p-0">


                        <!-- No sessions -->

                        <div
                            v-if="sessions.length === 0"
                            class="text-center py-5 text-muted"
                        >

                            <div class="fs-1 mb-2">
                                💻
                            </div>

                            <p class="mb-0">
                                No active sessions found.
                            </p>

                        </div>


                        <!-- Sessions -->

                        <div
                            v-for="session in sessions"
                            :key="session.id"
                            class="session-item"
                        >


                            <div class="session-info">


                                <div class="device-icon">

                                    {{
                                        getDeviceIcon(
                                            session.device_name
                                        )
                                    }}

                                </div>


                                <div>


                                    <div
                                        class="d-flex align-items-center gap-2 mb-1"
                                    >

                                        <h6 class="fw-bold mb-0">

                                            {{
                                                session.browser ||
                                                'Unknown Browser'
                                            }}

                                        </h6>


                                        <span
                                            v-if="session.is_current"
                                            class="badge bg-success"
                                        >
                                            Current Device
                                        </span>


                                        <span
                                            v-else-if="session.is_active"
                                            class="badge bg-primary"
                                        >
                                            Active
                                        </span>

                                    </div>


                                    <div class="session-details">


                                        <div>

                                            💻

                                            {{
                                                session.device_name ||
                                                'Unknown Device'
                                            }}

                                        </div>


                                        <div>

                                            🖥️

                                            {{
                                                session.platform ||
                                                'Unknown Platform'
                                            }}

                                        </div>


                                        <div>

                                            🌐 IP:

                                            {{
                                                session.ip_address ||
                                                'Unknown'
                                            }}

                                        </div>


                                        <div>

                                            🕐 Login:

                                            {{
                                                formatDate(
                                                    session.login_at
                                                )
                                            }}

                                        </div>


                                        <div>

                                            🔄 Last Activity:

                                            {{
                                                formatDate(
                                                    session.last_activity_at
                                                )
                                            }}

                                        </div>

                                    </div>

                                </div>

                            </div>


                            <!-- Logout device -->

                            <div v-if="!session.is_current">

                                <button
                                    @click="logoutSession(session.id)"
                                    class="btn btn-outline-danger btn-sm"
                                    :disabled="processing"
                                >
                                    Logout
                                </button>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- ===================================================== -->
                <!-- RECENT SECURITY ACTIVITY -->
                <!-- ===================================================== -->

                <div class="card security-card">


                    <div class="card-header bg-transparent py-3">

                        <h5 class="fw-bold mb-1">
                            Recent Security Activity
                        </h5>

                        <small class="text-muted">
                            Recent login and security events.
                        </small>

                    </div>


                    <div class="card-body p-0">


                        <!-- No activities -->

                        <div
                            v-if="activities.length === 0"
                            class="text-center py-5 text-muted"
                        >
                            No recent security activity.
                        </div>


                        <!-- Activities -->

                        <div
                            v-for="activity in activities"
                            :key="activity.id"
                            class="activity-item"
                        >


                            <div>

                                <span
                                    class="badge"
                                    :class="
                                        getActivityBadge(
                                            activity.action
                                        )
                                    "
                                >

                                    {{
                                        activity.action
                                    }}

                                </span>

                            </div>


                            <div class="activity-description">

                                {{
                                    activity.description
                                }}

                            </div>


                            <div class="activity-ip">

                                {{
                                    activity.ip_address ||
                                    'Unknown IP'
                                }}

                            </div>


                            <div class="activity-date">

                                {{
                                    formatDate(
                                        activity.created_at
                                    )
                                }}

                            </div>

                        </div>

                    </div>

                </div>


            </template>

        </div>

    </div>

</template>


<script>

export default {

    data() {

        return {

            loading: true,

            processing: false,

            error: null,


            security: {

                account_created_at: null,

                last_login_at: null,

                last_login_ip: null,

                password_changed_at: null,

                active_sessions_count: 0,

                total_sessions_count: 0,

                failed_login_attempts: 0,

                failed_login_attempts_30_days: 0,

            },


            sessions: [],

            activities: [],

        };

    },


    mounted() {

        this.loadSecurityData();

    },


    methods: {


        /*
        |--------------------------------------------------------------------------
        | LOAD SECURITY DATA
        |--------------------------------------------------------------------------
        */

        async loadSecurityData() {

            this.loading = true;

            this.error = null;


            try {

                const response = await fetch(
                    '/api/security',
                    {
                        method: 'GET',

                        credentials: 'same-origin',

                        headers: {

                            'Accept':
                                'application/json',

                            'X-Requested-With':
                                'XMLHttpRequest',

                        },

                    }
                );


                if (!response.ok) {

                    if (response.status === 401) {

                        window.authUser = null;

                        this.$router.push('/login');

                        return;

                    }


                    throw new Error(
                        `Unable to load security information. HTTP ${response.status}`
                    );

                }


                const data =
                    await response.json();


                if (!data.success) {

                    throw new Error(
                        data.message ||
                        'Unable to load security information.'
                    );

                }


                this.security = {

                    ...this.security,

                    ...(data.security || {}),

                };


                this.sessions =
                    data.sessions || [];


                this.activities =
                    data.recent_activities || [];


            } catch (error) {

                console.error(
                    'Security dashboard error:',
                    error
                );


                this.error =
                    error.message ||
                    'Unable to load security information.';


            } finally {

                this.loading = false;

            }

        },


        /*
        |--------------------------------------------------------------------------
        | LOGOUT ONE SESSION
        |--------------------------------------------------------------------------
        */

        async logoutSession(sessionId) {

            if (
                !confirm(
                    'Are you sure you want to logout this device?'
                )
            ) {

                return;

            }


            this.processing = true;


            try {

                const response = await fetch(

                    `/api/security/sessions/${sessionId}`,

                    {

                        method: 'DELETE',

                        credentials: 'same-origin',

                        headers: {

                            'Accept':
                                'application/json',

                            'X-Requested-With':
                                'XMLHttpRequest',

                            'X-CSRF-TOKEN':
                                this.getCsrfToken(),

                        },

                    }

                );


                const data =
                    await this.getJsonResponse(
                        response
                    );


                if (
                    !response.ok ||
                    !data.success
                ) {

                    throw new Error(

                        data.message ||
                        'Unable to logout this session.'

                    );

                }


                this.showToast(

                    data.message ||
                    'Device logged out successfully.',

                    'success'

                );


                await this.loadSecurityData();


            } catch (error) {

                console.error(error);


                this.showToast(

                    error.message ||
                    'Unable to logout device.',

                    'error'

                );


            } finally {

                this.processing = false;

            }

        },


        /*
        |--------------------------------------------------------------------------
        | LOGOUT OTHER SESSIONS
        |--------------------------------------------------------------------------
        */

        async logoutOtherSessions() {

            if (
                !confirm(
                    'Are you sure you want to logout all other devices?'
                )
            ) {

                return;

            }


            this.processing = true;


            try {

                const response = await fetch(

                    '/api/security/sessions',

                    {

                        method: 'DELETE',

                        credentials: 'same-origin',

                        headers: {

                            'Accept':
                                'application/json',

                            'X-Requested-With':
                                'XMLHttpRequest',

                            'X-CSRF-TOKEN':
                                this.getCsrfToken(),

                        },

                    }

                );


                const data =
                    await this.getJsonResponse(
                        response
                    );


                if (
                    !response.ok ||
                    !data.success
                ) {

                    throw new Error(

                        data.message ||
                        'Unable to logout other devices.'

                    );

                }


                this.showToast(

                    data.message ||
                    'All other devices have been logged out.',

                    'success'

                );


                await this.loadSecurityData();


            } catch (error) {

                console.error(error);


                this.showToast(

                    error.message ||
                    'Unable to logout other devices.',

                    'error'

                );


            } finally {

                this.processing = false;

            }

        },


        /*
        |--------------------------------------------------------------------------
        | JSON RESPONSE HELPER
        |--------------------------------------------------------------------------
        */

        async getJsonResponse(response) {

            const text =
                await response.text();


            try {

                return text
                    ? JSON.parse(text)
                    : {};

            } catch (error) {

                return {

                    success: false,

                    message:
                        `Server returned HTTP ${response.status}`,

                };

            }

        },


        /*
        |--------------------------------------------------------------------------
        | CSRF TOKEN
        |--------------------------------------------------------------------------
        */

        getCsrfToken() {

            const element =
                document.querySelector(
                    'meta[name="csrf-token"]'
                );


            if (!element) {

                console.error(
                    'CSRF meta tag not found.'
                );

                return '';

            }


            return element.getAttribute(
                'content'
            );

        },


        /*
        |--------------------------------------------------------------------------
        | DATE FORMAT
        |--------------------------------------------------------------------------
        */

        formatDate(date) {

            if (!date) {

                return 'Never';

            }


            const parsedDate =
                new Date(date);


            if (
                isNaN(
                    parsedDate.getTime()
                )
            ) {

                return date;

            }


            return parsedDate.toLocaleString();

        },


        /*
        |--------------------------------------------------------------------------
        | DEVICE ICON
        |--------------------------------------------------------------------------
        */

        getDeviceIcon(device) {

            if (!device) {

                return '💻';

            }


            const value =
                device.toLowerCase();


            if (

                value.includes('mobile') ||
                value.includes('android') ||
                value.includes('iphone') ||
                value.includes('ios')

            ) {

                return '📱';

            }


            if (

                value.includes('tablet') ||
                value.includes('ipad')

            ) {

                return '📲';

            }


            return '💻';

        },


        /*
        |--------------------------------------------------------------------------
        | ACTIVITY BADGE
        |--------------------------------------------------------------------------
        */

        getActivityBadge(action) {

            switch (
                (action || '').toLowerCase()
            ) {

                case 'login':
                    return 'bg-success';

                case 'logout':
                    return 'bg-secondary';

                case 'register':
                    return 'bg-primary';

                case 'password_change':
                case 'password_changed':
                    return 'bg-warning text-dark';

                case 'failed_login':
                    return 'bg-danger';

                case 'session_revoked':
                case 'sessions_revoked':
                    return 'bg-danger';

                case 'profile_update':
                    return 'bg-info';

                case 'avatar_upload':
                    return 'bg-info';

                case 'avatar_delete':
                    return 'bg-secondary';

                case 'forgot_password':
                    return 'bg-warning text-dark';

                default:
                    return 'bg-info';

            }

        },


        /*
        |--------------------------------------------------------------------------
        | TOAST
        |--------------------------------------------------------------------------
        */

        showToast(
            message,
            type = 'success'
        ) {

            if (

                this.$root &&
                typeof this.$root.showToast ===
                    'function'

            ) {

                this.$root.showToast(
                    message,
                    type
                );

            } else {

                alert(message);

            }

        },

    },

};

</script>


<style scoped>

.security-page {
    min-height: calc(100vh - 80px);
}


.security-card {

    border: none;

    border-radius: 12px;

    box-shadow:
        0 4px 15px
        rgba(0, 0, 0, 0.08);

}


.security-icon {

    font-size: 28px;

    margin-bottom: 10px;

}


.session-item {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    padding: 20px;

    border-bottom:
        1px solid #eeeeee;

}


.session-item:last-child {

    border-bottom: none;

}


.session-info {

    display: flex;

    align-items: flex-start;

    gap: 15px;

}


.device-icon {

    width: 45px;

    height: 45px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 28px;

    background: #f5f7fa;

    border-radius: 10px;

}


.session-details {

    color: #6c757d;

    font-size: 14px;

    line-height: 1.8;

}


.activity-item {

    display: grid;

    grid-template-columns:
        140px
        1fr
        150px
        200px;

    gap: 15px;

    align-items: center;

    padding: 15px 20px;

    border-bottom:
        1px solid #eeeeee;

}


.activity-item:last-child {

    border-bottom: none;

}


.activity-description {

    font-weight: 500;

}


.activity-ip,
.activity-date {

    color: #6c757d;

    font-size: 14px;

}


@media (max-width: 768px) {

    .session-item {

        flex-direction: column;

        align-items: flex-start;

    }


    .activity-item {

        grid-template-columns: 1fr;

        gap: 5px;

    }

}

</style>