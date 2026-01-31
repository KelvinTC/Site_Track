<template>
    <AppLayout>
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <h1>Invoices</h1>
                    <p class="header-subtitle">Create and manage invoices for your jobs</p>
                </div>
                <div class="header-actions">
                    <div class="search-box">
                        <i class="bi bi-search search-icon"></i>
                        <input v-model="searchQuery" type="text" placeholder="Search invoices..." class="search-input" />
                    </div>
                    <button @click="openCreateModal" class="btn btn-success">
                        <i class="bi bi-plus-lg"></i>
                        <span>New Invoice</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-primary">
                    <i class="bi bi-file-text-fill"></i>
                </div>
                <div class="stat-value">{{ invoices.length }}</div>
                <div class="stat-label">Total Invoices</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-warning">
                    <i class="bi bi-clock-fill"></i>
                </div>
                <div class="stat-value">${{ formatNumber(unpaidTotal) }}</div>
                <div class="stat-label">Unpaid</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-success">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-value">${{ formatNumber(paidTotal) }}</div>
                <div class="stat-label">Collected</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div class="stat-value">{{ overdueCount }}</div>
                <div class="stat-label">Overdue</div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-container">
            <div class="loader"></div>
            <p class="loading-text">Loading invoices...</p>
        </div>

        <!-- Invoices Card -->
        <div v-else class="card">
            <div class="card-header card-header-primary">
                <div class="header-left">
                    <i class="bi bi-receipt"></i>
                    <span>Invoice List</span>
                </div>
                <span class="invoice-count">{{ filteredInvoices.length }} invoices</span>
            </div>
            <div class="card-body p-0">
                <div v-if="filteredInvoices.length === 0" class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <h3 class="empty-state-title">No invoices found</h3>
                    <p class="empty-state-description">Create your first invoice to start billing.</p>
                    <button @click="openCreateModal" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i>
                        Create First Invoice
                    </button>
                </div>

                <div v-else class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Customer</th>
                                <th>Job</th>
                                <th class="text-right">Total</th>
                                <th class="text-center">Due Date</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="invoice in filteredInvoices" :key="invoice.id">
                                <td>
                                    <span class="invoice-number">{{ invoice.invoice_number }}</span>
                                </td>
                                <td>
                                    <div class="customer-cell">
                                        <div class="customer-avatar">
                                            {{ getInitials(invoice.customer?.name) }}
                                        </div>
                                        <span>{{ invoice.customer?.name || 'Unknown' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="job-ref">{{ invoice.siteJob?.job_number || invoice.site_job?.job_number || '-' }}</span>
                                </td>
                                <td class="text-right">
                                    <span class="total-amount">${{ formatNumber(invoice.total) }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="date-cell">{{ invoice.due_date ? formatDate(invoice.due_date) : '-' }}</span>
                                </td>
                                <td class="text-right">
                                    <div class="action-buttons">
                                        <button @click="viewInvoice(invoice)" class="btn btn-sm btn-ghost" title="View">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button @click="deleteInvoice(invoice.id)" class="btn btn-sm btn-ghost btn-ghost-danger" title="Delete">
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

        <!-- Create Invoice Modal -->
        <Transition name="modal">
            <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
                <div class="modal-content modal-lg">
                    <div class="modal-header modal-header-primary">
                        <h5>Create Invoice</h5>
                        <button @click="closeModal" class="modal-close">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <form @submit.prevent="saveInvoice" class="modal-body">
                        <div class="form-group">
                            <label class="form-label">Job *</label>
                            <select v-model="form.site_job_id" required class="form-control">
                                <option value="">Select a job</option>
                                <option v-for="job in jobs" :key="job.id" :value="job.id">
                                    {{ job.job_number }} - {{ job.title }} ({{ job.customer?.name }})
                                </option>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Issue Date *</label>
                                <input v-model="form.issue_date" type="date" required class="form-control" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Due Date *</label>
                                <input v-model="form.due_date" type="date" required class="form-control" />
                            </div>
                        </div>

                        <!-- Line Items -->
                        <div class="line-items-section">
                            <div class="line-items-header">
                                <span class="line-items-title">Line Items</span>
                                <button type="button" @click="addLineItem" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus"></i> Add Item
                                </button>
                            </div>
                            <div class="line-items-body">
                                <div v-for="(item, index) in form.items" :key="index" class="line-item">
                                    <div class="line-item-grid">
                                        <div class="line-item-desc">
                                            <label v-if="index === 0" class="form-label-sm">Description</label>
                                            <input v-model="item.description" type="text" required class="form-control-sm" placeholder="Item description" />
                                        </div>
                                        <div class="line-item-type">
                                            <label v-if="index === 0" class="form-label-sm">Type</label>
                                            <select v-model="item.type" required class="form-control-sm">
                                                <option value="labor">Labor</option>
                                                <option value="material">Material</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                        <div class="line-item-qty">
                                            <label v-if="index === 0" class="form-label-sm">Qty</label>
                                            <input v-model.number="item.quantity" type="number" min="0" step="0.01" required class="form-control-sm" />
                                        </div>
                                        <div class="line-item-price">
                                            <label v-if="index === 0" class="form-label-sm">Price</label>
                                            <input v-model.number="item.unit_price" type="number" step="0.01" min="0" required class="form-control-sm" />
                                        </div>
                                        <div class="line-item-total">
                                            <label v-if="index === 0" class="form-label-sm">Total</label>
                                            <span class="item-total">${{ formatNumber(item.quantity * item.unit_price) }}</span>
                                        </div>
                                        <div class="line-item-action">
                                            <button type="button" @click="removeLineItem(index)" :disabled="form.items.length === 1" class="btn-remove">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="line-items-footer">
                                <strong>Total: ${{ formatNumber(subtotal) }}</strong>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Notes</label>
                            <textarea v-model="form.notes" rows="2" class="form-control" placeholder="Optional notes for the invoice"></textarea>
                        </div>
                    </form>
                    <div class="modal-footer">
                        <button type="button" @click="closeModal" class="btn btn-secondary">Cancel</button>
                        <button @click="saveInvoice" :disabled="saving" class="btn btn-primary">
                            <span v-if="saving" class="spinner"></span>
                            <span v-else>
                                <i class="bi bi-check-lg"></i>
                                Create Invoice
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- View Invoice Modal -->
        <Transition name="modal">
            <div v-if="showViewModal" class="modal-backdrop" @click.self="showViewModal = false">
                <div class="modal-content modal-md">
                    <div class="modal-header">
                        <h5>Invoice Preview</h5>
                        <button @click="printInvoice" class="btn btn-sm btn-primary">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
                    <div v-if="viewingInvoice" class="modal-body invoice-preview" id="invoice-print">
                        <!-- Invoice Header -->
                        <div class="invoice-header-row">
                            <div class="invoice-company">
                                <img src="/images/logo.png" alt="Company Logo" class="company-logo-img" />
                                <div class="company-details">
                                    <p class="company-name">{{ companyProfile.company_name }}</p>
                                    <p v-if="companyProfile.address">{{ companyProfile.address }}</p>
                                    <p v-if="companyProfile.city || companyProfile.state">{{ [companyProfile.city, companyProfile.state].filter(Boolean).join(', ') }}</p>
                                    <p v-if="companyProfile.phone">{{ companyProfile.phone }}</p>
                                    <p v-if="companyProfile.email">{{ companyProfile.email }}</p>
                                </div>
                            </div>
                            <div class="invoice-title-block">
                                <h2 class="invoice-title">INVOICE</h2>
                            </div>
                        </div>

                        <!-- Invoice Info Row -->
                        <div class="invoice-info-row">
                            <div class="bill-to">
                                <p class="detail-label">Bill To:</p>
                                <p class="detail-value">{{ viewingInvoice.customer?.name }}</p>
                                <p class="detail-sub">{{ viewingInvoice.customer?.address }}</p>
                                <p v-if="viewingInvoice.customer?.city" class="detail-sub">{{ [viewingInvoice.customer?.city, viewingInvoice.customer?.state].filter(Boolean).join(', ') }}</p>
                            </div>
                            <div class="invoice-meta">
                                <div class="invoice-meta-item">
                                    <span class="meta-label">Invoice #</span>
                                    <span class="meta-value">{{ viewingInvoice.invoice_number }}</span>
                                </div>
                                <div class="invoice-meta-item">
                                    <span class="meta-label">Date</span>
                                    <span class="meta-value">{{ formatDate(viewingInvoice.issue_date) }}</span>
                                </div>
                                <div class="invoice-meta-item">
                                    <span class="meta-label">Due Date</span>
                                    <span class="meta-value">{{ viewingInvoice.due_date ? formatDate(viewingInvoice.due_date) : 'N/A' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <table class="invoice-table">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th class="text-center">Type</th>
                                    <th class="text-right">Qty</th>
                                    <th class="text-right">Price</th>
                                    <th class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in viewingInvoice.items" :key="item.id">
                                    <td>{{ item.description }}</td>
                                    <td class="text-center"><span class="type-badge">{{ item.type }}</span></td>
                                    <td class="text-right">{{ item.quantity }}</td>
                                    <td class="text-right">${{ formatNumber(item.unit_price) }}</td>
                                    <td class="text-right font-semibold">${{ formatNumber(item.total || item.quantity * item.unit_price) }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Total -->
                        <div class="invoice-total">
                            <span>Total</span>
                            <span class="total-value">${{ formatNumber(viewingInvoice.total) }}</span>
                        </div>

                        <!-- Notes -->
                        <div v-if="viewingInvoice.notes || companyProfile.invoice_notes" class="invoice-notes">
                            <p class="notes-label">Notes:</p>
                            <p v-if="viewingInvoice.notes">{{ viewingInvoice.notes }}</p>
                            <p v-if="companyProfile.invoice_notes">{{ companyProfile.invoice_notes }}</p>
                        </div>

                        <!-- Footer -->
                        <div class="invoice-footer">
                            {{ companyProfile.invoice_footer || 'Thank you for your business!' }}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button @click="showViewModal = false" class="btn btn-secondary">Close</button>
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
const invoices = ref([]);
const jobs = ref([]);
const companyProfile = ref({});
const searchQuery = ref('');
const showModal = ref(false);
const showViewModal = ref(false);
const viewingInvoice = ref(null);
const formErrors = ref({});

const getEmptyForm = () => ({
    site_job_id: '',
    issue_date: new Date().toISOString().split('T')[0],
    due_date: '',
    notes: '',
    items: [{ description: '', type: 'labor', quantity: 1, unit_price: 0 }],
});

const form = ref(getEmptyForm());

const filteredInvoices = computed(() => {
    if (!searchQuery.value) return invoices.value;
    const query = searchQuery.value.toLowerCase();
    return invoices.value.filter(i =>
        i.invoice_number?.toLowerCase().includes(query) ||
        i.customer?.name?.toLowerCase().includes(query)
    );
});

const subtotal = computed(() =>
    form.value.items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0)
);

const unpaidTotal = computed(() =>
    invoices.value
        .filter(i => ['unpaid', 'partial', 'sent', 'overdue'].includes(i.status))
        .reduce((sum, i) => sum + parseFloat(i.total || 0), 0)
);

const paidTotal = computed(() =>
    invoices.value
        .filter(i => i.status === 'paid')
        .reduce((sum, i) => sum + parseFloat(i.total || 0), 0)
);

const overdueCount = computed(() =>
    invoices.value.filter(i => i.status === 'overdue').length
);

const formatNumber = (num) => new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0);
const formatDate = (date) => new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
const getInitials = (name) => name ? name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '?';

const loadData = async () => {
    try {
        loading.value = true;
        const [invoicesRes, jobsRes, profileRes] = await Promise.all([
            api.invoices.getAll(),
            api.jobs.getAll(),
            api.companyProfile.get(),
        ]);
        invoices.value = invoicesRes.data;
        jobs.value = jobsRes.data;
        companyProfile.value = profileRes.data;
    } catch (error) {
        console.error('Error loading data:', error);
    } finally {
        loading.value = false;
    }
};

const openCreateModal = () => {
    form.value = getEmptyForm();
    formErrors.value = {};
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const viewInvoice = (invoice) => {
    viewingInvoice.value = invoice;
    showViewModal.value = true;
};

const addLineItem = () => {
    form.value.items.push({ description: '', type: 'labor', quantity: 1, unit_price: 0 });
};

const removeLineItem = (index) => {
    if (form.value.items.length > 1) {
        form.value.items.splice(index, 1);
    }
};

const saveInvoice = async () => {
    try {
        saving.value = true;
        formErrors.value = {};
        await api.invoices.create(form.value);
        await loadData();
        closeModal();
    } catch (error) {
        console.error('Error saving invoice:', error);
        if (error.response?.status === 422 && error.response?.data?.errors) {
            formErrors.value = error.response.data.errors;
            alert('Validation errors:\n' + Object.values(error.response.data.errors).flat().join('\n'));
        } else {
            alert('Error creating invoice. Please try again.');
        }
    } finally {
        saving.value = false;
    }
};

const deleteInvoice = async (id) => {
    if (!confirm('Are you sure you want to delete this invoice?')) return;
    try {
        await api.invoices.delete(id);
        await loadData();
    } catch (error) {
        console.error('Error deleting invoice:', error);
    }
};

const printInvoice = () => {
    window.print();
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
    min-width: 200px;
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

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.25rem;
    margin-bottom: 2.5rem;
}

@media (min-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 1.75rem;
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
.icon-warning { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #d97706; }
.icon-success { background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); color: #059669; }
.icon-danger { background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); color: #dc2626; }

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

.invoice-count {
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

.invoice-number {
    font-weight: 700;
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

.job-ref { color: #6b7280; }

.total-amount {
    font-weight: 700;
    color: #111827;
}

.date-cell { color: #6b7280; }

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
    border-radius: 0.5rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    width: 100%;
    max-height: calc(100vh - 2rem);
    overflow-y: auto;
}

.modal-lg { max-width: 450px; }
.modal-md { max-width: 500px; }

.modal-header {
    padding: 0.4rem 0.6rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #f3f4f6;
}

.modal-header h5 {
    font-size: 0.6875rem;
    font-weight: 600;
    margin: 0;
}

.modal-header-primary { background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%); }
.modal-header-primary h5 { color: white; }

.modal-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 1.25rem;
    height: 1.25rem;
    border-radius: 0.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.625rem;
}

.modal-body { padding: 0.5rem; }
.modal-footer {
    padding: 0.4rem 0.6rem;
    border-top: 1px solid #f3f4f6;
    display: flex;
    justify-content: flex-end;
    gap: 0.3rem;
    background: #f9fafb;
}

/* Form */
.form-group { margin-bottom: 0.35rem; }

.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.35rem;
}

.form-label {
    display: block;
    font-weight: 500;
    font-size: 0.5rem;
    color: #6b7280;
    margin-bottom: 0.1rem;
}

.form-label-sm {
    font-size: 0.5rem;
    font-weight: 500;
    color: #6b7280;
    margin-bottom: 0.05rem;
}

.form-control {
    width: 100%;
    padding: 0.15rem 0.25rem;
    font-size: 0.5625rem;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 0.2rem;
}

.form-control:focus {
    outline: none;
    border-color: #4f46e5;
}

.form-control-sm {
    width: 100%;
    padding: 0.1rem 0.2rem;
    font-size: 0.5rem;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 0.15rem;
}

.form-control-sm:focus {
    outline: none;
    border-color: #4f46e5;
}

/* Line Items */
.line-items-section {
    background: #f9fafb;
    border-radius: 0.75rem;
    margin-bottom: 1rem;
    overflow: hidden;
}

.line-items-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    background: #f3f4f6;
}

.line-items-title {
    font-weight: 600;
    font-size: 0.875rem;
}

.line-items-body { padding: 1rem; }

.line-item { margin-bottom: 0.75rem; }

.line-item-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto;
    gap: 0.5rem;
    align-items: end;
}

@media (max-width: 640px) {
    .line-item-grid {
        grid-template-columns: 1fr 1fr;
    }
}

.item-total {
    font-weight: 600;
    color: #111827;
    padding: 0.5rem;
    display: block;
}

.btn-remove {
    padding: 0.5rem;
    background: #fee2e2;
    color: #dc2626;
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
}

.btn-remove:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.line-items-footer {
    padding: 0.75rem 1rem;
    background: #f3f4f6;
    text-align: right;
    font-size: 1rem;
}

/* Invoice Preview */
.invoice-preview { background: white; }

.invoice-header-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid #4f46e5;
    margin-bottom: 1.5rem;
}

.invoice-company {
    flex-shrink: 0;
}

.company-logo-img {
    width: 3rem;
    height: 3rem;
    border-radius: 0.25rem;
    object-fit: contain;
}

.company-details {
    font-size: 0.5625rem;
    color: #4b5563;
    line-height: 1.3;
}

.company-details .company-name {
    font-weight: 600;
    color: #111827;
    font-size: 0.6875rem;
}

.company-details p {
    margin: 0;
}

.invoice-title-block {
    text-align: right;
}

.invoice-title {
    font-size: 2rem;
    font-weight: 800;
    color: #4f46e5;
    margin: 0;
    letter-spacing: 0.05em;
}

.invoice-info-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.invoice-meta {
    text-align: right;
}

.invoice-meta-item {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-bottom: 0.25rem;
}

.meta-label {
    color: #6b7280;
    font-size: 0.8125rem;
}

.meta-value {
    font-weight: 600;
    color: #111827;
    font-size: 0.875rem;
    min-width: 6rem;
    text-align: right;
}

.detail-label {
    font-size: 0.75rem;
    color: #6b7280;
    margin: 0;
}

.detail-value {
    font-weight: 600;
    color: #111827;
    margin: 0;
}

.detail-sub {
    font-size: 0.8125rem;
    color: #6b7280;
    margin: 0;
}


.invoice-table {
    width: 100%;
    font-size: 0.875rem;
    margin-bottom: 1rem;
}

.invoice-table th {
    background: #f9fafb;
    padding: 0.625rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    color: #6b7280;
    border-bottom: 1px solid #e5e7eb;
}

.invoice-table td {
    padding: 0.625rem 0.75rem;
    border-bottom: 1px solid #f3f4f6;
}

.type-badge {
    background: #f3f4f6;
    color: #4b5563;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
    font-size: 0.6875rem;
    text-transform: capitalize;
}

.invoice-total {
    display: flex;
    justify-content: space-between;
    padding: 1rem 0;
    border-top: 2px solid #e5e7eb;
    font-size: 1.25rem;
    font-weight: 700;
}

.total-value { color: #4f46e5; }

.invoice-notes {
    background: #f9fafb;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-top: 1rem;
    font-size: 0.875rem;
}

.notes-label {
    color: #6b7280;
    margin: 0 0 0.25rem;
    font-size: 0.75rem;
}

.invoice-footer {
    text-align: center;
    color: #9ca3af;
    font-size: 0.875rem;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
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
.font-semibold { font-weight: 600; }

@media print {
    .page-header,
    .stats-grid,
    .card,
    .loading-container {
        display: none !important;
    }

    .modal-backdrop {
        position: absolute;
        background: white !important;
        backdrop-filter: none;
    }

    .modal-content {
        box-shadow: none;
        max-height: none;
        border: none;
        width: 100%;
        max-width: 100%;
    }

    .modal-header,
    .modal-footer {
        display: none !important;
    }

    .modal-body {
        padding: 0;
    }

    .invoice-preview {
        padding: 0;
    }
}
</style>
