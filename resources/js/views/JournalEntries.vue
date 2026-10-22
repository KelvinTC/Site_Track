<template>
    <AppLayout>
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <h1>Journal Entries</h1>
                    <p class="header-subtitle">Record and manage accounting transactions</p>
                </div>
                <div class="header-actions">
                    <button @click="showModal = true" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i>
                        <span>New Entry</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-bar">
            <div class="filter-group">
                <label>From</label>
                <input type="date" v-model="startDate" class="filter-input" />
            </div>
            <div class="filter-group">
                <label>To</label>
                <input type="date" v-model="endDate" class="filter-input" />
            </div>
            <div class="filter-group">
                <label>Status</label>
                <select v-model="filterStatus" class="filter-select">
                    <option value="">All</option>
                    <option value="draft">Draft</option>
                    <option value="posted">Posted</option>
                    <option value="void">Void</option>
                </select>
            </div>
            <button @click="loadEntries" class="btn btn-secondary">
                <i class="bi bi-search"></i>
                <span>Search</span>
            </button>
        </div>

        <!-- Journal Entries -->
        <div class="card">
            <div class="card-header">
                <div class="header-left">
                    <i class="bi bi-journal-bookmark-fill"></i>
                    <span>Journal Entries</span>
                </div>
                <span class="record-count">{{ entries.length }} entries</span>
            </div>
            <div class="card-body p-0">
                <div v-if="loading" class="loading-container">
                    <div class="loader"></div>
                </div>
                <div v-else-if="!entries.length" class="empty-state">
                    <i class="bi bi-journal-x"></i>
                    <h3>No Journal Entries</h3>
                    <p>Create your first journal entry.</p>
                    <button @click="showModal = true" class="btn btn-primary">New Entry</button>
                </div>
                <div v-else>
                    <div v-for="entry in entries" :key="entry.id" class="journal-entry">
                        <div class="entry-header">
                            <div class="entry-info">
                                <span class="entry-number">{{ entry.entry_number }}</span>
                                <span class="entry-date">{{ formatDate(entry.date) }}</span>
                                <span :class="['status-badge', 'status-' + entry.status]">{{ entry.status }}</span>
                            </div>
                            <div class="entry-actions">
                                <button v-if="entry.status === 'draft'" @click="postEntry(entry)" class="btn btn-sm btn-success">
                                    <i class="bi bi-check-lg"></i> Post
                                </button>
                                <button @click="viewEntry(entry)" class="btn btn-sm btn-secondary">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button v-if="entry.status === 'draft'" @click="deleteEntry(entry)" class="btn btn-sm btn-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="entry-memo" v-if="entry.memo">{{ entry.memo }}</div>
                        <table class="lines-table">
                            <thead>
                                <tr>
                                    <th>Account</th>
                                    <th class="text-right">Debit</th>
                                    <th class="text-right">Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="line in entry.lines" :key="line.id">
                                    <td>
                                        <span class="account-code">{{ line.account_code }}</span>
                                        {{ line.account_name }}
                                    </td>
                                    <td class="text-right">{{ line.debit ? '$' + formatNumber(line.debit) : '' }}</td>
                                    <td class="text-right">{{ line.credit ? '$' + formatNumber(line.credit) : '' }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td><strong>Total</strong></td>
                                    <td class="text-right"><strong>${{ formatNumber(entry.total_debit) }}</strong></td>
                                    <td class="text-right"><strong>${{ formatNumber(entry.total_credit) }}</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- New Entry Modal -->
        <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
            <div class="modal modal-lg">
                <div class="modal-header">
                    <h3>New Journal Entry</h3>
                    <button @click="closeModal" class="close-btn"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Date *</label>
                            <input type="date" v-model="form.date" class="form-input" />
                        </div>
                        <div class="form-group">
                            <label>Reference</label>
                            <input type="text" v-model="form.reference" placeholder="Optional reference" class="form-input" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Memo</label>
                        <textarea v-model="form.memo" rows="2" placeholder="Description of this entry..." class="form-input"></textarea>
                    </div>

                    <!-- Journal Lines -->
                    <div class="lines-section">
                        <div class="lines-header">
                            <span>Entry Lines</span>
                            <button @click="addLine" class="btn btn-sm btn-secondary">
                                <i class="bi bi-plus"></i> Add Line
                            </button>
                        </div>
                        <table class="form-lines-table">
                            <thead>
                                <tr>
                                    <th>Account</th>
                                    <th>Description</th>
                                    <th class="text-right">Debit</th>
                                    <th class="text-right">Credit</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(line, index) in form.lines" :key="index">
                                    <td>
                                        <select v-model="line.account_id" class="form-input">
                                            <option value="">Select Account</option>
                                            <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                                                {{ acc.code }} - {{ acc.name }}
                                            </option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" v-model="line.description" class="form-input" placeholder="Description" />
                                    </td>
                                    <td>
                                        <input type="number" v-model="line.debit" class="form-input text-right" step="0.01" placeholder="0.00" @input="line.credit = ''" />
                                    </td>
                                    <td>
                                        <input type="number" v-model="line.credit" class="form-input text-right" step="0.01" placeholder="0.00" @input="line.debit = ''" />
                                    </td>
                                    <td>
                                        <button @click="removeLine(index)" class="btn btn-sm btn-ghost" v-if="form.lines.length > 2">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2" class="text-right"><strong>Totals:</strong></td>
                                    <td class="text-right"><strong>${{ formatNumber(totalDebit) }}</strong></td>
                                    <td class="text-right"><strong>${{ formatNumber(totalCredit) }}</strong></td>
                                    <td></td>
                                </tr>
                                <tr v-if="!isBalanced">
                                    <td colspan="5" class="balance-warning">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        Entry is not balanced! Difference: ${{ formatNumber(Math.abs(totalDebit - totalCredit)) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button @click="closeModal" class="btn btn-secondary">Cancel</button>
                    <button @click="saveEntry('draft')" class="btn btn-secondary" :disabled="saving || !isBalanced">
                        Save as Draft
                    </button>
                    <button @click="saveEntry('posted')" class="btn btn-primary" :disabled="saving || !isBalanced">
                        {{ saving ? 'Saving...' : 'Post Entry' }}
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
const entries = ref([]);
const accounts = ref([]);
const startDate = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0]);
const endDate = ref(new Date().toISOString().split('T')[0]);
const filterStatus = ref('');

const form = ref({
    date: new Date().toISOString().split('T')[0],
    reference: '',
    memo: '',
    lines: [
        { account_id: '', description: '', debit: '', credit: '' },
        { account_id: '', description: '', debit: '', credit: '' }
    ]
});

const formatNumber = (num) => new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0);
const formatDate = (date) => date ? new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '-';

