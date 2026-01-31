<template>
    <AppLayout>
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <h1>Payments</h1>
                    <p class="header-subtitle">Track and record payment transactions</p>
                </div>
                <div class="header-actions">
                    <div class="search-box">
                        <i class="bi bi-search search-icon"></i>
                        <input v-model="searchQuery" type="text" placeholder="Search payments..." class="search-input" />
                    </div>
                    <select v-model="methodFilter" class="filter-select">
                        <option value="">All Methods</option>
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                    </select>
                    <button @click="openCreateModal" class="btn btn-success">
                        <i class="bi bi-plus-lg"></i>
                        <span>Add Payment</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-primary">
                    <i class="bi bi-receipt"></i>
                </div>
                <div class="stat-value">{{ payments.length }}</div>
                <div class="stat-label">Total Payments</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-success">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
                <div class="stat-value">${{ formatNumber(thisMonthTotal) }}</div>
                <div class="stat-label">This Month</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-info">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div class="stat-value">${{ formatNumber(allTimeTotal) }}</div>
                <div class="stat-label">All Time</div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-container">
            <div class="loader"></div>
            <p class="loading-text">Loading payments...</p>
        </div>

        <!-- Payments Card -->
        <div v-else class="card">
            <div class="card-header card-header-primary">
                <div class="header-left">
                    <i class="bi bi-credit-card-fill"></i>
                    <span>Payment History</span>
                </div>
                <span class="payment-count">{{ filteredPayments.length }} payments</span>
            </div>
            <div class="card-body p-0">
                <!-- Empty State -->
                <div v-if="filteredPayments.length === 0" class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-credit-card"></i>
                    </div>
                    <h3 class="empty-state-title">No payments found</h3>
                    <p class="empty-state-description">
                        {{ searchQuery || methodFilter ? 'Try adjusting your filters.' : 'Record your first payment to get started.' }}
                    </p>
                    <button v-if="!searchQuery && !methodFilter" @click="openCreateModal" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i>
                        Record First Payment
                    </button>
                </div>

                <!-- Payments Table -->
                <div v-else class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Invoice</th>
                                <th>Customer</th>
                                <th class="text-center">Method</th>
                                <th class="text-right">Amount</th>
                                <th>Reference</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="payment in filteredPayments" :key="payment.id">
                                <td>
                                    <span class="date-cell">{{ formatDate(payment.payment_date) }}</span>
                                </td>
                                <td>
                                    <span class="invoice-ref">{{ payment.invoice?.invoice_number || 'N/A' }}</span>
                                </td>
                                <td>
                                    <div class="customer-cell">
                                        <div class="customer-avatar">
                                            {{ getInitials(payment.invoice?.customer?.name) }}
                                        </div>
                                        <span>{{ payment.invoice?.customer?.name || 'N/A' }}</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span :class="getMethodBadgeClass(payment.payment_method)">
                                        {{ formatMethod(payment.payment_method) }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <span class="amount-cell">+${{ formatNumber(payment.amount) }}</span>
                                </td>
                                <td>
                                    <span class="reference-cell">{{ payment.reference || '-' }}</span>
                                </td>
                                <td class="text-right">
                                    <div class="action-buttons">
                                        <button @click="openEditModal(payment)" class="btn btn-sm btn-ghost" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button @click="deletePayment(payment.id)" class="btn btn-sm btn-ghost btn-ghost-danger" title="Delete">
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

        <!-- Create/Edit Payment Modal -->
        <Transition name="modal">
            <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
                <div class="modal-content">
                    <div class="modal-header modal-header-primary">
                        <h5>{{ editingPayment ? 'Edit Payment' : 'Record Payment' }}</h5>
                        <button @click="closeModal" class="modal-close">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <form @submit.prevent="savePayment" class="modal-body">
                        <div class="form-group">
                            <label class="form-label">Invoice *</label>
                            <select v-model="form.invoice_id" required class="form-control">
                                <option value="">Select an invoice</option>
                                <option v-for="invoice in invoiceOptions" :key="invoice.id" :value="invoice.id">
                                    {{ invoice.invoice_number }} - {{ invoice.customer?.name || 'Unknown' }} (${{ formatNumber(invoice.total) }})
                                </option>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Amount *</label>
                                <div class="input-wrapper">
                                    <span class="input-prefix">$</span>
                                    <input v-model="form.amount" type="number" step="0.01" min="0" required class="form-control with-prefix" placeholder="0.00" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Payment Date *</label>
                                <input v-model="form.payment_date" type="date" required class="form-control" />
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Payment Method</label>
                            <div class="method-options">
                                <label class="method-option" :class="{ active: form.payment_method === 'cash' }">
                                    <input type="radio" v-model="form.payment_method" value="cash" />
                                    <i class="bi bi-cash-stack"></i>
                                    <span>Cash</span>
                                </label>
                                <label class="method-option" :class="{ active: form.payment_method === 'bank_transfer' }">
                                    <input type="radio" v-model="form.payment_method" value="bank_transfer" />
                                    <i class="bi bi-bank"></i>
                                    <span>Bank Transfer</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Reference Number</label>
                            <input v-model="form.reference" type="text" class="form-control" placeholder="e.g., Transaction ID" />
                        </div>

                        <div class="form-group">
                            <label class="form-label">Notes</label>
                            <textarea v-model="form.notes" rows="2" class="form-control" placeholder="Add any notes about this payment..."></textarea>
                        </div>
                    </form>
                    <div class="modal-footer">
                        <button type="button" @click="closeModal" class="btn btn-secondary">Cancel</button>
                        <button @click="savePayment" :disabled="saving" class="btn btn-success">
                            <span v-if="saving" class="spinner"></span>
                            <span v-else>
                                <i class="bi bi-check-lg"></i>
                                {{ editingPayment ? 'Update' : 'Record' }} Payment
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
const payments = ref([]);
const invoices = ref([]);
const searchQuery = ref('');
const methodFilter = ref('');
const showModal = ref(false);
const editingPayment = ref(null);

const getEmptyForm = () => ({
    invoice_id: '',
    amount: '',
    payment_date: new Date().toISOString().split('T')[0],
    payment_method: 'cash',
    reference: '',
    notes: '',
});

const form = ref(getEmptyForm());

const invoiceOptions = computed(() =>
    invoices.value.filter(i => ['unpaid', 'partial', 'sent'].includes(i.status))
);

const filteredPayments = computed(() => {
    let filtered = payments.value;
    if (methodFilter.value) {
        filtered = filtered.filter(p => p.payment_method === methodFilter.value);
    }
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(p =>
            p.invoice?.invoice_number?.toLowerCase().includes(query) ||
            p.invoice?.customer?.name?.toLowerCase().includes(query) ||
            p.reference?.toLowerCase().includes(query)
        );
    }
    return filtered;
});

