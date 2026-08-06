<template>
  <Layout :path="path">
    <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="card shadow">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0">User Profile</h5>
          </div>
          <div class="card-body">
            <div v-if="loading" class="text-center py-5">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>

            <div v-else-if="user">
              <div class="text-center mb-4">
                <div class="position-relative d-inline-block">
                  <img
                    v-if="avatarPreview || user.avatar"
                    :src="avatarPreview || avatarUrl"
                    class="rounded-circle border"
                    width="120"
                    height="120"
                    alt="Profile"
                    @error="handleImageError"
                  />
                  <div
                    v-else
                    class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white mx-auto"
                    style="width: 120px; height: 120px; font-size: 48px;"
                  >
                    {{ user.name.charAt(0).toUpperCase() }}
                  </div>
                  <button
                    @click="triggerFileInput"
                    class="btn btn-sm btn-primary position-absolute bottom-0 end-0 rounded-circle"
                    style="width: 36px; height: 36px;"
                  >
                    <i class="bi bi-camera"></i>
                  </button>
                </div>
                <input
                  ref="fileInput"
                  type="file"
                  accept="image/*"
                  class="d-none"
                  @change="handleAvatarUpload"
                />
                <div v-if="uploadError" class="text-danger small mt-2">
                  {{ uploadError }}
                </div>
              </div>

              <form @submit.prevent="updateProfile">
                <div class="mb-3">
                  <label class="form-label">Name</label>
                  <input
                    v-model="form.name"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': errors.name }"
                  />
                  <div v-if="errors.name" class="invalid-feedback d-block">
                    {{ errors.name[0] }}
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Email</label>
                  <input
                    v-model="form.email"
                    type="email"
                    class="form-control"
                    :class="{ 'is-invalid': errors.email }"
                  />
                  <div v-if="errors.email" class="invalid-feedback d-block">
                    {{ errors.email[0] }}
                  </div>
                </div>

                <button type="submit" class="btn btn-primary" :disabled="saving">
                  <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                  Update Profile
                </button>
              </form>

              <hr class="my-4" />

              <h5>Change Password</h5>
              <form @submit.prevent="updatePassword">
                <div class="mb-3">
                  <label class="form-label">Current Password</label>
                  <input
                    v-model="passwordForm.current_password"
                    type="password"
                    class="form-control"
                    :class="{ 'is-invalid': passwordErrors.current_password }"
                  />
                  <div v-if="passwordErrors.current_password" class="invalid-feedback d-block">
                    {{ passwordErrors.current_password[0] }}
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label">New Password</label>
                  <input
                    v-model="passwordForm.password"
                    type="password"
                    class="form-control"
                    :class="{ 'is-invalid': passwordErrors.password }"
                  />
                  <div v-if="passwordErrors.password" class="invalid-feedback d-block">
                    {{ passwordErrors.password[0] }}
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Confirm New Password</label>
                  <input
                    v-model="passwordForm.password_confirmation"
                    type="password"
                    class="form-control"
                  />
                </div>

                <button type="submit" class="btn btn-warning" :disabled="passwordSaving">
                  <span v-if="passwordSaving" class="spinner-border spinner-border-sm me-2"></span>
                  Change Password
                </button>
              </form>
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
      saving: false,
      passwordSaving: false,
      user: null,
      form: { name: '', email: '' },
      passwordForm: { current_password: '', password: '', password_confirmation: '' },
      errors: {},
      passwordErrors: {},
      avatarPreview: null,
      uploadError: '',
    };
  },
  computed: {
    avatarUrl() {
      if (!this.user?.avatar) return '';
      const timestamp = new Date().getTime();
      return '/avatars/' + this.user.avatar + '?t=' + timestamp;
    },
  },
  mounted() {
    this.fetchProfile();
  },
  methods: {
    async fetchProfile() {
      try {
        const res = await fetch('/api/profile', {
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json();
        if (data.success) {
          this.user = data.user;
          this.form.name = data.user.name;
          this.form.email = data.user.email;
        }
      } catch (e) {
        this.$root.showToast('Failed to load profile', 'error');
      } finally {
        this.loading = false;
      }
    },
    async updateProfile() {
      this.saving = true;
      this.errors = {};
      try {
        const res = await fetch('/api/profile', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify(this.form),
        });
        const data = await res.json();
        if (data.success) {
          this.user = data.user;
          window.authUser = data.user;
          this.$root.showToast(data.message, 'success');
        } else {
          this.errors = data.errors || {};
          this.$root.showToast('Please fix the errors', 'error');
        }
      } catch (e) {
        this.$root.showToast('Failed to update profile', 'error');
      } finally {
        this.saving = false;
      }
    },
    async updatePassword() {
      this.passwordSaving = true;
      this.passwordErrors = {};
      try {
        const res = await fetch('/api/password/change', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify(this.passwordForm),
        });
        const data = await res.json();
        if (data.success) {
          this.passwordForm = { current_password: '', password: '', password_confirmation: '' };
          this.$root.showToast(data.message, 'success');
        } else {
          this.passwordErrors = data.errors || {};
          this.$root.showToast(data.message || 'Please fix the errors', 'error');
        }
      } catch (e) {
        this.$root.showToast('Failed to change password', 'error');
      } finally {
        this.passwordSaving = false;
      }
    },
    triggerFileInput() {
      this.$refs.fileInput.click();
    },
    async handleAvatarUpload(event) {
      const file = event.target.files[0];
      if (!file) return;

      this.uploadError = '';
      this.avatarPreview = null;

      const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/avif', 'image/bmp', 'image/tiff'];
      if (!validTypes.includes(file.type)) {
        this.uploadError = 'Invalid file type. Please upload an image.';
        return;
      }

      if (file.size > 5 * 1024 * 1024) {
        this.uploadError = 'File size must be less than 5MB.';
        return;
      }

      const reader = new FileReader();
      reader.onload = (e) => {
        this.avatarPreview = e.target.result;
      };
      reader.readAsDataURL(file);

      const formData = new FormData();
      formData.append('avatar', file);

      try {
        const res = await fetch('/api/avatar/upload', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: formData,
        });
        const data = await res.json();
        if (data.success) {
          this.user.avatar = data.avatar;
          window.authUser.avatar = data.avatar;
          this.avatarPreview = data.avatar_url || null;
          this.$refs.fileInput.value = '';
          this.$root.showToast(data.message, 'success');
        } else {
          this.avatarPreview = null;
          this.uploadError = data.message || 'Failed to upload avatar';
          this.$root.showToast(data.message || 'Failed to upload avatar', 'error');
        }
      } catch (e) {
        this.avatarPreview = null;
        this.uploadError = 'Something went wrong';
        this.$root.showToast('Failed to upload avatar', 'error');
      }
    },
    handleImageError() {
      this.avatarPreview = null;
    },
  },
};
</script>
