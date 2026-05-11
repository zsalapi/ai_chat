<template>
  <div class="min-h-[80vh] flex items-center justify-center px-4">
    <div class="max-w-md w-full space-y-8 p-8 bg-white rounded-2xl shadow-xl border border-gray-100">
      <div class="text-center">
        <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">Reset Password</h2>
        <p class="mt-2 text-sm text-gray-600">
          Enter your new password below.
        </p>
      </div>

      <form class="mt-8 space-y-6" @submit.prevent="handleSubmit">
        <div class="rounded-md space-y-4">
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email address</label>
            <input
              id="email"
              v-model="form.email"
              name="email"
              type="email"
              required
              class="appearance-none relative block w-full px-4 py-3 border border-gray-300 placeholder-gray-400 text-gray-900 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm transition-all duration-200"
              placeholder="Email address"
            />
          </div>
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
            <input
              id="password"
              v-model="form.password"
              name="password"
              type="password"
              required
              class="appearance-none relative block w-full px-4 py-3 border border-gray-300 placeholder-gray-400 text-gray-900 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm transition-all duration-200"
              placeholder="New password"
            />
          </div>
          <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
            <input
              id="password_confirmation"
              v-model="form.password_confirmation"
              name="password_confirmation"
              type="password"
              required
              class="appearance-none relative block w-full px-4 py-3 border border-gray-300 placeholder-gray-400 text-gray-900 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm transition-all duration-200"
              placeholder="Confirm password"
            />
          </div>
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
              Resetting...
            </span>
            <span v-else>Reset Password</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
// New password entry view
import { reactive, ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import apiClient from '@/services/api';

// Vue Router tools. 
// "route" is needed to read URL parameters (e.g. ?token=123).
// "router" is for programmatic navigation (redirecting to another page).
const route = useRoute();
const router = useRouter();

// Single reactive form object. We use "reactive" here, which doesn't need ".value".
const form = reactive({
  token: '',
  email: '',
  password: '',
  password_confirmation: ''
});

const error = ref('');
const loading = ref(false);

// When the page loads (Lifecycle Hook): we read the hidden tokens from the URL
// that the user brought along after clicking the link in the email.
onMounted(() => {
  form.token = route.query.token || '';
  form.email = route.query.email || '';
});

// Submit form
const handleSubmit = async () => {
  loading.value = true;
  error.value = '';

  try {
    // Send the new password to the API along with the token.
    await apiClient.post('/password/reset', form);
    // If successful, push immediately to the login page with a (success) parameter.
    router.push({ path: '/login', query: { reset: 'success' } });
  } catch (err) {
    error.value = err.response?.data?.message || 'An error occurred. Please try again!';
  } finally {
    loading.value = false;
  }
};
</script>
