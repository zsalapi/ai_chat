// PINIA STORE - The "Single Source of Truth" for authentication.
// Think of this file as a global memory for Vue, which 
// any component can access without passing props down.

import { defineStore } from 'pinia';
import api from '@/services/api'; // The recently improved Axios client
import router from '@/router';

export const useAuthStore = defineStore('auth', {
  // "state" is the data itself that we store.
  // Here we read from the browser's "localStorage" on startup, in case we are already logged in (Persistence).
  state: () => ({
    user: JSON.parse(localStorage.getItem('user')),
    token: localStorage.getItem('token'),
  }),
  
  // "getters" are values calculated based on state (Like computed in components)
  getters: {
    // We are authenticated if user data exists. !! double negation converts to Boolean.
    isAuthenticated: (state) => !!state.user,
    currentUser: (state) => state.user,
  },
  
  // "actions" are methods, functions that MODIFY the State. API calls go here!
  actions: {
    // LOGIN Action
    async login(credentials) {
      try {
        const response = await api.post('/login', credentials);
        const { token, user } = response.data.data;

        // Store data in memory (state update)
        this.token = token;
        this.user = user;

        // ...and store in browser's physical storage (stay logged in after F5 / refresh)
        localStorage.setItem('token', token);
        localStorage.setItem('user', JSON.stringify(user));

        // After successful login, send Vue Router to the dashboard
        router.push({ name: 'dashboard' });
      } catch (error) {
        console.error('Failed login:', error);
        throw error; // Throw error so LoginView can display the red error message on UI.
      }
    },
    
    // REGISTRATION Action
    async register(userInfo) {
        const response = await api.post('/register', userInfo);
        const { token, user } = response.data.data;

        this.token = token;
        this.user = user;

        localStorage.setItem('token', token);
        localStorage.setItem('user', JSON.stringify(user));

        router.push({ name: 'dashboard' });
    },
    
    // GET USER Action (e.g. if visitor manually Refreshes (F5) the page and we want to validate)
    async getUser() {
      if (!this.token) return;

      try {
        // Since we added the interceptor in api.js, it automatically prefixes the token.
        const response = await api.get('/user');
        this.user = response.data;
        localStorage.setItem('user', JSON.stringify(this.user));
      } catch (error) {
        console.error('Failed to load user. Token might be expired.', error);
        throw error;
      }
    },
    
    // LOGOUT Action
    async logout() {
      try {
        // Tell Laravel to revoke and invalidate the token for security reasons.
        await api.post('/logout');
      } catch (error) {
        console.error('Logout from backend failed, but logging out client anyway.', error);
      } finally {
        // The 'finally' block always runs, even if server is unreachable (offline),
        // so we definitely forget the data in the browser!
        this.user = null;
        this.token = null;
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        
        // 'replace' so they can't go back with the Back arrow to preceding (protected) pages.
        router.replace({ name: 'login' });
      }
    },
  },
});