const totalDebit = computed(() => form.value.lines.reduce((sum, l) => sum + (parseFloat(l.debit) || 0), 0));
const totalCredit = computed(() => form.value.lines.reduce((sum, l) => sum + (parseFloat(l.credit) || 0), 0));
const isBalanced = computed(() => Math.abs(totalDebit.value - totalCredit.value) < 0.01 && totalDebit.value > 0);

const addLine = () => {
    form.value.lines.push({ account_id: '', description: '', debit: '', credit: '' });
};

const removeLine = (index) => {
    form.value.lines.splice(index, 1);
};

const loadEntries = async () => {
    try {
        loading.value = true;
        const response = await api.journalEntries.list({
            start_date: startDate.value,
            end_date: endDate.value,
            status: filterStatus.value
        });
        entries.value = response.data;
    } catch (error) {
        console.error('Error loading entries:', error);
    } finally {
        loading.value = false;
    }
};

const loadAccounts = async () => {
    try {
        const response = await api.chartOfAccounts.list();
        accounts.value = response.data;
    } catch (error) {
        console.error('Error loading accounts:', error);
    }
};

const resetForm = () => {
    form.value = {
        date: new Date().toISOString().split('T')[0],
        reference: '',
        memo: '',
        lines: [
            { account_id: '', description: '', debit: '', credit: '' },
            { account_id: '', description: '', debit: '', credit: '' }
        ]
    };
};

const closeModal = () => {
    showModal.value = false;
    resetForm();
};

const saveEntry = async (status) => {
    try {
        saving.value = true;
        await api.journalEntries.create({ ...form.value, status });
        closeModal();
        loadEntries();
    } catch (error) {
        console.error('Error saving:', error);
        alert('Error saving journal entry');
    } finally {
        saving.value = false;
    }
};

const postEntry = async (entry) => {
    if (!confirm('Post this journal entry? This action cannot be undone.')) return;
    try {
        await api.journalEntries.post(entry.id);
        loadEntries();
    } catch (error) {
        console.error('Error posting:', error);
    }
};

const viewEntry = (entry) => {
    alert(`Entry #${entry.entry_number}\n\nMemo: ${entry.memo || 'None'}\nTotal: $${formatNumber(entry.total_debit)}`);
};

const deleteEntry = async (entry) => {
    if (!confirm('Delete this draft entry?')) return;
    try {
        await api.journalEntries.delete(entry.id);
        loadEntries();
    } catch (error) {
        console.error('Error deleting:', error);
    }
};

onMounted(() => {
    loadEntries();
    loadAccounts();
});
</script>

