<template>
    <AppLayout>
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <h1>Employee Jobs Report</h1>
                    <p class="header-subtitle">Jobs assigned to employees</p>
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
                    <button @click="exportReport('csv')" class="btn btn-secondary">
                        <i class="bi bi-filetype-csv"></i>
                        <span>CSV</span>
                    </button>
                    <button @click="loadReport" class="btn btn-primary" :disabled="loading">
                        <i class="bi bi-arrow-clockwise" :class="{ 'spin': loading }"></i>
                        <span>Refresh</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters Bar -->
        <div class="filters-bar">
            <div class="filter-group">
                <label>Search</label>
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" v-model="searchQuery" placeholder="Search employee or job..." class="search-input" />
                </div>
            </div>
            <div class="filter-group">
                <label>Department</label>
                <select v-model="filterDepartment" class="filter-select">
                    <option value="">All Departments</option>
                    <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Employee</label>
                <select v-model="filterEmployee" class="filter-select">
                    <option value="">All Employees</option>
                    <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }}</option>
                </select>
            </div>
            <button @click="clearFilters" class="btn btn-ghost" v-if="hasActiveFilters">
                <i class="bi bi-x-circle"></i>
                Clear
            </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-container">
            <div class="loader"></div>
            <p>Loading report...</p>
        </div>

        <!-- Summary Cards -->
        <div v-else class="summary-cards">
            <div class="summary-card">
                <div class="summary-icon blue">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="summary-content">
                    <span class="summary-value">{{ summary.total_employees }}</span>
                    <span class="summary-label">Total Employees</span>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon green">
                    <i class="bi bi-briefcase-fill"></i>
                </div>
                <div class="summary-content">
                    <span class="summary-value">{{ summary.total_jobs }}</span>
                    <span class="summary-label">Jobs Assigned</span>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon purple">
                    <i class="bi bi-clock-fill"></i>
                </div>
                <div class="summary-content">
                    <span class="summary-value">{{ summary.total_hours }}</span>
                    <span class="summary-label">Total Hours</span>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon orange">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="summary-content">
                    <span class="summary-value">${{ formatNumber(summary.total_earnings) }}</span>
                    <span class="summary-label">Total Earnings</span>
                </div>
            </div>
        </div>

        <!-- Report Table -->
        <div v-if="!loading" class="card">
            <div class="card-header">
                <div class="header-left">
                    <i class="bi bi-person-lines-fill"></i>
                    <span>Employee Job Assignments</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div v-if="!filteredData.length" class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <h3>No Data Found</h3>
                    <p>No job assignments found for the selected criteria.</p>
                </div>
                <div v-else class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Job #</th>
                                <th>Client</th>
                                <th>Job Description</th>
                                <th class="text-center">Date</th>
                                <th class="text-right">Hours</th>
                                <th class="text-right">Rate</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="employee in filteredData" :key="employee.id">
                                <tr class="employee-row">
                                    <td colspan="9">
                                        <div class="employee-header">
                                            <div class="employee-avatar">{{ getInitials(employee.name) }}</div>
                                            <div class="employee-info">
                                                <span class="employee-name">{{ employee.name }}</span>
                                                <span class="employee-dept">{{ employee.department }}</span>
                                            </div>
                                            <div class="employee-stats">
                                                <span class="stat">{{ employee.jobs.length }} {{ employee.jobs.length === 1 ? 'job' : 'jobs' }}</span>
                                                <span class="stat">{{ employee.total_hours }} hrs</span>
                                                <span class="stat total">${{ formatNumber(employee.total_earnings) }}</span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-for="job in employee.jobs" :key="job.id" class="job-row">
                                    <td></td>
                                    <td></td>
                                    <td><span class="job-number">{{ job.job_number }}</span></td>
                                    <td>{{ job.client_name }}</td>
                                    <td>{{ job.description }}</td>
                                    <td class="text-center">{{ formatDate(job.date) }}</td>
                                    <td class="text-right">{{ job.hours }}</td>
                                    <td class="text-right">${{ formatNumber(job.rate) }}</td>
                                    <td class="text-right"><strong>${{ formatNumber(job.amount) }}</strong></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import api from '../../api';
