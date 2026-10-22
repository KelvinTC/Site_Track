<template>
    <AppLayout>
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <h1>Aging Report</h1>
                    <p class="header-subtitle">Accounts receivable aging analysis</p>
                </div>
                <div class="header-actions">
                    <div class="filter-group">
                        <label>As of Date</label>
                        <input type="date" v-model="asOfDate" @change="loadAging" class="date-input" />
                    </div>
                    <button @click="exportReport" class="btn btn-secondary">
                        <i class="bi bi-file-earmark-excel"></i>
                        <span>Export</span>
                    </button>
                    <button @click="loadAging" class="btn btn-primary" :disabled="loading">
                        <i class="bi bi-arrow-clockwise" :class="{ 'spin': loading }"></i>
                        <span>Refresh</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-container">
            <div class="loader"></div>
            <p class="loading-text">Loading aging report...</p>
        </div>

        <template v-else>
            <!-- Aging Summary Cards -->
            <div class="aging-summary">
                <div class="aging-card aging-current">
                    <div class="aging-header">
                        <span class="aging-label">Current</span>
                        <span class="aging-count">{{ agingData.summary?.current?.count || 0 }}</span>
                    </div>
                    <div class="aging-amount">${{ formatNumber(agingData.summary?.current?.total || 0) }}</div>
                    <div class="aging-bar">
                        <div class="aging-bar-fill" :style="{ width: getPercentage('current') + '%' }"></div>
                    </div>
                </div>

                <div class="aging-card aging-1-30">
                    <div class="aging-header">
                        <span class="aging-label">1-30 Days</span>
                        <span class="aging-count">{{ agingData.summary?.['1-30']?.count || 0 }}</span>
                    </div>
                    <div class="aging-amount">${{ formatNumber(agingData.summary?.['1-30']?.total || 0) }}</div>
                    <div class="aging-bar">
                        <div class="aging-bar-fill" :style="{ width: getPercentage('1-30') + '%' }"></div>
                    </div>
                </div>

                <div class="aging-card aging-31-60">
                    <div class="aging-header">
                        <span class="aging-label">31-60 Days</span>
                        <span class="aging-count">{{ agingData.summary?.['31-60']?.count || 0 }}</span>
                    </div>
                    <div class="aging-amount">${{ formatNumber(agingData.summary?.['31-60']?.total || 0) }}</div>
                    <div class="aging-bar">
                        <div class="aging-bar-fill" :style="{ width: getPercentage('31-60') + '%' }"></div>
                    </div>
                </div>

                <div class="aging-card aging-61-90">
                    <div class="aging-header">
                        <span class="aging-label">61-90 Days</span>
                        <span class="aging-count">{{ agingData.summary?.['61-90']?.count || 0 }}</span>
                    </div>
                    <div class="aging-amount">${{ formatNumber(agingData.summary?.['61-90']?.total || 0) }}</div>
                    <div class="aging-bar">
                        <div class="aging-bar-fill" :style="{ width: getPercentage('61-90') + '%' }"></div>
                    </div>
                </div>

                <div class="aging-card aging-90plus">
                    <div class="aging-header">
                        <span class="aging-label">90+ Days</span>
                        <span class="aging-count">{{ agingData.summary?.['90+']?.count || 0 }}</span>
                    </div>
                    <div class="aging-amount">${{ formatNumber(agingData.summary?.['90+']?.total || 0) }}</div>
                    <div class="aging-bar">
                        <div class="aging-bar-fill" :style="{ width: getPercentage('90+') + '%' }"></div>
                    </div>
                </div>

                <div class="aging-card aging-total">
                    <div class="aging-header">
                        <span class="aging-label">Total Outstanding</span>
                        <span class="aging-count">{{ agingData.invoice_count || 0 }}</span>
                    </div>
                    <div class="aging-amount">${{ formatNumber(agingData.total_outstanding || 0) }}</div>
                    <div class="aging-bar">
                        <div class="aging-bar-fill" style="width: 100%"></div>
                    </div>
                </div>
            </div>

            <!-- Filters Bar -->
            <div class="filters-bar">
                <div class="filter-group">
                    <label>Search</label>
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input
                            type="text"
                            v-model="searchQuery"
                            placeholder="Search customer or invoice..."
                            class="search-input"
                        />
                    </div>
                </div>
                <div class="filter-group">
                    <label>Customer</label>
                    <select v-model="filterCustomer" class="filter-select">
                        <option value="">All Customers</option>
                        <option v-for="customer in uniqueCustomers" :key="customer.id" :value="customer.id">
                            {{ customer.name }}
                        </option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Aging Bucket</label>
                    <select v-model="filterBucket" class="filter-select">
                        <option value="">All Buckets</option>
                        <option value="current">Current</option>
                        <option value="1-30">1-30 Days</option>
                        <option value="31-60">31-60 Days</option>
                        <option value="61-90">61-90 Days</option>
                        <option value="90+">90+ Days</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Min Amount</label>
                    <input
                        type="number"
                        v-model="filterMinAmount"
                        placeholder="0.00"
                        class="filter-input"
                        min="0"
                        step="0.01"
                    />
                </div>
                <button @click="clearFilters" class="btn btn-ghost" v-if="hasActiveFilters">
                    <i class="bi bi-x-circle"></i>
                    Clear Filters
                </button>
            </div>

            <!-- Toggle View -->
            <div class="view-toggle">
                <button @click="viewMode = 'customer'" :class="['toggle-btn', { active: viewMode === 'customer' }]">
                    <i class="bi bi-people"></i> By Customer
                </button>
                <button @click="viewMode = 'invoice'" :class="['toggle-btn', { active: viewMode === 'invoice' }]">
                    <i class="bi bi-receipt"></i> All Invoices
                </button>
            </div>

            <!-- By Customer View -->
            <div v-if="viewMode === 'customer'" class="card">
                <div class="card-header card-header-warning">
                    <div class="header-left">
                        <i class="bi bi-people-fill"></i>
                        <span>Aging by Customer</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div v-if="!filteredCustomers.length" class="empty-state">
                        <i class="bi bi-check-circle"></i>
                        <h3>{{ hasActiveFilters ? 'No Matching Results' : 'No Outstanding Invoices' }}</h3>
                        <p>{{ hasActiveFilters ? 'Try adjusting your filters.' : 'All invoices have been paid!' }}</p>
                        <button v-if="hasActiveFilters" @click="clearFilters" class="btn btn-primary">Clear Filters</button>
                    </div>
                    <div v-else class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th class="text-center">Invoices</th>
                                    <th class="text-right">Current</th>
                                    <th class="text-right">1-30</th>
                                    <th class="text-right">31-60</th>
                                    <th class="text-right">61-90</th>
                                    <th class="text-right">90+</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="customer in filteredCustomers" :key="customer.customer_id">
                                    <td>
                                        <div class="customer-cell">
                                            <div class="customer-avatar">{{ getInitials(customer.customer_name) }}</div>
                                            <span>{{ customer.customer_name }}</span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="invoice-count-badge">{{ customer.invoice_count }}</span>
                                    </td>
                                    <td class="text-right">
                                        <span :class="{ 'has-value': customer.buckets.current > 0 }">
                                            ${{ formatNumber(customer.buckets.current) }}
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <span :class="{ 'has-value warning': customer.buckets['1-30'] > 0 }">
                                            ${{ formatNumber(customer.buckets['1-30']) }}
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <span :class="{ 'has-value danger-light': customer.buckets['31-60'] > 0 }">
                                            ${{ formatNumber(customer.buckets['31-60']) }}
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <span :class="{ 'has-value danger': customer.buckets['61-90'] > 0 }">
                                            ${{ formatNumber(customer.buckets['61-90']) }}
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <span :class="{ 'has-value danger-dark': customer.buckets['90+'] > 0 }">
                                            ${{ formatNumber(customer.buckets['90+']) }}
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <span class="total-amount">${{ formatNumber(customer.total_balance) }}</span>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="totals-row">
                                    <td><strong>Total{{ hasActiveFilters ? ' (Filtered)' : '' }}</strong></td>
                                    <td class="text-center"><strong>{{ filteredTotals.count }}</strong></td>
                                    <td class="text-right"><strong>${{ formatNumber(filteredTotals.current || 0) }}</strong></td>
                                    <td class="text-right"><strong>${{ formatNumber(filteredTotals['1-30'] || 0) }}</strong></td>
                                    <td class="text-right"><strong>${{ formatNumber(filteredTotals['31-60'] || 0) }}</strong></td>
                                    <td class="text-right"><strong>${{ formatNumber(filteredTotals['61-90'] || 0) }}</strong></td>
                                    <td class="text-right"><strong>${{ formatNumber(filteredTotals['90+'] || 0) }}</strong></td>
                                    <td class="text-right"><strong>${{ formatNumber(filteredTotals.total || 0) }}</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- All Invoices View -->
            <div v-else class="card">
                <div class="card-header card-header-primary">
                    <div class="header-left">
                        <i class="bi bi-receipt"></i>
                        <span>All Outstanding Invoices</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div v-if="!filteredInvoices.length" class="empty-state">
                        <i class="bi bi-check-circle"></i>
                        <h3>{{ hasActiveFilters ? 'No Matching Results' : 'No Outstanding Invoices' }}</h3>
                        <p>{{ hasActiveFilters ? 'Try adjusting your filters.' : 'All invoices have been paid!' }}</p>
                        <button v-if="hasActiveFilters" @click="clearFilters" class="btn btn-primary">Clear Filters</button>
                    </div>
                    <div v-else class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th>Job #</th>
                                    <th class="text-center">Due Date</th>
                                    <th class="text-center">Days Overdue</th>
                                    <th class="text-center">Aging</th>
                                    <th class="text-right">Total</th>
                                    <th class="text-right">Paid</th>
                                    <th class="text-right">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="invoice in filteredInvoices" :key="invoice.id">
                                    <td>
                                        <span class="invoice-number">{{ invoice.invoice_number }}</span>
                                    </td>
                                    <td>{{ invoice.customer_name }}</td>
                                    <td>
                                        <span class="job-number">{{ invoice.job_number || '-' }}</span>
                                    </td>
                                    <td class="text-center">{{ formatDate(invoice.due_date) }}</td>
                                    <td class="text-center">
                                        <span v-if="invoice.days_overdue > 0" class="days-overdue">
                                            {{ invoice.days_overdue }} days
                                        </span>
                                        <span v-else class="days-current">Current</span>
                                    </td>
                                    <td class="text-center">
                                        <span :class="['aging-badge', 'badge-' + invoice.bucket]">
                                            {{ invoice.bucket_label }}
                                        </span>
                                    </td>
                                    <td class="text-right">${{ formatNumber(invoice.total) }}</td>
                                    <td class="text-right">${{ formatNumber(invoice.amount_paid) }}</td>
                                    <td class="text-right">
                                        <span class="balance-amount">${{ formatNumber(invoice.balance) }}</span>
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
import AppLayout from '../Layouts/AppLayout.vue';
import api from '../api';

