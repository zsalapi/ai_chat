import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

// Feltételezem, hogy a komponenseket a 'resources/js/views' mappába helyezted.
import LoginView from '@/views/LoginView.vue';
// import RegisterView from '@/views/RegisterView.vue';
import DashboardView from '@/views/DashboardView.vue';
import EventsView from '@/views/EventsView.vue';
import AgentDashboard from '@/views/AgentDashboard.vue';
import ClosedChats from '@/views/ClosedChats.vue';


const routes = [
    {
        path: '/login',
        name: 'login',
        component: LoginView,
        meta: { guest: true } // Csak be nem jelentkezett felhasználók érhetik el
    },
    // {
    //     path: '/register',
    //     name: 'register',
    //     component: RegisterView,
    //     meta: { guest: true }
    // },
    {
        path: '/',
        name: 'dashboard',
        component: DashboardView,
        meta: { requiresAuth: true } // Csak bejelentkezett felhasználók érhetik el
    },
    {
        path: '/events',
        name: 'events',
        component: EventsView,
        meta: { requiresAuth: true } // Ez is egy védett útvonal
    },
    {
        path: '/agent/dashboard',
        name: 'agent-dashboard',
        component: AgentDashboard,
        meta: { requiresAuth: true, requiresRole: 'agent' }
    },
    {
        path: '/agent/closed-chats',
        name: 'agent-closed-chats',
        component: ClosedChats,
        meta: { requiresAuth: true, requiresRole: 'agent' }
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();

    // Helyesen a localStorage-ból olvassuk a tokent.
    const tokenInStorage = localStorage.getItem('token');

    // Ha van token a tárolóban, de a user adat nincs a store-ban (pl. oldalfrissítés után),
    // akkor próbáljuk meg lekérni a felhasználót a token validálásához.
    if (tokenInStorage && !authStore.isAuthenticated) {
        try {
            await authStore.getUser();
        } catch (error) {
            // A getUser() hiba esetén a response interceptor már elindítja a logout() folyamatot,
            // így a következő ellenőrzés a login oldalra fog irányítani.
        }
    }

    // A (potenciálisan frissült) állapot alapján döntünk a navigációról.
    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        // Ha az útvonal authentikációt igényel, de a user nincs bejelentkezve, irányítás a loginra.
        next({ name: 'login' });
    } else if (to.meta.guest && authStore.isAuthenticated) {
        // Ha a user be van jelentkezve és vendég oldalt (pl. login) próbál elérni, irányítás a dashboardra.
        next({ name: 'dashboard' });
    } else if (to.meta.requiresRole && authStore.user?.role !== to.meta.requiresRole) {
        // If the route requires a specific role and the user does not have it,
        // redirect to the dashboard.
        next({ name: 'dashboard' });
    } else {
        next();
    }
});

export default router;