const thisMonthTotal = computed(() => {
    const now = new Date();
    const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
    return payments.value
        .filter(p => new Date(p.payment_date) >= startOfMonth)
        .reduce((sum, p) => sum + parseFloat(p.amount || 0), 0);
});

const allTimeTotal = computed(() =>
    payments.value.reduce((sum, p) => sum + parseFloat(p.amount || 0), 0)
);

const formatNumber = (num) => new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0);
const formatDate = (date) => new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
const formatMethod = (method) => method?.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
const getInitials = (name) => name ? name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '?';

const getMethodBadgeClass = (method) => {
    const classes = {
        cash: 'badge badge-success',
        bank_transfer: 'badge badge-primary',
    };
    return classes[method] || 'badge badge-secondary';
};

const loadData = async () => {
    try {
        loading.value = true;
        const [paymentsRes, invoicesRes] = await Promise.all([
            api.payments.getAll(),
            api.invoices.getAll(),
        ]);
        payments.value = paymentsRes.data;
        invoices.value = invoicesRes.data;
    } catch (error) {
        console.error('Error loading data:', error);
    } finally {
        loading.value = false;
    }
};

const openCreateModal = () => {
    editingPayment.value = null;
    form.value = getEmptyForm();
    showModal.value = true;
};

const openEditModal = (payment) => {
    editingPayment.value = payment;
    form.value = {
        invoice_id: payment.invoice_id,
        amount: payment.amount,
        payment_date: payment.payment_date,
        payment_method: payment.payment_method,
        reference: payment.reference || '',
        notes: payment.notes || '',
    };
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingPayment.value = null;
};

const savePayment = async () => {
    try {
        saving.value = true;
        if (editingPayment.value) {
            await api.payments.update(editingPayment.value.id, form.value);
        } else {
            await api.payments.create(form.value);
        }
        await loadData();
        closeModal();
    } catch (error) {
        console.error('Error saving payment:', error);
    } finally {
        saving.value = false;
    }
};

