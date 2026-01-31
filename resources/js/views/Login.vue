<template>
    <div class="login-page">
        <!-- Background decoration -->
        <div class="bg-decoration">
            <div class="bg-shape bg-shape-1"></div>
            <div class="bg-shape bg-shape-2"></div>
            <div class="bg-shape bg-shape-3"></div>
        </div>

        <div class="login-container">
            <!-- Logo -->
            <div class="logo-section">
                <img src="/images/logo.png" alt="Mount Sinai" class="logo-img" />
            </div>

            <!-- Login Card -->
            <div class="login-card">
                <div class="login-header">
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
                </form>

                <!-- Footer -->
<!--                <div class="login-footer">-->
<!--                    <p>Protected by enterprise-grade security</p>-->
<!--                    <div class="security-badges">-->
<!--                        <i class="bi bi-shield-check"></i>-->
<!--                        <i class="bi bi-lock-fill"></i>-->
<!--                        <i class="bi bi-patch-check-fill"></i>-->
<!--                    </div>-->
<!--                </div>-->
            </div>

            <!-- Copyright -->
            <p class="copyright">
                &copy; {{ new Date().getFullYear() }} Mount Sinai Construction. All rights reserved.
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

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
        await new Promise(resolve => setTimeout(resolve, 1500));

        if (form.email && form.password) {
            localStorage.setItem('isAuthenticated', 'true');
            router.push('/');
        } else {
            error.value = 'Please enter your email and password';
        }
    } catch (err) {
        error.value = 'Invalid email or password. Please try again.';
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
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
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
    background: rgba(255, 255, 255, 0.1);
    animation: float 20s ease-in-out infinite;
}

.bg-shape-1 {
    width: 400px;
    height: 400px;
    top: -100px;
    right: -100px;
    animation-delay: 0s;
}

.bg-shape-2 {
    width: 300px;
    height: 300px;
    bottom: -50px;
    left: -50px;
    animation-delay: -5s;
}

.bg-shape-3 {
    width: 200px;
    height: 200px;
    top: 50%;
    left: 10%;
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
    max-width: 440px;
    position: relative;
    z-index: 1;
}

/* Logo Section */
.logo-section {
    text-align: center;
    margin-bottom: 2rem;
}

.logo-img {
    width: 8rem;
    height: 8rem;
    border-radius: 1.25rem;
    object-fit: contain;
    margin: 0 auto 1.5rem;
    background: white;
    padding: 0.75rem;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

.logo-text {
    font-size: 2rem;
    font-weight: 800;
    color: white;
    letter-spacing: -0.02em;
    margin: 0;
}

.logo-tagline {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9375rem;
    margin-top: 0.25rem;
}

/* Login Card */
.login-card {
    background: white;
    border-radius: 1.5rem;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
    padding: 2.5rem;
    animation: slideUp 0.5s ease-out;
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

.login-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #111827;
    margin: 0 0 0.5rem;
}

.login-header p {
    color: #6b7280;
    font-size: 0.9375rem;
    margin: 0;
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
    color: #374151;
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
    border-color: #667eea;
    background: white;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
}

.form-control:focus + .input-icon,
.input-wrapper:focus-within .input-icon {
    color: #667eea;
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
    color: #4b5563;
    cursor: pointer;
}

.form-checkbox {
    width: 1rem;
    height: 1rem;
    border-radius: 0.25rem;
    border: 1.5px solid #d1d5db;
    cursor: pointer;
    accent-color: #667eea;
}

.forgot-link {
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s ease;
}

.forgot-link:hover {
    color: #5a67d8;
}

/* Login Button */
.btn-login {
    width: 100%;
    padding: 1rem;
    font-size: 1rem;
    font-weight: 600;
    border-radius: 0.75rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.35);
    margin-top: 0.5rem;
}

.btn-login:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
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

/* Footer */
.login-footer {
    text-align: center;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
}

.login-footer p {
    color: #9ca3af;
    font-size: 0.8125rem;
    margin: 0 0 0.75rem;
}

.security-badges {
    display: flex;
    justify-content: center;
    gap: 1rem;
}

.security-badges i {
    font-size: 1.25rem;
    color: #d1d5db;
    transition: color 0.2s ease;
}

.security-badges i:hover {
    color: #667eea;
}

/* Copyright */
.copyright {
    text-align: center;
    color: rgba(255, 255, 255, 0.7);
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

    .logo-icon {
        width: 4rem;
        height: 4rem;
    }

    .logo-icon i {
        font-size: 1.75rem;
    }

    .logo-text {
        font-size: 1.75rem;
    }

    .login-header h2 {
        font-size: 1.375rem;
    }
}
</style>
