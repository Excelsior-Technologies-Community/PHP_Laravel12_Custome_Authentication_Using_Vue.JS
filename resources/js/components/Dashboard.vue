<template>
  <Layout :path="path">
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <div v-else-if="user">
      <div class="row">
        <div class="col-md-4 mb-4">
          <div class="card shadow text-center">
            <div class="card-body">
              <div class="position-relative d-inline-block mb-3">
                <img
                  v-if="avatarPreview || user.avatar"
                  :src="avatarPreview || avatarUrl"
                  class="rounded-circle border"
                  width="150"
                  height="150"
                  alt="Profile"
                  @error="handleImageError"
                />
                <div
                  v-else
                  class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white mx-auto"
                  style="width: 150px; height: 150px; font-size: 64px;"
                >
                  {{ user.name.charAt(0).toUpperCase() }}
                </div>
              </div>
              <h4>{{ user.name }}</h4>
              <p class="text-muted">{{ user.email }}</p>
              <router-link to="/profile" class="btn btn-primary">
                Edit Profile
              </router-link>
            </div>
          </div>
        </div>

        <div class="col-md-8">
          <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">
              <h5 class="mb-0">Activity Logs</h5>
            </div>
            <div class="card-body">
              <div v-if="logs.loading" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
              </div>
              <div v-else-if="logs.data.length > 0">
                <div class="table-responsive">
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
                      <tr v-for="log in logs.data" :key="log.id">
                        <td><span class="badge bg-primary">{{ log.action }}</span></td>
                        <td>{{ log.description }}</td>
                        <td>{{ log.ip_address }}</td>
                        <td>{{ formatDate(log.created_at) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <div v-else class="text-center text-muted py-4">
                <i class="bi bi-journal-x display-4"></i>
                <p class="mt-2">No activity logs yet</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script>
import Layout from './Layout.vue';

export default {
  components: { Layout },
  data() {
    return {
      path: window.location.pathname,
      loading: true,
      user: null,
      logs: { data: [], loading: true },
      avatarPreview: null,
    };
  },
  computed: {
    avatarUrl() {
      if (!this.user?.avatar) return '';
      return '/avatars/' + this.user.avatar;
    },
  },
  mounted() {
    this.fetchUser();
    this.fetchLogs();
  },
  methods: {
    async fetchUser() {
      try {
        const res = await fetch('/api/profile', {
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        if (data.success) {
          this.user = data.user;
          window.authUser = data.user;
        }
      } catch (e) {
        this.$root.showToast('Failed to load user data', 'error');
      } finally {
        this.loading = false;
      }
    },
    async fetchLogs() {
      try {
        const res = await fetch('/api/activity-logs', {
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        if (data.success) {
          this.logs.data = data.logs.data || [];
        }
      } catch (e) {
        this.$root.showToast('Failed to load activity logs', 'error');
      } finally {
        this.logs.loading = false;
      }
    },
    formatDate(date) {
      if (!date) return '';
      return new Date(date).toLocaleString();
    },
    handleImageError() {
      this.avatarPreview = null;
    },
  },
};
</script>
