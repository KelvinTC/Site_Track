<template>
    <AppLayout>
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <h1>Jobs Summary Report</h1>
                    <p class="header-subtitle">Overview of all jobs and their status</p>
                </div>
                <div class="header-actions">
                    <div class="filter-group">
                        <label>Date Range</label>
                        <div class="date-range">
                            <input type="date" v-model="startDate" class="date-input" />
                            <span>to</span>
                            <input type="date" v-model="endDate" class="date-input" />
                        </div>
                    </div>
                    <button @click="exportReport('excel')" class="btn btn-secondary">
                        <i class="bi bi-file-earmark-excel"></i>
                        <span>Excel</span>
                    </button>
                    <button @click="loadReport" class="btn btn-primary" :disabled="loading">
                        <i class="bi bi-arrow-clockwise" :class="{ 'spin': loading }"></i>
                        <span>Refresh</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-bar">
            <div class="filter-group">
                <label>Search</label>
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" v-model="searchQuery" placeholder="Search jobs..." class="search-input" />
                </div>
            </div>
            <div class="filter-group">
                <label>Client</label>
                <select v-model="filterClient" class="filter-select">
                    <option value="">All Clients</option>
                    <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Status</label>
                <select v-model="filterStatus" class="filter-select">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <button @click="clearFilters" class="btn btn-ghost" v-if="hasActiveFilters">
                <i class="bi bi-x-circle"></i>
                Clear
            </button>
        </div>

        <div v-if="loading" class="loading-container">
            <div class="loader"></div>
            <p>Loading report...</p>
        </div>

        <template v-else>
            <!-- Summary Cards -->
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="summary-icon blue">
                        <i class="bi bi-briefcase-fill"></i>
                    </div>
                    <div class="summary-content">
                        <span class="summary-value">{{ summary.total_jobs }}</span>
                        <span class="summary-label">Total Jobs</span>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon green">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div class="summary-content">
                        <span class="summary-value">{{ summary.completed }}</span>
                        <span class="summary-label">Completed</span>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon orange">
                        <i class="bi bi-play-circle-fill"></i>
                    </div>
                    <div class="summary-content">
                        <span class="summary-value">{{ summary.in_progress }}</span>
                        <span class="summary-label">In Progress</span>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon purple">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div class="summary-content">
                        <span class="summary-value">${{ formatNumber(summary.total_value) }}</span>
                        <span class="summary-label">Total Value</span>
                    </div>
                </div>
            </div>

            <!-- Jobs Table -->
            <div class="card">
                <div class="card-header">
                    <div class="header-left">
                        <i class="bi bi-briefcase-fill"></i>
                        <span>Jobs List</span>
                    </div>
                    <span class="record-count">{{ filteredJobs.length }} jobs</span>
                </div>
                <div class="card-body p-0">
                    <div v-if="!filteredJobs.length" class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <h3>No Jobs Found</h3>
                        <p>No jobs match the selected criteria.</p>
                    </div>
                    <div v-else class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Job #</th>
                                    <th>Client</th>
                                    <th>Description</th>
                                    <th class="text-center">Date</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Workers</th>
                                    <th class="text-right">Value</th>
                                    <th class="text-right">Invoiced</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="job in filteredJobs" :key="job.id">
                                    <td><span class="job-number">{{ job.job_number }}</span></td>
                                    <td>
                                        <div class="client-cell">
                                            <div class="client-avatar">{{ getInitials(job.client_name) }}</div>
                                            <span>{{ job.client_name }}</span>
                                        </div>
                                    </td>
                                    <td>{{ job.description || '-' }}</td>
                                    <td class="text-center">{{ formatDate(job.date) }}</td>
                                    <td class="text-center">
                                        <span :class="['status-badge', 'status-' + job.status]">{{ formatStatus(job.status) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="worker-count">{{ job.worker_count || 0 }}</span>
                                    </td>
                                    <td class="text-right">${{ formatNumber(job.value) }}</td>
                                    <td class="text-right">
                                        <span :class="{ 'text-success': job.invoiced, 'text-muted': !job.invoiced }">
                                            {{ job.invoiced ? 'Yes' : 'No' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </template>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import api from '../../api';

const loading = ref(true);
const startDate = ref(new Date(new Date().getFullYear(), 0, 1).toISOString().split('T')[0]);
const endDate = ref(new Date().toISOString().split('T')[0]);
const jobs = ref([]);
const clients = ref([]);

const searchQuery = ref('');
const filterClient = ref('');
const filterStatus = ref('');

const formatNumber = (num) => new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0);
const formatDate = (date) => date ? new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '-';
const getInitials = (name) => name ? name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '?';
const formatStatus = (status) => status ? status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) : '-';

const hasActiveFilters = computed(() => searchQuery.value || filterClient.value || filterStatus.value);

const filteredJobs = computed(() => {
    let result = [...jobs.value];

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(job =>
            job.job_number?.toLowerCase().includes(query) ||
            job.client_name?.toLowerCase().includes(query) ||
            job.description?.toLowerCase().includes(query)
        );
    }

    if (filterClient.value) {
        result = result.filter(job => job.client_id == filterClient.value);
    }

    if (filterStatus.value) {
        result = result.filter(job => job.status === filterStatus.value);
    }

    return result;
});

const summary = computed(() => ({
    total_jobs: filteredJobs.value.length,
    completed: filteredJobs.value.filter(j => j.status === 'completed').length,
    in_progress: filteredJobs.value.filter(j => j.status === 'in_progress').length,
    total_value: filteredJobs.value.reduce((sum, j) => sum + (j.value || 0), 0),
}));

const clearFilters = () => {
    searchQuery.value = '';
    filterClient.value = '';
    filterStatus.value = '';
};

const loadReport = async () => {
    try {
        loading.value = true;
        const response = await api.reports.jobsSummary({ start_date: startDate.value, end_date: endDate.value });
        jobs.value = response.data.jobs || [];
        clients.value = response.data.clients || [];
    } catch (error) {
        console.error('Error loading report:', error);
    } finally {
        loading.value = false;
    }
};

const exportReport = (format) => {
    const params = new URLSearchParams({ start_date: startDate.value, end_date: endDate.value, format });
    window.open(`/api/reports/jobs-summary/export?${params}`, '_blank');
};

onMounted(() => loadReport());
</script>

<style scoped>
.page-header { margin-bottom: 1.5rem; }
.header-content { display: flex; flex-direction: column; gap: 1rem; }
@media (min-width: 768px) { .header-content { flex-direction: row; align-items: flex-end; justify-content: space-between; } }
.header-title h1 { font-size: 1.875rem; font-weight: 800; color: #111827; margin: 0; }
.header-subtitle { color: #6b7280; font-size: 0.9375rem; margin: 0.25rem 0 0; }
.header-actions { display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: flex-end; }

.filter-group { display: flex; flex-direction: column; gap: 0.25rem; }
.filter-group label { font-size: 0.6875rem; font-weight: 500; color: #6b7280; text-transform: uppercase; }
.date-input { padding: 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem; }
.date-range { display: flex; align-items: center; gap: 0.5rem; }
.date-range span { color: #6b7280; font-size: 0.875rem; }

.filters-bar { display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end; background: white; padding: 1rem 1.25rem; border-radius: 0.75rem; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
.search-box { position: relative; }
.search-box i { position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #9ca3af; }
.search-input { width: 200px; padding: 0.5rem 0.75rem 0.5rem 2.25rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: #f9fafb; }
.search-input:focus { outline: none; border-color: #4f46e5; background: white; }
.filter-select { min-width: 150px; padding: 0.5rem 2rem 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: #f9fafb url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") right 0.5rem center/1.25rem no-repeat; appearance: none; }

.summary-cards { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
@media (min-width: 768px) { .summary-cards { grid-template-columns: repeat(4, 1fr); } }
.summary-card { background: white; border-radius: 1rem; padding: 1.25rem; display: flex; align-items: center; gap: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
.summary-icon { width: 3rem; height: 3rem; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
.summary-icon.blue { background: #dbeafe; color: #2563eb; }
.summary-icon.green { background: #d1fae5; color: #059669; }
.summary-icon.orange { background: #fed7aa; color: #ea580c; }
.summary-icon.purple { background: #ede9fe; color: #7c3aed; }
.summary-content { display: flex; flex-direction: column; }
.summary-value { font-size: 1.5rem; font-weight: 800; color: #111827; }
.summary-label { font-size: 0.75rem; color: #6b7280; }

.card { background: white; border-radius: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden; }
.card-header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.5rem; background: linear-gradient(135deg, #059669, #10b981); color: white; }
.header-left { display: flex; align-items: center; gap: 0.625rem; font-weight: 600; }
.record-count { font-size: 0.75rem; opacity: 0.9; }
.p-0 { padding: 0 !important; }

.table-container { overflow-x: auto; }
.table { width: 100%; font-size: 0.875rem; border-collapse: collapse; }
.table thead th { background: #f9fafb; color: #6b7280; font-weight: 600; font-size: 0.75rem; text-transform: uppercase; padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
.table tbody td { padding: 0.875rem 1rem; vertical-align: middle; border-bottom: 1px solid #f3f4f6; }
.table tbody tr:hover { background: #f9fafb; }

.job-number { font-family: monospace; font-weight: 700; color: #4f46e5; }
.client-cell { display: flex; align-items: center; gap: 0.75rem; }
.client-avatar { width: 2rem; height: 2rem; border-radius: 0.5rem; background: linear-gradient(135deg, #e0e7ff, #c7d2fe); display: flex; align-items: center; justify-content: center; font-size: 0.6875rem; font-weight: 700; color: #4f46e5; }
.worker-count { display: inline-block; min-width: 1.5rem; padding: 0.125rem 0.5rem; background: #e0e7ff; color: #4f46e5; font-weight: 600; font-size: 0.75rem; border-radius: 9999px; }

.status-badge { display: inline-block; padding: 0.25rem 0.625rem; font-size: 0.6875rem; font-weight: 600; border-radius: 0.375rem; }
.status-pending { background: #fef3c7; color: #b45309; }
.status-in_progress { background: #dbeafe; color: #1d4ed8; }
.status-completed { background: #d1fae5; color: #059669; }
.status-cancelled { background: #fee2e2; color: #dc2626; }

.text-success { color: #059669; font-weight: 600; }
.text-muted { color: #9ca3af; }

.empty-state { text-align: center; padding: 3rem; color: #6b7280; }
.empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.5; }
.empty-state h3 { font-size: 1.125rem; font-weight: 700; margin: 0 0 0.5rem; color: #374151; }

.loading-container { display: flex; flex-direction: column; align-items: center; padding: 4rem; }
.loader { width: 3rem; height: 3rem; border: 3px solid #e5e7eb; border-top-color: #059669; border-radius: 50%; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.spin { animation: spin 1s linear infinite; }

.btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; border: none; cursor: pointer; }
.btn-primary { background: #059669; color: white; }
.btn-primary:hover { background: #047857; }
.btn-secondary { background: #f3f4f6; color: #374151; }
.btn-secondary:hover { background: #e5e7eb; }
.btn-ghost { background: transparent; color: #ef4444; }

.text-center { text-align: center; }
.text-right { text-align: right; }
</style>
