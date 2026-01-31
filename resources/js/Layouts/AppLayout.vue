<template>
    <div class="min-h-screen app-wrapper">
        <!-- Navigation -->
        <nav class="navbar">
            <div class="navbar-container">
                <div class="navbar-inner">
                    <div class="flex items-center">
                        <!-- Logo -->
                        <router-link to="/" class="navbar-brand">
                            <img src="/images/logo.png" alt="Mount Sinai Construction" class="logo-img" />
                        </router-link>

                        <!-- Desktop Nav Links -->
                        <div class="hidden md:flex items-center ml-8 space-x-1">
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
                            <router-link to="/settings" class="nav-link" :class="{ active: $route.path === '/settings' }">
                                <i class="bi bi-gear-fill"></i>
                                <span>Settings</span>
                            </router-link>
                        </div>
                    </div>

                    <!-- Right Side Actions -->
                    <div class="flex items-center gap-2">
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
    background: var(--bg-base);
    min-height: 100vh;
}

.main-content {
    padding: 1rem;
    display: flex;
    justify-content: center;
}

@media (min-width: 768px) {
    .main-content {
        padding: 1.5rem;
    }
}

.content-wrapper {
    width: 100%;
    max-width: 1100px;
}

.navbar {
    background: white;
    border-bottom: 1px solid var(--border-light);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    position: sticky;
    top: 0;
    z-index: 100;
}

.navbar-container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 1rem;
}

.navbar-inner {
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: 3rem;
}

.navbar-brand {
    display: flex;
    align-items: center;
    text-decoration: none;
}

.logo-img {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.25rem;
    object-fit: contain;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.375rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--text-muted);
    border-radius: 0.375rem;
    text-decoration: none;
    transition: all 0.15s ease;
}

.nav-link i {
    font-size: 0.875rem;
}

.nav-link:hover {
    color: var(--primary-500);
    background: rgba(99, 102, 241, 0.1);
}

.nav-link.active {
    color: var(--primary-500);
    background: rgba(99, 102, 241, 0.15);
    font-weight: 600;
}

.btn-logout {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    border-radius: 0.375rem;
}

@media print {
    .navbar {
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