const loading = ref(true);
const asOfDate = ref(new Date().toISOString().split('T')[0]);
const agingData = ref({});
const viewMode = ref('customer');

// Filter state
const searchQuery = ref('');
const filterCustomer = ref('');
const filterBucket = ref('');
const filterMinAmount = ref('');

const formatNumber = (num) => new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0);
const formatDate = (date) => date ? new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : '-';
const getInitials = (name) => name ? name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '?';

const getPercentage = (bucket) => {
    const total = agingData.value.total_outstanding || 0;
    if (total === 0) return 0;
    const bucketTotal = agingData.value.summary?.[bucket]?.total || 0;
    return Math.round((bucketTotal / total) * 100);
};

// Get unique customers for filter dropdown
const uniqueCustomers = computed(() => {
    if (!agingData.value.by_customer) return [];
    return agingData.value.by_customer.map(c => ({
        id: c.customer_id,
        name: c.customer_name
    })).sort((a, b) => a.name.localeCompare(b.name));
});

// Check if any filters are active
const hasActiveFilters = computed(() => {
    return searchQuery.value || filterCustomer.value || filterBucket.value || filterMinAmount.value;
});

// Filtered invoices
const filteredInvoices = computed(() => {
    if (!agingData.value.invoices) return [];

    let result = [...agingData.value.invoices];

    // Search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(inv =>
            inv.invoice_number?.toLowerCase().includes(query) ||
            inv.customer_name?.toLowerCase().includes(query) ||
            inv.job_number?.toLowerCase().includes(query)
        );
    }

    // Customer filter
    if (filterCustomer.value) {
        result = result.filter(inv => inv.customer_id == filterCustomer.value);
    }

    // Bucket filter
    if (filterBucket.value) {
        result = result.filter(inv => inv.bucket === filterBucket.value);
    }

    // Min amount filter
    if (filterMinAmount.value) {
        const minAmt = parseFloat(filterMinAmount.value);
        result = result.filter(inv => inv.balance >= minAmt);
    }

    return result;
});

