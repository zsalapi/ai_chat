// A Laravel beépített bootstrap fájlja: Beállítja az Axios HTTP klienst (automatikus CSRF token és JSON fejléc paraméterek), és rácsatlakozik a Laravel Echo-ra (Pusher WebSockets).
import './bootstrap';
// A projekt globális stíluslapja (itt él a Tailwind CSS).
import '../css/app.css';

// Importáljuk a Vue 3 magját (createApp) és az új hivatalos State Management (állapotkezelő) könyvtárat, a Piniát.
import { createApp } from 'vue';
import { createPinia } from 'pinia';

// Behozzuk a "Gyökér" dom elemet (App.vue), és a navigációs motor (Router) konfigurációnkat.
import App from './App.vue';
import router from './router';

// Létrehozzuk a globális "Agyat" (Pinia Store).
const pinia = createPinia();

// Létrehozzuk a Vue alkalmazást az <App /> gyökér komponensből.
const app = createApp(App);

// "Felszereljük" a Piniát és a Routert az alkalmazásra (Plugin-ként működnek).
app.use(pinia);
app.use(router);

// Végül "befecskendezzük" vagy "ráillesztjük" (mount) az elkészült Vue alkalmazást a 'resources/views/welcome.blade.php' fájlban lévő <div id="app"></div> elembe.
app.mount('#app');
