<template>
  <div class="min-h-[80vh] flex items-center justify-center px-4 pt-12">
    <div class="max-w-md w-full space-y-8 p-8 bg-white rounded-2xl shadow-xl border border-gray-100">
      <div class="text-center">
        <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">Login</h2>
        <p class="mt-2 text-sm text-gray-500 font-medium">Please log in to your account</p>
      </div>

      <form class="mt-8 space-y-5" @submit.prevent="handleLogin">
        <div>
          <label for="email" class="block text-sm font-semibold text-gray-700">Email Address</label>
          <input 
            type="email" 
            id="email" 
            v-model="form.email" 
            required 
            track-id="login-email"
            class="mt-2 block w-full rounded-xl border border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:ring-opacity-50 sm:text-sm py-3 px-4 transition-colors bg-gray-50 focus:bg-white" 
            placeholder="example@email.com" 
          />
        </div>

        <div>
          <div class="flex items-center justify-between">
            <label for="password" class="block text-sm font-semibold text-gray-700">Password</label>
            <router-link 
              to="/forgot-password" 
              class="text-xs font-bold text-indigo-600 hover:text-indigo-500 transition-colors"
            >
              Forgot password?
            </router-link>
          </div>
          <input 
            type="password" 
            id="password" 
            v-model="form.password" 
            required 
            track-id="login-password"
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
          {{ loading ? 'Logging in...' : 'Login' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
// Another great example of Vue 3 Composition API simplicity
import { ref } from 'vue';
import { useAuthStore } from '@/stores/auth'; // Import the Pinia store (state manager)

const authStore = useAuthStore();

// The form object as a reactive variable. v-model="form.email" in HTML automatically syncs with this!
const form = ref({
  email: '',
  password: '',
});

// Variables to control the button loading spinner and error messages
const loading = ref(false);
const error = ref('');

// This function runs when the form is submitted (@submit.prevent prevents page reload).
const handleLogin = async () => {
  loading.value = true;
  error.value = ''; // Clear previous errors
  try {
    // Pass the data to Pinia. Behind the scenes it communicates with Laravel API (using Axios).
    await authStore.login(form.value);
    // If login is successful, Pinia will automatically redirect to the dashboard (router), nothing to do here.
  } catch (err) {
    // If Laravel returns an error message (e.g. password mismatch)
    error.value = 'Invalid email or password. Please try again!';
    console.error(err);
  } finally {
    // This block always runs at the end (for both success and error), so we stop the loading icon.
    loading.value = false;
  }
};
</script>

<style scoped>
/* Styles are defined in App.vue for consistency */
</style>
