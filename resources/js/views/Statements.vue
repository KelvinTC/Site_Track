<template>
    <AppLayout>
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <h1>Statements</h1>
                    <p class="header-subtitle">Generate client account statements</p>
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
                    <!-- Client Selection -->
                    <div class="form-group">
                        <label class="form-label">Client *</label>
                        <select v-model="selectedCustomerId" class="form-control">
                            <option value="">Select a client...</option>
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
                <div class="header-actions">
                    <button @click="exportStatement('excel')" class="btn btn-sm btn-secondary">
                        <i class="bi bi-file-earmark-excel"></i>
                        Excel
                    </button>
                    <button @click="exportStatement('csv')" class="btn btn-sm btn-secondary">
                        <i class="bi bi-filetype-csv"></i>
                        CSV
                    </button>
                    <button @click="exportStatement('pdf')" class="btn btn-sm btn-primary">
                        <i class="bi bi-file-earmark-pdf"></i>
                        PDF
                    </button>
                    <button @click="printStatement" class="btn btn-sm btn-secondary">
                        <i class="bi bi-printer"></i>
                        Print
                    </button>
                    <button @click="statementData = null" class="btn btn-sm btn-ghost">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
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

const exportStatement = (format) => {
    if (!selectedCustomerId.value) return;
    let url;
    switch (format) {
        case 'excel':
            url = api.exports.statementExcel(selectedCustomerId.value, fromDate.value, toDate.value);
            break;
        case 'csv':
            url = api.exports.statementCsv(selectedCustomerId.value, fromDate.value, toDate.value);
            break;
        case 'pdf':
            url = api.exports.statementPdf(selectedCustomerId.value, fromDate.value, toDate.value);
            break;
    }
    if (url) api.download(url);
};

const printStatement = () => {
    window.print();
};

onMounted(() => {
    loadCustomers();
    applyDatePreset(datePresets[0]); // Default to This Month
});
</script>

<style scoped>

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Generator Form */
.generator-form {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    max-width: 600px;
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
