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
                    <option value="new">New</option>
                    <option value="in_progress">In Progress</option>
                    <option value="on_hold">On Hold</option>
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
                    <span class="record-count">{{ filteredJobs.length }} {{ filteredJobs.length === 1 ? 'job' : 'jobs' }}</span>
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
                                    <th>Job</th>
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
                                    <td>
                                        <div class="job-title">{{ job.title }}</div>
                                        <div v-if="job.description" class="job-description">{{ job.description }}</div>
                                    </td>
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
import { toDateInput } from '@/utils/date';

const loading = ref(true);
const startDate = ref(toDateInput(new Date(new Date().getFullYear(), 0, 1)));
const endDate = ref(toDateInput());
const jobs = ref([]);
const clients = ref([]);

const searchQuery = ref('');
const filterClient = ref('');
const filterStatus = ref('');

const formatNumber = (num) => new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0);
const formatDate = (date) => date ? new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '-';
const getInitials = (name) => name ? name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '?';
const formatStatus = (status) => status ? status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : '-';

const hasActiveFilters = computed(() => searchQuery.value || filterClient.value || filterStatus.value);

const filteredJobs = computed(() => {
    let result = [...jobs.value];

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(job =>
            job.job_number?.toLowerCase().includes(query) ||
            job.client_name?.toLowerCase().includes(query) ||
            job.title?.toLowerCase().includes(query) ||
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
    total_value: filteredJobs.value.reduce((sum, j) => sum + Number(j.value || 0), 0),
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
    api.download(`/api/reports/jobs-summary/export?${params}`);
};

onMounted(() => loadReport());
</script>

<style scoped>

.date-input { padding: 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid var(--gray-300); border-radius: 0.5rem; }
.date-range { display: flex; align-items: center; gap: 0.5rem; }
.date-range span { color: var(--gray-500); font-size: 0.875rem; }

@media (min-width: 768px) { .summary-cards { grid-template-columns: repeat(4, 1fr); }
}
.summary-icon.blue { background: var(--info-100); color: var(--info-600); }
.summary-icon.purple { background: var(--accent-100); color: var(--accent-600); }

.record-count { font-size: 0.75rem; opacity: 0.9; }

.client-cell { display: flex; align-items: center; gap: 0.75rem; }
.client-avatar { width: 2rem; height: 2rem; border-radius: 0.5rem; background: linear-gradient(135deg, var(--primary-100), var(--primary-200)); display: flex; align-items: center; justify-content: center; font-size: 0.6875rem; font-weight: 700; color: var(--primary-600); }
.job-title { font-weight: 600; color: var(--text-primary); }
.job-description { font-size: 0.75rem; color: var(--text-muted); margin-top: 0.125rem; }
.worker-count { display: inline-block; min-width: 1.5rem; padding: 0.125rem 0.5rem; background: var(--primary-100); color: var(--primary-600); font-weight: 600; font-size: 0.75rem; border-radius: 9999px; }

.status-new { background: var(--primary-100); color: var(--primary-text); }
.status-on_hold { background: var(--warning-100); color: var(--warning-700); }
.status-in_progress { background: var(--info-100); color: var(--info-700); }
.status-completed { background: var(--success-100); color: var(--success-600); }
.status-cancelled { background: var(--danger-100); color: var(--danger-600); }

@keyframes spin { to { transform: rotate(360deg); } }

</style>