import { toDateInput } from '@/utils/date';

const loading = ref(true);
const startDate = ref(toDateInput(new Date(new Date().getFullYear(), new Date().getMonth(), 1)));
const endDate = ref(toDateInput());
const reportData = ref([]);
const departments = ref([]);
const employees = ref([]);

// Filters
const searchQuery = ref('');
const filterDepartment = ref('');
const filterEmployee = ref('');

const formatNumber = (num) => new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0);
const formatDate = (date) => date ? new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : '-';
const getInitials = (name) => name ? name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '?';

const hasActiveFilters = computed(() => searchQuery.value || filterDepartment.value || filterEmployee.value);

const filteredData = computed(() => {
    let result = [...reportData.value];

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(emp =>
            emp.name?.toLowerCase().includes(query) ||
            emp.jobs?.some(j => j.job_number?.toLowerCase().includes(query) || j.client_name?.toLowerCase().includes(query))
        );
    }

    if (filterDepartment.value) {
        result = result.filter(emp => emp.department_id == filterDepartment.value);
    }

    if (filterEmployee.value) {
        result = result.filter(emp => emp.id == filterEmployee.value);
    }

    return result;
});

const summary = computed(() => {
    const data = filteredData.value;
    return {
        total_employees: data.length,
        total_jobs: data.reduce((sum, emp) => sum + (emp.jobs?.length || 0), 0),
        total_hours: data.reduce((sum, emp) => sum + (emp.total_hours || 0), 0),
        total_earnings: data.reduce((sum, emp) => sum + (emp.total_earnings || 0), 0),
    };
});

const clearFilters = () => {
    searchQuery.value = '';
    filterDepartment.value = '';
    filterEmployee.value = '';
};

const loadReport = async () => {
    try {
        loading.value = true;
        const response = await api.reports.employeeJobs({ start_date: startDate.value, end_date: endDate.value });
        reportData.value = response.data.employees || [];
        departments.value = response.data.departments || [];
        employees.value = response.data.employee_list || [];
    } catch (error) {
        console.error('Error loading report:', error);
    } finally {
        loading.value = false;
    }
};

const exportReport = (format) => {
    const params = new URLSearchParams({
        start_date: startDate.value,
        end_date: endDate.value,
        format
    });
    api.download(`/api/reports/employee-jobs/export?${params}`);
};

onMounted(() => {
    loadReport();
});
</script>

<style scoped>

.date-input { padding: 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid var(--gray-300); border-radius: 0.5rem; }
.date-range { display: flex; align-items: center; gap: 0.5rem; }
.date-range span { color: var(--gray-500); font-size: 0.875rem; }

@media (min-width: 768px) { .summary-cards { grid-template-columns: repeat(4, 1fr); }
}
.summary-icon.blue { background: var(--info-100); color: var(--info-600); }
.summary-icon.purple { background: var(--accent-100); color: var(--accent-600); }

.employee-row { background: var(--gray-50); }
.employee-row td { padding: 0 !important; }
.employee-header { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; }
.employee-avatar { width: 2.5rem; height: 2.5rem; border-radius: 0.625rem; background: linear-gradient(135deg, var(--primary-600), var(--accent-600)); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 0.875rem; }
.employee-info { flex: 1; }
.employee-name { display: block; font-weight: 700; color: var(--gray-900); }
.employee-dept { font-size: 0.75rem; color: var(--gray-500); }
.employee-stats { display: flex; gap: 1.5rem; }
.employee-stats .stat { font-size: 0.875rem; color: var(--gray-500); }
.employee-stats .stat.total { font-weight: 700; color: var(--success-600); }

.job-row { background: white; }
.job-row:hover { background: var(--gray-50); }

@keyframes spin { to { transform: rotate(360deg); } }

</style>
