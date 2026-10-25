<template>
    <div class="login-page">
        <!-- Dark Mode Toggle -->
        <button @click="toggleDarkMode" class="dark-mode-toggle-login" :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'">
            <i :class="isDark ? 'bi bi-sun-fill' : 'bi bi-moon-fill'"></i>
        </button>

        <!-- Background decoration -->
        <div class="bg-decoration">
            <div class="bg-shape bg-shape-1"></div>
            <div class="bg-shape bg-shape-2"></div>
            <div class="bg-shape bg-shape-3"></div>
        </div>

        <div class="login-container">
            <!-- Login Card -->
            <div class="login-card">
                <div class="login-header">
                    <div class="app-logo">
                        <i class="bi bi-briefcase-fill"></i>
                    </div>
                    <h2>Welcome back</h2>
                    <p>Sign in to continue to your dashboard</p>
                </div>

                <!-- Error Message -->
                <Transition name="slide-fade">
                    <div v-if="error" class="alert alert-danger">
                        <i class="bi bi-exclamation-circle"></i>
                        <span>{{ error }}</span>
                    </div>
                </Transition>

                <form @submit.prevent="handleLogin" class="login-form">
                    <!-- Email Field -->
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-wrapper">
                            <i class="bi bi-envelope input-icon"></i>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="form-control with-icon"
                                placeholder="you@company.com"
                                required
                                autocomplete="email"
                            />
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-wrapper">
                            <i class="bi bi-lock input-icon"></i>
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                class="form-control with-icon"
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"
                            />
                            <button type="button" class="toggle-password" @click="showPassword = !showPassword">
                                <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" v-model="rememberMe" class="form-checkbox" />
                            <span>Remember me</span>
                        </label>
                        <a href="#" class="forgot-link">Forgot password?</a>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary btn-login" :disabled="loading">
                        <span v-if="loading" class="spinner"></span>
                        <span v-else class="btn-text">
                            <span>Sign In</span>
                            <i class="bi bi-arrow-right"></i>
                        </span>
                    </button>

                    <!-- Register Link -->
                    <p class="auth-link">
                        Don't have an account?
                        <router-link to="/register">Create one</router-link>
                    </p>
                </form>
            </div>

            <!-- Copyright -->
            <p class="copyright">
                &copy; {{ new Date().getFullYear() }} SiteTrack. All rights reserved.
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useDarkMode } from '@/composables/useDarkMode';
import api from '@/api';

const router = useRouter();
const { isDark, toggleDarkMode, initTheme } = useDarkMode();

// Initialize theme on mount
initTheme();

const form = reactive({
    email: '',
    password: '',
});

const loading = ref(false);
const error = ref('');
const showPassword = ref(false);
const rememberMe = ref(false);

const handleLogin = async () => {
    loading.value = true;
    error.value = '';

    try {
        const response = await api.auth.login({
            email: form.email,
            password: form.password,
        });

        // Store auth data
        localStorage.setItem('token', response.data.token);
        localStorage.setItem('user', JSON.stringify(response.data.user));
        localStorage.setItem('isAuthenticated', 'true');

        router.push('/');
    } catch (err) {
        if (err.response?.data?.errors?.email) {
            error.value = err.response.data.errors.email[0];
        } else if (err.response?.data?.message) {
            error.value = err.response.data.message;
        } else {
            error.value = 'Invalid email or password. Please try again.';
        }
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
.login-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--primary-950) 0%, var(--primary-900) 100%);
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
    transition: background 0.3s ease;
}

/* Dark Mode Toggle on Login */
.dark-mode-toggle-login {
    position: absolute;
    top: 1.5rem;
    right: 1.5rem;
    z-index: 100;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 0.75rem;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    cursor: pointer;
    transition: all 0.2s ease;
}

.dark-mode-toggle-login:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: scale(1.05);
}

.dark-mode-toggle-login i {
    font-size: 1.25rem;
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.dark-mode-toggle-login:hover i {
    transform: rotate(15deg);
}

/* Background Decorations */
.bg-decoration {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
}

.bg-shape {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.05);
    animation: float 20s ease-in-out infinite;
}

.bg-shape-1 {
    width: 500px;
    height: 500px;
    top: -150px;
    right: -150px;
    animation-delay: 0s;
}

.bg-shape-2 {
    width: 400px;
    height: 400px;
    bottom: -100px;
    left: -100px;
    animation-delay: -5s;
}

.bg-shape-3 {
    width: 250px;
    height: 250px;
    top: 50%;
    left: 15%;
    animation-delay: -10s;
}

@keyframes float {
    0%, 100% { transform: translate(0, 0) rotate(0deg); }
    25% { transform: translate(10px, -10px) rotate(5deg); }
    50% { transform: translate(-5px, 15px) rotate(-3deg); }
    75% { transform: translate(-15px, -5px) rotate(3deg); }
}

.login-container {
    width: 100%;
    max-width: 420px;
    position: relative;
    z-index: 1;
}

/* Login Card */
.login-card {
    background: white;
    border-radius: 1.5rem;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
    padding: 2.5rem;
    animation: slideUp 0.5s ease-out;
    transition: background 0.3s ease, box-shadow 0.3s ease;
}

:global(.dark) .login-card {
    background: var(--gray-900);
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5);
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.login-header {
    text-align: center;
    margin-bottom: 2rem;
}

.app-logo {
    width: 4rem;
    height: 4rem;
    margin: 0 auto 1rem;
    background: linear-gradient(135deg, var(--primary-600) 0%, var(--accent-600) 100%);
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 24px rgba(79, 70, 229, 0.3);
}

