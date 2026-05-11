import axios from 'axios';
import { useAuthStore } from '@/stores/auth';

// Itt hozzuk létre a saját "apiClient" AXIOS példányunkat.
// Ez fog minden jövőbeli hálózati kérést intézni a projektben.
const apiClient = axios.create({
  baseURL: 'https://localhost:8000/api', // A Backend API címe (Itt HTTPS helyett HTTP kellhet lokálisan, kivéve ha beállítottad a cert-et)
  headers: {
    // Megmondjuk a Laravelnek, hogy JSON választ kérünk, még hibák esetén is.
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  }
});

// A KÉRÉS (Request) FELTARTÓZTATÓJA (Interceptor)
// Ez a blokk MINDEN EGYES kérés előtt lefut, mielőtt az kimenne az internetre a szerver felé.
apiClient.interceptors.request.use(config => {
  const authStore = useAuthStore(); // Elkérjük a Piniát
  const token = authStore.token;    // Kinyerjük a bejelentkezett felhasználó Tokenjét
  
  if (token) {
    // Ha van tokenünk, RÁRAGASZTJUK a kérés fejlécére (Authorization: Bearer <token>)
    // Így ellenőrzi a Laravel auth:api middleware, hogy ki vagy!
    config.headers.Authorization = `Bearer ${token}`;
  }

  // Ha a Websocket (Echo) fel van állva, hozzáadjuk a Socket azonosítót.
  // Ez elengedhetetlen ahhoz, hogy a backend-en a .toOthers() működjön!
  if (window.Echo && window.Echo.socketId()) {
    config.headers['X-Socket-Id'] = window.Echo.socketId();
  }

  return config; // Elengedjük a kérést az útjára
}, error => {
  return Promise.reject(error);
});

// Opcionális: VÁLASZ (Response) Interceptor
// Itt el lehetne kapni a 401 (Unauthorized) hibákat, ha például lejárt a token, 
// és automatikusan kijelentkeztetni a felhasználót a Piniából.

export default apiClient;

