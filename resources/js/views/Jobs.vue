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
                    <a href="#" @click.prevent="api.download(api.exports.jobsExcel())" class="btn btn-secondary">
                        <i class="bi bi-file-earmark-excel"></i>
                        <span>Export</span>
                    </a>
                    <button @click="openCreateModal" class="btn btn-primary">
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
                <span class="job-count">{{ filteredJobs.length }} {{ filteredJobs.length === 1 ? 'job' : 'jobs' }}</span>
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
                                <input v-model="form.title" type="text" required class="form-control" />
                            </div>
                            <div class="form-group full-width">
                                <label class="form-label">Description</label>
                                <textarea v-model="form.description" rows="2" class="form-control"></textarea>
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
                                <input v-model="form.site_address" type="text" class="form-control" />
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
                                <input v-model="form.estimated_cost" type="number" step="0.01" min="0" class="form-control" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Actual Cost</label>
                                <input v-model="form.actual_cost" type="number" step="0.01" min="0" class="form-control" />
                            </div>
                            <div class="form-group full-width">
                                <label class="form-label">Notes</label>
                                <textarea v-model="form.notes" rows="2" class="form-control"></textarea>
                            </div>

                            <!-- Casual Assignment Section -->
                            <div class="form-group full-width casuals-section">
                                <label class="form-label">Assigned Employees</label>

                                <!-- Assigned Employees Badges -->
                                <div v-if="displayedCasuals.length > 0" class="assigned-casuals">
                                    <span
                                        v-for="casual in displayedCasuals"
                                        :key="casual.id"
                                        class="casual-badge"
                                    >
                                        {{ casual.name }}
                                        <button
                                            type="button"
                                            @click="removeCasual(casual.id)"
                                            class="badge-remove"
                                            title="Remove"
                                        >
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </span>
                                </div>
                                <p v-else class="no-casuals-text">No employees assigned to this job</p>

                                <!-- Add Casuals -->
                                <div class="add-casual-row">
                                    <select v-model="selectedCasualId" class="form-control casual-select">
                                        <option value="">Select employee to add...</option>
                                        <option
                                            v-for="casual in availableCasuals"
                                            :key="casual.id"
                                            :value="casual.id"
                                        >
                                            {{ casual.name }} ({{ casual.casual_number }})
                                        </option>
                                    </select>
                                    <input
                                        v-model="casualWorkDate"
                                        type="date"
                                        class="form-control work-date-input"
                                        placeholder="Work date"
                                    />
                                    <button
                                        type="button"
                                        @click="addCasual"
                                        :disabled="!selectedCasualId"
                                        class="btn btn-sm btn-primary"
                                    >
                                        <i class="bi bi-plus"></i>
                                        Add
                                    </button>
                                </div>
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
const casuals = ref([]);
const searchQuery = ref('');
const statusFilter = ref('');
const showModal = ref(false);
const editingJob = ref(null);

// Casual assignment state
const assignedCasuals = ref([]);
const pendingCasuals = ref([]); // For casuals added during job creation
const selectedCasualId = ref('');
const casualWorkDate = ref('');

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

// Casuals currently displayed (either from DB or pending for new job)
const displayedCasuals = computed(() => {
    return editingJob.value ? assignedCasuals.value : pendingCasuals.value;
});

const availableCasuals = computed(() => {
    const usedIds = displayedCasuals.value.map(c => c.id);
    return casuals.value.filter(c => c.is_active && !usedIds.includes(c.id));
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
        const [jobsRes, customersRes, casualsRes] = await Promise.all([
            api.jobs.getAll(),
            api.customers.getAll(),
            api.casuals.getAll()
        ]);
        jobs.value = jobsRes.data;
        customers.value = customersRes.data;
        casuals.value = casualsRes.data;
    } catch (error) {
        console.error('Error loading data:', error);
    } finally {
        loading.value = false;
    }
};

const loadJobCasuals = async (jobId) => {
    try {
        const response = await api.siteJobCasuals.getForJob(jobId);
        assignedCasuals.value = response.data;
    } catch (error) {
        console.error('Error loading job casuals:', error);
        assignedCasuals.value = [];
    }
};

