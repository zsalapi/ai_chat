<template>
  <div class="min-h-[80vh] flex items-center justify-center px-4">
    <div class="max-w-md w-full space-y-8 p-8 bg-white rounded-2xl shadow-xl border border-gray-100">
      <div class="text-center">
        <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">Forgot Password?</h2>
        <p class="mt-2 text-sm text-gray-600">
          Enter your email address and we'll send you a link to reset your password.
        </p>
      </div>

      <div v-if="status" class="p-4 bg-green-50 border border-green-200 rounded-lg">
        <p class="text-sm text-green-700 font-medium">{{ status }}</p>
      </div>

      <form class="mt-8 space-y-6" @submit.prevent="handleSubmit">
        <div>
          <label for="email-address" class="block text-sm font-medium text-gray-700 mb-1">Email address</label>
          <input
            id="email-address"
            v-model="email"
            name="email"
            type="email"
            required
            class="appearance-none relative block w-full px-4 py-3 border border-gray-300 placeholder-gray-400 text-gray-900 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm transition-all duration-200"
            placeholder="john@example.com"
          />
        </div>

        <div v-if="error" class="text-red-600 text-sm font-medium bg-red-50 p-3 rounded-lg border border-red-100">
          {{ error }}
        </div>

        <div>
          <button
            type="submit"
            :disabled="loading"
            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-semibold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98]"
          >
            <span v-if="loading" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Sending...
            </span>
            <span v-else>Send Reset Link</span>
          </button>
        </div>

        <div class="text-center">
          <router-link to="/login" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 transition-colors">
            Back to login
          </router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
// Passport for Password Reset (Forgot Password view)
import { ref } from 'vue';
import apiClient from '@/services/api';

const email = ref('');
const status = ref(''); // Success message
const error = ref(''); // Error message
const loading = ref(false); // Spinner state

const handleSubmit = async () => {
  loading.value = true;
  error.value = '';
  status.value = '';

  try {
    // API call to Laravel backend to send email.
    const response = await apiClient.post('/password/email', { email: email.value });
    // If successful, set the status message for the user to see in the green box.
    status.value = response.data.message || 'If an account exists with this email address, we have sent the reset link.';
    email.value = ''; // Clear field for security and UX reasons.
  } catch (err) {
    // If there is an error, show it in the red box.
    error.value = err.response?.data?.message || 'An error occurred. Please try again!';
  } finally {
    loading.value = false; // Turn off spinner in all cases.
  }
};
</script>
