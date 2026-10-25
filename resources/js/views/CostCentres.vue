<template>
    <AppLayout>
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <h1>Cost Centres</h1>
                    <p class="header-subtitle">Manage cost centres and track expenses by department</p>
                </div>
                <div class="header-actions">
                    <button @click="showModal = true" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i>
                        <span>Add Cost Centre</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="summary-card">
                <div class="summary-icon blue"><i class="bi bi-diagram-3-fill"></i></div>
                <div class="summary-content">
                    <span class="summary-value">{{ costCentres.length }}</span>
                    <span class="summary-label">Total Cost Centres</span>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon green"><i class="bi bi-check-circle-fill"></i></div>
                <div class="summary-content">
                    <span class="summary-value">{{ activeCentres }}</span>
                    <span class="summary-label">Active</span>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon orange"><i class="bi bi-wallet2"></i></div>
                <div class="summary-content">
                    <span class="summary-value">${{ formatNumber(totalBudget) }}</span>
                    <span class="summary-label">Total Budget</span>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon purple"><i class="bi bi-cash-stack"></i></div>
                <div class="summary-content">
                    <span class="summary-value">${{ formatNumber(totalSpent) }}</span>
                    <span class="summary-label">Total Spent</span>
                </div>
            </div>
        </div>

        <!-- Cost Centres Table -->
        <div class="card">
            <div class="card-header">
                <div class="header-left">
                    <i class="bi bi-diagram-3-fill"></i>
                    <span>Cost Centres</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div v-if="loading" class="loading-container">
                    <div class="loader"></div>
                </div>
                <div v-else-if="!costCentres.length" class="empty-state">
                    <i class="bi bi-diagram-3"></i>
                    <h3>No Cost Centres</h3>
                    <p>Create your first cost centre to start tracking expenses.</p>
                    <button @click="showModal = true" class="btn btn-primary">Add Cost Centre</button>
                </div>
                <div v-else class="cost-centre-cards">
                    <div v-for="centre in costCentres" :key="centre.id" class="cost-centre-card">
                        <div class="cc-header">
                            <div class="cc-info">
                                <span class="code-badge">{{ centre.code }}</span>
                                <h3>{{ centre.name }}</h3>
                            </div>
                            <div class="cc-actions">
                                <button @click="editCentre(centre)" class="action-btn"><i class="bi bi-pencil"></i></button>
                                <button @click="deleteCentre(centre)" class="action-btn delete"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                        <p class="cc-description">{{ centre.description || 'No description' }}</p>
                        <div class="cc-budget">
                            <div class="budget-row">
                                <span class="budget-label">Budget</span>
                                <span class="budget-value">${{ formatNumber(centre.budget) }}</span>
                            </div>
                            <div class="budget-row">
                                <span class="budget-label">Spent</span>
                                <span class="budget-value spent">${{ formatNumber(centre.spent) }}</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" :style="{ width: getProgressWidth(centre) + '%' }" :class="getProgressClass(centre)"></div>
                            </div>
                            <div class="budget-row remaining-row">
                                <span class="budget-label">Remaining</span>
                                <span :class="['budget-value', centre.remaining >= 0 ? 'text-success' : 'text-danger']">
                                    ${{ formatNumber(centre.remaining) }}
                                </span>
                            </div>
                        </div>
                        <div class="cc-footer">
                            <span :class="['status-badge', centre.is_active ? 'status-active' : 'status-inactive']">
                                {{ centre.is_active ? 'Active' : 'Inactive' }}
                            </span>
                            <span class="utilization">{{ getProgressWidth(centre).toFixed(0) }}% used</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
            <div class="modal">
                <div class="modal-header">
                    <h3>{{ editingId ? 'Edit' : 'Add' }} Cost Centre</h3>
                    <button @click="closeModal" class="close-btn"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Code *</label>
                            <input type="text" v-model="form.code" placeholder="e.g., CC-001" class="form-input" />
                        </div>
                        <div class="form-group">
                            <label>Name *</label>
                            <input type="text" v-model="form.name" placeholder="Cost centre name" class="form-input" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Budget ($)</label>
                        <input type="number" v-model="form.budget" placeholder="0.00" step="0.01" class="form-input" />
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea v-model="form.description" rows="3" placeholder="Description..." class="form-input"></textarea>
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
                    <button @click="saveCentre" class="btn btn-primary" :disabled="saving">
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
const costCentres = ref([]);
const departments = ref([]);

const form = ref({
    code: '',
    name: '',
    department_id: '',
    budget: '',
    description: '',
    is_active: true
});

const formatNumber = (num) => new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0);

const activeCentres = computed(() => costCentres.value.filter(c => c.is_active).length);
const totalBudget = computed(() => costCentres.value.reduce((sum, c) => sum + parseFloat(c.budget || 0), 0));
const totalSpent = computed(() => costCentres.value.reduce((sum, c) => sum + parseFloat(c.spent || 0), 0));

