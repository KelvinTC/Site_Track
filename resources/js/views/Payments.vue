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
                    <a href="#" @click.prevent="api.download(api.exports.paymentsExcel())" class="btn btn-secondary">
                        <i class="bi bi-file-earmark-excel"></i>
                        <span>Export</span>
                    </a>
                    <button @click="openCreateModal" class="btn btn-primary">
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
                <span class="payment-count">{{ filteredPayments.length }} {{ filteredPayments.length === 1 ? 'payment' : 'payments' }}</span>
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
                                    <span class="reference-cell">{{ payment.reference_number || '-' }}</span>
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
                            <input v-model="form.reference_number" type="text" class="form-control" placeholder="e.g., Transaction ID" />
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
import { toDateInput } from '@/utils/date';

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
    payment_date: toDateInput(),
    payment_method: 'cash',
    reference_number: '',
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
        reference_number: payment.reference_number || '',
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

.icon-primary { background: linear-gradient(135deg, var(--primary-50) 0%, var(--primary-100) 100%); color: var(--primary-600); }
.icon-success { background: linear-gradient(135deg, var(--success-100) 0%, var(--success-200) 100%); color: var(--success-600); }
.icon-info { background: linear-gradient(135deg, var(--info-100) 0%, var(--info-200) 100%); color: var(--info-600); }

.stat-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--gray-900);
}

.stat-label {
    font-size: 0.75rem;
    color: var(--gray-500);
    font-weight: 600;
    text-transform: uppercase;
    margin-top: 0.25rem;
}

@keyframes spin { to { transform: rotate(360deg); } }

.payment-count {
    font-size: 0.8125rem;
    font-weight: 500;
    opacity: 0.9;
}

.date-cell {
    font-weight: 600;
    color: var(--gray-900);
}

.invoice-ref {
    color: var(--primary-600);
    font-family: monospace;
}

.amount-cell {
    font-weight: 700;
    color: var(--success-600);
}

.reference-cell {
    color: var(--gray-500);
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

.badge-primary { background: var(--primary-100); color: var(--primary-700); }
.badge-warning { background: var(--warning-100); color: var(--warning-700); }
.badge-info { background: var(--info-100); color: var(--info-700); }

.input-wrapper {
    position: relative;
}

.input-prefix {
    position: absolute;
    left: 0.625rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray-500);
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
    background: var(--gray-50);
    border: 1.5px solid var(--gray-200);
    border-radius: 0.75rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.method-option input {
    display: none;
}

.method-option i {
    font-size: 1.5rem;
    color: var(--gray-500);
}

.method-option span {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--gray-500);
}

.method-option:hover {
    border-color: var(--primary-600);
}

.method-option.active {
    background: var(--primary-50);
    border-color: var(--primary-600);
}

.method-option.active i,
.method-option.active span {
    color: var(--primary-600);
}

</style>
