<template>
    <div class="statement-container" :class="{ 'print-mode': isPrinting }">
        <!-- Company Header -->
        <div class="statement-header">
            <div class="company-info" v-if="companyProfile">
                <h2 class="company-name">{{ companyProfile.company_name }}</h2>
                <p v-if="companyProfile.address">{{ companyProfile.address }}</p>
                <p v-if="companyProfile.city || companyProfile.state || companyProfile.zip">
                    {{ [companyProfile.city, companyProfile.state, companyProfile.zip].filter(Boolean).join(', ') }}
                </p>
                <p v-if="companyProfile.phone">{{ companyProfile.phone }}</p>
                <p v-if="companyProfile.email">{{ companyProfile.email }}</p>
            </div>
            <div class="statement-title">
                <h1>Customer Statement</h1>
                <p class="period-text">{{ formatDate(statement.period?.from_date) }} - {{ formatDate(statement.period?.to_date) }}</p>
            </div>
        </div>

        <!-- Customer Details -->
        <div class="customer-details" v-if="statement.customer">
            <h3>Bill To:</h3>
            <p class="customer-name">{{ statement.customer.name }}</p>
            <p v-if="statement.customer.address">{{ statement.customer.address }}</p>
            <p v-if="statement.customer.city || statement.customer.state || statement.customer.zip">
                {{ [statement.customer.city, statement.customer.state, statement.customer.zip].filter(Boolean).join(', ') }}
            </p>
            <p v-if="statement.customer.phone">{{ statement.customer.phone }}</p>
            <p v-if="statement.customer.email">{{ statement.customer.email }}</p>
        </div>

        <!-- Statement Table -->
        <div class="statement-table-container">
            <table class="statement-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Reference</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Credit</th>
                        <th class="text-right">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Opening Balance Row -->
                    <tr class="opening-balance-row">
                        <td>{{ formatDate(statement.period?.from_date) }}</td>
                        <td colspan="4"><strong>Opening Balance</strong></td>
                        <td class="text-right"><strong>{{ formatCurrency(statement.opening_balance) }}</strong></td>
                    </tr>

                    <!-- Transaction Rows -->
                    <tr v-for="(transaction, index) in statement.transactions" :key="index" :class="transaction.type">
                        <td>{{ formatDate(transaction.date) }}</td>
                        <td>{{ transaction.description }}</td>
                        <td>{{ transaction.ref }}</td>
                        <td class="text-right">{{ transaction.debit ? formatCurrency(transaction.debit) : '' }}</td>
                        <td class="text-right">{{ transaction.credit ? formatCurrency(transaction.credit) : '' }}</td>
                        <td class="text-right">{{ formatCurrency(transaction.balance) }}</td>
                    </tr>

                    <!-- Closing Balance Row -->
                    <tr class="closing-balance-row">
                        <td>{{ formatDate(statement.period?.to_date) }}</td>
                        <td colspan="4"><strong>Closing Balance</strong></td>
                        <td class="text-right"><strong>{{ formatCurrency(statement.closing_balance) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Summary -->
        <div class="statement-summary">
            <div class="summary-row">
                <span>Total Invoiced:</span>
                <span>{{ formatCurrency(statement.total_invoiced) }}</span>
            </div>
            <div class="summary-row">
                <span>Total Paid:</span>
                <span>{{ formatCurrency(statement.total_paid) }}</span>
            </div>
            <div class="summary-row total">
                <span>Balance Due:</span>
                <span>{{ formatCurrency(statement.closing_balance) }}</span>
            </div>
        </div>

        <!-- Print Button (hidden when printing) -->
        <div class="print-actions" v-if="!isPrinting">
            <button @click="printStatement" class="btn btn-primary">
                <i class="bi bi-printer"></i>
                Print Statement
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../api';

const props = defineProps({
    statement: {
        type: Object,
        required: true,
    },
});

const companyProfile = ref(null);
const isPrinting = ref(false);

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatCurrency = (amount) => {
    if (amount === null || amount === undefined) return '';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

const loadCompanyProfile = async () => {
    try {
        const response = await api.companyProfile.get();
        companyProfile.value = response.data;
    } catch (error) {
        console.error('Error loading company profile:', error);
    }
};

const printStatement = () => {
    isPrinting.value = true;
    setTimeout(() => {
        window.print();
        isPrinting.value = false;
    }, 100);
};

onMounted(() => {
    loadCompanyProfile();
});
</script>

<style scoped>
.statement-container {
    background: white;
    padding: 2rem;
    max-width: 800px;
    margin: 0 auto;
}

.statement-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid #e5e7eb;
}

.company-info {
    text-align: left;
}

.company-name {
    font-size: 1.5rem;
    font-weight: 700;
    color: #111827;
    margin: 0 0 0.5rem;
}

.company-info p {
    margin: 0.25rem 0;
    color: #4b5563;
    font-size: 0.875rem;
}

.statement-title {
    text-align: right;
}

.statement-title h1 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #4f46e5;
    margin: 0 0 0.5rem;
}

.period-text {
    color: #6b7280;
    font-size: 0.9375rem;
    margin: 0;
}

.customer-details {
    margin-bottom: 2rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 0.5rem;
}

.customer-details h3 {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #6b7280;
    margin: 0 0 0.5rem;
}

.customer-details .customer-name {
    font-weight: 600;
    color: #111827;
    margin: 0 0 0.25rem;
}

.customer-details p {
    margin: 0.25rem 0;
    color: #4b5563;
    font-size: 0.875rem;
}

.statement-table-container {
    overflow-x: auto;
    margin-bottom: 1.5rem;
}

.statement-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.statement-table thead th {
    background: #f3f4f6;
    color: #374151;
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 0.75rem 1rem;
    text-align: left;
    border-bottom: 2px solid #e5e7eb;
}

.statement-table tbody td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f3f4f6;
    color: #4b5563;
}

.statement-table tbody tr:hover {
    background: #fafafa;
}

.statement-table tbody tr.invoice td {
    color: #111827;
}

.statement-table tbody tr.payment td {
    color: #059669;
}

.opening-balance-row,
.closing-balance-row {
    background: #f9fafb;
}

.opening-balance-row td,
.closing-balance-row td {
    color: #111827 !important;
}

.closing-balance-row {
    border-top: 2px solid #e5e7eb;
}

.text-right {
    text-align: right;
}

.statement-summary {
    margin-top: 1.5rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 0.5rem;
    max-width: 300px;
    margin-left: auto;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    font-size: 0.875rem;
    color: #4b5563;
}

.summary-row.total {
    border-top: 2px solid #e5e7eb;
    margin-top: 0.5rem;
    padding-top: 0.75rem;
    font-weight: 700;
    font-size: 1rem;
    color: #111827;
}

.print-actions {
    margin-top: 2rem;
    text-align: center;
}

/* Print Styles */
@media print {
    .statement-container {
        padding: 0;
        max-width: 100%;
    }

    .print-actions {
        display: none !important;
    }

    .statement-header {
        border-bottom-color: #000;
    }

    .statement-table thead th {
        background: #eee !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .statement-table tbody tr:hover {
        background: transparent;
    }

    .opening-balance-row,
    .closing-balance-row,
    .statement-summary {
        background: #f5f5f5 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .customer-details {
        background: #f5f5f5 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>
