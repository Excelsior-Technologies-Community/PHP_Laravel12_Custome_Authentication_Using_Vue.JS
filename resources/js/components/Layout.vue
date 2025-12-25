<template>
  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4">
    <a class="navbar-brand" href="/">CustomerApp</a>

    <div class="ms-auto">
      <!-- Guest links -->
      <a v-if="path !== '/login'" href="/login" class="btn btn-outline-light me-2">
        Login
      </a>
      <a v-if="path !== '/register'" href="/register" class="btn btn-outline-light">
        Register
      </a>

      <!-- Logout (only dashboard) -->
      <form
        v-if="path === '/dashboard'"
        method="POST"
        action="/logout"
        class="d-inline"
      >
        <input type="hidden" name="_token" :value="csrf" />
        <button class="btn btn-danger ms-3">Logout</button>
      </form>
    </div>
  </nav>

  <!-- PAGE CONTENT -->
  <div class="container mt-5">
    <!-- 🔥 Yahin Login / Register / Dashboard inject hoga -->
    <slot></slot>
  </div>
</template>

<script>
export default {
  props: ['path'],
  data() {
    return {
      csrf: document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute('content'),
    };
  },
};
</script>
