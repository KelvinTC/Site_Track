<template>
    <div class="min-h-screen app-wrapper">
        <!-- Premium Navigation -->
        <nav class="navbar">
            <div class="navbar-container">
                <div class="navbar-inner">
                    <div class="flex items-center">
                        <!-- Logo -->
                        <router-link to="/" class="navbar-brand">
                            <img src="/images/logo.png" alt="Mount Sinai Construction" class="logo-img" />
                        </router-link>

                        <!-- Desktop Nav Links -->
                        <div class="hidden md:flex items-center ml-10 space-x-1">
                            <router-link to="/" class="nav-link" :class="{ active: $route.path === '/' }">
                                <i class="bi bi-grid-1x2-fill"></i>
                                <span>Dashboard</span>
                            </router-link>
                            <router-link to="/customers" class="nav-link" :class="{ active: $route.path === '/customers' }">
                                <i class="bi bi-people-fill"></i>
                                <span>Customers</span>
                            </router-link>
                            <router-link to="/jobs" class="nav-link" :class="{ active: $route.path === '/jobs' }">
                                <i class="bi bi-briefcase-fill"></i>
                                <span>Jobs</span>
                            </router-link>
                            <router-link to="/invoices" class="nav-link" :class="{ active: $route.path === '/invoices' }">
                                <i class="bi bi-receipt"></i>
                                <span>Invoices</span>
                            </router-link>
                            <router-link to="/payments" class="nav-link" :class="{ active: $route.path === '/payments' }">
                                <i class="bi bi-credit-card-fill"></i>
                                <span>Payments</span>
                            </router-link>
                        </div>
                    </div>

                    <!-- Right Side Actions -->
                    <div class="flex items-center gap-2">
                        <!-- Dark Mode Toggle -->
                        <button @click="toggleDarkMode" class="dark-mode-toggle" :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'">
                            <i :class="isDark ? 'bi bi-sun-fill' : 'bi bi-moon-fill'"></i>
                        </button>

                        <!-- User Menu -->
                        <div class="hidden sm:flex items-center gap-2 mr-2">
                            <div class="user-avatar">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <span class="text-sm font-medium username-text">Admin</span>
                        </div>

                        <button @click="logout" class="btn btn-primary btn-logout">
                            <i class="bi bi-box-arrow-right"></i>
                            <span class="hidden sm:inline">Logout</span>
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="main-content">
            <div class="content-wrapper">
                <slot></slot>
            </div>
        </main>

        <!-- Premium Mobile Bottom Nav -->
        <nav class="mobile-bottom-nav md:hidden">
            <router-link to="/" class="nav-item" :class="{ active: $route.path === '/' }">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </router-link>
            <router-link to="/customers" class="nav-item" :class="{ active: $route.path === '/customers' }">
                <i class="bi bi-people-fill"></i>
                <span>Customers</span>
            </router-link>
            <router-link to="/jobs" class="nav-item" :class="{ active: $route.path === '/jobs' }">
                <i class="bi bi-briefcase-fill"></i>
                <span>Jobs</span>
            </router-link>
            <router-link to="/invoices" class="nav-item" :class="{ active: $route.path === '/invoices' }">
                <i class="bi bi-receipt"></i>
                <span>Invoices</span>
            </router-link>
        </nav>

        <!-- Mobile padding for bottom nav -->
        <div class="md:hidden h-20"></div>
    </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { useDarkMode } from '@/composables/useDarkMode';

const router = useRouter();
const { isDark, toggleDarkMode, initTheme } = useDarkMode();

// Initialize theme on mount
initTheme();

const logout = () => {
    localStorage.removeItem('isAuthenticated');
    router.push('/login');
};
</script>

<style scoped>
.app-wrapper {
    background: var(--bg-base);
    min-height: 100vh;
    transition: background var(--transition-slow);
}

/* Main Content Area */
.main-content {
    padding: 2rem 1.5rem 3rem;
    display: flex;
    justify-content: center;
}

@media (min-width: 768px) {
    .main-content {
        padding: 2.5rem 2rem 4rem;
    }
}

@media (min-width: 1024px) {
    .main-content {
        padding: 3rem 3rem 5rem;
    }
}

