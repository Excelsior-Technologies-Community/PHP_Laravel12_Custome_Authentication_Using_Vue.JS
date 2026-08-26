<template>
  <Layout :path="path">

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div
        class="spinner-border text-primary"
        role="status"
      >
        <span class="visually-hidden">
          Loading...
        </span>
      </div>
    </div>

    <div v-else>

      <!-- Profile -->
      <div
        v-if="user"
        class="row mb-4"
      >

        <div class="col-md-4 mb-4">
          <div class="card shadow h-100">
            <div class="card-body text-center">

              <div class="position-relative d-inline-block mb-3">

                <img
                  v-if="user.avatar"
                  :src="avatarUrl"
                  class="rounded-circle border"
                  width="120"
                  height="120"
                  alt="Profile"
                  @error="handleImageError"
                />

                <div
                  v-else
                  class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white mx-auto"
                  style="
                    width: 120px;
                    height: 120px;
                    font-size: 48px;
                  "
                >
                  {{ user.name.charAt(0).toUpperCase() }}
                </div>

              </div>

              <h4>
                {{ user.name }}
              </h4>

              <p class="text-muted mb-3">
                {{ user.email }}
              </p>

              <router-link
                to="/profile"
                class="btn btn-primary"
              >
                <i class="bi bi-person-gear me-1"></i>
                Edit Profile
              </router-link>

            </div>
          </div>
        </div>

        <!-- Security Summary -->
        <div class="col-md-8">

          <div class="row g-3">

            <!-- Active Sessions -->
            <div class="col-md-6">
              <div class="card shadow h-100">
                <div class="card-body">

                  <div class="d-flex justify-content-between">

                    <div>
                      <p class="text-muted mb-1">
                        Active Sessions
                      </p>

                      <h2 class="mb-0">
                        {{ security.active_sessions_count }}
                      </h2>
                    </div>

                    <div class="security-icon bg-primary">
                      <i class="bi bi-laptop"></i>
                    </div>

                  </div>

                </div>
              </div>
            </div>

            <!-- Failed Login -->
            <div class="col-md-6">
              <div class="card shadow h-100">
                <div class="card-body">

                  <div class="d-flex justify-content-between">

                    <div>
                      <p class="text-muted mb-1">
                        Failed Logins
                      </p>

                      <h2 class="mb-0">
                        {{ security.failed_login_attempts_30_days }}
                      </h2>

                      <small class="text-muted">
                        Last 30 days
                      </small>
                    </div>

                    <div class="security-icon bg-danger">
                      <i class="bi bi-shield-exclamation"></i>
                    </div>

                  </div>

                </div>
              </div>
            </div>

            <!-- Last Login -->
            <div class="col-md-6">
              <div class="card shadow h-100">
                <div class="card-body">

                  <p class="text-muted mb-1">
                    Last Login
                  </p>

                  <h6 class="mb-1">
                    {{ formatDate(security.last_login_at) }}
                  </h6>

                  <small class="text-muted">
                    IP:
                    {{ security.last_login_ip || 'N/A' }}
                  </small>

                </div>
              </div>
            </div>

            <!-- Password Changed -->
            <div class="col-md-6">
              <div class="card shadow h-100">
                <div class="card-body">

                  <p class="text-muted mb-1">
                    Password Last Changed
                  </p>

                  <h6 class="mb-0">
                    {{ formatDate(security.password_changed_at) }}
                  </h6>

                </div>
              </div>
            </div>

          </div>

        </div>

      </div>

      <!-- Security Header -->
      <div class="card shadow mb-4">

        <div class="card-header bg-primary text-white">

          <div class="d-flex justify-content-between align-items-center">

            <div>
              <h5 class="mb-0">
                <i class="bi bi-shield-lock me-2"></i>
                Account Security
              </h5>

              <small>
                Manage your active devices and account security
              </small>
            </div>

            <button
              @click="revokeOtherSessions"
              class="btn btn-light btn-sm"
              :disabled="revokingAll || security.active_sessions_count <= 1"
            >
              <span
                v-if="revokingAll"
                class="spinner-border spinner-border-sm me-1"
              ></span>

              <i
                v-else
                class="bi bi-box-arrow-right me-1"
              ></i>

              Logout Other Devices
            </button>

          </div>

        </div>

        <div class="card-body">

          <!-- Sessions -->
          <div class="mb-4">

            <h6 class="fw-bold mb-3">
              <i class="bi bi-pc-display-horizontal me-2"></i>
              Active Devices
            </h6>

            <div
              v-if="sessions.length === 0"
              class="text-center text-muted py-4"
            >
              No active sessions found.
            </div>

            <div
              v-for="session in sessions"
              :key="session.id"
              class="session-card mb-3"
              :class="{
                'current-session': session.is_current
              }"
            >

              <div class="row align-items-center">

                <div class="col-md-1 text-center mb-3 mb-md-0">

                  <div class="device-icon">

                    <i
                      :class="getDeviceIcon(session.platform)"
                    ></i>

                  </div>

                </div>

                <div class="col-md-7">

                  <div
                    class="d-flex align-items-center flex-wrap gap-2"
                  >

                    <h6 class="mb-0">
                      {{ session.device_name }}
                    </h6>

                    <span
                      v-if="session.is_current"
                      class="badge bg-success"
                    >
                      Current Device
                    </span>

                    <span
                      v-if="session.is_active"
                      class="badge bg-primary"
                    >
                      Active
                    </span>

                  </div>

                  <div class="mt-2 small text-muted">

                    <div>
                      <i class="bi bi-browser-chrome me-1"></i>
                      {{ session.browser }}
                    </div>

                    <div>
                      <i class="bi bi-globe me-1"></i>
                      IP: {{ session.ip_address || 'N/A' }}
                    </div>

                    <div>
                      <i class="bi bi-clock me-1"></i>
                      Login:
                      {{ formatDate(session.login_at) }}
                    </div>

                    <div>
                      <i class="bi bi-activity me-1"></i>
                      Last activity:
                      {{ formatDate(session.last_activity_at) }}
                    </div>

                  </div>

                </div>

                <div class="col-md-4 text-md-end mt-3 mt-md-0">

                  <button
                    v-if="!session.is_current"
                    @click="revokeSession(session)"
                    class="btn btn-outline-danger btn-sm"
                    :disabled="revokingId === session.id"
                  >

                    <span
                      v-if="revokingId === session.id"
                      class="spinner-border spinner-border-sm me-1"
                    ></span>

                    <i
                      v-else
                      class="bi bi-box-arrow-right me-1"
                    ></i>

                    Logout Device

                  </button>

                  <span
                    v-else
                    class="text-success small fw-semibold"
                  >
                    <i class="bi bi-check-circle me-1"></i>
                    This device
                  </span>

                </div>

              </div>

            </div>

          </div>

          <hr />

          <!-- Account Information -->
          <div>

            <h6 class="fw-bold mb-3">
              <i class="bi bi-info-circle me-2"></i>
              Account Security Information
            </h6>

            <div class="row">

              <div class="col-md-6 mb-3">

                <div class="security-info">

                  <span>
                    <i class="bi bi-calendar-check me-2"></i>
                    Account Created
                  </span>

                  <strong>
                    {{ formatDate(security.account_created_at) }}
                  </strong>

                </div>

              </div>

              <div class="col-md-6 mb-3">

                <div class="security-info">

                  <span>
                    <i class="bi bi-key me-2"></i>
                    Password Changed
                  </span>

                  <strong>
                    {{ formatDate(security.password_changed_at) }}
                  </strong>

                </div>

              </div>

              <div class="col-md-6 mb-3">

                <div class="security-info">

                  <span>
                    <i class="bi bi-shield-exclamation me-2"></i>
                    Failed Login Attempts
                  </span>

                  <strong>
                    {{ security.failed_login_attempts }}
                  </strong>

                </div>

              </div>

              <div class="col-md-6 mb-3">

                <div class="security-info">

                  <span>
                    <i class="bi bi-laptop me-2"></i>
                    Total Sessions
                  </span>

                  <strong>
                    {{ security.total_sessions_count }}
                  </strong>

                </div>

              </div>

            </div>

          </div>

        </div>

      </div>

      <!-- Recent Security Activity -->
      <div class="card shadow mb-4">

        <div class="card-header bg-dark text-white">

          <h5 class="mb-0">
            <i class="bi bi-clock-history me-2"></i>
            Recent Security Activity
          </h5>

        </div>

        <div class="card-body p-0">

          <div
            v-if="recentActivities.length === 0"
            class="text-center text-muted py-4"
          >
            No security activity found.
          </div>

          <div
            v-else
            class="table-responsive"
          >

            <table class="table table-hover mb-0">

              <thead>

                <tr>
                  <th>Action</th>
                  <th>Description</th>
                  <th>IP Address</th>
                  <th>Date</th>
                </tr>

              </thead>

              <tbody>

                <tr
                  v-for="activity in recentActivities"
                  :key="activity.id"
                >

                  <td>
                    <span
                      class="badge"
                      :class="getActivityBadge(activity.action)"
                    >
                      {{ formatAction(activity.action) }}
                    </span>
                  </td>

                  <td>
                    {{ activity.description }}
                  </td>

                  <td>
                    {{ activity.ip_address || 'N/A' }}
                  </td>

                  <td>
                    {{ formatDate(activity.created_at) }}
                  </td>

                </tr>

              </tbody>

            </table>

          </div>

        </div>

      </div>

      <!-- Existing Activity Logs -->
      <div class="card shadow mb-4">

        <div class="card-header bg-primary text-white">

          <h5 class="mb-0">
            <i class="bi bi-journal-text me-2"></i>
            Activity Logs
          </h5>

        </div>

        <div class="card-body">

          <div
            v-if="logs.loading"
            class="text-center py-4"
          >

            <div
              class="spinner-border text-primary"
              role="status"
            ></div>

          </div>

          <div
            v-else-if="logs.data.length > 0"
            class="table-responsive"
          >

            <table class="table table-hover">

              <thead>

                <tr>
                  <th>Action</th>
                  <th>Description</th>
                  <th>IP Address</th>
                  <th>Date</th>
                </tr>

              </thead>

              <tbody>

                <tr
                  v-for="log in logs.data"
                  :key="log.id"
                >

                  <td>

                    <span class="badge bg-primary">
                      {{ log.action }}
                    </span>

                  </td>

                  <td>
                    {{ log.description }}
                  </td>

                  <td>
                    {{ log.ip_address }}
                  </td>

                  <td>
                    {{ formatDate(log.created_at) }}
                  </td>

                </tr>

              </tbody>

            </table>

          </div>

          <div
            v-else
            class="text-center text-muted py-4"
          >

            <i class="bi bi-journal-x display-4"></i>

            <p class="mt-2">
              No activity logs yet
            </p>

          </div>

        </div>

      </div>

    </div>

  </Layout>