const getProgressWidth = (centre) => {
    if (!centre.budget || centre.budget <= 0) return 0;
    const percentage = (centre.spent / centre.budget) * 100;
    return Math.min(percentage, 100);
};

const getProgressClass = (centre) => {
    const percentage = getProgressWidth(centre);
    if (percentage >= 100) return 'over-budget';
    if (percentage >= 80) return 'warning';
    return 'normal';
};

const loadData = async () => {
    try {
        loading.value = true;
        const [centresRes, deptsRes] = await Promise.all([
            api.costCentres.list(),
            api.departments.list()
        ]);
        costCentres.value = centresRes.data;
        departments.value = deptsRes.data;
    } catch (error) {
        console.error('Error loading data:', error);
    } finally {
        loading.value = false;
    }
};

const resetForm = () => {
    form.value = { code: '', name: '', department_id: '', budget: '', description: '', is_active: true };
    editingId.value = null;
};

const closeModal = () => {
    showModal.value = false;
    resetForm();
};

const editCentre = (centre) => {
    editingId.value = centre.id;
    form.value = { ...centre };
    showModal.value = true;
};

const saveCentre = async () => {
    try {
        saving.value = true;
        if (editingId.value) {
            await api.costCentres.update(editingId.value, form.value);
        } else {
            await api.costCentres.create(form.value);
        }
        closeModal();
        loadData();
    } catch (error) {
        console.error('Error saving:', error);
        alert('Error saving cost centre');
    } finally {
        saving.value = false;
    }
};

const deleteCentre = async (centre) => {
    if (!confirm(`Delete "${centre.name}"?`)) return;
    try {
        await api.costCentres.delete(centre.id);
        loadData();
    } catch (error) {
        console.error('Error deleting:', error);
    }
};

onMounted(() => loadData());
</script>

<style scoped>

@media (min-width: 768px) { .summary-cards { grid-template-columns: repeat(4, 1fr); }
}
.summary-icon.blue { background: var(--info-100); color: var(--info-600); }
.summary-icon.purple { background: var(--accent-100); color: var(--accent-600); }

.code-badge { font-family: monospace; font-weight: 600; background: var(--primary-100); color: var(--primary-600); padding: 0.25rem 0.5rem; border-radius: 0.375rem; font-size: 0.8125rem; }
.status-active { background: var(--success-100); color: var(--success-600); }
.status-inactive { background: var(--gray-100); color: var(--gray-500); }
.text-danger { color: var(--danger-600); font-weight: 600; }

.action-btn { background: transparent; border: none; padding: 0.5rem; color: var(--gray-500); cursor: pointer; border-radius: 0.375rem; }
.action-btn:hover { background: var(--gray-100); color: var(--primary-600); }
.action-btn.delete:hover { color: var(--danger-600); }

/* Cost Centre Cards */
.cost-centre-cards { display: grid; grid-template-columns: repeat(1, 1fr); gap: 1rem; padding: 1rem; }
@media (min-width: 768px) { .cost-centre-cards { grid-template-columns: repeat(2, 1fr); }
}
@media (min-width: 1024px) { .cost-centre-cards { grid-template-columns: repeat(3, 1fr); }
}

.cost-centre-card { background: var(--bg-card); border-radius: 0.75rem; padding: 1.25rem; border: 1px solid var(--gray-200); transition: box-shadow 0.2s; }
.cost-centre-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }

.cc-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; }
.cc-info { display: flex; flex-direction: column; gap: 0.375rem; }
.cc-info h3 { margin: 0; font-size: 1rem; font-weight: 600; color: var(--gray-900); }
.cc-actions { display: flex; gap: 0.25rem; }
.cc-description { font-size: 0.8125rem; color: var(--gray-500); margin: 0 0 1rem; line-height: 1.4; }

.cc-budget { background: var(--gray-50); border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem; }
.budget-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
.budget-row:last-child { margin-bottom: 0; }
.budget-label { font-size: 0.8125rem; color: var(--gray-500); }
.budget-value { font-size: 0.9375rem; font-weight: 600; color: var(--gray-900); }
.budget-value.spent { color: var(--danger-600); }
.remaining-row { padding-top: 0.5rem; border-top: 1px solid var(--gray-200); margin-top: 0.5rem; }

.progress-bar { height: 8px; background: var(--gray-200); border-radius: 4px; overflow: hidden; margin-bottom: 0.75rem; }
.progress-fill { height: 100%; border-radius: 4px; transition: width 0.3s ease; }
.progress-fill.normal { background: linear-gradient(90deg, var(--success-500), var(--success-400)); }
.progress-fill.warning { background: linear-gradient(90deg, var(--warning-500), var(--warning-400)); }
.progress-fill.over-budget { background: linear-gradient(90deg, var(--danger-500), var(--danger-400)); }

.cc-footer { display: flex; justify-content: space-between; align-items: center; }
.utilization { font-size: 0.75rem; color: var(--gray-500); font-weight: 500; }

@keyframes spin { to { transform: rotate(360deg); } }

</style>