// Filtered customers (for by customer view)
const filteredCustomers = computed(() => {
    if (!agingData.value.by_customer) return [];

    let result = [...agingData.value.by_customer];

    // Search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(c => c.customer_name?.toLowerCase().includes(query));
    }

    // Customer filter
    if (filterCustomer.value) {
        result = result.filter(c => c.customer_id == filterCustomer.value);
    }

    // Bucket filter - show only customers with balance in that bucket
    if (filterBucket.value) {
        result = result.filter(c => c.buckets[filterBucket.value] > 0);
    }

    // Min amount filter
    if (filterMinAmount.value) {
        const minAmt = parseFloat(filterMinAmount.value);
        result = result.filter(c => c.total_balance >= minAmt);
    }

    return result;
});

// Filtered totals
const filteredTotals = computed(() => {
    const invoices = filteredInvoices.value;
    const buckets = ['current', '1-30', '31-60', '61-90', '90+'];
    const totals = {};

    buckets.forEach(bucket => {
        totals[bucket] = invoices.filter(i => i.bucket === bucket).reduce((sum, i) => sum + i.balance, 0);
    });

    totals.total = invoices.reduce((sum, i) => sum + i.balance, 0);
    totals.count = invoices.length;

    return totals;
});

// Clear all filters
const clearFilters = () => {
    searchQuery.value = '';
    filterCustomer.value = '';
    filterBucket.value = '';
    filterMinAmount.value = '';
};