</template>

<script>
import Layout from './Layout.vue';

export default {
  components: {
    Layout,
  },

  data() {
    return {
      path: window.location.pathname,

      loading: true,

      user: null,

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

      recentActivities: [],

      logs: {
        data: [],
        loading: true,
      },

      revokingId: null,

      revokingAll: false,

      avatarError: false,
    };
  },

  computed: {
    avatarUrl() {
      if (!this.user?.avatar || this.avatarError) {
        return '';
      }

      return (
        '/avatars/' +
        this.user.avatar +
        '?t=' +
        new Date().getTime()
      );
    },
  },

  mounted() {
    this.fetchUser();
    this.fetchSecurity();
    this.fetchLogs();
  },

  methods: {

    // =========================================================
    // FETCH USER
    // =========================================================

    async fetchUser() {
      try {

        const res = await this.$root.apiFetch(
          '/api/profile',
          {
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
            },
          }
        );

        // Session expired / revoked
        if (res.status === 401) {
          return;
        }

        const data = await res.json();

        if (data.success) {
          this.user = data.user;

          window.authUser = data.user;
        }

      } catch (e) {

        this.$root.showToast(
          'Failed to load user data',
          'error'
        );

      } finally {

        this.loading = false;

      }
    },


    // =========================================================
    // FETCH SECURITY INFORMATION
    // =========================================================

    async fetchSecurity() {
      try {

        const res = await this.$root.apiFetch(
          '/api/security',
          {
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
            },
          }
        );

        // Session expired / revoked
        if (res.status === 401) {
          return;
        }

        const data = await res.json();

        if (data.success) {

          this.security =
            data.security;

          this.sessions =
            data.sessions || [];

          this.recentActivities =
            data.recent_activities || [];
        }

      } catch (e) {

        this.$root.showToast(
          'Failed to load security information',
          'error'
        );

      }
    },


    // =========================================================
    // FETCH ACTIVITY LOGS
    // =========================================================

    async fetchLogs() {

      try {

        const res = await this.$root.apiFetch(
          '/api/activity-logs',
          {
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
            },
          }
        );

        // Session expired / revoked
        if (res.status === 401) {
          return;
        }

        const data = await res.json();

        if (data.success) {

          this.logs.data =
            data.logs.data || [];

        }

      } catch (e) {

        this.$root.showToast(
          'Failed to load activity logs',
          'error'
        );

      } finally {

        this.logs.loading = false;

      }
    },


    // =========================================================
    // REVOKE SINGLE SESSION
    // =========================================================

    async revokeSession(session) {

      if (
        !confirm(
          `Logout ${session.device_name} from this account?`
        )
      ) {
        return;
      }


      this.revokingId =
        session.id;


      try {

        const res =
          await this.$root.apiFetch(
            `/api/security/sessions/${session.id}`,
            {
              method: 'DELETE',

              headers: {
                'X-CSRF-TOKEN':
                  this.$root.getCsrfToken(),

                'X-Requested-With':
                  'XMLHttpRequest',
              },
            }
          );


        // Session expired / revoked
        if (res.status === 401) {
          return;
        }


        const data =
          await res.json();


        if (data.success) {

          this.sessions =
            this.sessions.filter(
              item =>
                item.id !== session.id
            );


          await this.fetchSecurity();


          this.$root.showToast(
            data.message,
            'success'
          );

        } else {

          this.$root.showToast(
            data.message ||
            'Failed to revoke session',
            'error'
          );

        }

      } catch (e) {

        console.error(
          'Revoke session error:',
          e
        );

        this.$root.showToast(
          'Failed to revoke session',
          'error'
        );

      } finally {

        this.revokingId =
          null;

      }
    },


    // =========================================================
    // REVOKE ALL OTHER SESSIONS
    // =========================================================

    async revokeOtherSessions() {

      if (
        !confirm(
          'Logout from all other devices?'
        )
      ) {
        return;
      }


      this.revokingAll =
        true;


      try {

        const res =
          await this.$root.apiFetch(
            '/api/security/sessions',
            {
              method: 'DELETE',

              headers: {
                'X-CSRF-TOKEN':
                  this.$root.getCsrfToken(),

                'X-Requested-With':
                  'XMLHttpRequest',
              },
            }
          );


        // Session expired / revoked
        if (res.status === 401) {
          return;
        }


        const data =
          await res.json();


        if (data.success) {

          await this.fetchSecurity();


          this.$root.showToast(
            data.message,
            'success'
          );

        } else {

          this.$root.showToast(
            data.message ||
            'Failed to logout other devices',
            'error'
          );

        }

      } catch (e) {

        console.error(
          'Revoke all sessions error:',
          e
        );

        this.$root.showToast(
          'Failed to logout other devices',
          'error'
        );

      } finally {

        this.revokingAll =
          false;

      }
    },


    // =========================================================
    // DEVICE ICON
    // =========================================================

    getDeviceIcon(platform) {

      switch (platform) {

        case 'Windows':
          return 'bi bi-windows';

        case 'macOS':
          return 'bi bi-apple';

        case 'Android':
          return 'bi bi-android2';

        case 'iOS':
          return 'bi bi-phone';

        case 'Linux':
          return 'bi bi-terminal';

        default:
          return 'bi bi-device-ssd';

      }
    },


    // =========================================================
    // ACTIVITY BADGE
    // =========================================================

    getActivityBadge(action) {

      switch (action) {

        case 'login':
          return 'bg-success';

        case 'failed_login':
          return 'bg-danger';

        case 'logout':
          return 'bg-secondary';

        case 'password_change':
          return 'bg-warning text-dark';

        case 'session_revoked':
          return 'bg-danger';

        case 'sessions_revoked':
          return 'bg-danger';

        case 'profile_update':
          return 'bg-info text-dark';

        default:
          return 'bg-primary';

      }
    },


    // =========================================================
    // FORMAT ACTION
    // =========================================================

    formatAction(action) {

      return action
        .replaceAll('_', ' ')
        .replace(
          /\b\w/g,
          letter =>
            letter.toUpperCase()
        );
    },


    // =========================================================
    // FORMAT DATE
    // =========================================================

    formatDate(date) {

      if (!date) {
        return 'Never';
      }

      return new Date(date)
        .toLocaleString();
    },


    // =========================================================
    // AVATAR ERROR
    // =========================================================

    handleImageError() {

      this.avatarError = true;

    },

  },
};
</script>

<style scoped>

.security-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;

  display: flex;
  align-items: center;
  justify-content: center;

  color: white;

  font-size: 22px;
}

.session-card {
  border: 1px solid #dee2e6;
  border-radius: 12px;
  padding: 18px;

  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease;
}

.session-card:hover {
  transform: translateY(-2px);

  box-shadow:
    0 8px 25px rgba(0, 0, 0, 0.08);
}

.session-card.current-session {
  border-color: #198754;
  background-color: #f8fff9;
}

.device-icon {
  width: 52px;
  height: 52px;

  border-radius: 12px;

  background: #f1f3f5;

  display: flex;
  align-items: center;
  justify-content: center;

  font-size: 25px;

  color: #0d6efd;
}

.security-info {
  display: flex;

  justify-content: space-between;

  align-items: center;

  border: 1px solid #e9ecef;

  border-radius: 10px;

  padding: 14px 16px;
}

.security-info span {
  color: #6c757d;
}

.security-info strong {
  color: #212529;
}

@media (max-width: 768px) {

  .security-info {
    flex-direction: column;

    align-items: flex-start;

    gap: 5px;
  }

  .session-card {
    padding: 14px;
  }
}

</style>