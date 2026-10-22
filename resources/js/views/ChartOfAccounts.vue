<template>
    <AppLayout>
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <h1>Chart of Accounts</h1>
                    <p class="header-subtitle">Manage your general ledger accounts</p>
                </div>
                <div class="header-actions">
                    <button @click="showModal = true" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i>
                        <span>Add Account</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Account Type Tabs -->
        <div class="account-tabs">
            <button v-for="type in accountTypes" :key="type.value"
                    @click="activeTab = type.value"
                    :class="['tab-btn', { active: activeTab === type.value }]">
                <i :class="type.icon"></i>
                <span>{{ type.label }}</span>
                <span class="tab-count">{{ getTypeCount(type.value) }}</span>
            </button>
        </div>

        <!-- Accounts Table -->
        <div class="card">
            <div class="card-header">
                <div class="header-left">
                    <i class="bi bi-journal-text"></i>
                    <span>{{ activeTabLabel }} Accounts</span>
                </div>
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" v-model="searchQuery" placeholder="Search accounts..." />
                </div>
            </div>
            <div class="card-body p-0">
                <div v-if="loading" class="loading-container">
                    <div class="loader"></div>
                </div>
                <div v-else-if="!filteredAccounts.length" class="empty-state">
                    <i class="bi bi-journal"></i>
                    <h3>No Accounts</h3>
                    <p>No {{ activeTabLabel.toLowerCase() }} accounts found.</p>
                    <button @click="showModal = true" class="btn btn-primary">Add Account</button>
                </div>
                <div v-else class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Account Name</th>
                                <th>Description</th>
                                <th class="text-center">Sub-type</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="account in filteredAccounts" :key="account.id" :class="{ 'parent-row': account.is_parent }">
                                <td>
                                    <span class="account-code" :class="'type-' + account.type">{{ account.code }}</span>
                                </td>
                                <td>
                                    <div class="account-name" :style="{ paddingLeft: (account.level || 0) * 1.5 + 'rem' }">
                                        <i v-if="account.is_parent" class="bi bi-folder-fill"></i>
                                        <strong>{{ account.name }}</strong>
                                    </div>
                                </td>
                                <td class="text-muted">{{ account.description || '-' }}</td>
                                <td class="text-center">
                                    <span class="subtype-badge">{{ account.sub_type || '-' }}</span>
                                </td>
                                <td class="text-center">
                                    <span :class="['status-badge', account.is_active ? 'status-active' : 'status-inactive']">
                                        {{ account.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button @click="editAccount(account)" class="action-btn"><i class="bi bi-pencil"></i></button>
                                    <button @click="deleteAccount(account)" class="action-btn delete"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
            <div class="modal">
                <div class="modal-header">
                    <h3>{{ editingId ? 'Edit' : 'Add' }} Account</h3>
                    <button @click="closeModal" class="close-btn"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Account Code *</label>
                            <input type="text" v-model="form.code" placeholder="e.g., 1000" class="form-input" />
                        </div>
                        <div class="form-group">
                            <label>Account Type *</label>
                            <select v-model="form.type" class="form-input">
                                <option value="">Select Type</option>
                                <option v-for="type in accountTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Account Name *</label>
                        <input type="text" v-model="form.name" placeholder="Account name" class="form-input" />
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Sub-type</label>
                            <input type="text" v-model="form.sub_type" placeholder="e.g., Current Asset" class="form-input" />
                        </div>
                        <div class="form-group">
                            <label>Parent Account</label>
                            <select v-model="form.parent_id" class="form-input">
                                <option value="">None (Top Level)</option>
                                <option v-for="acc in parentAccounts" :key="acc.id" :value="acc.id">{{ acc.code }} - {{ acc.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea v-model="form.description" rows="2" placeholder="Description..." class="form-input"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" v-model="form.is_active" />
                            <span>Active</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button @click="closeModal" class="btn btn-secondary">Cancel</button>
                    <button @click="saveAccount" class="btn btn-primary" :disabled="saving">
                        {{ saving ? 'Saving...' : 'Save' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';
import api from '../api';

const loading = ref(true);
const saving = ref(false);
const showModal = ref(false);
const editingId = ref(null);
const accounts = ref([]);
const searchQuery = ref('');
const activeTab = ref('asset');

const accountTypes = [
    { value: 'asset', label: 'Assets', icon: 'bi bi-safe2' },
    { value: 'liability', label: 'Liabilities', icon: 'bi bi-credit-card-2-back' },
    { value: 'equity', label: 'Equity', icon: 'bi bi-pie-chart' },
    { value: 'revenue', label: 'Revenue', icon: 'bi bi-graph-up-arrow' },
    { value: 'expense', label: 'Expenses', icon: 'bi bi-graph-down-arrow' },
];

const form = ref({
    code: '',
    name: '',
    type: '',
    sub_type: '',
    parent_id: '',
    description: '',
    is_active: true
});

const activeTabLabel = computed(() => accountTypes.find(t => t.value === activeTab.value)?.label || '');
const getTypeCount = (type) => accounts.value.filter(a => a.type === type).length;
const parentAccounts = computed(() => accounts.value.filter(a => a.is_parent && a.type === form.value.type));

const filteredAccounts = computed(() => {
    let result = accounts.value.filter(a => a.type === activeTab.value);
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(a =>
            a.code?.toLowerCase().includes(query) ||
            a.name?.toLowerCase().includes(query)
        );
    }
    return result;
});

const loadData = async () => {
    try {
        loading.value = true;
        const response = await api.chartOfAccounts.list();
        accounts.value = response.data;
    } catch (error) {
        console.error('Error loading accounts:', error);
    } finally {
        loading.value = false;
    }
};

const resetForm = () => {
    form.value = { code: '', name: '', type: '', sub_type: '', parent_id: '', description: '', is_active: true };
    editingId.value = null;
};

const closeModal = () => {
    showModal.value = false;
    resetForm();
};

const editAccount = (account) => {
    editingId.value = account.id;
    form.value = { ...account };
    showModal.value = true;
};

const saveAccount = async () => {
    try {
        saving.value = true;
        if (editingId.value) {
            await api.chartOfAccounts.update(editingId.value, form.value);
        } else {
            await api.chartOfAccounts.create(form.value);
        }
        closeModal();
        loadData();
    } catch (error) {
        console.error('Error saving:', error);
        alert('Error saving account');
    } finally {
        saving.value = false;
    }
};

const deleteAccount = async (account) => {
    if (!confirm(`Delete "${account.name}"?`)) return;
    try {
        await api.chartOfAccounts.delete(account.id);
        loadData();
    } catch (error) {
        console.error('Error deleting:', error);
    }
};

onMounted(() => loadData());
</script>

<style scoped>
.page-header { margin-bottom: 1.5rem; }
.header-content { display: flex; flex-direction: column; gap: 1rem; }
@media (min-width: 768px) { .header-content { flex-direction: row; align-items: center; justify-content: space-between; } }
.header-title h1 { font-size: 1.875rem; font-weight: 800; color: #111827; margin: 0; }
.header-subtitle { color: #6b7280; font-size: 0.9375rem; margin: 0.25rem 0 0; }

.account-tabs { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
.tab-btn { display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; background: white; border: 1px solid #e5e7eb; border-radius: 0.625rem; font-size: 0.875rem; font-weight: 500; color: #6b7280; cursor: pointer; transition: all 0.2s; }
.tab-btn:hover { border-color: #4f46e5; color: #4f46e5; }
.tab-btn.active { background: #4f46e5; border-color: #4f46e5; color: white; }
.tab-count { background: rgba(0,0,0,0.1); padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; }
.tab-btn.active .tab-count { background: rgba(255,255,255,0.2); }

.card { background: white; border-radius: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden; }
.card-header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.5rem; background: linear-gradient(135deg, #1e3a5f, #2563eb); color: white; }
.header-left { display: flex; align-items: center; gap: 0.625rem; font-weight: 600; }
.search-box { position: relative; }
.search-box i { position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.6); }
.search-box input { padding: 0.5rem 0.75rem 0.5rem 2.25rem; border: 1px solid rgba(255,255,255,0.2); border-radius: 0.5rem; background: rgba(255,255,255,0.1); color: white; font-size: 0.875rem; width: 200px; }
.search-box input::placeholder { color: rgba(255,255,255,0.5); }
.search-box input:focus { outline: none; background: rgba(255,255,255,0.15); }
.p-0 { padding: 0 !important; }

.table-container { overflow-x: auto; }
.table { width: 100%; font-size: 0.875rem; border-collapse: collapse; }
.table thead th { background: #f9fafb; color: #6b7280; font-weight: 600; font-size: 0.75rem; text-transform: uppercase; padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
.table tbody td { padding: 0.875rem 1rem; vertical-align: middle; border-bottom: 1px solid #f3f4f6; }
.table tbody tr:hover { background: #f9fafb; }
.parent-row { background: #f8fafc; }

.account-code { font-family: monospace; font-weight: 600; padding: 0.25rem 0.5rem; border-radius: 0.375rem; font-size: 0.8125rem; }
.account-code.type-asset { background: #dbeafe; color: #1d4ed8; }
.account-code.type-liability { background: #fee2e2; color: #dc2626; }
.account-code.type-equity { background: #ede9fe; color: #7c3aed; }
.account-code.type-revenue { background: #d1fae5; color: #059669; }
.account-code.type-expense { background: #fed7aa; color: #ea580c; }

.account-name { display: flex; align-items: center; gap: 0.5rem; }
.account-name i { color: #f59e0b; }
.text-muted { color: #9ca3af; }
.subtype-badge { font-size: 0.75rem; color: #6b7280; }
.status-badge { display: inline-block; padding: 0.25rem 0.625rem; font-size: 0.6875rem; font-weight: 600; border-radius: 0.375rem; }
.status-active { background: #d1fae5; color: #059669; }
.status-inactive { background: #f3f4f6; color: #6b7280; }

.action-btn { background: transparent; border: none; padding: 0.5rem; color: #6b7280; cursor: pointer; border-radius: 0.375rem; }
.action-btn:hover { background: #f3f4f6; color: #4f46e5; }
.action-btn.delete:hover { color: #dc2626; }

.empty-state { text-align: center; padding: 3rem; color: #6b7280; }
.empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.5; }
.empty-state h3 { font-size: 1.125rem; font-weight: 700; margin: 0 0 0.5rem; color: #374151; }
.empty-state p { margin: 0 0 1rem; }

.loading-container { display: flex; justify-content: center; padding: 3rem; }
.loader { width: 2.5rem; height: 2.5rem; border: 3px solid #e5e7eb; border-top-color: #2563eb; border-radius: 50%; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem; }
.modal { background: white; border-radius: 1rem; width: 100%; max-width: 500px; max-height: 90vh; overflow-y: auto; }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 1.5rem; border-bottom: 1px solid #e5e7eb; }
.modal-header h3 { margin: 0; font-size: 1.125rem; font-weight: 700; }
.close-btn { background: transparent; border: none; font-size: 1.25rem; color: #6b7280; cursor: pointer; }
.modal-body { padding: 1.5rem; }
.modal-footer { display: flex; justify-content: flex-end; gap: 0.75rem; padding: 1rem 1.5rem; border-top: 1px solid #e5e7eb; }

.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.375rem; }
.form-input { width: 100%; padding: 0.625rem 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; }
.form-input:focus { outline: none; border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,0.1); }
.checkbox-label { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; }

.btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; border: none; cursor: pointer; }
.btn-primary { background: #4f46e5; color: white; }
.btn-primary:hover { background: #4338ca; }
.btn-secondary { background: #f3f4f6; color: #374151; }
.btn-secondary:hover { background: #e5e7eb; }

.text-center { text-align: center; }
</style>