const loadAging = async () => {
    try {
        loading.value = true;
        const response = await api.invoices.getAging(asOfDate.value);
        agingData.value = response.data;
    } catch (error) {
        console.error('Error loading aging report:', error);
    } finally {
        loading.value = false;
    }
};

const exportReport = () => {
    // TODO: Implement Excel export for aging report
    alert('Export functionality coming soon!');
};

onMounted(() => {
    loadAging();
});
</script>

<style scoped>
.page-header {
    margin-bottom: 2rem;
}

.header-content {
    display: flex;
    flex-direction: column;
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
}

.header-subtitle {
    color: #6b7280;
    font-size: 0.9375rem;
    margin: 0.5rem 0 0;
}

.header-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    align-items: flex-end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.filter-group label {
    font-size: 0.6875rem;
    font-weight: 500;
    color: #6b7280;
}

.date-input {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
}

/* Aging Summary Cards */
.aging-summary {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

@media (min-width: 768px) {
    .aging-summary {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (min-width: 1024px) {
    .aging-summary {
        grid-template-columns: repeat(6, 1fr);
    }
}

.aging-card {
    background: white;
    border-radius: 1rem;
    padding: 1.25rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    border-left: 4px solid;
}

.aging-current { border-left-color: #10b981; }
.aging-1-30 { border-left-color: #f59e0b; }
.aging-31-60 { border-left-color: #f97316; }
.aging-61-90 { border-left-color: #ef4444; }
.aging-90plus { border-left-color: #7f1d1d; }
.aging-total { border-left-color: #4f46e5; background: linear-gradient(135deg, #eef2ff, #e0e7ff); }

.aging-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.aging-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.aging-count {
    font-size: 0.6875rem;
    font-weight: 700;
    background: #f3f4f6;
    color: #374151;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
}

.aging-amount {
    font-size: 1.25rem;
    font-weight: 800;
    color: #111827;
    margin-bottom: 0.75rem;
}

.aging-bar {
    height: 4px;
    background: #e5e7eb;
    border-radius: 2px;
    overflow: hidden;
}

.aging-bar-fill {
    height: 100%;
    border-radius: 2px;
    transition: width 0.5s ease;
}

.aging-current .aging-bar-fill { background: #10b981; }
.aging-1-30 .aging-bar-fill { background: #f59e0b; }
.aging-31-60 .aging-bar-fill { background: #f97316; }
.aging-61-90 .aging-bar-fill { background: #ef4444; }
.aging-90plus .aging-bar-fill { background: #7f1d1d; }
.aging-total .aging-bar-fill { background: #4f46e5; }

/* View Toggle */
.view-toggle {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.toggle-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    background: white;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s ease;
}

.toggle-btn:hover {
    background: #f9fafb;
}

.toggle-btn.active {
    background: #4f46e5;
    color: white;
    border-color: #4f46e5;
}

/* Card */
.card {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    overflow: hidden;
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
}

.card-header-warning {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.card-header-primary {
    background: linear-gradient(135deg, #4f46e5, #4338ca);
    color: white;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    font-weight: 600;
}

.card-body {
    padding: 1rem;
}

.p-0 { padding: 0 !important; }

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

.table tbody td {
    padding: 1rem;
    vertical-align: middle;
    border-bottom: 1px solid #f3f4f6;
}

.table tbody tr:hover {
    background: #f9fafb;
}

.totals-row {
    background: #f9fafb;
}

.totals-row td {
    border-top: 2px solid #e5e7eb;
}

/* Customer Cell */
.customer-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.customer-avatar {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.5rem;
    background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
    color: #4f46e5;
}

.invoice-count-badge {
    display: inline-block;
    min-width: 1.5rem;
    padding: 0.25rem 0.5rem;
    background: #e0e7ff;
    color: #4f46e5;
    font-weight: 600;
    font-size: 0.75rem;
    border-radius: 9999px;
}

.invoice-number {
    font-weight: 700;
    color: #4f46e5;
    font-family: monospace;
}

.job-number {
    color: #6b7280;
    font-size: 0.8125rem;
}

.has-value {
    font-weight: 600;
}

.has-value.warning { color: #b45309; }
.has-value.danger-light { color: #c2410c; }
.has-value.danger { color: #dc2626; }
.has-value.danger-dark { color: #7f1d1d; font-weight: 700; }

.total-amount {
    font-weight: 700;
    color: #111827;
}

.balance-amount {
    font-weight: 700;
    color: #dc2626;
}

.days-overdue {
    color: #dc2626;
    font-weight: 600;
}

.days-current {
    color: #10b981;
    font-weight: 600;
}

/* Aging Badges */
.aging-badge {
    display: inline-block;
    padding: 0.25rem 0.625rem;
    font-size: 0.6875rem;
    font-weight: 700;
    border-radius: 0.375rem;
}

.badge-current { background: #d1fae5; color: #059669; }
.badge-1-30 { background: #fef3c7; color: #b45309; }
.badge-31-60 { background: #fed7aa; color: #c2410c; }
.badge-61-90 { background: #fecaca; color: #dc2626; }
.badge-90\+ { background: #7f1d1d; color: #fef2f2; }

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem;
    color: #10b981;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.empty-state h3 {
    font-size: 1.125rem;
    font-weight: 700;
    margin: 0 0 0.5rem;
}

.empty-state p {
    color: #6b7280;
    margin: 0;
}

/* Loading */
.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 4rem;
}

.loader {
    width: 3rem;
    height: 3rem;
    border: 3px solid #e5e7eb;
    border-top-color: #f59e0b;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.loading-text {
    margin-top: 1rem;
    color: #6b7280;
}

.spin {
    animation: spin 1s linear infinite;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.5rem;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-primary {
    background: #4f46e5;
    color: white;
}

.btn-primary:hover {
    background: #4338ca;
}

.btn-secondary {
    background: #f3f4f6;
    color: #374151;
}

.btn-secondary:hover {
    background: #e5e7eb;
}

/* Filters Bar */
.filters-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-end;
    background: white;
    padding: 1rem 1.25rem;
    border-radius: 0.75rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

.search-box {
    position: relative;
    display: flex;
    align-items: center;
}

.search-box i {
    position: absolute;
    left: 0.75rem;
    color: #9ca3af;
    font-size: 0.875rem;
}

.search-input {
    width: 220px;
    padding: 0.5rem 0.75rem 0.5rem 2.25rem;
    font-size: 0.875rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    background: #f9fafb;
    transition: all 0.2s ease;
}

.search-input:focus {
    outline: none;
    border-color: #4f46e5;
    background: white;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.filter-select {
    min-width: 160px;
    padding: 0.5rem 2rem 0.5rem 0.75rem;
    font-size: 0.875rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    background: #f9fafb url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") right 0.5rem center/1.25rem no-repeat;
    appearance: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.filter-select:focus {
    outline: none;
    border-color: #4f46e5;
    background-color: white;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.filter-input {
    width: 120px;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    background: #f9fafb;
    transition: all 0.2s ease;
}

.filter-input:focus {
    outline: none;
    border-color: #4f46e5;
    background: white;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.btn-ghost {
    background: transparent;
    color: #ef4444;
    border: 1px solid transparent;
}

.btn-ghost:hover {
    background: #fef2f2;
    border-color: #fecaca;
}

/* Utilities */
.text-center { text-align: center; }
.text-right { text-align: right; }
</style>
