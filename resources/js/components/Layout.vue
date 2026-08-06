<template>
  <div :class="{ 'dark-mode': darkMode && !isAuthPage && !isDashboardPage }">
    <nav class="navbar navbar-expand-lg" :class="darkMode && !isAuthPage && !isDashboardPage ? 'navbar-dark bg-dark' : 'navbar-light bg-light'">
      <div class="container">
        <router-link class="navbar-brand" to="/dashboard">
          CustomerApp
        </router-link>

        <button
          class="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#navbarNav"
          aria-controls="navbarNav"
          aria-expanded="false"
          aria-label="Toggle navigation"
        >
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
          <div class="ms-auto">
            <template v-if="isAuthenticated()">
              <router-link to="/dashboard" class="btn me-2" :class="darkMode && !isAuthPage && !isDashboardPage ? 'btn-outline-light' : 'btn-outline-dark'">
                Dashboard
              </router-link>
              <router-link to="/profile" class="btn me-2" :class="darkMode && !isAuthPage && !isDashboardPage ? 'btn-outline-light' : 'btn-outline-dark'">
                Profile
              </router-link>
              <button @click="logout" class="btn btn-danger">
                Logout
              </button>
            </template>

            <template v-else>
              <router-link to="/login" class="btn me-2" :class="darkMode && !isAuthPage && !isDashboardPage ? 'btn-outline-light' : 'btn-outline-primary'">
                Login
              </router-link>
              <router-link to="/register" class="btn btn-primary">
                Register
              </router-link>
            </template>

            <button v-if="!isAuthPage && !isDashboardPage" @click="toggleDarkMode" class="btn btn-sm ms-2" :class="darkMode ? 'btn-outline-light' : 'btn-outline-dark'">
              {{ darkMode ? '☀️ Light' : '🌙 Dark' }}
            </button>
          </div>
        </div>
      </div>
    </nav>

    <div class="layout-content" :class="{ 'auth-layout': isAuthPage }">
      <slot></slot>
    </div>

    <ToastNotification :toasts="$root.toasts" />
  </div>
</template>

<script>
export default {
  props: ['path'],
  data() {
    return {
      csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      darkMode: localStorage.getItem('darkMode') === 'true',
    };
  },
  computed: {
    isAuthPage() {
      const authPaths = ['/login', '/register', '/forgot-password', '/reset-password'];
      return authPaths.includes(this.path);
    },
    isDashboardPage() {
      return ['/dashboard', '/profile'].includes(this.path);
    },
  },
  mounted() {
    this.applyDarkMode();
  },
  methods: {
    isAuthenticated() {
      return !!window.authUser;
    },
    async logout() {
      try {
        const res = await fetch('/logout', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': this.csrf,
            'X-Requested-With': 'XMLHttpRequest',
          },
        });
        const data = await res.json();
        if (data.success) {
          window.authUser = null;
          this.$root.showToast(data.message, 'success');
          this.$router.push('/login');
        }
      } catch (e) {
        this.$root.showToast('Logout failed', 'error');
      }
    },
    toggleDarkMode() {
      this.darkMode = !this.darkMode;
      localStorage.setItem('darkMode', this.darkMode);
      this.applyDarkMode();
    },
    applyDarkMode() {
      if (this.darkMode && !this.isAuthPage && !this.isDashboardPage) {
        document.body.classList.add('dark-mode');
        document.body.style.backgroundColor = '#0f0f23';
        document.body.style.color = '#e0e0e0';
      } else {
        document.body.classList.remove('dark-mode');
        document.body.style.backgroundColor = '';
        document.body.style.color = '';
      }
    },
  },
};
</script>

<style scoped>
.layout-content {
  min-height: calc(100vh - 80px);
}
.layout-content.auth-layout {
  min-height: 100vh;
  padding: 0;
  margin-top: -80px;
  padding-top: 80px;
}
.dark-mode .card {
  background-color: #1a1a2e !important;
  color: #e0e0e0 !important;
  border-color: #16213e !important;
}
.dark-mode .form-control {
  background-color: #16213e !important;
  border-color: #0f3460 !important;
  color: #e0e0e0 !important;
}
.dark-mode .form-control:focus {
  background-color: #16213e !important;
  color: #e0e0e0 !important;
}
.dark-mode .form-label {
  color: #e0e0e0 !important;
}
.dark-mode .input-group-text {
  background-color: #0f3460 !important;
  border-color: #0f3460 !important;
  color: #e0e0e0 !important;
}
.dark-mode .btn-close {
  filter: invert(1);
}
.dark-mode .text-muted {
  color: #b0b0b0 !important;
}
.dark-mode table {
  color: #e0e0e0 !important;
}
.dark-mode thead {
  background-color: #0f3460 !important;
}
.dark-mode .badge {
  filter: brightness(1.2);
}
</style>
