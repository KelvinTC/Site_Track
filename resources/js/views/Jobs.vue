<template>
    <AppLayout>
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <h1>Jobs</h1>
                    <p class="header-subtitle">Track and manage all your site jobs</p>
                </div>
                <div class="header-actions">
                    <div class="search-box">
                        <i class="bi bi-search search-icon"></i>
                        <input v-model="searchQuery" type="text" placeholder="Search jobs..." class="search-input" />
                    </div>
                    <select v-model="statusFilter" class="filter-select">
                        <option value="">All Status</option>
                        <option value="new">New</option>
                        <option value="in_progress">In Progress</option>
                        <option value="on_hold">On Hold</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <button @click="openCreateModal" class="btn btn-success">
                        <i class="bi bi-plus-lg"></i>
                        <span>New Job</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-container">
            <div class="loader"></div>
            <p class="loading-text">Loading jobs...</p>
        </div>

        <!-- Jobs Card -->
        <div v-else class="card">
            <div class="card-header card-header-primary">
                <div class="header-left">
                    <i class="bi bi-briefcase-fill"></i>
                    <span>Job List</span>
                </div>
                <span class="job-count">{{ filteredJobs.length }} jobs</span>
            </div>
            <div class="card-body p-0">
                <!-- Empty State -->
                <div v-if="filteredJobs.length === 0" class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-briefcase"></i>
                    </div>
                    <h3 class="empty-state-title">No jobs found</h3>
                    <p class="empty-state-description">
                        {{ searchQuery || statusFilter ? 'Try adjusting your filters.' : 'Create your first job to get started.' }}
                    </p>
                    <button v-if="!searchQuery && !statusFilter" @click="openCreateModal" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i>
                        Create First Job
                    </button>
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
                                <th>Start Date</th>
                                <th class="text-right">Est. Cost</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="job in filteredJobs" :key="job.id">
                                <td>
                                    <span class="job-number">{{ job.job_number }}</span>
                                </td>
                                <td>
                                    <div class="customer-cell">
                                        <div class="customer-avatar">
                                            {{ getInitials(job.customer?.name) }}
                                        </div>
                                        <span>{{ job.customer?.name || 'N/A' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="job-title">{{ job.title }}</span>
                                </td>
                                <td class="text-center">
                                    <select
                                        :value="job.status"
                                        @change="updateJobStatus(job.id, $event.target.value)"
                                        :class="['status-select', getStatusClass(job.status)]"
                                    >
                                        <option value="new">New</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="on_hold">On Hold</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </td>
                                <td>
                                    <span class="date-cell">{{ job.start_date ? formatDate(job.start_date) : 'Not set' }}</span>
                                </td>
                                <td class="text-right">
                                    <span class="cost-cell">{{ job.estimated_cost ? '$' + formatNumber(job.estimated_cost) : '-' }}</span>
                                </td>
                                <td class="text-right">
                                    <div class="action-buttons">
                                        <button @click="openEditModal(job)" class="btn btn-sm btn-ghost" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button @click="deleteJob(job.id)" class="btn btn-sm btn-ghost btn-ghost-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Transition name="modal">
            <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
                <div class="modal-content">
                    <div class="modal-header modal-header-primary">
                        <h5>{{ editingJob ? 'Edit Job' : 'Create New Job' }}</h5>
                        <button @click="closeModal" class="modal-close">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <form @submit.prevent="saveJob" class="modal-body">
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label class="form-label">Customer *</label>
                                <select v-model="form.customer_id" required class="form-control">
                                    <option value="">Select a customer</option>
                                    <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                        {{ customer.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="form-group full-width">
                                <label class="form-label">Job Title *</label>
                                <input v-model="form.title" type="text" required class="form-control" placeholder="e.g., Roof Repair" />
                            </div>
                            <div class="form-group full-width">
                                <label class="form-label">Description</label>
                                <textarea v-model="form.description" rows="3" class="form-control" placeholder="Job details and scope..."></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select v-model="form.status" class="form-control">
                                    <option value="new">New</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="on_hold">On Hold</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Site Address</label>
                                <input v-model="form.site_address" type="text" class="form-control" placeholder="123 Main St" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Start Date</label>
                                <input v-model="form.start_date" type="date" class="form-control" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">End Date</label>
                                <input v-model="form.end_date" type="date" class="form-control" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Estimated Cost</label>
                                <input v-model="form.estimated_cost" type="number" step="0.01" min="0" class="form-control" placeholder="0.00" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Actual Cost</label>
                                <input v-model="form.actual_cost" type="number" step="0.01" min="0" class="form-control" placeholder="0.00" />
                            </div>
                            <div class="form-group full-width">
                                <label class="form-label">Notes</label>
                                <textarea v-model="form.notes" rows="2" class="form-control" placeholder="Additional notes..."></textarea>
                            </div>
                        </div>
                    </form>
                    <div class="modal-footer">
                        <button type="button" @click="closeModal" class="btn btn-secondary">Cancel</button>
                        <button @click="saveJob" :disabled="saving" class="btn btn-primary">
                            <span v-if="saving" class="spinner"></span>
                            <span v-else>
                                <i class="bi bi-check-lg"></i>
                                {{ editingJob ? 'Update Job' : 'Create Job' }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';
import api from '../api';

const loading = ref(true);
const saving = ref(false);
const jobs = ref([]);
const customers = ref([]);
const searchQuery = ref('');
const statusFilter = ref('');
const showModal = ref(false);
const editingJob = ref(null);

const form = ref({
    customer_id: '',
    title: '',
    description: '',
    status: 'new',
    start_date: '',
    end_date: '',
    estimated_cost: '',
    actual_cost: '',
    site_address: '',
    notes: '',
});

const filteredJobs = computed(() => {
    let filtered = jobs.value;
    if (statusFilter.value) {
        filtered = filtered.filter(j => j.status === statusFilter.value);
    }
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(j =>
            j.job_number.toLowerCase().includes(query) ||
            j.title.toLowerCase().includes(query) ||
            (j.customer?.name && j.customer.name.toLowerCase().includes(query))
        );
    }
    return filtered;
});

const formatNumber = (num) => new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0);
const formatDate = (date) => new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
const getInitials = (name) => name ? name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '?';

const getStatusClass = (status) => {
    const classes = {
        'new': 'status-new',
        'in_progress': 'status-progress',
        'on_hold': 'status-hold',
        'completed': 'status-completed',
        'cancelled': 'status-cancelled',
    };
    return classes[status] || 'status-new';
};

const loadData = async () => {
    try {
        loading.value = true;
        const [jobsRes, customersRes] = await Promise.all([
            api.jobs.getAll(),
            api.customers.getAll()
        ]);
        jobs.value = jobsRes.data;
        customers.value = customersRes.data;
    } catch (error) {
        console.error('Error loading data:', error);
    } finally {
        loading.value = false;
    }
};

const openCreateModal = () => {
    editingJob.value = null;
    form.value = {
        customer_id: '',
        title: '',
        description: '',
        status: 'new',
        start_date: '',
        end_date: '',
        estimated_cost: '',
        actual_cost: '',
        site_address: '',
        notes: '',
    };
    showModal.value = true;
};

const openEditModal = (job) => {
    editingJob.value = job;
    form.value = {
        customer_id: job.customer_id,
        title: job.title,
        description: job.description || '',
        status: job.status,
        start_date: job.start_date || '',
        end_date: job.end_date || '',
        estimated_cost: job.estimated_cost || '',
        actual_cost: job.actual_cost || '',
        site_address: job.site_address || '',
        notes: job.notes || '',
    };
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingJob.value = null;
};

const saveJob = async () => {
    try {
        saving.value = true;
        // Clean form data - convert empty strings to null for optional fields
        const cleanedData = {
            customer_id: form.value.customer_id || null,
            title: form.value.title,
            description: form.value.description || null,
            status: form.value.status,
            start_date: form.value.start_date || null,
            end_date: form.value.end_date || null,
            estimated_cost: form.value.estimated_cost !== '' ? form.value.estimated_cost : null,
            actual_cost: form.value.actual_cost !== '' ? form.value.actual_cost : null,
            site_address: form.value.site_address || null,
            notes: form.value.notes || null,
        };
        if (editingJob.value) {
            await api.jobs.update(editingJob.value.id, cleanedData);
        } else {
            await api.jobs.create(cleanedData);
        }
        await loadData();
        closeModal();
    } catch (error) {
        console.error('Error saving job:', error);
    } finally {
        saving.value = false;
    }
};

const updateJobStatus = async (jobId, newStatus) => {
    try {
        await api.jobs.updateStatus(jobId, newStatus);
        await loadData();
    } catch (error) {
        console.error('Error updating job status:', error);
    }
};

const deleteJob = async (id) => {
    if (!confirm('Are you sure you want to delete this job? This action cannot be undone.')) return;
    try {
        await api.jobs.delete(id);
        await loadData();
    } catch (error) {
        console.error('Error deleting job:', error);
    }
};

onMounted(() => {
    loadData();
});
</script>

<style scoped>
/* Page Header */
.page-header {
    margin-bottom: 2.5rem;
    text-align: center;
}

@media (min-width: 768px) {
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

@media (min-width: 768px) {
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

.header-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    justify-content: center;
}

@media (min-width: 768px) {
    .header-actions {
        justify-content: flex-end;
    }
}

/* Search Box */
.search-box {
    position: relative;
    min-width: 180px;
}

.search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    font-size: 1rem;
}

.search-input {
    width: 100%;
    padding: 0.625rem 1rem 0.625rem 2.75rem;
    font-size: 0.9375rem;
    color: #111827;
    background: white;
    border: 1.5px solid #e5e7eb;
    border-radius: 0.75rem;
    transition: all 0.2s ease;
}

.search-input:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
}

.filter-select {
    padding: 0.625rem 1rem;
    font-size: 0.9375rem;
    color: #111827;
    background: white;
    border: 1.5px solid #e5e7eb;
    border-radius: 0.75rem;
    min-width: 130px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.filter-select:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
}

/* Loading */
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
}

/* Card */
.card {
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
}

.job-count {
    font-size: 0.8125rem;
    font-weight: 500;
    opacity: 0.9;
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

/* Job Number */
.job-number {
    font-weight: 700;
    color: #4f46e5;
    font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Fira Mono', monospace;
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

.job-title {
    color: #374151;
    font-weight: 500;
}

.date-cell, .cost-cell {
    color: #6b7280;
    font-size: 0.875rem;
}

.cost-cell {
    font-weight: 600;
    color: #374151;
}

/* Status Select */
.status-select {
    padding: 0.5rem 1rem;
    font-size: 0.8125rem;
    font-weight: 700;
    border-radius: 9999px;
    border: 2px solid transparent;
    cursor: pointer;
    color: white;
    transition: all 0.2s ease;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
}

.status-select:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.status-new {
    background: #2563eb;
    border-color: #1d4ed8;
}
.status-progress {
    background: #d97706;
    border-color: #b45309;
}
.status-hold {
    background: #4b5563;
    border-color: #374151;
}
.status-completed {
    background: #059669;
    border-color: #047857;
}
.status-cancelled {
    background: #dc2626;
    border-color: #b91c1c;
}

.status-select option {
    background: white;
    color: #374151;
    font-weight: 500;
    padding: 0.5rem;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    justify-content: flex-end;
    gap: 0.375rem;
}

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

.btn-ghost-danger:hover {
    background: #fee2e2;
    color: #dc2626;
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
    margin: 0 auto 1.5rem;
}

/* Modal */
.modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(17, 24, 39, 0.6);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1050;
    padding: 1rem;
}

.modal-content {
    background: white;
    border-radius: 1.25rem;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
    width: 100%;
    max-width: 700px;
    max-height: calc(100vh - 2rem);
    overflow-y: auto;
}

.modal-header {
    padding: 1.25rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-header h5 {
    font-size: 1.125rem;
    font-weight: 700;
    margin: 0;
    color: white;
}

.modal-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    padding: 1.25rem 1.5rem;
    border-top: 1px solid #f3f4f6;
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    background: #f9fafb;
}

/* Form */
.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

@media (max-width: 480px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-label {
    font-weight: 600;
    font-size: 0.875rem;
    color: #374151;
}

.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    font-size: 0.9375rem;
    color: #111827;
    background: #f9fafb;
    border: 1.5px solid #e5e7eb;
    border-radius: 0.625rem;
    transition: all 0.2s ease;
}

.form-control:focus {
    outline: none;
    border-color: #4f46e5;
    background: white;
    box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
}

textarea.form-control {
    resize: vertical;
}

.spinner {
    width: 1.25rem;
    height: 1.25rem;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

/* Modal Transition */
.modal-enter-active, .modal-leave-active {
    transition: all 0.3s ease;
}

.modal-enter-from, .modal-leave-to {
    opacity: 0;
}

/* Utilities */
.p-0 { padding: 0 !important; }
.text-center { text-align: center; }
.text-right { text-align: right; }
</style>
