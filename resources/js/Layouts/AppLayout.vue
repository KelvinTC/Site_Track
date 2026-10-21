<template>
    <div class="app-wrapper">
        <!-- Desktop Sidebar -->
        <aside class="sidebar">
            <!-- Logo -->
            <div class="sidebar-header">
                <router-link to="/" class="sidebar-brand">
                    <img src="/images/logo.png" alt="SiteTrack" class="sidebar-logo" />
                    <span class="brand-text">SiteTrack</span>
                </router-link>
            </div>

            <!-- Navigation -->
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <span class="nav-section-title">Menu</span>
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
                    <router-link to="/casuals" class="nav-item" :class="{ active: $route.path === '/casuals' }">
                        <i class="bi bi-person-badge-fill"></i>
                        <span>Casuals</span>
                    </router-link>
                </div>

                <div class="nav-section">
                    <span class="nav-section-title">Finance</span>
                    <router-link to="/invoices" class="nav-item" :class="{ active: $route.path === '/invoices' }">
                        <i class="bi bi-receipt"></i>
                        <span>Invoices</span>
                    </router-link>
                    <router-link to="/payments" class="nav-item" :class="{ active: $route.path === '/payments' }">
                        <i class="bi bi-credit-card-fill"></i>
                        <span>Payments</span>
                    </router-link>
                    <router-link to="/statements" class="nav-item" :class="{ active: $route.path === '/statements' }">
                        <i class="bi bi-file-earmark-text"></i>
                        <span>Statements</span>
                    </router-link>
                </div>
            </nav>

            <!-- Sidebar Footer -->
            <div class="sidebar-footer">
                <router-link to="/settings" class="nav-item" :class="{ active: $route.path === '/settings' }">
                    <i class="bi bi-gear-fill"></i>
                    <span>Settings</span>
                </router-link>
                <button @click="logout" class="nav-item logout-btn">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </button>
            </div>
        </aside>

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
    display: flex;
}

/* ==========================================
   SIDEBAR
   ========================================== */

.sidebar {
    display: none;
    width: 260px;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    background: linear-gradient(180deg, #1e1b4b 0%, #312e81 100%);
    flex-direction: column;
    z-index: 100;
    box-shadow: 4px 0 24px rgba(0, 0, 0, 0.15);
}

@media (min-width: 1024px) {
    .sidebar {
        display: flex;
    }
}

.sidebar-header {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
}

.sidebar-logo {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 0.75rem;
    object-fit: contain;
    background: white;
    padding: 0.25rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.sidebar .brand-text {
    font-size: 1.375rem;
    font-weight: 800;
    color: white;
    letter-spacing: -0.02em;
}

.sidebar-nav {
    flex: 1;
    padding: 1rem 0;
    overflow-y: auto;
}

.nav-section {
    padding: 0.5rem 1rem;
}

.nav-section-title {
    display: block;
    font-size: 0.6875rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: rgba(255, 255, 255, 0.4);
    padding: 0.5rem 0.75rem;
    margin-bottom: 0.25rem;
}

.sidebar .nav-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    font-size: 0.9375rem;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    border-radius: 0.625rem;
    transition: all 0.2s ease;
    margin-bottom: 0.25rem;
    border: none;
    background: none;
    width: 100%;
    cursor: pointer;
    text-align: left;
}

.sidebar .nav-item i {
    font-size: 1.125rem;
    width: 1.5rem;
    text-align: center;
}

.sidebar .nav-item:hover {
    color: white;
    background: rgba(255, 255, 255, 0.1);
}

.sidebar .nav-item.active {
    color: white;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.5) 0%, rgba(139, 92, 246, 0.5) 100%);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.sidebar-footer {
    padding: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar .logout-btn:hover {
    background: rgba(239, 68, 68, 0.2);
    color: #fca5a5;
}

/* ==========================================
   MOBILE HEADER
   ========================================== */

.mobile-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--border-light);
    position: sticky;
    top: 0;
    z-index: 100;
}

@media (min-width: 1024px) {
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
    flex: 1;
    padding: 1.5rem 1rem;
    padding-bottom: 6rem;
    min-height: 100vh;
}

@media (min-width: 1024px) {
    .main-content {
        margin-left: 260px;
        padding: 2rem 2.5rem;
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

@media (min-width: 1024px) {
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
    .sidebar,
    .mobile-header,
    .mobile-bottom-nav {
        display: none !important;
    }

    .app-wrapper {
        background: white !important;
    }

    .main-content {
        margin-left: 0 !important;
        padding: 0 !important;
    }
}
</style>
