<template>
  <div class="min-h-[80vh] flex items-center justify-center px-4 pt-12">
    <div class="max-w-md w-full space-y-8 p-8 bg-white rounded-2xl shadow-xl border border-gray-100">
      <div class="text-center">
        <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">Create an Account</h2>
        <p class="mt-2 text-sm text-gray-500 font-medium">Sign up to get started</p>
      </div>

      <form class="mt-8 space-y-5" @submit.prevent="handleRegister">
        <div>
          <label for="name" class="block text-sm font-semibold text-gray-700">Full Name</label>
          <input
            type="text"
            id="name"
            v-model="form.name"
            required
            class="mt-2 block w-full rounded-xl border border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:ring-opacity-50 sm:text-sm py-3 px-4 transition-colors bg-gray-50 focus:bg-white"
            placeholder="John Doe"
          />
        </div>

        <div>
          <label for="email" class="block text-sm font-semibold text-gray-700">Email Address</label>
          <input
            type="email"
            id="email"
            v-model="form.email"
            required
            class="mt-2 block w-full rounded-xl border border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:ring-opacity-50 sm:text-sm py-3 px-4 transition-colors bg-gray-50 focus:bg-white"
            placeholder="example@email.com"
          />
        </div>

        <div>
          <label for="password" class="block text-sm font-semibold text-gray-700">Password</label>
          <input
            type="password"
            id="password"
            v-model="form.password"
            required
            class="mt-2 block w-full rounded-xl border border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:ring-opacity-50 sm:text-sm py-3 px-4 transition-colors bg-gray-50 focus:bg-white"
            placeholder="••••••••"
          />
        </div>

        <div>
          <label for="password_confirmation" class="block text-sm font-semibold text-gray-700">Confirm Password</label>
          <input
            type="password"
            id="password_confirmation"
            v-model="form.password_confirmation"
            required
            class="mt-2 block w-full rounded-xl border border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:ring-opacity-50 sm:text-sm py-3 px-4 transition-colors bg-gray-50 focus:bg-white"
            placeholder="••••••••"
          />
        </div>

        <div v-if="error" class="rounded-lg bg-red-50 p-3 border border-red-100">
          <p class="text-xs font-semibold text-red-600 text-center">{{ error }}</p>
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full flex justify-center py-3.5 px-4 border border-transparent rounded-xl shadow-md text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all transform hover:-translate-y-0.5 disabled:opacity-60 disabled:cursor-not-allowed"
        >
          <span v-if="loading" class="mr-2">
            <svg class="animate-spin h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </span>
          {{ loading ? 'Creating Account...' : 'Register' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
// Registration VIEW - Pinia usage example
import { ref } from 'vue';
import { useAuthStore } from '@/stores/auth'; // Import Pinia

// Instantiate Pinia Store to access actions (like register).
const authStore = useAuthStore();

// Reactive form data - this "ref" wraps an entire object.
const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

// Loading spinner and error handling
const loading = ref(false);
const error = ref('');

// This function is triggered by the form @submit.prevent.
const handleRegister = async () => {
  // 1. Basic client-side validation (Does password match confirmation?)
  if (form.value.password !== form.value.password_confirmation) {
    error.value = 'Passwords do not match!';
    return;
  }
  
  loading.value = true; // Disable button / start spinner
  error.value = ''; // Hide old errors
  try {
    // 2. Call the central "register" action in the Pinia Store with the data.
    await authStore.register(form.value);
    // If registration is successful, Pinia handles redirection (router.push), so nothing else to do here.
  } catch (err) {
    // Laravel rejected it (e.g. email already exists).
    error.value = 'Registration failed. Please check your data!';
    console.error(err);
  } finally {
    loading.value = false; // Turn off spinner
  }
};
</script>
