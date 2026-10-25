<template>
    <AppLayout>
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <h1>Bank Accounts</h1>
                    <p class="header-subtitle">Manage bank accounts and reconciliation</p>
                </div>
                <div class="header-actions">
                    <button @click="showModal = true" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i>
                        <span>Add Account</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="summary-cards">
            <div class="summary-card total">
                <div class="summary-icon"><i class="bi bi-bank2"></i></div>
                <div class="summary-content">
                    <span class="summary-label">Total Balance</span>
                    <span class="summary-value">${{ formatNumber(totalBalance) }}</span>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon green"><i class="bi bi-check-circle"></i></div>
                <div class="summary-content">
                    <span class="summary-label">Reconciled</span>
                    <span class="summary-value">${{ formatNumber(reconciledBalance) }}</span>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon orange"><i class="bi bi-clock"></i></div>
                <div class="summary-content">
                    <span class="summary-label">Pending</span>
                    <span class="summary-value">${{ formatNumber(pendingBalance) }}</span>
                </div>
            </div>
        </div>

        <!-- Bank Accounts Grid -->
        <div class="accounts-grid">
            <div v-for="account in bankAccounts" :key="account.id" class="bank-card">
                <div class="bank-header">
                    <div class="bank-icon" :style="{ background: account.color || '#4f46e5' }">
                        <i class="bi bi-bank"></i>
                    </div>
                    <div class="bank-info">
                        <h3>{{ account.name }}</h3>
                        <span class="bank-number">{{ maskAccountNumber(account.account_number) }}</span>
                    </div>
                    <div class="bank-actions">
                        <button @click="editAccount(account)" class="action-btn"><i class="bi bi-pencil"></i></button>
                        <button @click="deleteAccount(account)" class="action-btn delete"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
                <div class="bank-balance">
                    <span class="balance-label">Current Balance</span>
                    <span class="balance-amount" :class="{ negative: account.balance < 0 }">
                        ${{ formatNumber(account.balance) }}
                    </span>
                </div>
                <div class="bank-details">
                    <div class="detail-row">
                        <span>Bank</span>
                        <span>{{ account.bank_name }}</span>
                    </div>
                    <div class="detail-row">
                        <span>Type</span>
                        <span>{{ account.account_type }}</span>
                    </div>
                    <div class="detail-row">
                        <span>Last Reconciled</span>
                        <span>{{ account.last_reconciled ? formatDate(account.last_reconciled) : 'Never' }}</span>
                    </div>
                </div>
                <div class="bank-footer">
                    <button @click="viewTransactions(account)" class="btn btn-secondary btn-sm">
                        <i class="bi bi-list-ul"></i> Transactions
                    </button>
                    <button @click="reconcile(account)" class="btn btn-primary btn-sm">
                        <i class="bi bi-check2-square"></i> Reconcile
                    </button>
                </div>
            </div>

            <!-- Add New Card -->
            <div class="bank-card add-card" @click="showModal = true">
                <div class="add-content">
                    <i class="bi bi-plus-circle"></i>
                    <span>Add Bank Account</span>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="card" style="margin-top: 1.5rem;">
            <div class="card-header">
                <div class="header-left">
                    <i class="bi bi-arrow-left-right"></i>
                    <span>Recent Transactions</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div v-if="!recentTransactions.length" class="empty-state-sm">
                    <p>No recent transactions</p>
                </div>
                <div v-else class="transactions-list">
                    <div v-for="txn in recentTransactions" :key="txn.id" class="transaction-row">
                        <div class="txn-icon" :class="txn.type">
                            <i :class="txn.type === 'credit' ? 'bi bi-arrow-down-left' : 'bi bi-arrow-up-right'"></i>
                        </div>
                        <div class="txn-info">
                            <span class="txn-desc">{{ txn.description }}</span>
                            <span class="txn-meta">{{ txn.account_name }} &bull; {{ formatDate(txn.date) }}</span>
                        </div>
                        <div class="txn-amount" :class="txn.type">
                            {{ txn.type === 'credit' ? '+' : '-' }}${{ formatNumber(Math.abs(txn.amount)) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
            <div class="modal">
                <div class="modal-header">
                    <h3>{{ editingId ? 'Edit' : 'Add' }} Bank Account</h3>
                    <button @click="closeModal" class="close-btn"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Account Name *</label>
                        <input type="text" v-model="form.name" placeholder="e.g., Main Operating Account" class="form-input" />
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Bank Name *</label>
                            <input type="text" v-model="form.bank_name" placeholder="Bank name" class="form-input" />
                        </div>
                        <div class="form-group">
                            <label>Account Type</label>
                            <select v-model="form.account_type" class="form-input">
                                <option value="checking">Checking</option>
                                <option value="savings">Savings</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="money_market">Money Market</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Account Number</label>
                            <input type="text" v-model="form.account_number" placeholder="Account number" class="form-input" />
                        </div>
                        <div class="form-group">
                            <label>Opening Balance</label>
                            <input type="number" v-model="form.opening_balance" step="0.01" placeholder="0.00" class="form-input" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label>GL Account</label>
                        <select v-model="form.gl_account_id" class="form-input">
                            <option value="">Select GL Account</option>
                            <option v-for="acc in glAccounts" :key="acc.id" :value="acc.id">
                                {{ acc.code }} - {{ acc.name }}
                            </option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Color</label>
                        <div class="color-options">
                            <button v-for="color in colors" :key="color" type="button"
                                    @click="form.color = color"
                                    :class="['color-btn', { active: form.color === color }]"
                                    :style="{ background: color }">
                            </button>
                        </div>
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
const bankAccounts = ref([]);
const recentTransactions = ref([]);
const glAccounts = ref([]);

const colors = ['#4f46e5', '#059669', '#ea580c', '#dc2626', '#7c3aed', '#0891b2', '#ca8a04', '#be185d'];

const form = ref({
    name: '',
    bank_name: '',
    account_type: 'checking',
    account_number: '',
    opening_balance: '',
    gl_account_id: '',
    color: '#4f46e5'
});

const formatNumber = (num) => new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0);
const formatDate = (date) => date ? new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : '-';
const maskAccountNumber = (num) => num ? '****' + num.slice(-4) : '****';

const totalBalance = computed(() => bankAccounts.value.reduce((sum, a) => sum + (a.balance || 0), 0));
const reconciledBalance = computed(() => bankAccounts.value.reduce((sum, a) => sum + (a.reconciled_balance || 0), 0));
const pendingBalance = computed(() => totalBalance.value - reconciledBalance.value);

const loadData = async () => {
    try {
        loading.value = true;
        const [accountsRes, txnRes, glRes] = await Promise.all([
            api.bankAccounts.list(),
            api.bankAccounts.recentTransactions(),
            api.chartOfAccounts.list({ type: 'asset' })
        ]);
        bankAccounts.value = accountsRes.data;
        recentTransactions.value = txnRes.data;
        glAccounts.value = glRes.data;
    } catch (error) {
        console.error('Error loading data:', error);
    } finally {
        loading.value = false;
    }
};

const resetForm = () => {
    form.value = { name: '', bank_name: '', account_type: 'checking', account_number: '', opening_balance: '', gl_account_id: '', color: '#4f46e5' };
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
            await api.bankAccounts.update(editingId.value, form.value);
        } else {
            await api.bankAccounts.create(form.value);
        }
        closeModal();
        loadData();
    } catch (error) {
        console.error('Error saving:', error);
        alert('Error saving bank account');
    } finally {
        saving.value = false;
    }
};

