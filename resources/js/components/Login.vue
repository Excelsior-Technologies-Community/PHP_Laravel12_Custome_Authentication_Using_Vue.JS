
<template>
  <Layout :path="path">
    <div class="row justify-content-center">
      <div class="col-md-4">
        <div class="card shadow">
          <div class="card-body">
            <h4 class="text-center mb-3">Login</h4>

            <form method="POST" action="/login">
              <input type="hidden" name="_token" :value="csrf" />

              <input
                class="form-control mb-3"
                name="email"
                placeholder="Email"
                required
              />

              <input
                class="form-control mb-3"
                type="password"
                name="password"
                placeholder="Password"
                required
              />

              <button class="btn btn-primary w-100">
                Login
              </button>
            </form>

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
      csrf: document.querySelector('meta[name="csrf-token"]').content,
    };
  },
};
</script>
