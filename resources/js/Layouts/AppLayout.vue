<template>
    <div class="app-wrapper">
        <!-- Desktop Sidebar -->
        <aside class="sidebar">
            <!-- Logo -->
            <div class="sidebar-header">
                <router-link to="/" class="sidebar-brand">
                    <div class="sidebar-logo-icon">
                        <i class="bi bi-briefcase-fill"></i>
                    </div>
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
                    <router-link v-if="isAdmin" to="/clients" class="nav-item" :class="{ active: $route.path === '/clients' }">
                        <i class="bi bi-people-fill"></i>
                        <span>Clients</span>
                    </router-link>
                    <router-link v-if="isAdmin" to="/jobs" class="nav-item" :class="{ active: $route.path === '/jobs' }">
                        <i class="bi bi-briefcase-fill"></i>
                        <span>Jobs</span>
                    </router-link>
                    <router-link v-if="isAdmin" to="/employees" class="nav-item" :class="{ active: $route.path === '/employees' }">
                        <i class="bi bi-person-badge-fill"></i>
                        <span>Employees</span>
                    </router-link>
                </div>

                <div class="nav-section">
                    <span class="nav-section-title">Finance</span>
                    <router-link to="/invoices" class="nav-item" :class="{ active: $route.path === '/invoices' }">
                        <i class="bi bi-receipt"></i>
                        <span>Invoices & Payments</span>
                    </router-link>
                    <router-link to="/expenses" class="nav-item" :class="{ active: $route.path === '/expenses' }">
                        <i class="bi bi-wallet2"></i>
                        <span>Expenses</span>
                    </router-link>
                    <router-link to="/statements" class="nav-item" :class="{ active: $route.path === '/statements' }">
                        <i class="bi bi-file-earmark-text"></i>
                        <span>Statements</span>
                    </router-link>
                </div>

                <!-- Accounting section - only Cost Centres for now -->
                <div class="nav-section">
                    <span class="nav-section-title">Accounting</span>
                    <router-link to="/cost-centres" class="nav-item" :class="{ active: $route.path === '/cost-centres' }">
                        <i class="bi bi-diagram-3-fill"></i>
                        <span>Cost Centres</span>
                    </router-link>
                    <!-- Commented out - future features
                    <router-link to="/chart-of-accounts" class="nav-item" :class="{ active: $route.path === '/chart-of-accounts' }">
                        <i class="bi bi-journal-text"></i>
                        <span>Chart of Accounts</span>
                    </router-link>
                    <router-link to="/journal-entries" class="nav-item" :class="{ active: $route.path === '/journal-entries' }">
                        <i class="bi bi-journal-bookmark-fill"></i>
                        <span>Journal Entries</span>
                    </router-link>
                    <router-link to="/bank-accounts" class="nav-item" :class="{ active: $route.path === '/bank-accounts' }">
                        <i class="bi bi-bank2"></i>
                        <span>Bank Accounts</span>
                    </router-link>
                    -->
                </div>

                <div class="nav-section">
                    <span class="nav-section-title">Reports</span>
                    <router-link to="/aging" class="nav-item" :class="{ active: $route.path === '/aging' }">
                        <i class="bi bi-clock-history"></i>
                        <span>Aging Report</span>
                    </router-link>
                    <router-link to="/reports/employee-jobs" class="nav-item" :class="{ active: $route.path === '/reports/employee-jobs' }">
                        <i class="bi bi-person-lines-fill"></i>
                        <span>Employee Jobs</span>
                    </router-link>
                    <router-link to="/reports/jobs-summary" class="nav-item" :class="{ active: $route.path === '/reports/jobs-summary' }">
                        <i class="bi bi-bar-chart-fill"></i>
                        <span>Jobs Summary</span>
                    </router-link>
                    <router-link to="/reports/revenue" class="nav-item" :class="{ active: $route.path === '/reports/revenue' }">
                        <i class="bi bi-graph-up-arrow"></i>
                        <span>Revenue Report</span>
                    </router-link>
                    <router-link to="/reports/profit-loss" class="nav-item" :class="{ active: $route.path === '/reports/profit-loss' }">
                        <i class="bi bi-calculator-fill"></i>
                        <span>Profit & Loss</span>
                    </router-link>
                </div>
            </nav>

            <!-- Sidebar Footer -->
            <div class="sidebar-footer">
                <router-link v-if="isAdmin" to="/users" class="nav-item" :class="{ active: $route.path === '/users' }">
                    <i class="bi bi-people-fill"></i>
                    <span>Team</span>
                </router-link>
                <router-link v-if="isOwner" to="/settings" class="nav-item" :class="{ active: $route.path === '/settings' }">
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
                <div class="mobile-logo-icon">
                    <i class="bi bi-briefcase-fill"></i>
                </div>
                <span>SiteTrack</span>
            </router-link>
            <div class="mobile-actions">
                <router-link v-if="isOwner" to="/settings" class="mobile-action-btn">
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
            <router-link v-if="isAdmin" to="/clients" class="nav-item" :class="{ active: $route.path === '/clients' }">
                <i class="bi bi-people-fill"></i>
                <span>Clients</span>
            </router-link>
            <router-link v-if="isAdmin" to="/jobs" class="nav-item" :class="{ active: $route.path === '/jobs' }">
                <i class="bi bi-briefcase-fill"></i>
                <span>Jobs</span>
            </router-link>
            <router-link to="/invoices" class="nav-item" :class="{ active: $route.path === '/invoices' }">
                <i class="bi bi-receipt"></i>
                <span>Invoices</span>
            </router-link>
            <router-link to="/reports/employee-jobs" class="nav-item" :class="{ active: $route.path.startsWith('/reports') }">
                <i class="bi bi-bar-chart-fill"></i>
                <span>Reports</span>
            </router-link>
        </nav>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/api';

const router = useRouter();
const user = ref(null);

// Load user from localStorage
onMounted(() => {
    const storedUser = localStorage.getItem('user');
    if (storedUser) {
        try {
            user.value = JSON.parse(storedUser);
        } catch (e) {
            console.error('Failed to parse stored user', e);
        }
    }
});

// Check if user is admin (owner or admin role)
const isAdmin = computed(() => {
    const role = user.value?.role;
    return role === 'owner' || role === 'admin';
});

// Check if user is owner
const isOwner = computed(() => {
    return user.value?.role === 'owner';
});

const logout = async () => {
    try {
        await api.auth.logout();
    } catch (err) {
        // Ignore errors - we're logging out anyway
    } finally {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        localStorage.removeItem('isAuthenticated');
        router.push('/login');
    }
};
</script>

<style scoped>
.app-wrapper {
    min-height: 100vh;
    background: var(--bg-base);
    display: flex;
    /* Stack the mobile header above the content; the desktop sidebar is fixed */
    flex-direction: column;
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
    background: linear-gradient(180deg, var(--primary-950) 0%, var(--primary-900) 100%);
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

.sidebar-logo-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.625rem;
    background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0.1) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: white;
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
    /* Thin, dark scrollbar so the browser default doesn't show a white strip */
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
}

.sidebar-nav::-webkit-scrollbar {
    width: 6px;
}

.sidebar-nav::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 3px;
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
    color: var(--danger-300);
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

.mobile-logo-icon {
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
    background: linear-gradient(135deg, var(--primary-600) 0%, var(--accent-500) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: white;
}

.mobile-brand span {
    font-size: 1.125rem;
    font-weight: 700;
    background: linear-gradient(135deg, var(--primary-600) 0%, var(--accent-500) 100%);
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
    min-width: 0;
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
    background: linear-gradient(90deg, var(--primary-500), var(--accent-500));
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
