<template>
  <div>
    <h1>Dashboard</h1>
    <!-- Display a welcome message if the user is logged in. -->
    <p v-if="authStore.user">
      Welcome back, {{ authStore.user.name }}! You are successfully logged in.
    </p>
    <p>This is a protected page.</p>
  </div>
</template>

<script setup>
// Az importált Pinia store biztosítja az auth adatok (név, token) elérését.
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
</script>
