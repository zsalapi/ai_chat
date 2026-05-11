// Ebbe a fájlba definiáljuk a FRONTEND felület útvonalait (Routing).
// Lényeges különbség: Ezek az URL-ek (pl. nálunk localhost:8000/events) nem a backend szerverre mennek (bár a Laravel oda is passzolja a fallback miatt feléjük),
// hanem a Vue azonnal kicseréli alattuk a DOM-ot az oldal újratöltése NÉLKÜL (Single Page Application - SPA).

import { createRouter, createWebHistory } from 'vue-router';
import DashboardView from '@/views/DashboardView.vue';
import EventsView from '@/views/EventsView.vue';
import LoginView from '@/views/LoginView.vue';
import { useAuthStore } from '@/stores/auth';
import AgentDashboard from '@/views/AgentDashboard.vue';
import ClosedChats from './views/ClosedChats.vue';

// Létrehozzuk a router példányt
const router = createRouter({
  // A "history" mód eltünteti a /#/ karaktereket az URL-ből, szép "tiszta" URL-eket ad (HTML5 History API-t használ).
  history: createWebHistory(import.meta.env.BASE_URL),

  // Itt regisztráljuk, hogy melyik URL-hez melyik Vue Komponens (View - Nézet) tartozik.
  routes: [
    {
      path: '/',
      name: 'dashboard',
      component: DashboardView,
      // Remove requiresAuth to allow homepage access for all users
    },
    {
      path: '/events',
      name: 'events',
      component: EventsView,
      meta: { requiresAuth: true }
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      // "guest" - csak vendégek láthatják (Aki be van lépve, minek látná a bejelentkezés oldalt?).
      meta: { guest: true }
    },
    {
      path: '/forgot-password',
      name: 'forgot-password',
      // Lusta betöltés (Lazy Loading): Ezt a fáljt csak akkor olvassa/tölti le a böngésző a hálózatról jövő Bundle.js-ből, ha rákattintasz a linkre! Óriási JS fájl optimalizáció.
      component: () => import('@/views/ForgotPasswordView.vue'),
      meta: { guest: true }
    },
    {
      path: '/password-reset',
      name: 'password-reset',
      component: () => import('@/views/ResetPasswordView.vue'),
      meta: { guest: true }
    },
    {
        path: '/agent/dashboard',
        name: 'agent-dashboard',
        component: AgentDashboard,
        meta: { requiresAuth: true }
    },
    {
        path: '/agent/closed-chats',
        name: 'closed-chats',
        component: ClosedChats,
        meta: { requiresAuth: true }
    }
  ]
});

// === NAVIGÁCIÓS ŐR (NAVIGATION GUARD) ===
// Ez a blokk MINDENNEL navigáció ELŐTT lefut! Itt ellenőrizzük a jogosultságokat.
router.beforeEach((to, from) => {
  const authStore = useAuthStore(); // Elkérjük a PINIÁBÓL a bejelentkezési státuszt

  // Ha az útvonal Auth-ot igényel (pl. /events) DE a felhasználó NINCS bejelentkezve:
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    // Visszadobjuk a főoldalra, és rakunk mögé egy GET paramétert (?auth=login),
    // amit az App.vue elkap, és egyből az orrába nyitja a Modális Belépő ablakot!
    return { path: '/', query: { auth: 'login' } };
  }
  // Ha ez "vendég" oldal (pl. /login), de Már be van jelentkezve:
  else if (to.meta.guest && authStore.isAuthenticated) {
    // Akkor be se engedjük, visszadobjuk a főoldalra
    return '/';
  }
  // Egyéb esetben (vagyis ha minden OK), a beforeEach magától továbbengedi az útvonalat.
});

export default router;
