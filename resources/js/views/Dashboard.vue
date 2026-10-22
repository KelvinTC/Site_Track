<template>
    <AppLayout>
        <!-- Page Header -->
        <div class="dashboard-header">
            <div class="header-content">
                <div class="greeting">
                    <h1 class="greeting-title">{{ greeting }}</h1>
                    <p class="greeting-subtitle">Here's what's happening with your business today</p>
                </div>
                <div class="header-actions">
                    <button @click="refreshData" class="btn btn-secondary" :disabled="loading">
                        <i class="bi bi-arrow-clockwise" :class="{ 'spin': loading }"></i>
                        <span>Refresh</span>
                    </button>
                    <router-link to="/jobs" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i>
                        <span>New Job</span>
                    </router-link>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-primary">
                    <i class="bi bi-briefcase-fill"></i>
                </div>
                <div class="stat-content">
                    <p class="stat-value">{{ stats.activeJobs }}</p>
                    <p class="stat-label">Active Jobs</p>
                </div>
                <div class="stat-trend trend-up" v-if="stats.activeJobs > 0">
                    <i class="bi bi-arrow-up-right"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-warning">
                    <i class="bi bi-receipt"></i>
                </div>
                <div class="stat-content">
                    <p class="stat-value">{{ stats.unpaidInvoices }}</p>
                    <p class="stat-label">Unpaid Invoices</p>
                </div>
                <div class="stat-trend trend-warning" v-if="stats.unpaidInvoices > 0">
                    <i class="bi bi-exclamation-circle"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-success">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="stat-content">
                    <p class="stat-value">${{ formatNumber(stats.totalRevenue) }}</p>
                    <p class="stat-label">Total Revenue</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-danger">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <div class="stat-content">
                    <p class="stat-value">${{ formatNumber(stats.outstanding) }}</p>
                    <p class="stat-label">Outstanding</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2 class="section-title">Quick Actions</h2>
            <div class="actions-grid">
                <router-link to="/customers" class="action-card">
                    <div class="action-icon" style="background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); color: #4f46e5;">
                        <i class="bi bi-person-plus-fill"></i>
                    </div>
                    <span class="action-label">Add Customer</span>
                </router-link>
                <router-link to="/jobs" class="action-card">
                    <div class="action-icon" style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); color: #059669;">
                        <i class="bi bi-briefcase-fill"></i>
                    </div>
                    <span class="action-label">Create Job</span>
                </router-link>
                <router-link to="/invoices" class="action-card">
                    <div class="action-icon" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #d97706;">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <span class="action-label">New Invoice</span>
                </router-link>
                <router-link to="/payments" class="action-card">
                    <div class="action-icon" style="background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%); color: #7c3aed;">
                        <i class="bi bi-credit-card-fill"></i>
                    </div>
                    <span class="action-label">Record Payment</span>
                </router-link>
            </div>
        </div>

        <!-- Aging Summary -->
        <div v-if="agingData.total_outstanding > 0" class="aging-widget">
            <div class="aging-widget-header">
                <h2 class="section-title">Receivables Aging</h2>
                <router-link to="/aging" class="view-all-link">
                    View Report
                    <i class="bi bi-arrow-right"></i>
                </router-link>
            </div>
            <div class="aging-bars">
                <div class="aging-bar-item">
                    <div class="aging-bar-header">
                        <span class="aging-label">Current</span>
                        <span class="aging-value">${{ formatNumber(agingData.summary?.current?.total || 0) }}</span>
                    </div>
                    <div class="aging-bar-track">
                        <div class="aging-bar-fill current" :style="{ width: getAgingPercent('current') + '%' }"></div>
                    </div>
                </div>
                <div class="aging-bar-item">
                    <div class="aging-bar-header">
                        <span class="aging-label">1-30 Days</span>
                        <span class="aging-value">${{ formatNumber(agingData.summary?.['1-30']?.total || 0) }}</span>
                    </div>
                    <div class="aging-bar-track">
                        <div class="aging-bar-fill days-30" :style="{ width: getAgingPercent('1-30') + '%' }"></div>
                    </div>
                </div>
                <div class="aging-bar-item">
                    <div class="aging-bar-header">
                        <span class="aging-label">31-60 Days</span>
                        <span class="aging-value">${{ formatNumber(agingData.summary?.['31-60']?.total || 0) }}</span>
                    </div>
                    <div class="aging-bar-track">
                        <div class="aging-bar-fill days-60" :style="{ width: getAgingPercent('31-60') + '%' }"></div>
                    </div>
                </div>
                <div class="aging-bar-item">
                    <div class="aging-bar-header">
                        <span class="aging-label">61-90 Days</span>
                        <span class="aging-value">${{ formatNumber(agingData.summary?.['61-90']?.total || 0) }}</span>
                    </div>
                    <div class="aging-bar-track">
                        <div class="aging-bar-fill days-90" :style="{ width: getAgingPercent('61-90') + '%' }"></div>
                    </div>
                </div>
                <div class="aging-bar-item">
                    <div class="aging-bar-header">
                        <span class="aging-label">90+ Days</span>
                        <span class="aging-value danger">${{ formatNumber(agingData.summary?.['90+']?.total || 0) }}</span>
                    </div>
                    <div class="aging-bar-track">
                        <div class="aging-bar-fill days-90plus" :style="{ width: getAgingPercent('90+') + '%' }"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="dashboard-grid">
            <!-- Recent Jobs -->
            <div class="card">
                <div class="card-header">
                    <div class="header-left">
                        <i class="bi bi-briefcase-fill"></i>
                        <span>Recent Jobs</span>
                    </div>
                    <router-link to="/jobs" class="view-all-link">
                        View All
                        <i class="bi bi-arrow-right"></i>
                    </router-link>
                </div>
                <div class="card-body p-0">
                    <div v-if="loading" class="loading-state">
                        <div class="skeleton-row" v-for="i in 4" :key="i">
                            <div class="skeleton skeleton-avatar"></div>
                            <div class="skeleton-content">
                                <div class="skeleton skeleton-title"></div>
                                <div class="skeleton skeleton-text"></div>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="recentJobs.length === 0" class="empty-state-small">
                        <i class="bi bi-briefcase"></i>
                        <p>No jobs yet</p>
                        <router-link to="/jobs" class="btn btn-sm btn-primary">Create First Job</router-link>
                    </div>
                    <div v-else class="list-items">
                        <div v-for="job in recentJobs" :key="job.id" class="list-item">
                            <div class="item-avatar">
                                {{ getInitials(job.customer?.name) }}
                            </div>
                            <div class="item-info">
                                <p class="item-title">{{ job.title }}</p>
                                <p class="item-subtitle">{{ job.customer?.name || 'No customer' }}</p>
                            </div>
                            <span :class="['item-badge', getStatusClass(job.status)]">
                                {{ formatStatus(job.status) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Unpaid Invoices -->
            <div class="card">
                <div class="card-header">
                    <div class="header-left">
                        <i class="bi bi-exclamation-triangle-fill" style="color: #d97706;"></i>
                        <span>Unpaid Invoices</span>
                    </div>
                    <router-link to="/invoices" class="view-all-link">
                        View All
                        <i class="bi bi-arrow-right"></i>
                    </router-link>
                </div>
                <div class="card-body p-0">
                    <div v-if="loading" class="loading-state">
                        <div class="skeleton-row" v-for="i in 4" :key="i">
                            <div class="skeleton skeleton-avatar"></div>
                            <div class="skeleton-content">
                                <div class="skeleton skeleton-title"></div>
                                <div class="skeleton skeleton-text"></div>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="unpaidInvoices.length === 0" class="empty-state-small success">
                        <i class="bi bi-check-circle-fill"></i>
                        <p>All invoices paid!</p>
                    </div>
                    <div v-else class="list-items">
                        <div v-for="invoice in unpaidInvoices" :key="invoice.id" class="list-item">
                            <div class="item-info">
                                <p class="item-title mono">{{ invoice.invoice_number }}</p>
                                <p class="item-subtitle">{{ invoice.customer?.name }}</p>
                            </div>
                            <div class="item-amount">
                                <p class="amount-value">${{ formatNumber(invoice.total) }}</p>
                                <p class="amount-status" :class="getInvoiceAgeClass(invoice)">
                                    {{ getInvoiceAge(invoice) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Payments -->
            <div class="card">
                <div class="card-header">
                    <div class="header-left">
                        <i class="bi bi-credit-card-fill" style="color: #059669;"></i>
                        <span>Recent Payments</span>
                    </div>
                    <router-link to="/payments" class="view-all-link">
                        View All
                        <i class="bi bi-arrow-right"></i>
                    </router-link>
                </div>
                <div class="card-body p-0">
                    <div v-if="loading" class="loading-state">
                        <div class="skeleton-row" v-for="i in 4" :key="i">
                            <div class="skeleton skeleton-avatar"></div>
                            <div class="skeleton-content">
                                <div class="skeleton skeleton-title"></div>
                                <div class="skeleton skeleton-text"></div>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="recentPayments.length === 0" class="empty-state-small">
                        <i class="bi bi-credit-card"></i>
                        <p>No payments yet</p>
                    </div>
                    <div v-else class="list-items">
                        <div v-for="payment in recentPayments" :key="payment.id" class="list-item">
                            <div class="item-avatar success">
                                <i :class="getPaymentIcon(payment.payment_method)"></i>
                            </div>
                            <div class="item-info">
                                <p class="item-title">{{ payment.invoice?.invoice_number }}</p>
                                <p class="item-subtitle">{{ formatDate(payment.payment_date) }}</p>
                            </div>
                            <p class="payment-amount">+${{ formatNumber(payment.amount) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Customers -->
            <div class="card">
                <div class="card-header">
                    <div class="header-left">
                        <i class="bi bi-people-fill"></i>
                        <span>Top Customers</span>
                    </div>
                    <router-link to="/customers" class="view-all-link">
                        View All
                        <i class="bi bi-arrow-right"></i>
                    </router-link>
                </div>
                <div class="card-body p-0">
                    <div v-if="loading" class="loading-state">
                        <div class="skeleton-row" v-for="i in 4" :key="i">
                            <div class="skeleton skeleton-avatar"></div>
                            <div class="skeleton-content">
                                <div class="skeleton skeleton-title"></div>
                                <div class="skeleton skeleton-text"></div>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="topCustomers.length === 0" class="empty-state-small">
                        <i class="bi bi-people"></i>
                        <p>No customers yet</p>
                        <router-link to="/customers" class="btn btn-sm btn-primary">Add Customer</router-link>
                    </div>
                    <div v-else class="list-items">
                        <div v-for="customer in topCustomers" :key="customer.id" class="list-item">
                            <div class="item-avatar">
                                {{ getInitials(customer.name) }}
                            </div>
                            <div class="item-info">
                                <p class="item-title">{{ customer.name }}</p>
                                <p class="item-subtitle">{{ customer.invoices?.length || 0 }} invoices</p>
                            </div>
                            <span :class="['item-badge', customer.is_active ? 'badge-success' : 'badge-secondary']">
                                {{ customer.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';
import api from '../api';

const loading = ref(true);
const jobs = ref([]);
const invoices = ref([]);
const payments = ref([]);
const customers = ref([]);
const agingData = ref({ summary: {} });

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good Morning';
    if (hour < 17) return 'Good Afternoon';
    return 'Good Evening';
});

const stats = computed(() => ({
    activeJobs: jobs.value.filter(j => ['new', 'in_progress'].includes(j.status)).length,
    unpaidInvoices: invoices.value.filter(i => i.status !== 'paid').length,
    totalRevenue: invoices.value.reduce((sum, i) => sum + parseFloat(i.total || 0), 0),
    outstanding: invoices.value
        .filter(i => i.status !== 'paid')
        .reduce((sum, i) => sum + parseFloat(i.total || 0), 0),
}));

const recentJobs = computed(() => jobs.value.slice(0, 5));
const unpaidInvoices = computed(() => invoices.value.filter(i => i.status !== 'paid').slice(0, 5));
const recentPayments = computed(() => payments.value.slice(0, 5));
const topCustomers = computed(() => customers.value.slice(0, 5));

const formatNumber = (num) => new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0);
const formatDate = (date) => date ? new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : '';
const getInitials = (name) => name ? name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '?';
const formatStatus = (status) => ({ 'new': 'New', 'in_progress': 'In Progress', 'on_hold': 'On Hold', 'completed': 'Completed', 'cancelled': 'Cancelled' }[status] || status);
const getStatusClass = (status) => ({ 'new': 'badge-info', 'in_progress': 'badge-warning', 'on_hold': 'badge-secondary', 'completed': 'badge-success', 'cancelled': 'badge-danger' }[status] || '');

const getInvoiceAge = (invoice) => {
    if (!invoice.due_date) return 'No due date';
    const diff = Math.floor((new Date() - new Date(invoice.due_date)) / (1000 * 60 * 60 * 24));
    if (diff > 0) return `${diff}d overdue`;
    if (diff === 0) return 'Due today';
    return `${Math.abs(diff)}d left`;
};

const getInvoiceAgeClass = (invoice) => {
    if (!invoice.due_date) return '';
    const diff = Math.floor((new Date() - new Date(invoice.due_date)) / (1000 * 60 * 60 * 24));
    return diff > 0 ? 'overdue' : diff === 0 ? 'due-today' : 'upcoming';
};

const getPaymentIcon = (method) => ({ 'cash': 'bi bi-cash-stack', 'bank_transfer': 'bi bi-bank', 'credit_card': 'bi bi-credit-card', 'check': 'bi bi-file-text' }[method] || 'bi bi-cash');

const getAgingPercent = (bucket) => {
    const total = agingData.value.total_outstanding || 0;
    if (total === 0) return 0;
    const bucketTotal = agingData.value.summary?.[bucket]?.total || 0;
    return Math.round((bucketTotal / total) * 100);
};

const loadData = async () => {
    try {
        loading.value = true;
        const [jobsRes, invoicesRes, paymentsRes, customersRes, agingRes] = await Promise.all([
            api.jobs.getAll(), api.invoices.getAll(), api.payments.getAll(), api.customers.getAll(), api.invoices.getAging()
        ]);
        jobs.value = jobsRes.data;
        invoices.value = invoicesRes.data;
        payments.value = paymentsRes.data;
        customers.value = customersRes.data;
        agingData.value = agingRes.data;
    } catch (error) {
        console.error('Error loading dashboard:', error);
    } finally {
        loading.value = false;
    }
};

const refreshData = () => loadData();
onMounted(() => loadData());
</script>

<style scoped>
/* Dashboard Header */
.dashboard-header { margin-bottom: 2rem; }

.header-content {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

@media (min-width: 768px) {
    .header-content {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }
}

.greeting-title {
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--gray-900);
    margin: 0;
    letter-spacing: -0.02em;
}

.greeting-subtitle {
    color: var(--gray-500);
    font-size: 0.9375rem;
    margin: 0.375rem 0 0;
}

.header-actions { display: flex; gap: 0.75rem; }

.spin { animation: spin 1s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}

@media (min-width: 768px) {
    .stats-grid { grid-template-columns: repeat(4, 1fr); }
}

.stat-card {
    background: white;
    border-radius: 1rem;
    padding: 1.25rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--gray-100);
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--primary-500), #8b5cf6);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.stat-card:hover::before { opacity: 1; }

.stat-icon {
    width: 3rem;
    height: 3rem;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    margin-bottom: 0.75rem;
}

.icon-primary { background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); color: #4f46e5; }
.icon-warning { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #d97706; }
.icon-success { background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); color: #059669; }
.icon-danger { background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); color: #dc2626; }

.stat-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--gray-900);
    margin: 0;
    letter-spacing: -0.02em;
}

.stat-label {
    font-size: 0.75rem;
    color: var(--gray-500);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin: 0.25rem 0 0;
}

.stat-trend {
    position: absolute;
    top: 1rem; right: 1rem;
    font-size: 0.875rem;
}

.trend-up { color: #059669; }
.trend-warning { color: #d97706; }

/* Quick Actions */
.quick-actions { margin-bottom: 2rem; }

.section-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--gray-900);
    margin: 0 0 1rem;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}

@media (min-width: 768px) {
    .actions-grid { grid-template-columns: repeat(4, 1fr); }
}

.action-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    padding: 1.25rem;
    background: white;
    border-radius: 1rem;
    border: 1px solid var(--gray-100);
    text-decoration: none;
    transition: all 0.3s ease;
}

.action-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
    border-color: var(--primary-200);
}

.action-icon {
    width: 3rem;
    height: 3rem;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    transition: transform 0.3s ease;
}

.action-card:hover .action-icon { transform: scale(1.1); }

.action-label {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--gray-700);
}

