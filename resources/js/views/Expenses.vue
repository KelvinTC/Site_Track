<template>
    <AppLayout>
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <h1>Expenses</h1>
                    <p class="header-subtitle">Track and manage company expenses</p>
                </div>
                <div class="header-actions">
                    <div class="search-box">
                        <i class="bi bi-search search-icon"></i>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search expenses..."
                            class="search-input"
                        />
                    </div>
                    <button @click="openCreateModal" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i>
                        <span>Add Expense</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="summary-grid">
            <div class="summary-card">
                <div class="summary-icon bg-blue">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div class="summary-content">
                    <span class="summary-value">${{ formatNumber(summary.total) }}</span>
                    <span class="summary-label">Total Expenses</span>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon bg-yellow">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="summary-content">
                    <span class="summary-value">{{ pendingCount }}</span>
                    <span class="summary-label">Pending Approval</span>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon bg-green">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="summary-content">
                    <span class="summary-value">${{ formatNumber(approvedTotal) }}</span>
                    <span class="summary-label">Approved</span>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon bg-purple">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div class="summary-content">
                    <span class="summary-value">${{ formatNumber(paidTotal) }}</span>
                    <span class="summary-label">Paid</span>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-bar">
            <div class="filter-group">
                <label>Cost Centre</label>
                <select v-model="filters.cost_centre_id" @change="loadExpenses" class="filter-select">
                    <option :value="null">All Cost Centres</option>
                    <option v-for="cc in costCentres" :key="cc.id" :value="cc.id">{{ cc.code }} - {{ cc.name }}</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Category</label>
                <select v-model="filters.category_id" @change="loadExpenses" class="filter-select">
                    <option :value="null">All Categories</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Status</label>
                <select v-model="filters.status" @change="loadExpenses" class="filter-select">
                    <option :value="null">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="paid">Paid</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <div class="filter-group">
                <label>From</label>
                <input type="date" v-model="filters.from_date" @change="loadExpenses" class="filter-input" />
            </div>
            <div class="filter-group">
                <label>To</label>
                <input type="date" v-model="filters.to_date" @change="loadExpenses" class="filter-input" />
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-container">
            <div class="loader"></div>
            <p class="loading-text">Loading expenses...</p>
        </div>

        <!-- Expenses Table -->
        <div v-else class="card">
            <div class="card-header card-header-danger">
                <div class="header-left">
                    <i class="bi bi-receipt"></i>
                    <span>Expense Records</span>
                </div>
                <span class="expense-count">{{ filteredExpenses.length }} {{ filteredExpenses.length === 1 ? 'expense' : 'expenses' }}</span>
            </div>
            <div class="card-body p-0">
                <div v-if="filteredExpenses.length === 0" class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-receipt-cutoff"></i>
                    </div>
                    <h3 class="empty-state-title">No expenses found</h3>
                    <p class="empty-state-description">
                        {{ searchQuery ? 'Try adjusting your search criteria.' : 'Get started by adding your first expense.' }}
                    </p>
                    <button v-if="!searchQuery" @click="openCreateModal" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i>
                        Add First Expense
                    </button>
                </div>

                <div v-else class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Expense #</th>
                                <th>Date</th>
                                <th>Cost Centre</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Vendor</th>
                                <th class="text-right">Amount</th>
                                <th class="text-center">Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="expense in filteredExpenses" :key="expense.id">
                                <td>
                                    <span class="expense-number">{{ expense.expense_number }}</span>
                                </td>
                                <td>{{ formatDate(expense.expense_date) }}</td>
                                <td>
                                    <span v-if="expense.cost_centre" class="cost-centre-badge">
                                        {{ expense.cost_centre.code }}
                                    </span>
                                    <span v-else class="text-muted">-</span>
                                </td>
                                <td>
                                    <span class="category-badge">{{ expense.category?.name }}</span>
                                </td>
                                <td>{{ expense.description }}</td>
                                <td>{{ expense.vendor || '-' }}</td>
                                <td class="text-right">
                                    <span class="amount">${{ formatNumber(expense.amount) }}</span>
                                </td>
                                <td class="text-center">
                                    <select
                                        :value="expense.status"
                                        @change="updateStatus(expense, $event.target.value)"
                                        :class="'status-select status-' + expense.status"
                                    >
                                        <option value="pending">Pending</option>
                                        <option value="approved">Approved</option>
                                        <option value="paid">Paid</option>
                                        <option value="rejected">Rejected</option>
                                    </select>
                                </td>
                                <td class="text-right">
                                    <div class="action-buttons">
                                        <button @click="openEditModal(expense)" class="btn btn-sm btn-ghost" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button @click="deleteExpense(expense.id)" class="btn btn-sm btn-ghost btn-ghost-danger" title="Delete">
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
                <div class="modal-content modal-large">
                    <div class="modal-header modal-header-danger">
                        <h5>{{ editingExpense ? 'Edit Expense' : 'Add New Expense' }}</h5>
                        <button @click="closeModal" class="modal-close">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <form @submit.prevent="saveExpense" class="modal-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Date *</label>
                                <input v-model="form.expense_date" type="date" required class="form-control" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Category *</label>
                                <select v-model="form.expense_category_id" required class="form-control">
                                    <option :value="null">Select Category</option>
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                </select>
                            </div>
                            <div class="form-group full-width">
                                <label class="form-label">Cost Centre *</label>
                                <select v-model="form.cost_centre_id" required class="form-control">
                                    <option :value="null">Select Cost Centre</option>
                                    <option v-for="cc in costCentres" :key="cc.id" :value="cc.id">
                                        {{ cc.code }} - {{ cc.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="form-group full-width">
                                <label class="form-label">Description *</label>
                                <input v-model="form.description" type="text" required class="form-control" placeholder="Brief description of the expense" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Amount *</label>
                                <input v-model="form.amount" type="number" step="0.01" min="0" required class="form-control" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Vendor/Supplier</label>
                                <input v-model="form.vendor" type="text" class="form-control" placeholder="Who was paid?" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Reference #</label>
                                <input v-model="form.reference" type="text" class="form-control" placeholder="Invoice/Receipt number" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Payment Method</label>
                                <select v-model="form.payment_method" class="form-control">
                                    <option :value="null">Select Method</option>
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="check">Check</option>
                                    <option value="credit_card">Credit Card</option>
                                    <option value="mobile_money">Mobile Money</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Job (Optional)</label>
                                <select v-model="form.site_job_id" class="form-control">
                                    <option :value="null">Not linked to a job</option>
                                    <option v-for="job in jobs" :key="job.id" :value="job.id">{{ job.job_number }} - {{ job.title }}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select v-model="form.status" class="form-control">
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="paid">Paid</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            <div class="form-group full-width">
                                <label class="form-label">Notes</label>
                                <textarea v-model="form.notes" rows="2" class="form-control" placeholder="Additional details..."></textarea>
                            </div>
                        </div>
                    </form>
                    <div class="modal-footer">
                        <button type="button" @click="closeModal" class="btn btn-secondary">Cancel</button>
                        <button @click="saveExpense" :disabled="saving" class="btn btn-primary">
                            <span v-if="saving" class="spinner"></span>
                            <span v-else>
                                <i class="bi bi-check-lg"></i>
                                {{ editingExpense ? 'Update Expense' : 'Add Expense' }}
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
const expenses = ref([]);
const categories = ref([]);
const departments = ref([]);
const costCentres = ref([]);
const jobs = ref([]);
const searchQuery = ref('');
const showModal = ref(false);
const editingExpense = ref(null);
const summary = ref({ total: 0, by_status: [] });

const filters = ref({
    category_id: null,
    department_id: null,
    cost_centre_id: null,
    status: null,
    from_date: '',
    to_date: '',
});

const form = ref({
    expense_category_id: null,
    cost_centre_id: null,
    department_id: null,
    site_job_id: null,
    expense_date: toDateInput(),
    description: '',
    reference: '',
    vendor: '',
    amount: '',
    payment_method: null,
    status: 'pending',
    notes: '',
});

const filteredExpenses = computed(() => {
    if (!searchQuery.value) return expenses.value;
    const query = searchQuery.value.toLowerCase();
    return expenses.value.filter(e =>
        e.expense_number?.toLowerCase().includes(query) ||
        e.description?.toLowerCase().includes(query) ||
        e.vendor?.toLowerCase().includes(query) ||
        e.category?.name?.toLowerCase().includes(query)
    );
});

const pendingCount = computed(() => {
    return expenses.value.filter(e => e.status === 'pending').length;
});

const approvedTotal = computed(() => {
    return expenses.value.filter(e => e.status === 'approved').reduce((sum, e) => sum + parseFloat(e.amount), 0);
});

const paidTotal = computed(() => {
    return expenses.value.filter(e => e.status === 'paid').reduce((sum, e) => sum + parseFloat(e.amount), 0);
});

const formatNumber = (num) => {
    return parseFloat(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const loadExpenses = async () => {
    try {
        loading.value = true;
        const params = {};
        if (filters.value.category_id) params.category_id = filters.value.category_id;
        if (filters.value.department_id) params.department_id = filters.value.department_id;
        if (filters.value.cost_centre_id) params.cost_centre_id = filters.value.cost_centre_id;
        if (filters.value.status) params.status = filters.value.status;
        if (filters.value.from_date) params.from_date = filters.value.from_date;
        if (filters.value.to_date) params.to_date = filters.value.to_date;

        const response = await api.expenses.getAll(params);
        expenses.value = response.data;

        // Update summary
        summary.value.total = expenses.value.reduce((sum, e) => sum + parseFloat(e.amount), 0);
    } catch (error) {
        console.error('Error loading expenses:', error);
    } finally {
        loading.value = false;
    }
};

const loadCategories = async () => {
    try {
        const response = await api.expenseCategories.getAll();
        categories.value = response.data;
    } catch (error) {
        console.error('Error loading categories:', error);
    }
};

const loadDepartments = async () => {
    try {
        const response = await api.departments.getAll();
        departments.value = response.data;
    } catch (error) {
        console.error('Error loading departments:', error);
    }
};

const loadCostCentres = async () => {
    try {
        const response = await api.costCentres.list();
        costCentres.value = response.data;
    } catch (error) {
        console.error('Error loading cost centres:', error);
    }
};

const loadJobs = async () => {
    try {
        const response = await api.jobs.getAll();
        jobs.value = response.data;
    } catch (error) {
        console.error('Error loading jobs:', error);
    }
};

const openCreateModal = () => {
    editingExpense.value = null;
    form.value = {
        expense_category_id: null,
        cost_centre_id: null,
        department_id: null,
        site_job_id: null,
        expense_date: toDateInput(),
        description: '',
        reference: '',
        vendor: '',
        amount: '',
        payment_method: null,
        status: 'pending',
        notes: '',
    };
    showModal.value = true;
};

const openEditModal = (expense) => {
    editingExpense.value = expense;
    form.value = {
        expense_category_id: expense.expense_category_id,
        cost_centre_id: expense.cost_centre_id,
        department_id: expense.department_id,
        site_job_id: expense.site_job_id,
        expense_date: expense.expense_date?.split('T')[0] || expense.expense_date,
        description: expense.description,
        reference: expense.reference || '',
        vendor: expense.vendor || '',
        amount: expense.amount,
        payment_method: expense.payment_method,
        status: expense.status,
        notes: expense.notes || '',
    };
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingExpense.value = null;
};

const saveExpense = async () => {
    try {
        saving.value = true;
        if (editingExpense.value) {
            await api.expenses.update(editingExpense.value.id, form.value);
        } else {
            await api.expenses.create(form.value);
        }
        await loadExpenses();
        closeModal();
    } catch (error) {
        console.error('Error saving expense:', error);
    } finally {
        saving.value = false;
    }
};

const deleteExpense = async (id) => {
    if (!confirm('Are you sure you want to delete this expense?')) return;
    try {
        await api.expenses.delete(id);
        await loadExpenses();
    } catch (error) {
        console.error('Error deleting expense:', error);
    }
};

const updateStatus = async (expense, status) => {
    try {
        await api.expenses.updateStatus(expense.id, status);
        expense.status = status;
    } catch (error) {
        console.error('Error updating status:', error);
    }
};

onMounted(async () => {
    await Promise.all([
        loadCategories(),
        loadDepartments(),
        loadCostCentres(),
        loadJobs(),
    ]);
    await loadExpenses();
});
</script>

<style scoped>

/* Summary Grid */
.summary-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

@media (min-width: 768px) {
    .summary-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

.bg-blue { background: linear-gradient(135deg, var(--info-500), var(--info-600)); }
.bg-yellow { background: linear-gradient(135deg, var(--warning-500), var(--warning-600)); }
.bg-green { background: linear-gradient(135deg, var(--success-500), var(--success-600)); }
.bg-purple { background: linear-gradient(135deg, var(--accent-500), var(--accent-600)); }

.filter-select,
.filter-input {
    padding: 0.5rem 0.75rem;
    font-size: 0.8125rem;
    border: 1px solid var(--gray-300);
    border-radius: 0.5rem;
    background: white;
    min-width: 140px;
}

.filter-select:focus,
.filter-input:focus {
    outline: none;
    border-color: var(--primary-600);
}

.expense-count {
    font-size: 0.8125rem;
    opacity: 0.9;
}

.expense-number {
    font-weight: 700;
    color: var(--danger-600);
    font-family: monospace;
    font-size: 0.8125rem;
}

.category-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background: var(--warning-100);
    color: var(--warning-800);
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.cost-centre-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background: var(--info-100);
    color: var(--info-700);
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.amount {
    font-weight: 600;
    color: var(--gray-900);
}

.status-select {
    padding: 0.375rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    border: none;
    border-radius: 9999px;
    cursor: pointer;
}

.status-pending { background: var(--warning-100); color: var(--warning-800); }
.status-approved { background: var(--success-100); color: var(--success-700); }
.status-paid { background: var(--info-100); color: var(--info-700); }
.status-rejected { background: var(--danger-100); color: var(--danger-600); }

@keyframes spin {
    to { transform: rotate(360deg); }
}

.modal-large {
    max-width: 640px;
}

.modal-header-danger {
    background: linear-gradient(135deg, var(--danger-500), var(--danger-600));
    color: white;
}

</style>
