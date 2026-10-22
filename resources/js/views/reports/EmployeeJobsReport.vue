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
                                                <span class="stat">{{ employee.jobs.length }} jobs</span>
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

const loading = ref(true);
const startDate = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0]);
const endDate = ref(new Date().toISOString().split('T')[0]);
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
    window.open(`/api/reports/employee-jobs/export?${params}`, '_blank');
};

onMounted(() => {
    loadReport();
});
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
.search-input { width: 220px; padding: 0.5rem 0.75rem 0.5rem 2.25rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: #f9fafb; }
.search-input:focus { outline: none; border-color: #4f46e5; background: white; box-shadow: 0 0 0 3px rgba(79,70,229,0.1); }
.filter-select { min-width: 160px; padding: 0.5rem 2rem 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: #f9fafb url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") right 0.5rem center/1.25rem no-repeat; appearance: none; cursor: pointer; }
.filter-select:focus { outline: none; border-color: #4f46e5; background-color: white; box-shadow: 0 0 0 3px rgba(79,70,229,0.1); }

.summary-cards { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
@media (min-width: 768px) { .summary-cards { grid-template-columns: repeat(4, 1fr); } }
.summary-card { background: white; border-radius: 1rem; padding: 1.25rem; display: flex; align-items: center; gap: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
.summary-icon { width: 3rem; height: 3rem; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
.summary-icon.blue { background: #dbeafe; color: #2563eb; }
.summary-icon.green { background: #d1fae5; color: #059669; }
.summary-icon.purple { background: #ede9fe; color: #7c3aed; }
.summary-icon.orange { background: #fed7aa; color: #ea580c; }
.summary-content { display: flex; flex-direction: column; }
.summary-value { font-size: 1.5rem; font-weight: 800; color: #111827; }
.summary-label { font-size: 0.75rem; color: #6b7280; }

.card { background: white; border-radius: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden; }
.card-header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.5rem; background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white; }
.header-left { display: flex; align-items: center; gap: 0.625rem; font-weight: 600; }
.card-body { padding: 1rem; }
.p-0 { padding: 0 !important; }

.table-container { overflow-x: auto; }
.table { width: 100%; font-size: 0.875rem; border-collapse: collapse; }
.table thead th { background: #f9fafb; color: #6b7280; font-weight: 600; font-size: 0.75rem; text-transform: uppercase; padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
.table tbody td { padding: 0.75rem 1rem; vertical-align: middle; border-bottom: 1px solid #f3f4f6; }

.employee-row { background: #f8fafc; }
.employee-row td { padding: 0 !important; }
.employee-header { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; }
.employee-avatar { width: 2.5rem; height: 2.5rem; border-radius: 0.625rem; background: linear-gradient(135deg, #4f46e5, #7c3aed); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 0.875rem; }
.employee-info { flex: 1; }
.employee-name { display: block; font-weight: 700; color: #111827; }
.employee-dept { font-size: 0.75rem; color: #6b7280; }
.employee-stats { display: flex; gap: 1.5rem; }
.employee-stats .stat { font-size: 0.875rem; color: #6b7280; }
.employee-stats .stat.total { font-weight: 700; color: #059669; }

.job-row { background: white; }
.job-row:hover { background: #f9fafb; }
.job-number { font-family: monospace; font-weight: 600; color: #4f46e5; }

.empty-state { text-align: center; padding: 3rem; color: #6b7280; }
.empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.5; }
.empty-state h3 { font-size: 1.125rem; font-weight: 700; margin: 0 0 0.5rem; color: #374151; }
.empty-state p { margin: 0; }

.loading-container { display: flex; flex-direction: column; align-items: center; padding: 4rem; }
.loader { width: 3rem; height: 3rem; border: 3px solid #e5e7eb; border-top-color: #4f46e5; border-radius: 50%; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.spin { animation: spin 1s linear infinite; }

.btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; border: none; cursor: pointer; transition: all 0.2s; }
.btn-primary { background: #4f46e5; color: white; }
.btn-primary:hover { background: #4338ca; }
.btn-secondary { background: #f3f4f6; color: #374151; }
.btn-secondary:hover { background: #e5e7eb; }
.btn-ghost { background: transparent; color: #ef4444; }
.btn-ghost:hover { background: #fef2f2; }

.text-center { text-align: center; }
.text-right { text-align: right; }
</style>
