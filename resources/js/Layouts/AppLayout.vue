<template>
    <div class="app-wrapper">
        <!-- Desktop Navigation -->
        <nav class="navbar desktop-nav">
            <div class="navbar-container">
                <div class="navbar-inner">
                    <!-- Logo -->
                    <router-link to="/" class="navbar-brand">
                        <img src="/images/logo.png" alt="SiteTrack" class="logo-img" />
                        <span class="brand-text">SiteTrack</span>
                    </router-link>

                    <!-- Desktop Nav Links -->
                    <div class="nav-links">
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
                        <router-link to="/statements" class="nav-link" :class="{ active: $route.path === '/statements' }">
                            <i class="bi bi-file-earmark-text"></i>
                            <span>Statements</span>
                        </router-link>
                    </div>

                    <!-- Right Actions -->
                    <div class="nav-actions">
                        <router-link to="/settings" class="nav-action-btn" :class="{ active: $route.path === '/settings' }" title="Settings">
                            <i class="bi bi-gear-fill"></i>
                        </router-link>
                        <button @click="logout" class="nav-action-btn logout-btn" title="Logout">
                            <i class="bi bi-box-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Mobile Header -->
        <header class="mobile-header">
            <router-link to="/" class="mobile-brand">
                <img src="/images/logo.png" alt="SiteTrack" class="mobile-logo" />
                <span>SiteTrack</span>
            </router-link>
            <div class="mobile-actions">
                <router-link to="/settings" class="mobile-action-btn">
                    <i class="bi bi-gear-fill"></i>
                </router-link>
                <button @click="logout" class="mobile-action-btn">
                    <i class="bi bi-box-arrow-right"></i>
                </button>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-wrapper">
                <slot></slot>
            </div>
        </main>

        <!-- Mobile Bottom Navigation -->
        <nav class="mobile-bottom-nav">
            <router-link to="/" class="nav-item" :class="{ active: $route.path === '/' }">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Home</span>
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
            <router-link to="/payments" class="nav-item" :class="{ active: $route.path === '/payments' }">
                <i class="bi bi-credit-card-fill"></i>
                <span>Payments</span>
            </router-link>
        </nav>
    </div>
</template>

<script setup>
import { useRouter } from 'vue-router';

const router = useRouter();

const logout = () => {
    localStorage.removeItem('isAuthenticated');
    router.push('/login');
};
</script>

<style scoped>
.app-wrapper {
    min-height: 100vh;
    background: var(--bg-base);
}

/* ==========================================
   DESKTOP NAVIGATION
   ========================================== */

.desktop-nav {
    display: none;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--border-light);
    position: sticky;
    top: 0;
    z-index: 100;
}

@media (min-width: 768px) {
    .desktop-nav {
        display: block;
    }
}

.navbar-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

.navbar-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 4rem;
    gap: 2rem;
}

.navbar-brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    flex-shrink: 0;
}

.logo-img {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.625rem;
    object-fit: contain;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.brand-text {
    font-size: 1.25rem;
    font-weight: 700;
    background: linear-gradient(135deg, var(--primary-600) 0%, #8b5cf6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    flex: 1;
    justify-content: center;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-muted);
    text-decoration: none;
    border-radius: 0.625rem;
    transition: all 0.2s ease;
    position: relative;
}

.nav-link i {
    font-size: 1rem;
}

.nav-link:hover {
    color: var(--primary-600);
    background: var(--primary-50);
}

.nav-link.active {
    color: var(--primary-600);
    background: var(--primary-50);
}

.nav-link.active::after {
    content: '';
    position: absolute;
    bottom: 0.25rem;
    left: 50%;
    transform: translateX(-50%);
    width: 1.5rem;
    height: 3px;
    background: linear-gradient(90deg, var(--primary-500), #8b5cf6);
    border-radius: 2px;
}

.nav-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.nav-action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.625rem;
    background: transparent;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}

.nav-action-btn:hover,
.nav-action-btn.active {
    background: var(--gray-100);
    color: var(--primary-600);
}

.nav-action-btn i {
    font-size: 1.125rem;
}

.logout-btn:hover {
    background: var(--error-light);
    color: var(--error-dark);
}

/* ==========================================
   MOBILE HEADER
   ========================================== */

.mobile-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--border-light);
    position: sticky;
    top: 0;
    z-index: 100;
}

@media (min-width: 768px) {
    .mobile-header {
        display: none;
    }
}

.mobile-brand {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    text-decoration: none;
}

.mobile-logo {
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
    object-fit: contain;
}

.mobile-brand span {
    font-size: 1.125rem;
    font-weight: 700;
    background: linear-gradient(135deg, var(--primary-600) 0%, #8b5cf6 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.mobile-actions {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.mobile-action-btn {
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
    text-decoration: none;
}

.mobile-action-btn:hover {
    background: var(--gray-100);
    color: var(--primary-600);
}

/* ==========================================
   MAIN CONTENT
   ========================================== */

.main-content {
    padding: 1.5rem 1rem;
    padding-bottom: 6rem;
    min-height: calc(100vh - 4rem);
}

@media (min-width: 768px) {
    .main-content {
        padding: 2rem 1.5rem;
        padding-bottom: 2rem;
    }
}

.content-wrapper {
    max-width: 1200px;
    margin: 0 auto;
}

/* ==========================================
   MOBILE BOTTOM NAVIGATION
   ========================================== */

.mobile-bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-top: 1px solid var(--border-light);
    display: flex;
    justify-content: space-around;
    padding: 0.5rem 0;
    padding-bottom: calc(0.5rem + env(safe-area-inset-bottom));
    z-index: 1000;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
}

@media (min-width: 768px) {
    .mobile-bottom-nav {
        display: none;
    }
}

.mobile-bottom-nav .nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    padding: 0.5rem 0.75rem;
    color: var(--text-muted);
    font-size: 0.625rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    border-radius: 0.75rem;
    position: relative;
}

.mobile-bottom-nav .nav-item i {
    font-size: 1.25rem;
    transition: transform 0.2s ease;
}

.mobile-bottom-nav .nav-item:active i {
    transform: scale(0.9);
}

.mobile-bottom-nav .nav-item.active {
    color: var(--primary-600);
}

.mobile-bottom-nav .nav-item.active::before {
    content: '';
    position: absolute;
    top: -0.5rem;
    left: 50%;
    transform: translateX(-50%);
    width: 2rem;
    height: 3px;
    background: linear-gradient(90deg, var(--primary-500), #8b5cf6);
    border-radius: 2px;
}

/* ==========================================
   PRINT STYLES
   ========================================== */

@media print {
    .desktop-nav,
    .mobile-header,
    .mobile-bottom-nav {
        display: none !important;
    }

    .app-wrapper {
        background: white !important;
    }

    .main-content {
        padding: 0 !important;
    }
}
</style>