const deleteAccount = async (account) => {
    if (!confirm(`Delete "${account.name}"?`)) return;
    try {
        await api.bankAccounts.delete(account.id);
        loadData();
    } catch (error) {
        console.error('Error deleting:', error);
    }
};

const viewTransactions = (account) => {
    alert(`View transactions for ${account.name} - Feature coming soon!`);
};

const reconcile = (account) => {
    alert(`Reconcile ${account.name} - Feature coming soon!`);
};

onMounted(() => loadData());
</script>

<style scoped>

.summary-card.total { background: linear-gradient(135deg, var(--info-900), var(--info-600)); color: white; }
.summary-card:not(.total) .summary-icon { background: var(--gray-100); color: var(--gray-500); }

.accounts-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; }
.bank-card { background: white; border-radius: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden; }
.bank-header { display: flex; align-items: center; gap: 1rem; padding: 1.25rem; border-bottom: 1px solid var(--gray-100); }
.bank-icon { width: 3rem; height: 3rem; border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.25rem; }
.bank-info { flex: 1; }
.bank-info h3 { margin: 0; font-size: 1rem; font-weight: 700; }
.bank-number { font-size: 0.75rem; color: var(--gray-500); font-family: monospace; }
.bank-actions { display: flex; gap: 0.25rem; }