const addCasual = async () => {
    if (!selectedCasualId.value) return;

    const casualToAdd = casuals.value.find(c => c.id === parseInt(selectedCasualId.value));
    if (!casualToAdd) return;

    if (editingJob.value) {
        // Editing existing job - assign via API
        try {
            await api.siteJobCasuals.assign(
                editingJob.value.id,
                [selectedCasualId.value],
                casualWorkDate.value || null
            );
            await loadJobCasuals(editingJob.value.id);
        } catch (error) {
            console.error('Error assigning casual:', error);
        }
    } else {
        // Creating new job - add to pending list
        pendingCasuals.value.push({
            ...casualToAdd,
            workDate: casualWorkDate.value || null
        });
    }

    selectedCasualId.value = '';
    casualWorkDate.value = '';
};

const removeCasual = async (casualId) => {
    if (editingJob.value) {
        // Editing existing job - unassign via API
        try {
            await api.siteJobCasuals.unassign(editingJob.value.id, casualId);
            await loadJobCasuals(editingJob.value.id);
        } catch (error) {
            console.error('Error unassigning casual:', error);
        }
    } else {
        // Creating new job - remove from pending list
        pendingCasuals.value = pendingCasuals.value.filter(c => c.id !== casualId);
    }
};

const openCreateModal = () => {
    editingJob.value = null;
    assignedCasuals.value = [];
    pendingCasuals.value = [];
    selectedCasualId.value = '';
    casualWorkDate.value = '';
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

const openEditModal = async (job) => {
    editingJob.value = job;
    selectedCasualId.value = '';
    casualWorkDate.value = '';
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
    await loadJobCasuals(job.id);
};

const closeModal = () => {
    showModal.value = false;
    editingJob.value = null;
    assignedCasuals.value = [];
    pendingCasuals.value = [];
    selectedCasualId.value = '';
    casualWorkDate.value = '';
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
            // Create new job
            const response = await api.jobs.create(cleanedData);
            const newJobId = response.data.id;

            // Assign pending casuals to the new job
            if (pendingCasuals.value.length > 0) {
                for (const casual of pendingCasuals.value) {
                    await api.siteJobCasuals.assign(
                        newJobId,
                        [casual.id],
                        casual.workDate || null
                    );
                }
            }
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

@keyframes spin {
    to { transform: rotate(360deg); }
}

.job-count {
    font-size: 0.8125rem;
    font-weight: 500;
    opacity: 0.9;
}

.job-title {
    color: var(--gray-700);
    font-weight: 500;
}

.date-cell, .cost-cell {
    color: var(--gray-500);
    font-size: 0.875rem;
}

.cost-cell {
    font-weight: 600;
    color: var(--gray-700);
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
    background: var(--info-600);
    border-color: var(--info-700);
}
.status-progress {
    background: var(--warning-600);
    border-color: var(--warning-700);
}
.status-hold {
    background: var(--gray-600);
    border-color: var(--gray-700);
}
.status-completed {
    background: var(--success-600);
    border-color: var(--success-700);
}
.status-cancelled {
    background: var(--danger-600);
    border-color: var(--danger-700);
}

.status-select option {
    background: white;
    color: var(--gray-700);
    font-weight: 500;
    padding: 0.5rem;
}

/* Casuals Assignment Section */
.casuals-section {
    margin-top: 0.5rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--gray-200);
}

.assigned-casuals {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.casual-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.5rem 0.375rem 0.75rem;
    background: linear-gradient(135deg, var(--info-100) 0%, var(--info-200) 100%);
    color: var(--info-800);
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 9999px;
}

.badge-remove {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 1.125rem;
    height: 1.125rem;
    padding: 0;
    background: rgba(30, 64, 175, 0.15);
    border: none;
    border-radius: 50%;
    color: var(--info-800);
    cursor: pointer;
    transition: all 0.15s ease;
}

.badge-remove:hover {
    background: var(--danger-600);
    color: white;
}

.no-casuals-text {
    font-size: 0.75rem;
    color: var(--gray-400);
    margin: 0 0 0.75rem;
    font-style: italic;
}

.add-casual-row {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.casual-select {
    flex: 1;
}

.work-date-input {
    width: 140px;
}

.add-casual-row .btn {
    flex-shrink: 0;
}
</style>
