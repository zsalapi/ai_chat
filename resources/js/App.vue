<template>
  <div class="min-h-screen bg-gray-50 flex flex-col font-sans">
    <!-- Modern Navigation Bar -->
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-40 shadow-sm backdrop-blur-md bg-opacity-90">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <!-- Logo area -->
            <div class="flex-shrink-0 flex items-center">
              <div class="h-8 w-8 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-lg flex items-center justify-center shadow-md mr-3">
                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 118 0m-4 5v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1"></path>
                </svg>
              </div>
              <span class="text-xl font-extrabold text-gray-900 tracking-tight hidden sm:block">Event<span class="text-indigo-600">Pro</span></span>
            </div>

            <!-- Navigation Links -->
            <div class="hidden sm:ml-10 sm:flex sm:space-x-8 h-full">
              <router-link
                to="/"
                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-all duration-200"
                :class="$route.name === 'dashboard' ? 'border-indigo-600 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
              >
                Dashboard
              </router-link>
              <router-link
                to="/events"
                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-all duration-200"
                :class="$route.name === 'events' ? 'border-indigo-600 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
              >
                Events
              </router-link>
              <router-link
                v-if="authStore.isAuthenticated && authStore.user?.role === 'agent'"
                to="/agent/dashboard"
                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-all duration-200"
                :class="$route.name === 'agent-dashboard' ? 'border-indigo-600 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
              >
                Agent Chat
              </router-link>
            </div>
          </div>

          <div class="flex items-center">
            <template v-if="authStore.isAuthenticated">
              <div class="hidden md:flex flex-col items-end mr-4 text-right">
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Logged in as</span>
                <span class="text-sm font-semibold text-gray-700">{{ authStore.user?.name }}</span>
              </div>
              <button
                @click="handleLogout"
                class="ml-2 inline-flex items-center px-4 py-2 border border-transparent text-sm font-semibold rounded-xl text-white bg-gray-900 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-all duration-200 shadow-sm"
              >
                Logout
              </button>
            </template>
            <template v-else>
              <button
                @click="openAuthModal('login')"
                class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-semibold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 shadow-md transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transform hover:-translate-y-0.5"
              >
                Login
              </button>
            </template>
          </div>
        </div>
      </div>
    </nav>

    <!-- Auth Modal -->
    <AuthModal
      :isOpen="isAuthModalOpen"
      :initialMode="authModalMode"
      @close="isAuthModalOpen = false"
      @success="onAuthSuccess"
    />

    <!-- Main Content Area -->
    <main class="flex-grow">
      <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <router-view v-slot="{ Component }">
          <transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
            mode="out-in"
          >
            <component :is="Component" />
          </transition>
        </router-view>
      </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-6 mt-auto">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <p class="text-center text-sm text-gray-500 font-medium">
          &copy; {{ new Date().getFullYear() }} EventPro system. All rights reserved.
        </p>
      </div>
    </footer>

    <!-- Chat Widget for guests and regular users, but not for agents/admins -->
    <ChatWidget v-if="!authStore.user || (authStore.user.role !== 'agent' && authStore.user.role !== 'admin')" />
  </div>
</template>

<script setup>
// App.vue is the root component of the application.
// All other components live within this (using the <router-view> tag for switching).

import { ref, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter, useRoute } from 'vue-router';
import AuthModal from '@/components/AuthModal.vue';
import ChatWidget from '@/components/ChatWidget.vue';

// Instantiate Pinia state manager (allows access to authStore.user and login/logout functions)
const authStore = useAuthStore();

// Instantiate Vue Router for programmatic navigation (e.g. router.push('/'))
const router = useRouter();
const route = useRoute();

// Reactive control for Auth Modal
const isAuthModalOpen = ref(false); // Is it open?
const authModalMode = ref('login'); // Are we in "login" or "register" mode?

// Opens the modal with the specified mode
const openAuthModal = (mode) => {
  authModalMode.value = mode;
  isAuthModalOpen.value = true;
};

// Executed after successful login/registration
const onAuthSuccess = () => {
  isAuthModalOpen.value = false; // Close modal.
  // authStore handles redirection to /dashboard, so no router call needed here.
};

// WATCHER: Monitor URL parameters.
// If a user visits a protected page (e.g. /dashboard) without login,
// Vue Router redirects here with ?auth=login URL parameter. We catch this and open the Login modal.
watch(() => route.query.auth, (newVal) => {
  if (newVal === 'login') {
    openAuthModal('login');
  }
}, { immediate: true }); // immediate: true means the check runs on initial load as well.

// Logout
const handleLogout = async () => {
  await authStore.logout(); // Delete Laravel Sanctum token in background.
  router.push('/'); // Redirect to home page.
};
</script>

<style>
/* Global scrollbar styles for premium feel */
::-webkit-scrollbar {
  width: 8px;
}
::-webkit-scrollbar-track {
  background: #f1f1f1;
}
::-webkit-scrollbar-thumb {
  background: #c7d2fe;
  border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
  background: #818cf8;
}

.animate-in {
  animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}
</style>
