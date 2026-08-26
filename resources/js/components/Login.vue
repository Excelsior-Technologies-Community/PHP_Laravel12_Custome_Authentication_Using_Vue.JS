<template>
  <Layout :path="path">
    <div class="auth-page">
      <div class="auth-card">
        <div class="auth-header">
          <h2 class="auth-title">Welcome Back</h2>
          <p class="auth-subtitle">Sign in to your account</p>
        </div>

        <div v-if="error" class="alert alert-danger">
          {{ error }}
        </div>

        <form @submit.prevent="submit">
          <div class="mb-3">
            <label class="form-label">Email</label>
            <div class="input-group">
              <span class="input-group-text">
                <i class="bi bi-envelope"></i>
              </span>
              <input
                v-model="form.email"
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

          <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="input-group">
              <span class="input-group-text">
                <i class="bi bi-lock"></i>
              </span>
              <input
                v-model="form.password"
                type="password"
                class="form-control"
                :class="{ 'is-invalid': errors.password }"
                placeholder="Enter your password"
                required
              />
            </div>
            <div v-if="errors.password" class="invalid-feedback d-block">
              {{ errors.password[0] }}
            </div>
          </div>

          <div class="mb-3 d-flex justify-content-between align-items-center">
            <div class="form-check">
              <input
                v-model="form.remember"
                type="checkbox"
                class="form-check-input"
                id="rememberMe"
              />
              <label class="form-check-label" for="rememberMe">
                Remember Me
              </label>
            </div>
            <router-link to="/forgot-password" class="forgot-link">
              Forgot Password?
            </router-link>
          </div>

          <button type="submit" class="btn btn-primary w-100" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
            {{ loading ? 'Signing in...' : 'Sign In' }}
          </button>
        </form>

        <div class="auth-footer">
          <span>Don't have an account? </span>
          <router-link to="/register">Create Account</router-link>
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
      form: { email: '', password: '', remember: false },
      loading: false,
      error: '',
      errors: {},
    };
  },
  methods: {
async submit() {

    this.loading = true;

    this.error = '';

    this.errors = {};


    try {

        const res =
            await this.$root.apiFetch(
                '/login',
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

                    body:
                        JSON.stringify(
                            this.form
                        ),
                }
            );


        const data =
            await res.json();


        if (data.success) {

            /*
            |--------------------------------------------------------------------------
            | Store Authenticated User
            |--------------------------------------------------------------------------
            */

            window.authUser =
                data.user;


            /*
            |--------------------------------------------------------------------------
            | Reset Session Expiration Flag
            |--------------------------------------------------------------------------
            */

            this.$root.sessionExpired =
                false;


            /*
            |--------------------------------------------------------------------------
            | Success Toast
            |--------------------------------------------------------------------------
            */

            this.$root.showToast(
                data.message,
                'success'
            );


            /*
            |--------------------------------------------------------------------------
            | Redirect
            |--------------------------------------------------------------------------
            */

            await this.$router.push(
                '/dashboard'
            );

        } else {

            if (data.errors) {

                this.errors =
                    data.errors;

            } else {

                this.error =
                    data.message ||
                    'Login failed';

            }


            this.$root.showToast(
                data.message ||
                'Login failed',
                'error'
            );

        }

    } catch (e) {

        console.error(
            'Login error:',
            e
        );


        this.error =
            'Something went wrong';


        this.$root.showToast(
            'Something went wrong',
            'error'
        );

    } finally {

        this.loading =
            false;

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
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
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
.forgot-link {
  font-size: 0.875rem;
  color: #667eea;
  text-decoration: none;
}
.forgot-link:hover {
  color: #764ba2;
}
.auth-footer {
  text-align: center;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e9ecef;
  color: #6c757d !important;
}
.auth-footer a {
  color: #667eea;
  text-decoration: none;
  font-weight: 600;
}
.auth-footer a:hover {
  color: #764ba2;
}
.alert {
  color: #1a1a2e !important;
}
</style>