<style scoped>
.page-header { margin-bottom: 1.5rem; }
.header-content { display: flex; flex-direction: column; gap: 1rem; }
@media (min-width: 768px) { .header-content { flex-direction: row; align-items: center; justify-content: space-between; } }
.header-title h1 { font-size: 1.875rem; font-weight: 800; color: #111827; margin: 0; }
.header-subtitle { color: #6b7280; font-size: 0.9375rem; margin: 0.25rem 0 0; }

.filters-bar { display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end; background: white; padding: 1rem 1.25rem; border-radius: 0.75rem; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
.filter-group { display: flex; flex-direction: column; gap: 0.25rem; }
.filter-group label { font-size: 0.6875rem; font-weight: 500; color: #6b7280; text-transform: uppercase; }
.filter-input, .filter-select { padding: 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: white; }

.card { background: white; border-radius: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden; }
.card-header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.5rem; background: linear-gradient(135deg, #7c3aed, #a855f7); color: white; }
.header-left { display: flex; align-items: center; gap: 0.625rem; font-weight: 600; }
.record-count { font-size: 0.75rem; opacity: 0.9; }
.p-0 { padding: 0 !important; }

.journal-entry { border-bottom: 1px solid #e5e7eb; padding: 1.25rem 1.5rem; }
.journal-entry:last-child { border-bottom: none; }
.entry-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
.entry-info { display: flex; align-items: center; gap: 1rem; }
.entry-number { font-family: monospace; font-weight: 700; color: #4f46e5; }
.entry-date { color: #6b7280; font-size: 0.875rem; }
.entry-memo { color: #6b7280; font-size: 0.875rem; margin-bottom: 0.75rem; font-style: italic; }
.entry-actions { display: flex; gap: 0.5rem; }

.status-badge { display: inline-block; padding: 0.25rem 0.625rem; font-size: 0.6875rem; font-weight: 600; border-radius: 0.375rem; text-transform: capitalize; }
.status-draft { background: #fef3c7; color: #b45309; }
.status-posted { background: #d1fae5; color: #059669; }
.status-void { background: #fee2e2; color: #dc2626; }

.lines-table { width: 100%; font-size: 0.8125rem; border-collapse: collapse; }
.lines-table th { background: #f9fafb; color: #6b7280; font-weight: 600; font-size: 0.6875rem; text-transform: uppercase; padding: 0.5rem 0.75rem; text-align: left; }
.lines-table td { padding: 0.5rem 0.75rem; border-top: 1px solid #f3f4f6; }
.lines-table tfoot td { border-top: 2px solid #e5e7eb; }
.account-code { font-family: monospace; font-size: 0.75rem; background: #f3f4f6; padding: 0.125rem 0.375rem; border-radius: 0.25rem; margin-right: 0.5rem; }

.empty-state { text-align: center; padding: 3rem; color: #6b7280; }
.empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.5; }
.empty-state h3 { font-size: 1.125rem; font-weight: 700; margin: 0 0 0.5rem; color: #374151; }
.empty-state p { margin: 0 0 1rem; }

.loading-container { display: flex; justify-content: center; padding: 3rem; }
.loader { width: 2.5rem; height: 2.5rem; border: 3px solid #e5e7eb; border-top-color: #7c3aed; border-radius: 50%; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem; }
.modal { background: white; border-radius: 1rem; width: 100%; max-width: 500px; max-height: 90vh; overflow-y: auto; }
.modal.modal-lg { max-width: 800px; }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 1.5rem; border-bottom: 1px solid #e5e7eb; }
.modal-header h3 { margin: 0; font-size: 1.125rem; font-weight: 700; }
.close-btn { background: transparent; border: none; font-size: 1.25rem; color: #6b7280; cursor: pointer; }
.modal-body { padding: 1.5rem; }
.modal-footer { display: flex; justify-content: flex-end; gap: 0.75rem; padding: 1rem 1.5rem; border-top: 1px solid #e5e7eb; }

.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.375rem; }
.form-input { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; }
.form-input:focus { outline: none; border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,0.1); }

.lines-section { margin-top: 1rem; }
.lines-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; font-weight: 600; }
.form-lines-table { width: 100%; font-size: 0.8125rem; border-collapse: collapse; }
.form-lines-table th { background: #f9fafb; color: #6b7280; font-weight: 600; font-size: 0.6875rem; text-transform: uppercase; padding: 0.5rem; text-align: left; }
.form-lines-table td { padding: 0.375rem; }
.form-lines-table .form-input { padding: 0.375rem 0.5rem; font-size: 0.8125rem; }
.balance-warning { background: #fef3c7; color: #b45309; padding: 0.75rem !important; text-align: center; font-weight: 500; }

.btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; border: none; cursor: pointer; }
.btn-sm { padding: 0.375rem 0.625rem; font-size: 0.75rem; }
.btn-primary { background: #7c3aed; color: white; }
.btn-primary:hover { background: #6d28d9; }
.btn-secondary { background: #f3f4f6; color: #374151; }
.btn-secondary:hover { background: #e5e7eb; }
.btn-success { background: #059669; color: white; }
.btn-danger { background: #dc2626; color: white; }
.btn-ghost { background: transparent; color: #6b7280; padding: 0.25rem; }

.text-right { text-align: right; }
</style>