/* Dashboard Grid */
.dashboard-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

@media (min-width: 768px) {
    .dashboard-grid { grid-template-columns: repeat(2, 1fr); }
}

/* Card */
.card {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--gray-100);
    overflow: hidden;
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--gray-100);
    background: transparent;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--gray-900);
}

.header-left i { color: var(--primary-500); }

.view-all-link {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--primary-600);
    text-decoration: none;
    transition: gap 0.2s ease;
}

.view-all-link:hover { gap: 0.5rem; }

.card-body { padding: 1rem; }
.p-0 { padding: 0 !important; }

/* Loading State */
.loading-state { padding: 1rem; }

.skeleton-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 0;
}

.skeleton {
    background: linear-gradient(90deg, var(--gray-200) 0%, var(--gray-100) 50%, var(--gray-200) 100%);
    background-size: 200% 100%;
    animation: shimmer 1.5s ease-in-out infinite;
    border-radius: 0.375rem;
}

@keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }

.skeleton-avatar { width: 2.5rem; height: 2.5rem; border-radius: 0.625rem; flex-shrink: 0; }
.skeleton-content { flex: 1; }
.skeleton-title { height: 0.875rem; width: 60%; margin-bottom: 0.5rem; }
.skeleton-text { height: 0.75rem; width: 40%; }