.app-logo i {
    font-size: 1.75rem;
    color: white;
}

.login-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--gray-900);
    margin: 0 0 0.5rem;
    transition: color 0.3s ease;
}

:global(.dark) .login-header h2 {
    color: var(--gray-50);
}

.login-header p {
    color: var(--gray-500);
    font-size: 0.9375rem;
    margin: 0;
    transition: color 0.3s ease;
}

:global(.dark) .login-header p {
    color: var(--gray-400);
}

/* Alert */
.alert {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    border-radius: 0.75rem;
    margin-bottom: 1.5rem;
    font-size: 0.9375rem;
}

.alert-danger {
    background: linear-gradient(135deg, var(--danger-100) 0%, var(--danger-50) 100%);
    border: 1px solid var(--danger-200);
    color: var(--danger-800);
}

.alert i {
    font-size: 1.25rem;
    flex-shrink: 0;
}

/* Form */
.login-form {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-label {
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--gray-700);
    transition: color 0.3s ease;
}

:global(.dark) .form-label {
    color: var(--gray-200);
}

.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.input-icon {
    position: absolute;
    left: 1rem;
    color: var(--gray-400);
    font-size: 1.125rem;
    pointer-events: none;
    transition: color 0.2s ease;
}

.form-control {
    width: 100%;
    padding: 0.875rem 1rem;
    font-size: 0.9375rem;
    color: var(--gray-900);
    background: var(--gray-50);
    border: 1.5px solid var(--gray-200);
    border-radius: 0.75rem;
    transition: all 0.2s ease;
}

:global(.dark) .form-control {
    color: var(--gray-50);
    background: var(--gray-800);
    border-color: var(--gray-700);
}

:global(.dark) .form-control::placeholder {
    color: var(--gray-500);
}

:global(.dark) .form-control:hover {
    border-color: var(--gray-600);
}

:global(.dark) .form-control:focus {
    background: var(--gray-900);
    border-color: var(--primary-400);
    box-shadow: 0 0 0 4px rgba(129, 140, 248, 0.2);
}

.form-control.with-icon {
    padding-left: 3rem;
}

.form-control::placeholder {
    color: var(--gray-400);
}

.form-control:hover {
    border-color: var(--gray-300);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-500);
    background: white;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
}

.form-control:focus + .input-icon,
.input-wrapper:focus-within .input-icon {
    color: var(--primary-500);
}

.toggle-password {
    position: absolute;
    right: 1rem;
    background: none;
    border: none;
    color: var(--gray-400);
    cursor: pointer;
    padding: 0.25rem;
    font-size: 1.125rem;
    transition: color 0.2s ease;
}

.toggle-password:hover {
    color: var(--gray-500);
}

:global(.dark) .toggle-password {
    color: var(--gray-500);
}

:global(.dark) .toggle-password:hover {
    color: var(--gray-400);
}

:global(.dark) .input-icon {
    color: var(--gray-500);
}

:global(.dark) .input-wrapper:focus-within .input-icon {
    color: var(--primary-300);
}

/* Form Options */
.form-options {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.875rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--gray-600);
    cursor: pointer;
    transition: color 0.3s ease;
}

:global(.dark) .checkbox-label {
    color: var(--gray-400);
}

.form-checkbox {
    width: 1rem;
    height: 1rem;
    border-radius: 0.25rem;
    border: 1.5px solid var(--gray-300);
    cursor: pointer;
    accent-color: var(--primary-500);
}

:global(.dark) .form-checkbox {
    border-color: var(--gray-600);
    background: var(--gray-800);
}

.forgot-link {
    color: var(--primary-500);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s ease;
}

.forgot-link:hover {
    color: var(--primary-600);
}

:global(.dark) .forgot-link {
    color: var(--primary-300);
}

:global(.dark) .forgot-link:hover {
    color: var(--primary-200);
}

/* Login Button */
.btn-login {
    width: 100%;
    padding: 1rem;
    font-size: 1rem;
    font-weight: 600;
    border-radius: 0.75rem;
    background: linear-gradient(135deg, var(--primary-600) 0%, var(--accent-600) 100%);
    color: white;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(79, 70, 229, 0.35);
    margin-top: 0.5rem;
}

.btn-login:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(79, 70, 229, 0.4);
}

.btn-login:active:not(:disabled) {
    transform: translateY(0);
}

.btn-login:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.btn-text {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.btn-text i {
    transition: transform 0.2s ease;
}

.btn-login:hover .btn-text i {
    transform: translateX(4px);
}

.spinner {
    width: 1.5rem;
    height: 1.5rem;
    border: 2.5px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Auth Link */
.auth-link {
    text-align: center;
    color: var(--gray-500);
    font-size: 0.9375rem;
    margin: 1rem 0 0;
}

.auth-link a {
    color: var(--primary-500);
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s ease;
}

.auth-link a:hover {
    color: var(--primary-600);
}

:global(.dark) .auth-link {
    color: var(--gray-400);
}

:global(.dark) .auth-link a {
    color: var(--primary-300);
}

:global(.dark) .auth-link a:hover {
    color: var(--primary-200);
}

/* Copyright */
.copyright {
    text-align: center;
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.8125rem;
    margin-top: 2rem;
}

/* Transition */
.slide-fade-enter-active {
    transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
    transition: all 0.2s ease-in;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

/* Responsive */
@media (max-width: 480px) {
    .login-page {
        padding: 1rem;
    }

    .login-card {
        padding: 1.75rem;
        border-radius: 1.25rem;
    }

    .logo-img {
        width: 4rem;
        height: 4rem;
    }

    .login-header h2 {
        font-size: 1.375rem;
    }
}
</style>
