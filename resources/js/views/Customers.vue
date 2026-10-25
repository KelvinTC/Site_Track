<template>
    <AppLayout>
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <h1>Customers</h1>
                    <p class="header-subtitle">Manage your customer database and relationships</p>
                </div>
                <div class="header-actions">
                    <div class="search-box">
                        <i class="bi bi-search search-icon"></i>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search customers..."
                            class="search-input"
                        />
                    </div>
                    <a href="#" @click.prevent="api.download(api.exports.customersExcel())" class="btn btn-secondary">
                        <i class="bi bi-file-earmark-excel"></i>
                        <span>Export</span>
                    </a>
                    <button @click="openCreateModal" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i>
                        <span>Add Customer</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-container">
            <div class="loader"></div>
            <p class="loading-text">Loading customers...</p>
        </div>

        <!-- Customers Card -->
        <div v-else class="card">
            <div class="card-header card-header-primary">
                <div class="header-left">
                    <i class="bi bi-people-fill"></i>
                    <span>Customer Directory</span>
                </div>
                <span class="customer-count">{{ filteredCustomers.length }} {{ filteredCustomers.length === 1 ? 'customer' : 'customers' }}</span>
            </div>
            <div class="card-body p-0">
                <!-- Empty State -->
                <div v-if="filteredCustomers.length === 0" class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <h3 class="empty-state-title">No customers found</h3>
                    <p class="empty-state-description">
                        {{ searchQuery ? 'Try adjusting your search criteria.' : 'Get started by adding your first customer.' }}
                    </p>
                    <button v-if="!searchQuery" @click="openCreateModal" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i>
                        Add First Customer
                    </button>
                </div>

                <!-- Customers Table -->
                <div v-else class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Contact</th>
                                <th>Location</th>
                                <th class="text-center">Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="customer in filteredCustomers" :key="customer.id">
                                <td>
                                    <div class="customer-cell">
                                        <div class="customer-avatar">
                                            {{ getInitials(customer.name) }}
                                        </div>
                                        <div class="customer-info">
                                            <span class="customer-name">{{ customer.name }}</span>
                                            <span class="customer-id">#{{ customer.id }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="contact-cell">
                                        <div class="contact-item" v-if="customer.email">
                                            <i class="bi bi-envelope"></i>
                                            <span>{{ customer.email }}</span>
                                        </div>
                                        <div class="contact-item" v-if="customer.phone">
                                            <i class="bi bi-telephone"></i>
                                            <span>{{ customer.phone }}</span>
                                        </div>
                                        <span v-if="!customer.email && !customer.phone" class="text-muted">No contact info</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="location-text">{{ formatAddress(customer) }}</span>
                                </td>
                                <td class="text-center">
                                    <span :class="customer.is_active ? 'badge badge-success badge-dot' : 'badge badge-secondary badge-dot'">
                                        {{ customer.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="action-buttons">
                                        <button @click="openStatementModal(customer)" class="btn btn-sm btn-ghost" title="Statement">
                                            <i class="bi bi-file-text"></i>
                                        </button>
                                        <button @click="openEditModal(customer)" class="btn btn-sm btn-ghost" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button @click="deleteCustomer(customer.id)" class="btn btn-sm btn-ghost btn-ghost-danger" title="Delete">
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
                        <h5>{{ editingCustomer ? 'Edit Customer' : 'Add New Customer' }}</h5>
                        <button @click="closeModal" class="modal-close">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <form @submit.prevent="saveCustomer" class="modal-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Full Name *</label>
                                <input v-model="form.name" type="text" required class="form-control" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <input v-model="form.email" type="email" class="form-control" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone Number</label>
                                <input v-model="form.phone" type="text" class="form-control" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">VAT Number</label>
                                <input v-model="form.vat_number" type="text" class="form-control" placeholder="Customer VAT registration" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">City</label>
                                <input v-model="form.city" type="text" class="form-control" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Province</label>
                                <input v-model="form.state" type="text" class="form-control" />
                            </div>
                            <div class="form-group full-width">
                                <label class="form-label">Street Address</label>
                                <textarea v-model="form.address" rows="2" class="form-control"></textarea>
                            </div>
                            <div class="form-group full-width">
                                <label class="form-label">Notes</label>
                                <textarea v-model="form.notes" rows="2" class="form-control"></textarea>
                            </div>
                            <div class="form-group full-width">
                                <label class="checkbox-label">
                                    <input v-model="form.is_active" type="checkbox" class="form-checkbox" />
                                    <span class="checkbox-custom"></span>
                                    <span class="checkbox-text">Active Customer</span>
                                </label>
                            </div>
                        </div>
                    </form>
                    <div class="modal-footer">
                        <button type="button" @click="closeModal" class="btn btn-secondary">
                            Cancel
                        </button>
                        <button @click="saveCustomer" :disabled="saving" class="btn btn-primary">
                            <span v-if="saving" class="spinner"></span>
                            <span v-else>
                                <i class="bi bi-check-lg"></i>
                                {{ editingCustomer ? 'Update Customer' : 'Add Customer' }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Statement Modal -->
        <Transition name="modal">
            <div v-if="showStatementModal" class="modal-backdrop" @click.self="closeStatementModal">
                <div class="modal-content modal-large">
                    <div class="modal-header modal-header-primary">
                        <h5>Customer Statement</h5>
                        <button @click="closeStatementModal" class="modal-close">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Date Range Picker -->
                        <div v-if="!statementData" class="date-range-picker">
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
                                    <input v-model="statementFromDate" type="date" class="form-control" />
                                </div>
                                <div class="form-group">
                                    <label class="form-label">To Date</label>
                                    <input v-model="statementToDate" type="date" class="form-control" />
                                </div>
                            </div>
                            <button @click="generateStatement" :disabled="loadingStatement" class="btn btn-primary">
                                <span v-if="loadingStatement" class="spinner"></span>
                                <span v-else>
                                    <i class="bi bi-file-earmark-text"></i>
                                    Generate Statement
                                </span>
                            </button>
                        </div>

                        <!-- Statement Display -->
                        <div v-if="statementData">
                            <button @click="statementData = null" class="btn btn-secondary btn-sm mb-3">
                                <i class="bi bi-arrow-left"></i>
                                Back to Date Selection
                            </button>
                            <CustomerStatement :statement="statementData" />
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';
import CustomerStatement from '../components/CustomerStatement.vue';
import api from '../api';
import { toDateInput } from '@/utils/date';

const loading = ref(true);
const saving = ref(false);
const customers = ref([]);
const searchQuery = ref('');
const showModal = ref(false);
const editingCustomer = ref(null);

const form = ref({
    name: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    state: '',
    zip: '',
    vat_number: '',
    notes: '',
    is_active: true,
});

// Statement state
const showStatementModal = ref(false);
const selectedCustomerForStatement = ref(null);
const statementFromDate = ref('');
const statementToDate = ref('');
const selectedPreset = ref('This Month');
const loadingStatement = ref(false);
const statementData = ref(null);

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
    return toDateInput(date);
};

const applyDatePreset = (preset) => {
    selectedPreset.value = preset.label;
    const range = preset.getRange();
    statementFromDate.value = range.from;
    statementToDate.value = range.to;
};

const openStatementModal = (customer) => {
    selectedCustomerForStatement.value = customer;
    statementData.value = null;
    applyDatePreset(datePresets[0]); // Default to This Month
    showStatementModal.value = true;
};

const closeStatementModal = () => {
    showStatementModal.value = false;
    selectedCustomerForStatement.value = null;
    statementData.value = null;
};

const generateStatement = async () => {
    if (!selectedCustomerForStatement.value || !statementFromDate.value || !statementToDate.value) return;
    try {
        loadingStatement.value = true;
        const response = await api.customers.getStatement(
            selectedCustomerForStatement.value.id,
            statementFromDate.value,
            statementToDate.value
        );
        statementData.value = response.data;
    } catch (error) {
        console.error('Error generating statement:', error);
    } finally {
        loadingStatement.value = false;
    }
};

const filteredCustomers = computed(() => {
    if (!searchQuery.value) return customers.value;
    const query = searchQuery.value.toLowerCase();
    return customers.value.filter(c =>
        c.name.toLowerCase().includes(query) ||
        (c.email && c.email.toLowerCase().includes(query)) ||
        (c.phone && c.phone.includes(query))
    );
});

const formatAddress = (customer) => {
    const parts = [customer.city, customer.state].filter(Boolean);
    return parts.join(', ') || 'No location';
};

const getInitials = (name) => name ? name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '?';

const loadCustomers = async () => {
    try {
        loading.value = true;
        const response = await api.customers.getAll();
        customers.value = response.data;
    } catch (error) {
        console.error('Error loading customers:', error);
    } finally {
        loading.value = false;
    }
};

const openCreateModal = () => {
    editingCustomer.value = null;
    form.value = {
        name: '',
        email: '',
        phone: '',
        address: '',
        city: '',
        state: '',
        zip: '',
        vat_number: '',
        notes: '',
        is_active: true,
    };
    showModal.value = true;
};

const openEditModal = (customer) => {
    editingCustomer.value = customer;
    form.value = { ...customer };
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingCustomer.value = null;
};

const saveCustomer = async () => {
    try {
        saving.value = true;
        if (editingCustomer.value) {
            await api.customers.update(editingCustomer.value.id, form.value);
        } else {
            await api.customers.create(form.value);
        }
        await loadCustomers();
        closeModal();
    } catch (error) {
        console.error('Error saving customer:', error);
    } finally {
        saving.value = false;
    }
};

const deleteCustomer = async (id) => {
    if (!confirm('Are you sure you want to delete this customer? This action cannot be undone.')) return;
    try {
        await api.customers.delete(id);
        await loadCustomers();
    } catch (error) {
        console.error('Error deleting customer:', error);
    }
};

onMounted(() => {
    loadCustomers();
});
</script>

<style scoped>

.search-input::placeholder {
    color: var(--gray-400);
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.customer-count {
    font-size: 0.8125rem;
    font-weight: 500;
    opacity: 0.9;
}

.table tbody tr:last-child td {
    border-bottom: none;
}

.customer-info {
    display: flex;
    flex-direction: column;
}

.customer-name {
    font-weight: 600;
    color: var(--gray-900);
}

.customer-id {
    font-size: 0.75rem;
    color: var(--gray-400);
}

/* Contact Cell */
.contact-cell {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
    color: var(--gray-600);
}

.contact-item i {
    font-size: 0.875rem;
    color: var(--gray-400);
}

/* Location Text */
.location-text {
    color: var(--gray-500);
    font-size: 0.875rem;
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

.modal-enter-from .modal-content,
.modal-leave-to .modal-content {
    transform: translateY(20px) scale(0.98);
}

.mb-3 { margin-bottom: 0.75rem; }

/* Large Modal */
.modal-large {
    max-width: 900px;
}

/* Date Range Picker */
.date-range-picker {
    display: flex;
    flex-direction: column;
    gap: 1rem;
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

@media (max-width: 480px) {
    .custom-date-range {
        grid-template-columns: 1fr;
    }
}
</style>
