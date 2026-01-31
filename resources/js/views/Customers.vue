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
                    <button @click="openCreateModal" class="btn btn-success">
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
                <span class="customer-count">{{ filteredCustomers.length }} customers</span>
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
                                        <span class="status-dot"></span>
                                        {{ customer.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="action-buttons">
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
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';
import api from '../api';

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
    notes: '',
    is_active: true,
});

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
    flex: 1;
    min-width: 200px;
}

.search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    font-size: 1rem;
}

.search-input {
    width: 100%;
    padding: 0.625rem 1rem 0.625rem 2.75rem;
    font-size: 0.9375rem;
    color: #111827;
    background: white;
    border: 1.5px solid #e5e7eb;
    border-radius: 0.75rem;
    transition: all 0.2s ease;
}

.search-input::placeholder {
    color: #9ca3af;
}

.search-input:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
}

/* Loading State */
.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
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

@keyframes spin {
    to { transform: rotate(360deg); }
}

.loading-text {
    margin-top: 1rem;
    color: #6b7280;
    font-size: 0.9375rem;
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
    font-size: 0.9375rem;
}

.header-left i {
    font-size: 1rem;
    opacity: 0.9;
}

.customer-count {
    font-size: 0.8125rem;
    font-weight: 500;
    opacity: 0.9;
}

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

.table tbody tr {
    transition: background-color 0.15s ease;
}

.table tbody tr:hover {
    background: linear-gradient(90deg, rgba(99, 102, 241, 0.04) 0%, transparent 100%);
}

.table tbody td {
    padding: 1rem;
    vertical-align: middle;
    border-bottom: 1px solid #f3f4f6;
}

.table tbody tr:last-child td {
    border-bottom: none;
}

/* Customer Cell */
.customer-cell {
    display: flex;
    align-items: center;
    gap: 0.875rem;
}

.customer-avatar {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.625rem;
    background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8125rem;
    font-weight: 700;
    color: #4f46e5;
    flex-shrink: 0;
}

.customer-info {
    display: flex;
    flex-direction: column;
}

.customer-name {
    font-weight: 600;
    color: #111827;
}

.customer-id {
    font-size: 0.75rem;
    color: #9ca3af;
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
    color: #4b5563;
}

.contact-item i {
    font-size: 0.875rem;
    color: #9ca3af;
}

/* Location Text */
.location-text {
    color: #6b7280;
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

.badge-success {
    background: #d1fae5;
    color: #047857;
}

.badge-secondary {
    background: #f3f4f6;
    color: #4b5563;
}

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

.btn-ghost:hover {
    background: #f3f4f6;
    color: #4f46e5;
}

.btn-ghost-danger:hover {
    background: #fee2e2;
    color: #dc2626;
}

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
    max-width: 20rem;
    margin: 0 auto 1.5rem;
    line-height: 1.5;
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
    border-radius: 1.25rem;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
    width: 100%;
    max-width: 600px;
    max-height: calc(100vh - 2rem);
    overflow-y: auto;
}

.modal-header {
    padding: 1.25rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-header h5 {
    font-size: 1.125rem;
    font-weight: 700;
    margin: 0;
}

.modal-header-primary h5 {
    color: white;
}

.modal-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s ease;
}

.modal-close:hover {
    background: rgba(255, 255, 255, 0.3);
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    padding: 1.25rem 1.5rem;
    border-top: 1px solid #f3f4f6;
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    background: #f9fafb;
    border-radius: 0 0 1.25rem 1.25rem;
}

/* Form Grid */
.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

@media (max-width: 480px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-label {
    font-weight: 600;
    font-size: 0.875rem;
    color: #374151;
}

.input-wrapper {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    font-size: 1rem;
}

.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    font-size: 0.9375rem;
    color: #111827;
    background: #f9fafb;
    border: 1.5px solid #e5e7eb;
    border-radius: 0.625rem;
    transition: all 0.2s ease;
}

.form-control.with-icon {
    padding-left: 2.75rem;
}

.form-control::placeholder {
    color: #9ca3af;
}

.form-control:focus {
    outline: none;
    border-color: #4f46e5;
    background: white;
    box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
}

textarea.form-control {
    resize: vertical;
    min-height: 80px;
}

/* Checkbox */
.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
}

.form-checkbox {
    width: 1.25rem;
    height: 1.25rem;
    border-radius: 0.375rem;
    border: 1.5px solid #d1d5db;
    cursor: pointer;
    accent-color: #4f46e5;
}

.checkbox-text {
    font-size: 0.9375rem;
    color: #374151;
}

/* Spinner */
.spinner {
    width: 1.25rem;
    height: 1.25rem;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

/* Modal Transition */
.modal-enter-active,
.modal-leave-active {
    transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from .modal-content,
.modal-leave-to .modal-content {
    transform: translateY(20px) scale(0.98);
}

/* Utilities */
.p-0 { padding: 0 !important; }
.text-center { text-align: center; }
.text-right { text-align: right; }
.text-muted { color: #9ca3af; font-size: 0.8125rem; }
</style>