.content-wrapper {
    width: 100%;
    max-width: 1100px;
}

/* Premium Navbar */
.navbar {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--glass-border);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04), 0 4px 12px rgba(0, 0, 0, 0.03);
    position: sticky;
    top: 0;
    z-index: 100;
    transition: background var(--transition-slow), border-color var(--transition-slow);
}

.navbar-container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

@media (min-width: 768px) {
    .navbar-container {
        padding: 0 2rem;
    }
}

@media (min-width: 1024px) {
    .navbar-container {
        padding: 0 3rem;
    }
}

.navbar-inner {
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: 4rem;
}

/* Logo */
.navbar-brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    font-weight: 800;
    font-size: 1.375rem;
    letter-spacing: -0.02em;
}

.navbar-brand span {
    background: linear-gradient(135deg, #4f46e5 0%, #8b5cf6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.logo-img {
    width: 3.5rem;
    height: 3.5rem;
    border-radius: 0.5rem;
    object-fit: contain;
}

/* Navigation Links */
.nav-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    font-size: 0.9375rem;
    font-weight: 500;
    color: var(--text-muted);
    border-radius: 0.75rem;
    text-decoration: none;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.nav-link i {
    font-size: 1rem;
    transition: transform 0.2s ease;
}

.nav-link:hover {
    color: var(--primary-500);
    background: rgba(99, 102, 241, 0.1);
}

.nav-link:hover i {
    transform: scale(1.1);
}

.nav-link.active {
    color: var(--primary-500);
    background: rgba(99, 102, 241, 0.15);
    font-weight: 600;
}

.nav-link.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 50%;
    height: 2px;
    background: linear-gradient(90deg, var(--primary-500), var(--accent-violet));
    border-radius: 999px;
}

/* User Avatar */
.user-avatar {
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
    background: linear-gradient(135deg, var(--primary-100) 0%, var(--primary-200) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-500);
    font-size: 0.875rem;
}

.username-text {
    color: var(--text-secondary);
}

/* Logout Button */
.btn-logout {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    border-radius: 0.75rem;
}

/* Premium Mobile Bottom Nav */
.mobile-bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-top: 1px solid var(--glass-border);
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.06);
    z-index: 1000;
    display: flex;
    justify-content: space-around;
    padding: 0.5rem 0.25rem;
    padding-bottom: calc(0.5rem + env(safe-area-inset-bottom));
    transition: background var(--transition-slow), border-color var(--transition-slow);
}

.mobile-bottom-nav .nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.5rem 0.75rem;
    color: var(--text-muted);
    font-size: 0.625rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    border-radius: 0.75rem;
    min-width: 4rem;
    position: relative;
}

.mobile-bottom-nav .nav-item i {
    font-size: 1.25rem;
    margin-bottom: 0.25rem;
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.mobile-bottom-nav .nav-item span {
    letter-spacing: 0.01em;
}

.mobile-bottom-nav .nav-item:active i {
    transform: scale(0.9);
}

.mobile-bottom-nav .nav-item.active {
    color: var(--primary-500);
    background: rgba(99, 102, 241, 0.15);
}

.mobile-bottom-nav .nav-item.active::before {
    content: '';
    position: absolute;
    top: -0.5rem;
    left: 50%;
    transform: translateX(-50%);
    width: 2.5rem;
    height: 3px;
    background: linear-gradient(90deg, var(--primary-500), var(--accent-violet));
    border-radius: 999px;
}

.mobile-bottom-nav .nav-item.active i {
    transform: scale(1.1);
}

/* Dark Mode Toggle */
.dark-mode-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.5rem;
    background: transparent;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.2s ease;
}

.dark-mode-toggle:hover {
    background: var(--border-light);
    color: var(--text-primary);
}

.dark-mode-toggle i {
    font-size: 1.125rem;
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.dark-mode-toggle:hover i {
    transform: rotate(15deg);
}

/* Print Styles */
@media print {
    .navbar,
    .mobile-bottom-nav,
    .dark-mode-toggle {
        display: none !important;
    }

    .app-wrapper {
        background: white !important;
    }

    .main-content {
        padding: 0 !important;
    }

    .content-wrapper {
        max-width: 100% !important;
    }
}
</style>
