<template>
  <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999">
    <div
      v-for="toast in toasts"
      :key="toast.id"
      :class="[
        'toast show',
        toast.type === 'success' ? 'text-bg-success' : 'text-bg-danger',
      ]"
      role="alert"
    >
      <div class="toast-body">
        {{ toast.message }}
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    toasts: Array,
  },
};
</script>

<style scoped>
.toast-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 9999;
}
.toast {
  min-width: 250px;
  margin-bottom: 10px;
  opacity: 1 !important;
}
</style>
