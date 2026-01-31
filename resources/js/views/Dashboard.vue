<template>
    <AppLayout>
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <h1>Dashboard</h1>
                    <p class="header-subtitle">Welcome back! Here's what's happening with your properties.</p>
                </div>
                <router-link to="/jobs" class="btn btn-success">
                    <i class="bi bi-plus-lg"></i>
                    <span>New Job</span>
                </router-link>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-container">
            <div class="loader"></div>
            <p class="loading-text">Loading your dashboard...</p>
        </div>

        <div v-else>
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon icon-primary">
                        <i class="bi bi-briefcase-fill"></i>
                    </div>
                    <div class="stat-value">{{ stats.activeJobs }}</div>
                    <div class="stat-label">Active Jobs</div>
                    <div class="stat-trend trend-up" v-if="stats.activeJobs > 0">
                        <i class="bi bi-arrow-up-short"></i>
                        <span>In progress</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon icon-warning">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="stat-value">{{ stats.unpaidInvoices }}</div>
                    <div class="stat-label">Unpaid Invoices</div>
                    <div class="stat-trend trend-neutral" v-if="stats.unpaidInvoices > 0">
                        <i class="bi bi-dash"></i>
                        <span>Pending payment</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon icon-success">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <div class="stat-value">${{ formatNumber(stats.totalRevenue) }}</div>
                    <div class="stat-label">Total Revenue</div>
                    <div class="stat-trend trend-up">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Collected</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon icon-danger">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div class="stat-value">${{ formatNumber(stats.outstandingBalance) }}</div>
                    <div class="stat-label">Outstanding</div>
                    <div class="stat-trend trend-down" v-if="stats.outstandingBalance > 0">
                        <i class="bi bi-arrow-down-short"></i>
                        <span>Awaiting</span>
                    </div>
                </div>
            </div>

            <!-- Recent Jobs Card -->
            <div class="card jobs-card">
                <div class="card-header card-header-primary">
                    <div class="header-left">
                        <i class="bi bi-briefcase"></i>
                        <span>Recent Jobs</span>
                    </div>
                    <router-link to="/jobs" class="view-all-link">
                        View all
                        <i class="bi bi-arrow-right"></i>
                    </router-link>
                </div>
                <div class="card-body p-0">
                    <!-- Empty State -->
                    <div v-if="recentJobs.length === 0" class="empty-state">
                        <div class="empty-state-icon">
                            <i class="bi bi-briefcase"></i>
                        </div>
                        <h3 class="empty-state-title">No jobs yet</h3>
                        <p class="empty-state-description">
                            Get started by creating your first job to track site work and progress.
                        </p>
                        <router-link to="/jobs" class="btn btn-primary">
                            <i class="bi bi-plus-lg"></i>
                            Create First Job
                        </router-link>
                    </div>

                    <!-- Jobs Table -->
                    <div v-else class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Job #</th>
                                    <th>Customer</th>
                                    <th>Title</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Date</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="job in recentJobs" :key="job.id" class="table-row">
                                    <td>
                                        <span class="job-number">{{ job.job_number }}</span>
                                    </td>
                                    <td>
                                        <div class="customer-cell">
                                            <div class="customer-avatar">
                                                {{ getInitials(job.customer?.name) }}
                                            </div>
                                            <span>{{ job.customer?.name || 'Unknown' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="job-title">{{ job.title }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span :class="getStatusClass(job.status)">
                                            <span class="status-dot"></span>
                                            {{ formatStatus(job.status) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="date-cell">{{ formatDate(job.created_at) }}</span>
                                    </td>
                                    <td class="text-right">
                                        <router-link :to="`/jobs?id=${job.id}`" class="btn btn-sm btn-ghost">
                                            <i class="bi bi-eye"></i>
                                        </router-link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';
import api from '../api';

const loading = ref(true);
const stats = ref({ activeJobs: 0, unpaidInvoices: 0, totalRevenue: 0, outstandingBalance: 0 });
const recentJobs = ref([]);

const formatNumber = (num) => new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0);
const formatDate = (date) => new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
const formatStatus = (status) => status?.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) || 'Unknown';
const getInitials = (name) => name ? name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '?';

const getStatusClass = (status) => {
    const classes = {
        'new': 'badge badge-info badge-dot',
        'in_progress': 'badge badge-warning badge-dot',
        'on_hold': 'badge badge-secondary badge-dot',
        'completed': 'badge badge-success badge-dot',
        'cancelled': 'badge badge-danger badge-dot',
    };
    return classes[status] || 'badge badge-secondary badge-dot';
};

const loadData = async () => {
    try {
        loading.value = true;
        const [jobsRes, invoicesRes] = await Promise.all([api.jobs.getAll(), api.invoices.getAll()]);
        const jobs = jobsRes.data;
        const invoices = invoicesRes.data;

        stats.value = {
            activeJobs: jobs.filter(j => ['new', 'in_progress'].includes(j.status)).length,
            unpaidInvoices: invoices.filter(i => ['unpaid', 'partial'].includes(i.status)).length,
            totalRevenue: invoices.filter(i => i.status === 'paid').reduce((sum, i) => sum + parseFloat(i.total), 0),
            outstandingBalance: invoices.filter(i => ['unpaid', 'partial'].includes(i.status)).reduce((sum, i) => sum + parseFloat(i.total - (i.amount_paid || 0)), 0),
        };
        recentJobs.value = jobs.slice(0, 8);
    } catch (error) {
        console.error('Error:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => loadData());
</script>

<style scoped>
/* Page Header */
.page-header {
    margin-bottom: 2.5rem;
    text-align: center;
}

@media (min-width: 640px) {
    .page-header {
        text-align: left;
    }
}

.header-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1.25rem;
}

@media (min-width: 640px) {
    .header-content {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }
}

.header-title h1 {
    font-size: 1.875rem;
    font-weight: 800;
    color: #111827;
    margin: 0;
    letter-spacing: -0.025em;
}

.header-subtitle {
    color: #6b7280;
    font-size: 0.9375rem;
    margin: 0.5rem 0 0;
    line-height: 1.5;
}

/* Loading State */
.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
}

.loader {
    width: 3rem;
    height: 3rem;
    border: 3px solid #e5e7eb;
    border-top-color: #4f46e5;
    border-radius: 50%;
    animation: spin 0.8s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.loading-text {
    margin-top: 1rem;
    color: #6b7280;
    font-size: 0.9375rem;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.25rem;
    margin-bottom: 2.5rem;
}

@media (min-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 1.75rem;
    }
}

@media (min-width: 1024px) {
    .stats-grid {
        gap: 2rem;
    }
}

/* Stat Card */
.stat-card {
    background: white;
    border-radius: 1.25rem;
    padding: 1.75rem 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06), 0 4px 12px rgba(0, 0, 0, 0.04);
    border: 1px solid rgba(0, 0, 0, 0.04);
    transition: all 0.25s ease;
    position: relative;
    overflow: hidden;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #4f46e5, #8b5cf6);
    opacity: 0;
    transition: opacity 0.2s ease;
}

.stat-card:hover {
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}

.stat-card:hover::before {
    opacity: 1;
}

.stat-icon {
    width: 3rem;
    height: 3rem;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    margin: 0 auto 0.75rem;
    transition: transform 0.3s ease;
}

.stat-card:hover .stat-icon {
    transform: scale(1.1);
}

.icon-primary {
    background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
    color: #4f46e5;
}

.icon-warning {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #d97706;
}

.icon-success {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #059669;
}

.icon-danger {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #dc2626;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: #111827;
    line-height: 1.2;
    letter-spacing: -0.02em;
}

@media (min-width: 768px) {
    .stat-value {
        font-size: 1.75rem;
    }
}

.stat-label {
    font-size: 0.75rem;
    color: #6b7280;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-top: 0.25rem;
}

.stat-trend {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
    font-size: 0.6875rem;
    font-weight: 600;
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid #f3f4f6;
}

.trend-up {
    color: #059669;
}

.trend-down {
    color: #dc2626;
}

.trend-neutral {
    color: #d97706;
}

/* Jobs Card */
.jobs-card {
    border-radius: 1.25rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06), 0 4px 12px rgba(0, 0, 0, 0.04);
    border: 1px solid rgba(0, 0, 0, 0.04);
    background: white;
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    font-weight: 600;
    font-size: 0.9375rem;
}

.header-left i {
    font-size: 1rem;
    opacity: 0.9;
}

.view-all-link {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.8125rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
}

.view-all-link:hover {
    color: white;
}

.view-all-link i {
    font-size: 0.875rem;
    transition: transform 0.2s ease;
}

.view-all-link:hover i {
    transform: translateX(3px);
}

/* Table */
.table-container {
    overflow-x: auto;
}

.table {
    width: 100%;
    font-size: 0.875rem;
}

.table thead th {
    background: #f9fafb;
    color: #6b7280;
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 0.875rem 1rem;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

.table tbody tr {
    transition: background-color 0.15s ease;
}

.table tbody tr:hover {
    background: linear-gradient(90deg, rgba(99, 102, 241, 0.04) 0%, transparent 100%);
}

.table tbody td {
    padding: 1rem;
    vertical-align: middle;
    border-bottom: 1px solid #f3f4f6;
}

.table tbody tr:last-child td {
    border-bottom: none;
}

/* Job Number */
.job-number {
    font-weight: 700;
    color: #4f46e5;
    font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Fira Mono', 'Droid Sans Mono', monospace;
    font-size: 0.8125rem;
}

/* Customer Cell */
.customer-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.customer-avatar {
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
    background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.6875rem;
    font-weight: 700;
    color: #4f46e5;
}

/* Job Title */
.job-title {
    color: #374151;
    font-weight: 500;
}

/* Date Cell */
.date-cell {
    color: #6b7280;
    font-size: 0.8125rem;
}

/* Badge */
.badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-dot .status-dot {
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 50%;
    background: currentColor;
}

.badge-info {
    background: #dbeafe;
    color: #1d4ed8;
}

.badge-warning {
    background: #fef3c7;
    color: #b45309;
}

.badge-success {
    background: #d1fae5;
    color: #047857;
}

.badge-danger {
    background: #fee2e2;
    color: #b91c1c;
}

.badge-secondary {
    background: #f3f4f6;
    color: #4b5563;
}

/* Ghost Button */
.btn-ghost {
    padding: 0.5rem;
    color: #6b7280;
    background: transparent;
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-ghost:hover {
    background: #f3f4f6;
    color: #4f46e5;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3.5rem 1.5rem;
}

.empty-state-icon {
    width: 4.5rem;
    height: 4.5rem;
    border-radius: 1rem;
    background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.25rem;
    font-size: 1.75rem;
    color: #4f46e5;
}

.empty-state-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: #111827;
    margin: 0 0 0.5rem;
}

.empty-state-description {
    font-size: 0.9375rem;
    color: #6b7280;
    max-width: 20rem;
    margin: 0 auto 1.5rem;
    line-height: 1.5;
}

/* Utilities */
.p-0 { padding: 0 !important; }
.text-center { text-align: center; }
.text-right { text-align: right; }
</style>
