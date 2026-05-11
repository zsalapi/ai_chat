<template>
  <div class="pagination">
    <button
      v-for="(link, index) in links"
      :key="index"
      :disabled="!link.url || link.active"
      :class="{ active: link.active }"
      @click="changePage(link.url)"
      v-html="link.label"
    ></button>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

// Wait for pagination links from the Laravel paginator.
const props = defineProps({
  links: {
    type: Array,
    required: true,
    default: () => []
  }
});

// Emit event when user clicks a new page
const emit = defineEmits(['change-page']);

function changePage(url) {
  if (url) {
    emit('change-page', url); // Tell the parent component to load a new page.
  }
}
</script>

<style scoped>
.pagination {
  display: flex;
  gap: 5px;
  margin-top: 20px;
}
.pagination button {
  padding: 5px 10px;
  border: 1px solid #ddd;
  background-color: white;
  cursor: pointer;
}
.pagination button.active {
  background-color: #007bff;
  color: white;
  border-color: #007bff;
}
.pagination button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
