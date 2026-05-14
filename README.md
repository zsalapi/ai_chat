## AI-powered chat application with event-driven architecture, built on Laravel

A modern, high-security Event Management and Chat application for customers built with **Laravel 12**, **Vue 3**, and **Tailwind CSS**. This project features a robust security architecture including OAuth 2.0 / JWT authentication and mandatory HTTPS enforcement.
The best feature is the AI automated chat for the Guests, They also can ask for Human Agent to answer.
(AI Chat-bot uses Ollama with local model.)

## 🚀 Features

- **OAuth 2.0 & JWT**: Secure authentication powered by **Laravel Passport**.
- **HTTPS Everywhere**: Mandatory TLS/SSL enforcement across both Backend and Frontend.
- **Premium UI**: Modern, responsive interface with glassmorphism effects and Tailwind CSS.
- **Password Recovery**: Complete secure password reset flow with email link generation.
- **Event Management**: Full CRUD functionality for managing professional events.
- **Local Dev Proxy**: Custom HTTPS proxy for seamless secure development on localhost.

## 🛡️ Security Implementation

### 1. Authentication (OAuth 2.0)
The system uses **Laravel Passport** to handle authentication.
- **Tokens**: Issues standard JWT (JSON Web Tokens).
- **Driver**: Configured with the `passport` guard in `config/auth.php`.
- **Infrastructure**: Automated token management and secure user registration.

### 2. HTTPS Enforcement
To prevent man-in-the-middle attacks, HTTPS is enforced at every layer:
- **Middleware**: A `ForceHttps` middleware redirects all non-secure traffic.
- **Vite**: The development server is configured with explicit SSL certificates.
- **Cookie Security**: Session cookies are marked as `Secure`, `HttpOnly`, and `SameSite=Lax`.

### 3. Password Security
- **Hashing**: All passwords are encrypted using `bcrypt`.
- **Reset Flow**: Uses secure, one-time reset tokens with custom SPA link routing.

## 🛠️ Installation & Setup

### Prerequisites
- PHP >= 8.2
- Composer
- Node.js & NPM
- PostgreSQL

### Steps

1. **Clone and Install Backend Dependencies**:
   ```bash
   composer install
   ```

2. **Setup Environment - .env has to modify as your needs**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   php artisan storage:link
   ```

3. **Database & Auth Setup**:
   ```bash
   php artisan migrate
   php artisan db:seed
   php artisan passport:install
   ```

4. **Install Frontend Dependencies**:
   ```bash
   npm install
   ```

5. **Generate SSL Certificates (Local Dev)**:
   Ensure you have `key.pem` and `cert.pem` in the root directory for the HTTPS dev server.

## 💻 Running Locally

To maintain a secure development environment, follow these steps:

1. **Start the HTTPS Proxy & Backend**:
   This runs the Laravel server and a secure proxy at `https://localhost:8000`.
   ```bash
   npm run secure-serve
   ```
2. **Start the Laravel reverb**:
   This runs the frontend with Hot Module Replacement (HMR) over HTTPS.
   ```bash
   php artisan reverb:start
   ```

3. **Start the Vite Dev Server**:
   This runs the frontend with Hot Module Replacement (HMR) over HTTPS.
   ```bash
   #rebuild front-end if you modified
   npm run build
   #Running Front-end (Vue)
   npm run dev
   ```

4. **Access the Application**:
   Open [https://localhost:8000](https://localhost:8000) in your browser.

## 🧪 Testing the API

A pre-configured Postman collection is included to help you test the secured endpoints.

- **File**: `postman_collection.json`
- **Setup**:
  1. Import the file into Postman.
  2. The collection is pre-configured with the `baseUrl` ([https://localhost:8000](https://localhost:8000)).
  3. For protected routes, use the "Login" request to obtain an `accessToken`, then paste it into the "Auth" tab as a **Bearer Token**.

## 📧 Email Testing
For local development, emails (like password reset links) are stored in:
`storage/logs/laravel.log`
