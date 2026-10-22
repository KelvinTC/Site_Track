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
            <!-- Register Card -->
            <div class="login-card">
                <div class="login-header">
                    <div class="app-logo">
                        <i class="bi bi-briefcase-fill"></i>
                    </div>
                    <h2>Create your account</h2>
                    <p>Get started with your business</p>
                </div>

                <!-- Error Message -->
                <Transition name="slide-fade">
                    <div v-if="error" class="alert alert-danger">
                        <i class="bi bi-exclamation-circle"></i>
                        <span>{{ error }}</span>
                    </div>
                </Transition>

                <!-- Success Message -->
                <Transition name="slide-fade">
                    <div v-if="success" class="alert alert-success">
                        <i class="bi bi-check-circle"></i>
                        <span>{{ success }}</span>
                    </div>
                </Transition>

                <form @submit.prevent="handleRegister" class="login-form">
                    <!-- Two column row -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name" class="form-label">Your Name</label>
                            <div class="input-wrapper">
                                <i class="bi bi-person input-icon"></i>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="form-control with-icon"
                                    placeholder="John Doe"
                                    required
                                />
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="company_name" class="form-label">Company</label>
                            <div class="input-wrapper">
                                <i class="bi bi-building input-icon"></i>
                                <input
                                    id="company_name"
                                    v-model="form.company_name"
                                    type="text"
                                    class="form-control with-icon"
                                    placeholder="Company Ltd"
                                    required
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Email Field -->
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
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

                    <!-- Password Row -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-wrapper">
                                <i class="bi bi-lock input-icon"></i>
                                <input
                                    id="password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    class="form-control with-icon"
                                    placeholder="Min. 8 chars"
                                    required
                                    minlength="8"
                                    autocomplete="new-password"
                                />
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation" class="form-label">Confirm</label>
                            <div class="input-wrapper">
                                <i class="bi bi-lock-fill input-icon"></i>
                                <input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    :type="showPassword ? 'text' : 'password'"
                                    class="form-control with-icon"
                                    placeholder="Confirm"
                                    required
                                    autocomplete="new-password"
                                />
                            </div>
                        </div>
                    </div>

                    <div v-if="form.password && form.password_confirmation && form.password !== form.password_confirmation" class="field-error">
                        Passwords do not match
                    </div>

                    <!-- Show password toggle -->
                    <label class="checkbox-label">
                        <input type="checkbox" v-model="showPassword" class="form-checkbox" />
                        <span>Show passwords</span>
                    </label>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary btn-login" :disabled="loading || !isFormValid">
                        <span v-if="loading" class="spinner"></span>
                        <span v-else class="btn-text">
                            <span>Create Account</span>
                            <i class="bi bi-arrow-right"></i>
                        </span>
                    </button>

                    <!-- Login Link -->
                    <p class="auth-link">
                        Already have an account?
                        <router-link to="/login">Sign in</router-link>
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
import { ref, reactive, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useDarkMode } from '@/composables/useDarkMode';
import api from '@/api';

const router = useRouter();
const { isDark, toggleDarkMode, initTheme } = useDarkMode();

// Initialize theme on mount
initTheme();

