<template>
    <AppLayout>
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <h1>Employees</h1>
                    <p class="header-subtitle">Manage your employees directory</p>
                </div>
                <div class="header-actions">
                    <div class="search-box">
                        <i class="bi bi-search search-icon"></i>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search employees..."
                            class="search-input"
                            @keyup.enter="handleSearch"
                            @input="debouncedSearch"
                        />
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" @click="toggleExportDropdown">
                            <i class="bi bi-download"></i>
                            <span>Export</span>
                        </button>
                        <div v-if="showExportDropdown" class="dropdown-menu show">
                            <a href="#" @click.prevent="api.download(api.exports.casualsExcel())" class="dropdown-item">
                                <i class="bi bi-file-earmark-excel"></i>
                                Export Directory
                            </a>
                            <button @click="openReportModal" class="dropdown-item">
                                <i class="bi bi-file-earmark-bar-graph"></i>
                                Jobs Report
                            </button>
                        </div>
                    </div>
                    <button @click="openImportModal" class="btn btn-secondary">
                        <i class="bi bi-upload"></i>
                        <span>Import</span>
                    </button>
                    <button @click="openCreateModal" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i>
                        <span>Add Employee</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-container">
            <div class="loader"></div>
            <p class="loading-text">Loading employees...</p>
        </div>

        <!-- Employees Card -->
        <div v-else class="card">
            <div class="card-header card-header-primary">
                <div class="header-left">
                    <i class="bi bi-person-badge-fill"></i>
                    <span>Employee Directory</span>
                </div>
                <span class="casual-count">{{ total }} {{ total === 1 ? 'employee' : 'employees' }}</span>
            </div>
            <div class="card-body p-0">
                <!-- Empty State -->
                <div v-if="casuals.length === 0" class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    <h3 class="empty-state-title">No employees found</h3>
                    <p class="empty-state-description">
                        {{ searchQuery ? 'Try adjusting your search criteria.' : 'Get started by adding your first employee.' }}
                    </p>
                    <button v-if="!searchQuery" @click="openCreateModal" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i>
                        Add First Employee
                    </button>
                </div>

                <!-- Employees Table -->
                <div v-else class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Employee #</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Job Title</th>
                                <th>Phone</th>
                                <th class="text-center">Total Jobs</th>
                                <th class="text-center">Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="casual in casuals" :key="casual.id">
                                <td>
                                    <span class="casual-number">{{ casual.casual_number }}</span>
                                </td>
                                <td>
                                    <div class="casual-cell">
                                        <div class="casual-avatar">
                                            {{ getInitials(casual.name) }}
                                        </div>
                                        <div class="casual-info">
                                            <span class="casual-name">{{ casual.name }}</span>
                                            <span v-if="casual.email" class="casual-email">{{ casual.email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="department-cell">{{ casual.department?.name || '-' }}</span>
                                </td>
                                <td>
                                    <span class="job-title-cell">{{ casual.job_title || '-' }}</span>
                                </td>
                                <td>
                                    <span class="phone-cell">{{ casual.phone || '-' }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="jobs-count-badge">{{ casual.site_jobs_count || 0 }}</span>
                                </td>
                                <td class="text-center">
                                    <span :class="casual.is_active ? 'badge badge-success badge-dot' : 'badge badge-secondary badge-dot'">
                                        {{ casual.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="action-buttons">
                                        <button @click="openEditModal(casual)" class="btn btn-sm btn-ghost" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button @click="deleteCasual(casual.id)" class="btn btn-sm btn-ghost btn-ghost-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="lastPage > 1" class="pagination-container">
                    <div class="pagination-info">
                        Showing {{ (currentPage - 1) * perPage + 1 }} to {{ Math.min(currentPage * perPage, total) }} of {{ total }} employees
                    </div>
                    <div class="pagination-controls">
                        <button
                            @click="goToPage(currentPage - 1)"
                            :disabled="currentPage === 1"
                            class="pagination-btn"
                        >
                            <i class="bi bi-chevron-left"></i>
                            Previous
                        </button>
                        <div class="pagination-pages">
                            <button
                                v-for="page in visiblePages"
                                :key="page"
                                @click="goToPage(page)"
                                :class="['pagination-page', { active: page === currentPage }]"
                            >
                                {{ page }}
                            </button>
                        </div>
                        <button
                            @click="goToPage(currentPage + 1)"
                            :disabled="currentPage === lastPage"
                            class="pagination-btn"
                        >
                            Next
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Transition name="modal">
            <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
                <div class="modal-content">
                    <div class="modal-header modal-header-primary">
                        <h5>{{ editingCasual ? 'Edit Employee' : 'Add New Employee' }}</h5>
                        <button @click="closeModal" class="modal-close">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <form @submit.prevent="saveCasual" class="modal-body">
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label class="form-label">Full Name *</label>
                                <input v-model="form.name" type="text" required class="form-control" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Department</label>
                                <select v-model="form.department_id" class="form-control">
                                    <option :value="null">Select Department</option>
                                    <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                                        {{ dept.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Job Title</label>
                                <input v-model="form.job_title" type="text" class="form-control" placeholder="e.g. Laborer, Mason" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone Number</label>
                                <input v-model="form.phone" type="text" class="form-control" />
                            </div>
                            <div class="form-group full-width">
                                <label class="form-label">Email Address</label>
                                <input v-model="form.email" type="email" class="form-control" />
                            </div>
                            <div class="form-group full-width">
                                <label class="form-label">Notes</label>
                                <textarea v-model="form.notes" rows="2" class="form-control"></textarea>
                            </div>
                            <div class="form-group full-width">
                                <label class="checkbox-label">
                                    <input v-model="form.is_active" type="checkbox" class="form-checkbox" />
                                    <span class="checkbox-custom"></span>
                                    <span class="checkbox-text">Active Worker</span>
                                </label>
                            </div>
                        </div>
                    </form>
                    <div class="modal-footer">
                        <button type="button" @click="closeModal" class="btn btn-secondary">
                            Cancel
                        </button>
                        <button @click="saveCasual" :disabled="saving" class="btn btn-primary">
                            <span v-if="saving" class="spinner"></span>
                            <span v-else>
                                <i class="bi bi-check-lg"></i>
                                {{ editingCasual ? 'Update Employee' : 'Add Employee' }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Import Modal -->
        <Transition name="modal">
            <div v-if="showImportModal" class="modal-backdrop" @click.self="closeImportModal">
                <div class="modal-content">
                    <div class="modal-header modal-header-primary">
                        <h5>Import Employees</h5>
                        <button @click="closeImportModal" class="modal-close">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div
                            class="upload-zone"
                            :class="{ 'drag-over': isDragging }"
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="handleFileDrop"
                        >
                            <input
                                type="file"
                                ref="fileInput"
                                @change="handleFileSelect"
                                accept=".xlsx,.xls,.csv"
                                class="file-input"
                            />
                            <div class="upload-content">
                                <i class="bi bi-cloud-upload"></i>
                                <p>Drag and drop your Excel file here</p>
                                <p class="upload-hint">or click to browse (xlsx, xls, csv)</p>
                                <p v-if="selectedFile" class="selected-file">
                                    <i class="bi bi-file-earmark-excel"></i>
                                    {{ selectedFile.name }}
                                </p>
                            </div>
                        </div>

                        <!-- Import Results -->
                        <div v-if="importResult" class="import-result">
                            <div v-if="importResult.imported > 0" class="result-success">
                                <i class="bi bi-check-circle"></i>
                                {{ importResult.imported }} employees imported successfully
                            </div>
                            <div v-if="importResult.failed > 0" class="result-errors">
                                <div class="error-header">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    {{ importResult.failed }} rows failed
                                </div>
                                <ul class="error-list">
                                    <li v-for="(error, index) in importResult.errors" :key="index">
                                        Row {{ error.row }}: {{ error.errors.join(', ') }}
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Template Download -->
                        <div class="template-info">
                            <p>Required columns: <strong>name</strong></p>
                            <p>Optional columns: department, job_title, phone, email, notes, is_active</p>
                            <p>Departments: Finance, Transport, Executive, General Staff, Casual</p>
                            <a href="#" @click.prevent="api.download(api.exports.casualsImportTemplate())" class="template-download-link">
                                <i class="bi bi-download"></i>
                                Download Import Template
                            </a>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" @click="closeImportModal" class="btn btn-secondary">
                            Close
                        </button>
                        <button
                            @click="importFile"
                            :disabled="!selectedFile || importing"
                            class="btn btn-primary"
                        >
                            <span v-if="importing" class="spinner"></span>
                            <span v-else>
                                <i class="bi bi-upload"></i>
                                Import
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Report Modal -->
        <Transition name="modal">
            <div v-if="showReportModal" class="modal-backdrop" @click.self="closeReportModal">
                <div class="modal-content modal-large">
                    <div class="modal-header modal-header-primary">
                        <h5>Employees Jobs Report</h5>
                        <button @click="closeReportModal" class="modal-close">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Date Range Picker -->
                        <div class="date-range-picker">
                            <div class="preset-buttons">
                                <button
                                    v-for="preset in datePresets"
                                    :key="preset.label"
                                    @click="applyDatePreset(preset)"
                                    class="btn btn-sm"
                                    :class="selectedPreset === preset.label ? 'btn-primary' : 'btn-secondary'"
                                >
                                    {{ preset.label }}
                                </button>
                            </div>
                            <div class="custom-date-range">
                                <div class="form-group">
                                    <label class="form-label">From Date</label>
                                    <input v-model="reportFromDate" type="date" class="form-control" />
                                </div>
                                <div class="form-group">
                                    <label class="form-label">To Date</label>
                                    <input v-model="reportToDate" type="date" class="form-control" />
                                </div>
                            </div>
                            <div class="report-actions">
                                <button @click="generateReport" :disabled="loadingReport" class="btn btn-primary">
                                    <span v-if="loadingReport" class="spinner"></span>
                                    <span v-else>
                                        <i class="bi bi-search"></i>
                                        Generate Report
                                    </span>
                                </button>
                                <a
                                    v-if="reportData"
                                    href="#" @click.prevent="api.download(api.exports.casualsReportExcel(reportFromDate, reportToDate))"
                                    class="btn btn-secondary"
                                >
                                    <i class="bi bi-file-earmark-excel"></i>
                                    Export Excel
                                </a>
                            </div>
                        </div>

                        <!-- Report Results -->
                        <div v-if="reportData" class="report-results">
                            <div class="report-summary">
                                <div class="summary-card">
                                    <span class="summary-value">{{ reportData.length }}</span>
                                    <span class="summary-label">Employees</span>
                                </div>
                                <div class="summary-card">
                                    <span class="summary-value">{{ totalJobAssignments }}</span>
                                    <span class="summary-label">Job Assignments</span>
                                </div>
                            </div>

                            <div class="report-table-container">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Employee #</th>
                                            <th>Name</th>
                                            <th>Job Title</th>
                                            <th class="text-center">Jobs Count</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="casual in reportData" :key="casual.id">
                                            <td>
                                                <span class="casual-number">{{ casual.casual_number }}</span>
                                            </td>
                                            <td>{{ casual.name }}</td>
                                            <td>{{ casual.job_title || '-' }}</td>
                                            <td class="text-center">
                                                <span class="jobs-count-badge">{{ casual.jobs_count }}</span>
                                            </td>
                                            <td>
                                                <span :class="casual.is_active ? 'badge badge-success' : 'badge badge-secondary'">
                                                    {{ casual.is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';
import api from '../api';

const loading = ref(true);
const saving = ref(false);
const casuals = ref([]);
const departments = ref([]);
const searchQuery = ref('');
const showModal = ref(false);
const editingCasual = ref(null);
const showExportDropdown = ref(false);

// Pagination
const currentPage = ref(1);
const lastPage = ref(1);
const perPage = ref(15);
const total = ref(0);

const form = ref({
    name: '',
    department_id: null,
    job_title: '',
    phone: '',
    email: '',
    notes: '',
    is_active: true,
});

// Import state
const showImportModal = ref(false);
const selectedFile = ref(null);
const isDragging = ref(false);
const importing = ref(false);
const importResult = ref(null);
const fileInput = ref(null);

// Report state
const showReportModal = ref(false);
const reportFromDate = ref('');
const reportToDate = ref('');
const selectedPreset = ref('This Month');
const loadingReport = ref(false);
const reportData = ref(null);

const datePresets = [
    { label: 'This Month', getRange: () => {
        const now = new Date();
        const start = new Date(now.getFullYear(), now.getMonth(), 1);
        const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        return { from: formatDateForInput(start), to: formatDateForInput(end) };
    }},
    { label: 'Last Month', getRange: () => {
        const now = new Date();
        const start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        const end = new Date(now.getFullYear(), now.getMonth(), 0);
        return { from: formatDateForInput(start), to: formatDateForInput(end) };
    }},
    { label: 'This Quarter', getRange: () => {
        const now = new Date();
        const quarter = Math.floor(now.getMonth() / 3);
        const start = new Date(now.getFullYear(), quarter * 3, 1);
        const end = new Date(now.getFullYear(), quarter * 3 + 3, 0);
        return { from: formatDateForInput(start), to: formatDateForInput(end) };
    }},
    { label: 'This Year', getRange: () => {
        const now = new Date();
        const start = new Date(now.getFullYear(), 0, 1);
        const end = new Date(now.getFullYear(), 11, 31);
        return { from: formatDateForInput(start), to: formatDateForInput(end) };
    }},
];

const formatDateForInput = (date) => {
    return date.toISOString().split('T')[0];
};

const visiblePages = computed(() => {
    const pages = [];
    const maxVisible = 5;
    let start = Math.max(1, currentPage.value - Math.floor(maxVisible / 2));
    let end = Math.min(lastPage.value, start + maxVisible - 1);

    if (end - start + 1 < maxVisible) {
        start = Math.max(1, end - maxVisible + 1);
    }

    for (let i = start; i <= end; i++) {
        pages.push(i);
    }
    return pages;
});

const totalJobAssignments = computed(() => {
    if (!reportData.value) return 0;
    return reportData.value.reduce((sum, c) => sum + c.jobs_count, 0);
});

const getInitials = (name) => name ? name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '?';

const loadEmployees = async (page = 1) => {
    try {
        loading.value = true;
        const params = {
            page,
            per_page: perPage.value,
        };
        if (searchQuery.value) {
            params.search = searchQuery.value;
        }
        const response = await api.casuals.getAll(params);

        // Handle paginated response
        if (response.data.data) {
            casuals.value = response.data.data;
            currentPage.value = response.data.current_page;
            lastPage.value = response.data.last_page;
            total.value = response.data.total;
        } else {
            casuals.value = response.data;
            total.value = response.data.length;
        }
    } catch (error) {
        console.error('Error loading casuals:', error);
    } finally {
        loading.value = false;
    }
};

const goToPage = (page) => {
    if (page >= 1 && page <= lastPage.value) {
        loadEmployees(page);
    }
};

let searchTimeout = null;

const handleSearch = () => {
    currentPage.value = 1;
    loadEmployees(1);
};

const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        handleSearch();
    }, 300);
};

const loadDepartments = async () => {
    try {
        const response = await api.departments.getAll();
        departments.value = response.data;
    } catch (error) {
        console.error('Error loading departments:', error);
    }
};

const openCreateModal = () => {
    editingCasual.value = null;
    form.value = {
        name: '',
        department_id: null,
        job_title: '',
        phone: '',
        email: '',
        notes: '',
        is_active: true,
    };
    showModal.value = true;
};

const openEditModal = (casual) => {
    editingCasual.value = casual;
    form.value = { ...casual };
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingCasual.value = null;
};

const saveCasual = async () => {
    try {
        saving.value = true;
        if (editingCasual.value) {
            await api.casuals.update(editingCasual.value.id, form.value);
        } else {
            await api.casuals.create(form.value);
        }
        await loadEmployees();
        closeModal();
    } catch (error) {
        console.error('Error saving casual:', error);
    } finally {
        saving.value = false;
    }
};

const deleteCasual = async (id) => {
    if (!confirm('Are you sure you want to delete this casual? This action cannot be undone.')) return;
    try {
        await api.casuals.delete(id);
        await loadEmployees();
    } catch (error) {
        console.error('Error deleting casual:', error);
    }
};

// Export dropdown
const toggleExportDropdown = () => {
    showExportDropdown.value = !showExportDropdown.value;
};

const closeExportDropdown = (e) => {
    if (!e.target.closest('.dropdown')) {
        showExportDropdown.value = false;
    }
};

// Import functions
const openImportModal = () => {
    showImportModal.value = true;
    showExportDropdown.value = false;
    selectedFile.value = null;
    importResult.value = null;
};

const closeImportModal = () => {
    showImportModal.value = false;
    selectedFile.value = null;
    importResult.value = null;
};

const handleFileSelect = (e) => {
    selectedFile.value = e.target.files[0];
    importResult.value = null;
};

const handleFileDrop = (e) => {
    isDragging.value = false;
    const file = e.dataTransfer.files[0];
    if (file && (file.name.endsWith('.xlsx') || file.name.endsWith('.xls') || file.name.endsWith('.csv'))) {
        selectedFile.value = file;
        importResult.value = null;
    }
};

const importFile = async () => {
    if (!selectedFile.value) return;
    try {
        importing.value = true;
        const response = await api.imports.casuals(selectedFile.value);
        importResult.value = response.data;
        if (response.data.imported > 0) {
            await loadEmployees();
        }
    } catch (error) {
        console.error('Error importing casuals:', error);
        importResult.value = {
            imported: 0,
            failed: 1,
            errors: [{ row: 0, errors: ['Upload failed. Please try again.'] }]
        };
    } finally {
        importing.value = false;
    }
};

// Report functions
const openReportModal = () => {
    showReportModal.value = true;
    showExportDropdown.value = false;
    reportData.value = null;
    applyDatePreset(datePresets[0]);
};

const closeReportModal = () => {
    showReportModal.value = false;
    reportData.value = null;
};

const applyDatePreset = (preset) => {
    selectedPreset.value = preset.label;
    const range = preset.getRange();
    reportFromDate.value = range.from;
    reportToDate.value = range.to;
};

const generateReport = async () => {
    try {
        loadingReport.value = true;
        const response = await api.casuals.getJobsReport(reportFromDate.value, reportToDate.value);
        reportData.value = response.data;
    } catch (error) {
        console.error('Error generating report:', error);
    } finally {
        loadingReport.value = false;
    }
};

onMounted(() => {
    loadEmployees();
    loadDepartments();
    document.addEventListener('click', closeExportDropdown);
});

onUnmounted(() => {
    document.removeEventListener('click', closeExportDropdown);
    clearTimeout(searchTimeout);
});
</script>

<style scoped>

.search-input::placeholder {
    color: var(--gray-400);
}

/* Dropdown */
.dropdown {
    position: relative;
}

.dropdown-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 0.5rem;
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    min-width: 180px;
    z-index: 100;
    overflow: hidden;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: var(--gray-700);
    font-size: 0.875rem;
    text-decoration: none;
    border: none;
    background: none;
    width: 100%;
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.dropdown-item:hover {
    background: var(--gray-100);
}

.dropdown-item i {
    font-size: 1rem;
    color: var(--gray-500);
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.casual-count {
    font-size: 0.8125rem;
    font-weight: 500;
    opacity: 0.9;
}

.table tbody tr:last-child td {
    border-bottom: none;
}

/* Casual Number */
.casual-number {
    font-weight: 700;
    color: var(--primary-600);
    font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Fira Mono', monospace;
    font-size: 0.8125rem;
}

/* Casual Cell */
.casual-cell {
    display: flex;
    align-items: center;
    gap: 0.875rem;
}

.casual-avatar {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.625rem;
    background: linear-gradient(135deg, var(--info-100) 0%, var(--info-200) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8125rem;
    font-weight: 700;
    color: var(--info-600);
    flex-shrink: 0;
}

.casual-info {
    display: flex;
    flex-direction: column;
}

.casual-name {
    font-weight: 600;
    color: var(--gray-900);
}

.casual-email {
    font-size: 0.75rem;
    color: var(--gray-400);
}

.department-cell,
.job-title-cell,
.phone-cell {
    color: var(--gray-500);
}

.jobs-count-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2rem;
    height: 1.5rem;
    padding: 0 0.5rem;
    background: var(--info-50);
    color: var(--info-600);
    font-weight: 600;
    font-size: 0.8125rem;
    border-radius: 9999px;
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

.modal-large {
    max-width: 900px;
}

.modal-header-primary h5 {
    color: white;
}

.form-checkbox {
    width: 1rem;
    height: 1rem;
    border-radius: 0.25rem;
    border: 1px solid var(--gray-300);
    cursor: pointer;
    accent-color: var(--primary-600);
}

.checkbox-text {
    font-size: 0.75rem;
    color: var(--gray-700);
}

/* Upload Zone */
.upload-zone {
    border: 2px dashed var(--gray-300);
    border-radius: 0.75rem;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    margin-bottom: 1rem;
}

.upload-zone:hover,
.upload-zone.drag-over {
    border-color: var(--primary-600);
    background: var(--accent-50);
}

.file-input {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
}

.upload-content i {
    font-size: 2.5rem;
    color: var(--gray-400);
    margin-bottom: 0.5rem;
}

.upload-content p {
    margin: 0.25rem 0;
    color: var(--gray-500);
}

.upload-hint {
    font-size: 0.8125rem;
    color: var(--gray-400);
}

.selected-file {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.75rem;
    padding: 0.5rem 1rem;
    background: var(--info-50);
    color: var(--info-600);
    border-radius: 0.5rem;
    font-weight: 500;
}

/* Import Result */
.import-result {
    margin-bottom: 1rem;
}

.result-success {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: var(--success-100);
    color: var(--success-700);
    border-radius: 0.5rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.result-errors {
    padding: 0.75rem 1rem;
    background: var(--danger-100);
    color: var(--danger-600);
    border-radius: 0.5rem;
}

.error-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.error-list {
    margin: 0;
    padding-left: 1.5rem;
    font-size: 0.8125rem;
}

.template-info {
    padding: 0.75rem 1rem;
    background: var(--gray-100);
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    color: var(--gray-500);
}

.template-info p {
    margin: 0.25rem 0;
}

.template-download-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.75rem;
    padding: 0.5rem 1rem;
    background: var(--primary-600);
    color: white;
    border-radius: 0.5rem;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.8125rem;
    transition: background-color 0.2s ease;
}

.template-download-link:hover {
    background: var(--primary-700);
}

/* Date Range Picker */
.date-range-picker {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.preset-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.custom-date-range {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.report-actions {
    display: flex;
    gap: 0.75rem;
}

/* Report Results */
.report-results {
    border-top: 1px solid var(--gray-200);
    padding-top: 1rem;
}

.report-summary {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.report-table-container {
    max-height: 300px;
    overflow-y: auto;
    border-radius: 0.5rem;
    border: 1px solid var(--gray-200);
}

.modal-enter-from .modal-content,
.modal-leave-to .modal-content {
    transform: translateY(20px) scale(0.98);
}

/* Pagination */
.pagination-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--gray-100);
    background: var(--gray-50);
}

.pagination-info {
    font-size: 0.875rem;
    color: var(--gray-500);
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.pagination-btn {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--gray-700);
    background: white;
    border: 1px solid var(--gray-300);
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.pagination-btn:hover:not(:disabled) {
    background: var(--gray-100);
    border-color: var(--gray-400);
}

.pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.pagination-pages {
    display: flex;
    gap: 0.25rem;
}

.pagination-page {
    min-width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--gray-700);
    background: white;
    border: 1px solid var(--gray-300);
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.pagination-page:hover {
    background: var(--gray-100);
    border-color: var(--gray-400);
}

.pagination-page.active {
    background: var(--primary-600);
    border-color: var(--primary-600);
    color: white;
}

@media (max-width: 768px) {
    .pagination-container {
        flex-direction: column;
        gap: 1rem;
    }

    .pagination-info {
        text-align: center;
    }
}

@media (max-width: 480px) {
    .custom-date-range {
        grid-template-columns: 1fr;
    }

    .pagination-pages {
        display: none;
    }
}
</style>
