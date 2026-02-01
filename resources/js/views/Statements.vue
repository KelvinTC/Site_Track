<template>
    <AppLayout>
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <h1>Statements</h1>
                    <p class="header-subtitle">Generate customer account statements</p>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-container">
            <div class="loader"></div>
            <p class="loading-text">Loading customers...</p>
        </div>

        <!-- Statement Generator Card -->
        <div v-else class="card">
            <div class="card-header card-header-primary">
                <div class="header-left">
                    <i class="bi bi-file-text"></i>
                    <span>Generate Statement</span>
                </div>
            </div>
            <div class="card-body">
                <div class="generator-form">
                    <!-- Customer Selection -->
                    <div class="form-group">
                        <label class="form-label">Customer *</label>
                        <select v-model="selectedCustomerId" class="form-control">
                            <option value="">Select a customer...</option>
                            <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                {{ customer.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Date Range Presets -->
                    <div class="form-group">
                        <label class="form-label">Period</label>
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
                    </div>

                    <!-- Custom Date Range -->
                    <div class="date-range-inputs">
                        <div class="form-group">
                            <label class="form-label">From Date</label>
                            <input v-model="fromDate" type="date" class="form-control" />
                        </div>
                        <div class="form-group">
                            <label class="form-label">To Date</label>
                            <input v-model="toDate" type="date" class="form-control" />
                        </div>
                    </div>

                    <!-- Generate Button -->
                    <div class="form-actions">
                        <button
                            @click="generateStatement"
                            :disabled="!selectedCustomerId || !fromDate || !toDate || loadingStatement"
                            class="btn btn-primary"
                        >
                            <span v-if="loadingStatement" class="spinner"></span>
                            <span v-else>
                                <i class="bi bi-file-earmark-text"></i>
                                Generate Statement
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statement Display -->
        <div v-if="statementData" class="card mt-4">
            <div class="card-header">
                <div class="header-left">
                    <i class="bi bi-receipt"></i>
                    <span>Statement Preview</span>
                </div>
                <button @click="statementData = null" class="btn btn-sm btn-secondary">
                    <i class="bi bi-x-lg"></i>
                    Clear
                </button>
            </div>
            <div class="card-body">
                <CustomerStatement :statement="statementData" />
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';
import CustomerStatement from '../components/CustomerStatement.vue';
import api from '../api';

const loading = ref(true);
const loadingStatement = ref(false);
const customers = ref([]);
const selectedCustomerId = ref('');
const fromDate = ref('');
const toDate = ref('');
const selectedPreset = ref('This Month');
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
    return date.toISOString().split('T')[0];
};

const applyDatePreset = (preset) => {
    selectedPreset.value = preset.label;
    const range = preset.getRange();
    fromDate.value = range.from;
    toDate.value = range.to;
};

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

const generateStatement = async () => {
    if (!selectedCustomerId.value || !fromDate.value || !toDate.value) return;
    try {
        loadingStatement.value = true;
        const response = await api.customers.getStatement(
            selectedCustomerId.value,
            fromDate.value,
            toDate.value
        );
        statementData.value = response.data;
    } catch (error) {
        console.error('Error generating statement:', error);
    } finally {
        loadingStatement.value = false;
    }
};

onMounted(() => {
    loadCustomers();
    applyDatePreset(datePresets[0]); // Default to This Month
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
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
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

.card-body {
    padding: 1.5rem;
}

/* Generator Form */
.generator-form {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    max-width: 600px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.form-label {
    font-weight: 500;
    font-size: 0.75rem;
    color: #6b7280;
}

.form-control {
    width: 100%;
    padding: 0.625rem 0.75rem;
    font-size: 0.875rem;
    color: #111827;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
}

.form-control:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

select.form-control {
    cursor: pointer;
}

/* Preset Buttons */
.preset-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

/* Date Range Inputs */
.date-range-inputs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

@media (max-width: 480px) {
    .date-range-inputs {
        grid-template-columns: 1fr;
    }
}

/* Form Actions */
.form-actions {
    padding-top: 0.5rem;
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

/* Utilities */
.mt-4 {
    margin-top: 1.5rem;
}

/* Print Styles */
@media print {
    .page-header,
    .generator-form,
    .card-header {
        display: none !important;
    }

    .card {
        box-shadow: none;
        border: none;
    }

    .card-body {
        padding: 0;
    }
}
</style>
