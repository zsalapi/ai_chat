<template>
  <transition 
    enter-active-class="ease-out duration-300" 
    enter-from-class="opacity-0" 
    enter-to-class="opacity-100" 
    leave-active-class="ease-in duration-200" 
    leave-from-class="opacity-100" 
    leave-to-class="opacity-0"
  >
    <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
        <!-- Background backdrop -->
        <div class="fixed inset-0 bg-gray-900 bg-opacity-40 backdrop-blur-sm transition-opacity" aria-hidden="true" @click="close"></div>

        <!-- Modal panel -->
        <transition 
          enter-active-class="ease-out duration-300 transform" 
          enter-from-class="opacity-0 translate-y-4 sm:translate-y-8 sm:scale-95" 
          enter-to-class="opacity-100 translate-y-0 sm:scale-100" 
          leave-active-class="ease-in duration-200 transform" 
          leave-from-class="opacity-100 translate-y-0 sm:scale-100" 
          leave-to-class="opacity-0 translate-y-4 sm:translate-y-8 sm:scale-95"
        >
          <div v-if="isOpen" class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-gray-100">
            
            <!-- Close button -->
            <div class="absolute right-0 top-0 hidden pr-4 pt-4 sm:block z-10">
              <button
                type="button"
                class="rounded-full bg-white text-gray-400 hover:text-gray-500 hover:bg-gray-100 transition-all p-2 focus:outline-none"
                @click="close"
              >
                <span class="sr-only">Close</span>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <div class="px-8 pt-10 pb-12">
              <div class="text-center mb-10">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-50 mb-6 group transition-all duration-300 hover:scale-110">
                  <svg class="h-8 w-8 text-indigo-600 transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                  </svg>
                </div>
                <h3 class="text-3xl font-extrabold text-gray-900 tracking-tight">Login</h3>
                <p class="mt-2 text-sm text-gray-500 font-medium">Please log in to your account</p>
              </div>

              <form @submit.prevent="handleSubmit" class="space-y-6">
                <div>
                  <label for="auth-email" class="block text-sm font-semibold text-gray-700">Email Address</label>
                  <input type="email" id="auth-email" v-model="form.email" required class="mt-2 block w-full rounded-xl border border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:ring-opacity-50 sm:text-sm py-3 px-4 transition-colors bg-gray-50 focus:bg-white" placeholder="example@email.com" />
                </div>
                
                <div>
                  <div class="flex items-center justify-between">
                    <label for="auth-password" class="block text-sm font-semibold text-gray-700">Password</label>
                    <router-link 
                      to="/forgot-password" 
                      @click="close"
                      class="text-xs font-bold text-indigo-600 hover:text-indigo-500 transition-colors"
                    >
                      Forgot password?
                    </router-link>
                  </div>
                  <input type="password" id="auth-password" v-model="form.password" required class="mt-2 block w-full rounded-xl border border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:ring-opacity-50 sm:text-sm py-3 px-4 transition-colors bg-gray-50 focus:bg-white" placeholder="••••••••" />
                </div>

                <div v-if="error" class="rounded-lg bg-red-50 p-3 border border-red-100 animate-in">
                  <p class="text-xs font-semibold text-red-600 text-center">{{ error }}</p>
                </div>

                <button
                  type="submit"
                  :disabled="loading"
                  class="w-full flex justify-center py-4 px-4 border border-transparent rounded-xl shadow-lg text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all transform hover:-translate-y-0.5 disabled:opacity-60 disabled:cursor-not-allowed mt-8"
                >
                  <span v-if="loading" class="mr-3">
                    <svg class="animate-spin h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                  </span>
                  Login
                </button>
              </form>
            </div>
          </div>
        </transition>
      </div>
    </div>
  </transition>
</template>

<script setup>
// "reactive" makes the whole JavaScript object reactive, while 'ref' is usually better for simple values.
import { ref, reactive, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';

// "defineProps" defines the input data this component expects from its parent (App.vue).
const props = defineProps({
  isOpen: Boolean,
});

// "defineEmits" allows us to message the parent (e.g. throwing a "@close" event).
const emit = defineEmits(['close', 'success']);

const authStore = useAuthStore();
const loading = ref(false);
const error = ref('');

// Form fields live here as a "reactive" object so v-model can continuously track typing.
const form = reactive({
  email: '',
  password: '',
});

// Watcher: When the modal opens (isOpen prop switches to true), we clear the form.
watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    form.email = '';
    form.password = '';
    error.value = '';
  }
});

// Emit the close event to the parent.
const close = () => {
  emit('close');
};

const handleSubmit = async () => {
  loading.value = true;
  error.value = '';
  try {
    // Normal login.
    await authStore.login(form);
    emit('success');
  } catch (err) {
    error.value = 'Not valid credentials. Please try again!';
    console.error(err);
  } finally {
    loading.value = false;
  }
};
</script>