const form = reactive({
    company_name: '',
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const loading = ref(false);
const error = ref('');
const success = ref('');
const showPassword = ref(false);

const isFormValid = computed(() => {
    return form.company_name &&
        form.name &&
        form.email &&
        form.password &&
        form.password.length >= 8 &&
        form.password === form.password_confirmation;
});

const handleRegister = async () => {
    if (!isFormValid.value) {
        error.value = 'Please fill in all required fields correctly';
        return;
    }

    loading.value = true;
    error.value = '';
    success.value = '';

    try {
        const response = await api.auth.register(form);

        // Store auth data
        localStorage.setItem('token', response.data.token);
        localStorage.setItem('user', JSON.stringify(response.data.user));
        localStorage.setItem('isAuthenticated', 'true');

        success.value = 'Account created successfully! Redirecting...';

        // Redirect to dashboard
        setTimeout(() => {
            router.push('/');
        }, 1000);
    } catch (err) {
        if (err.response?.data?.errors) {
            // Laravel validation errors
            const errors = err.response.data.errors;
            error.value = Object.values(errors).flat().join('. ');
        } else if (err.response?.data?.message) {
            error.value = err.response.data.message;
        } else {
            error.value = 'Registration failed. Please try again.';
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
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
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
    background: #1f1f23;
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
    margin-bottom: 1.25rem;
}

.app-logo {
    width: 3.5rem;
    height: 3.5rem;
    margin: 0 auto 1rem;
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    border-radius: 0.875rem;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 24px rgba(79, 70, 229, 0.3);
}

.app-logo i {
    font-size: 1.5rem;
    color: white;
}

.login-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #111827;
    margin: 0 0 0.5rem;
    transition: color 0.3s ease;
}

:global(.dark) .login-header h2 {
    color: #fafafa;
}

.login-header p {
    color: #6b7280;
    font-size: 0.9375rem;
    margin: 0;
    transition: color 0.3s ease;
}

:global(.dark) .login-header p {
    color: #a1a1aa;
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
    background: linear-gradient(135deg, #fee2e2 0%, #fef2f2 100%);
    border: 1px solid #fecaca;
    color: #991b1b;
}

.alert-success {
    background: linear-gradient(135deg, #d1fae5 0%, #ecfdf5 100%);
    border: 1px solid #a7f3d0;
    color: #065f46;
}

.alert i {
    font-size: 1.25rem;
    flex-shrink: 0;
}

/* Form */
.login-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
}

@media (max-width: 480px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.form-label {
    font-weight: 600;
    font-size: 0.875rem;
    color: #374151;
    transition: color 0.3s ease;
}

.form-label .optional {
    font-weight: 400;
    color: #9ca3af;
}

:global(.dark) .form-label {
    color: #e4e4e7;
}

:global(.dark) .form-label .optional {
    color: #71717a;
}

.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.input-icon {
    position: absolute;
    left: 1rem;
    color: #9ca3af;
    font-size: 1.125rem;
    pointer-events: none;
    transition: color 0.2s ease;
}

.form-control {
    width: 100%;
    padding: 0.875rem 1rem;
    font-size: 0.9375rem;
    color: #111827;
    background: #f9fafb;
    border: 1.5px solid #e5e7eb;
    border-radius: 0.75rem;
    transition: all 0.2s ease;
}

:global(.dark) .form-control {
    color: #fafafa;
    background: #27272a;
    border-color: #3f3f46;
}

:global(.dark) .form-control::placeholder {
    color: #71717a;
}

:global(.dark) .form-control:hover {
    border-color: #52525b;
}

:global(.dark) .form-control:focus {
    background: #1f1f23;
    border-color: #818cf8;
    box-shadow: 0 0 0 4px rgba(129, 140, 248, 0.2);
}

.form-control.with-icon {
    padding-left: 3rem;
}

.form-control::placeholder {
    color: #9ca3af;
}

.form-control:hover {
    border-color: #d1d5db;
}

.form-control:focus {
    outline: none;
    border-color: #6366f1;
    background: white;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
}

.form-control:focus + .input-icon,
.input-wrapper:focus-within .input-icon {
    color: #6366f1;
}

.toggle-password {
    position: absolute;
    right: 1rem;
    background: none;
    border: none;
    color: #9ca3af;
    cursor: pointer;
    padding: 0.25rem;
    font-size: 1.125rem;
    transition: color 0.2s ease;
}

.toggle-password:hover {
    color: #6b7280;
}

:global(.dark) .toggle-password {
    color: #71717a;
}

:global(.dark) .toggle-password:hover {
    color: #a1a1aa;
}

:global(.dark) .input-icon {
    color: #71717a;
}

:global(.dark) .input-wrapper:focus-within .input-icon {
    color: #a5b4fc;
}

.field-error {
    color: #dc2626;
    font-size: 0.8125rem;
    margin: 0;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #6b7280;
    font-size: 0.875rem;
    cursor: pointer;
}

:global(.dark) .checkbox-label {
    color: #a1a1aa;
}

.form-checkbox {
    width: 1rem;
    height: 1rem;
    border-radius: 0.25rem;
    border: 1.5px solid #d1d5db;
    cursor: pointer;
    accent-color: #6366f1;
}

:global(.dark) .form-checkbox {
    border-color: #52525b;
    background: #27272a;
}

/* Login Button */
.btn-login {
    width: 100%;
    padding: 1rem;
    font-size: 1rem;
    font-weight: 600;
    border-radius: 0.75rem;
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
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
    color: #6b7280;
    font-size: 0.9375rem;
    margin: 1rem 0 0;
}

.auth-link a {
    color: #6366f1;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s ease;
}

.auth-link a:hover {
    color: #4f46e5;
}

:global(.dark) .auth-link {
    color: #a1a1aa;
}

:global(.dark) .auth-link a {
    color: #a5b4fc;
}

:global(.dark) .auth-link a:hover {
    color: #c7d2fe;
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
