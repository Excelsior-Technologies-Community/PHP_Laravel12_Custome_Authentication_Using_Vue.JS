<template>
  <Layout :path="path">
    <div class="card shadow text-center">
      <div class="card-body">
        <h2>
          Welcome, <span class="text-primary">{{ user.name }}</span> 🎉
        </h2>

        <p class="text-muted mt-2">
          You are logged in successfully
        </p>

        <form method="POST" action="/logout">
          <input type="hidden" name="_token" :value="csrf" />
          <button class="btn btn-danger mt-3">
            Logout
          </button>
        </form>
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
      csrf: document.querySelector('meta[name="csrf-token"]').content,
      user: window.authUser, // 👈 Laravel se aaya hua user
    };
  },
};
</script>