.bank-balance { padding: 1.25rem; text-align: center; background: var(--gray-50); }
.balance-label { display: block; font-size: 0.75rem; color: var(--gray-500); margin-bottom: 0.25rem; }
.balance-amount { font-size: 1.75rem; font-weight: 800; color: var(--gray-900); }
.balance-amount.negative { color: var(--danger-600); }

.bank-details { padding: 1rem 1.25rem; }
.detail-row { display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--gray-100); font-size: 0.875rem; }
.detail-row:last-child { border-bottom: none; }
.detail-row span:first-child { color: var(--gray-500); }
.detail-row span:last-child { font-weight: 500; }

.bank-footer { display: flex; gap: 0.75rem; padding: 1rem 1.25rem; border-top: 1px solid var(--gray-100); }
.bank-footer .btn { flex: 1; justify-content: center; }

.add-card { border: 2px dashed var(--gray-300); background: transparent; display: flex; align-items: center; justify-content: center; min-height: 280px; cursor: pointer; transition: all 0.2s; }
.add-card:hover { border-color: var(--primary-600); background: var(--accent-50); }
.add-content { display: flex; flex-direction: column; align-items: center; gap: 0.75rem; color: var(--gray-500); }
.add-content i { font-size: 2.5rem; }
.add-card:hover .add-content { color: var(--primary-600); }

.transactions-list { max-height: 300px; overflow-y: auto; }
.transaction-row { display: flex; align-items: center; gap: 1rem; padding: 1rem 1.5rem; border-bottom: 1px solid var(--gray-100); }
.transaction-row:last-child { border-bottom: none; }
.txn-icon { width: 2.25rem; height: 2.25rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
.txn-icon.credit { background: var(--success-100); color: var(--success-600); }
.txn-icon.debit { background: var(--danger-100); color: var(--danger-600); }
.txn-info { flex: 1; }
.txn-desc { display: block; font-weight: 500; }
.txn-meta { font-size: 0.75rem; color: var(--gray-500); }
.txn-amount { font-weight: 700; }
.txn-amount.credit { color: var(--success-600); }
.txn-amount.debit { color: var(--danger-600); }

.empty-state-sm { padding: 2rem; text-align: center; color: var(--gray-500); }

.action-btn { background: transparent; border: none; padding: 0.5rem; color: var(--gray-500); cursor: pointer; border-radius: 0.375rem; }
.action-btn:hover { background: var(--gray-100); color: var(--primary-600); }
.action-btn.delete:hover { color: var(--danger-600); }

.color-options { display: flex; gap: 0.5rem; }
.color-btn { width: 2rem; height: 2rem; border-radius: 50%; border: 3px solid transparent; cursor: pointer; transition: all 0.2s; }
.color-btn:hover { transform: scale(1.1); }
.color-btn.active { border-color: var(--gray-900); box-shadow: 0 0 0 2px white; }

</style>