const deletePayment = async (id) => {
    if (!confirm('Are you sure you want to delete this payment?')) return;
    try {
        await api.payments.delete(id);
        await loadData();
    } catch (error) {
        console.error('Error deleting payment:', error);
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
}

.search-input {
    width: 100%;
    padding: 0.625rem 1rem 0.625rem 2.75rem;
    font-size: 0.9375rem;
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
    background: white;
    border: 1.5px solid #e5e7eb;
    border-radius: 0.75rem;
    min-width: 130px;
    cursor: pointer;
}

.filter-select:focus {
    outline: none;
    border-color: #4f46e5;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.25rem;
    margin-bottom: 2.5rem;
}

@media (max-width: 640px) {
    .stats-grid {
        grid-template-columns: 1fr;
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
    text-align: center;
    transition: all 0.25s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.stat-card:hover {
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    transform: translateY(-3px);
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
}

.icon-primary { background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%); color: #4f46e5; }
.icon-success { background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); color: #059669; }
.icon-info { background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #2563eb; }

.stat-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: #111827;
}

.stat-label {
    font-size: 0.75rem;
    color: #6b7280;
    font-weight: 600;
    text-transform: uppercase;
    margin-top: 0.25rem;
}

/* Loading */
.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
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

@keyframes spin { to { transform: rotate(360deg); } }

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

.payment-count {
    font-size: 0.8125rem;
    font-weight: 500;
    opacity: 0.9;
}

/* Table */
.table-container { overflow-x: auto; }

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
    padding: 0.875rem 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.table tbody tr:hover {
    background: linear-gradient(90deg, rgba(99, 102, 241, 0.04) 0%, transparent 100%);
}

.table tbody td {
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
}

.date-cell {
    font-weight: 600;
    color: #111827;
}

.invoice-ref {
    color: #4f46e5;
    font-family: monospace;
}

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

.amount-cell {
    font-weight: 700;
    color: #059669;
}

.reference-cell {
    color: #6b7280;
}

/* Badges */
.badge {
    display: inline-flex;
    align-items: center;
    padding: 0.375rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-primary { background: #e0e7ff; color: #4338ca; }
.badge-success { background: #d1fae5; color: #047857; }
.badge-warning { background: #fef3c7; color: #b45309; }
.badge-info { background: #dbeafe; color: #1d4ed8; }
.badge-secondary { background: #f3f4f6; color: #4b5563; }

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

.btn-ghost:hover { background: #f3f4f6; color: #4f46e5; }
.btn-ghost-danger:hover { background: #fee2e2; color: #dc2626; }

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
    border-radius: 0.75rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    width: 100%;
    max-width: 420px;
    max-height: calc(100vh - 2rem);
    overflow-y: auto;
}

.modal-header {
    padding: 0.75rem 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-header h5 {
    font-size: 0.875rem;
    font-weight: 600;
    margin: 0;
    color: white;
}

.modal-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 1.5rem;
    height: 1.5rem;
    border-radius: 0.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.modal-body { padding: 1rem; }

.modal-footer {
    padding: 0.75rem 1rem;
    border-top: 1px solid #f3f4f6;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    background: #f9fafb;
}

/* Form */
.form-group { margin-bottom: 0.75rem; }

.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}

.form-label {
    display: block;
    font-weight: 500;
    font-size: 0.6875rem;
    color: #6b7280;
    margin-bottom: 0.25rem;
}

.form-control {
    width: 100%;
    padding: 0.5rem 0.625rem;
    font-size: 0.75rem;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
}

.form-control:focus {
    outline: none;
    border-color: #4f46e5;
}

.input-wrapper {
    position: relative;
}

.input-prefix {
    position: absolute;
    left: 0.625rem;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
    font-weight: 500;
    font-size: 0.75rem;
}

.form-control.with-prefix {
    padding-left: 1.5rem;
}

/* Method Options */
.method-options {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}

.method-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1.25rem 1rem;
    background: #f9fafb;
    border: 1.5px solid #e5e7eb;
    border-radius: 0.75rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.method-option input {
    display: none;
}

.method-option i {
    font-size: 1.5rem;
    color: #6b7280;
}

.method-option span {
    font-size: 0.875rem;
    font-weight: 600;
    color: #6b7280;
}

.method-option:hover {
    border-color: #4f46e5;
}

.method-option.active {
    background: #eef2ff;
    border-color: #4f46e5;
}

.method-option.active i,
.method-option.active span {
    color: #4f46e5;
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
.modal-enter-active, .modal-leave-active { transition: all 0.3s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }

/* Utilities */
.p-0 { padding: 0 !important; }
.text-center { text-align: center; }
.text-right { text-align: right; }
</style>