/* Empty States */
.empty-state-small {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    text-align: center;
    color: var(--gray-400);
}

.empty-state-small i { font-size: 2rem; margin-bottom: 0.75rem; }
.empty-state-small p { margin: 0 0 1rem; font-size: 0.875rem; }
.empty-state-small.success { color: #059669; }

/* List Items */
.list-items { padding: 0.5rem 0; }

.list-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1.25rem;
    transition: background 0.2s ease;
}

.list-item:hover { background: var(--gray-50); }

.item-avatar {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.625rem;
    background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
    color: #4f46e5;
    flex-shrink: 0;
}

.item-avatar.success {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #059669;
}

.item-info { flex: 1; min-width: 0; }

.item-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--gray-900);
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.item-title.mono {
    font-family: 'SF Mono', Monaco, monospace;
    color: var(--primary-600);
}

.item-subtitle {
    font-size: 0.75rem;
    color: var(--gray-500);
    margin: 0.125rem 0 0;
}

.item-badge {
    font-size: 0.6875rem;
    font-weight: 700;
    padding: 0.25rem 0.625rem;
    border-radius: 9999px;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}

.badge-info { background: #dbeafe; color: #1d4ed8; }
.badge-warning { background: #fef3c7; color: #b45309; }
.badge-secondary { background: #f3f4f6; color: #4b5563; }
.badge-success { background: #d1fae5; color: #047857; }
.badge-danger { background: #fee2e2; color: #b91c1c; }

/* Invoice Amount */
.item-amount { text-align: right; }

.amount-value {
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--gray-900);
    margin: 0;
}

.amount-status {
    font-size: 0.6875rem;
    margin: 0.125rem 0 0;
}

.amount-status.overdue { color: #dc2626; }
.amount-status.due-today { color: #d97706; }
.amount-status.upcoming { color: #059669; }

/* Payment Amount */
.payment-amount {
    font-size: 0.9375rem;
    font-weight: 700;
    color: #059669;
    margin: 0;
}

/* Aging Widget */
.aging-widget {
    background: white;
    border-radius: 1rem;
    padding: 1.25rem;
    margin-bottom: 2rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--gray-100);
}

.aging-widget-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.aging-widget-header .section-title {
    margin: 0;
}

.aging-bars {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 1rem;
}

@media (max-width: 768px) {
    .aging-bars {
        grid-template-columns: repeat(2, 1fr);
    }
}

.aging-bar-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.aging-bar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.aging-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--gray-500);
}

.aging-value {
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--gray-900);
}

.aging-value.danger {
    color: #dc2626;
}

.aging-bar-track {
    height: 6px;
    background: var(--gray-200);
    border-radius: 3px;
    overflow: hidden;
}

.aging-bar-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.5s ease;
}

.aging-bar-fill.current { background: #10b981; }
.aging-bar-fill.days-30 { background: #f59e0b; }
.aging-bar-fill.days-60 { background: #f97316; }
.aging-bar-fill.days-90 { background: #ef4444; }
.aging-bar-fill.days-90plus { background: #7f1d1d; }
</style>
