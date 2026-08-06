<template>
  <Layout :path="path">
    <div class="auth-page">
      <div class="auth-card">
        <div class="auth-header">
          <h2 class="auth-title">Forgot Password?</h2>
          <p class="auth-subtitle">No worries, we'll send you reset instructions.</p>
        </div>

        <div v-if="success" class="alert alert-success">
          <i class="bi bi-check-circle-fill"></i> {{ message }}
        </div>

        <form v-else @submit.prevent="submit">
          <div class="mb-3">
            <label class="form-label">Email</label>
            <div class="input-group">
              <span class="input-group-text">
                <i class="bi bi-envelope"></i>
              </span>
              <input
                v-model="email"
                type="email"
                class="form-control"
                :class="{ 'is-invalid': errors.email }"
                placeholder="you@example.com"
                required
              />
            </div>
            <div v-if="errors.email" class="invalid-feedback d-block">
              {{ errors.email[0] }}
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-100" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
            Send Reset Link
          </button>
        </form>

        <div class="auth-footer">
          <router-link to="/login">
            <i class="bi bi-arrow-left"></i> Back to Login
          </router-link>
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
      email: '',
      loading: false,
      success: false,
      message: '',
      errors: {},
    };
  },
  methods: {
    async submit() {
      this.loading = true;
      this.errors = {};
      try {
        const res = await fetch('/forgot-password', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify({ email: this.email }),
        });
        const data = await res.json();
        if (data.success) {
          this.success = true;
          this.message = data.message;
        } else {
          this.errors = data.errors || {};
          this.$root.showToast(data.message || 'Failed', 'error');
        }
      } catch (e) {
        this.$root.showToast('Something went wrong', 'error');
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
}
.auth-card {
  background: #ffffff !important;
  border-radius: 16px;
  padding: 2.5rem;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  color: #1a1a2e !important;
}
.auth-header {
  text-align: center;
  margin-bottom: 2rem;
}
.auth-title {
  font-size: 1.75rem;
  font-weight: 700;
  margin: 0;
  color: #1a1a2e !important;
}
.auth-subtitle {
  color: #6c757d !important;
  margin: 0.5rem 0 0;
}
.form-label {
  color: #1a1a2e !important;
}
.input-group-text {
  background-color: #f8f9fa;
  border-right: none;
}
.form-control {
  border-left: none;
  background-color: #ffffff !important;
  color: #1a1a2e !important;
}
.form-control:focus {
  box-shadow: none;
  border-color: #ced4da;
  background-color: #ffffff !important;
  color: #1a1a2e !important;
}
.input-group:focus-within .input-group-text {
  border-color: #86b7fe;
}
.auth-footer {
  text-align: center;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e9ecef;
}
.auth-footer a {
  color: #f5576c;
  text-decoration: none;
  font-weight: 600;
}
.auth-footer a:hover {
  color: #f093fb;
}
.alert {
  color: #1a1a2e !important;
}
</style>
