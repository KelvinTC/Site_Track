<template>
    <AppLayout>
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <h1>Profit & Loss</h1>
                    <p class="header-subtitle">Income statement for the selected period</p>
                </div>
                <div class="header-actions">
                    <div class="filter-group">
                        <label>Period</label>
                        <select v-model="period" @change="loadReport" class="filter-select">
                            <option value="month">This Month</option>
                            <option value="quarter">This Quarter</option>
                            <option value="year">This Year</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <template v-if="period === 'custom'">
                        <div class="filter-group">
                            <label>From</label>
                            <input type="date" v-model="startDate" class="date-input" />
                        </div>
                        <div class="filter-group">
                            <label>To</label>
                            <input type="date" v-model="endDate" class="date-input" />
                        </div>
                    </template>
                    <button @click="exportReport" class="btn btn-secondary">
                        <i class="bi bi-file-earmark-excel"></i>
                        <span>Export</span>
                    </button>
                    <button @click="printReport" class="btn btn-secondary">
                        <i class="bi bi-printer"></i>
                        <span>Print</span>
                    </button>
                    <button @click="loadReport" class="btn btn-primary" :disabled="loading">
                        <i class="bi bi-arrow-clockwise" :class="{ 'spin': loading }"></i>
                    </button>
                </div>
            </div>
        </div>

        <div v-if="loading" class="loading-container">
            <div class="loader"></div>
            <p>Loading P&L data...</p>
        </div>

        <template v-else>
            <!-- Summary Cards -->
            <div class="pl-summary">
                <div class="pl-card revenue">
                    <div class="pl-icon"><i class="bi bi-graph-up-arrow"></i></div>
                    <div class="pl-content">
                        <span class="pl-label">Total Revenue</span>
                        <span class="pl-value">${{ formatNumber(data.total_revenue) }}</span>
                    </div>
                </div>
                <div class="pl-card expenses">
                    <div class="pl-icon"><i class="bi bi-graph-down-arrow"></i></div>
                    <div class="pl-content">
                        <span class="pl-label">Total Expenses</span>
                        <span class="pl-value">${{ formatNumber(data.total_expenses) }}</span>
                    </div>
                </div>
                <div class="pl-card" :class="data.net_profit >= 0 ? 'profit' : 'loss'">
                    <div class="pl-icon"><i :class="data.net_profit >= 0 ? 'bi bi-trophy-fill' : 'bi bi-exclamation-triangle-fill'"></i></div>
                    <div class="pl-content">
                        <span class="pl-label">Net {{ data.net_profit >= 0 ? 'Profit' : 'Loss' }}</span>
                        <span class="pl-value">${{ formatNumber(Math.abs(data.net_profit)) }}</span>
                    </div>
                </div>
                <div class="pl-card margin">
                    <div class="pl-icon"><i class="bi bi-percent"></i></div>
                    <div class="pl-content">
                        <span class="pl-label">Profit Margin</span>
                        <span class="pl-value">{{ data.profit_margin?.toFixed(1) || 0 }}%</span>
                    </div>
                </div>
            </div>

            <!-- P&L Statement -->
            <div class="card pl-statement">
                <div class="card-header">
                    <div class="header-left">
                        <i class="bi bi-calculator-fill"></i>
                        <span>Profit & Loss Statement</span>
                    </div>
                    <span class="period-label">{{ periodLabel }}</span>
                </div>
                <div class="card-body">
                    <!-- Revenue Section -->
                    <div class="pl-section">
                        <div class="section-header">
                            <span>Revenue</span>
                        </div>
                        <div class="pl-row" v-for="item in data.revenue_items" :key="item.name">
                            <span class="row-label">{{ item.name }}</span>
                            <span class="row-value">${{ formatNumber(item.amount) }}</span>
                        </div>
                        <div class="pl-row total">
                            <span class="row-label">Total Revenue</span>
                            <span class="row-value">${{ formatNumber(data.total_revenue) }}</span>
                        </div>
                    </div>

                    <!-- Cost of Sales -->
                    <div class="pl-section">
                        <div class="section-header">
                            <span>Cost of Sales</span>
                        </div>
                        <div class="pl-row" v-for="item in data.cost_of_sales" :key="item.name">
                            <span class="row-label">{{ item.name }}</span>
                            <span class="row-value">${{ formatNumber(item.amount) }}</span>
                        </div>
                        <div class="pl-row total">
                            <span class="row-label">Total Cost of Sales</span>
                            <span class="row-value">${{ formatNumber(data.total_cost_of_sales) }}</span>
                        </div>
                    </div>

                    <!-- Gross Profit -->
                    <div class="pl-row gross-profit">
                        <span class="row-label">Gross Profit</span>
                        <span class="row-value">${{ formatNumber(data.gross_profit) }}</span>
                    </div>

                    <!-- Operating Expenses -->
                    <div class="pl-section">
                        <div class="section-header">
                            <span>Operating Expenses</span>
                        </div>
                        <div class="pl-row" v-for="item in data.operating_expenses" :key="item.name">
                            <span class="row-label">{{ item.name }}</span>
                            <span class="row-value">${{ formatNumber(item.amount) }}</span>
                        </div>
                        <div class="pl-row total">
                            <span class="row-label">Total Operating Expenses</span>
                            <span class="row-value">${{ formatNumber(data.total_operating_expenses) }}</span>
                        </div>
                    </div>

                    <!-- Net Profit -->
                    <div class="pl-row net-profit" :class="{ loss: data.net_profit < 0 }">
                        <span class="row-label">Net {{ data.net_profit >= 0 ? 'Profit' : 'Loss' }}</span>
                        <span class="row-value">${{ formatNumber(Math.abs(data.net_profit)) }}</span>
                    </div>
                </div>
            </div>

            <!-- Expense Breakdown -->
            <div class="card" style="margin-top: 1.5rem;">
                <div class="card-header card-header-orange">
                    <div class="header-left">
                        <i class="bi bi-pie-chart-fill"></i>
                        <span>Expense Breakdown by Category</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th class="text-right">Amount</th>
                                    <th>% of Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="cat in data.expense_breakdown" :key="cat.name">
                                    <td>
                                        <div class="category-cell">
                                            <div class="category-dot" :style="{ background: cat.color }"></div>
                                            <span>{{ cat.name }}</span>
                                        </div>
                                    </td>
                                    <td class="text-right">${{ formatNumber(cat.amount) }}</td>
                                    <td>
                                        <div class="percent-bar">
                                            <div class="percent-fill" :style="{ width: cat.percentage + '%', background: cat.color }"></div>
                                            <span>{{ cat.percentage.toFixed(1) }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </template>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import api from '../../api';

const loading = ref(true);
const period = ref('month');
const startDate = ref('');
const endDate = ref('');
const data = ref({
    total_revenue: 0,
    total_expenses: 0,
    net_profit: 0,
    profit_margin: 0,
    revenue_items: [],
    cost_of_sales: [],
    total_cost_of_sales: 0,
    gross_profit: 0,
    operating_expenses: [],
    total_operating_expenses: 0,
    expense_breakdown: []
});

const formatNumber = (num) => new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0);

const periodLabel = computed(() => {
    const now = new Date();
    switch (period.value) {
        case 'month': return now.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        case 'quarter': return `Q${Math.ceil((now.getMonth() + 1) / 3)} ${now.getFullYear()}`;
        case 'year': return now.getFullYear().toString();
        case 'custom': return `${startDate.value} to ${endDate.value}`;
        default: return '';
    }
});

const loadReport = async () => {
    try {
        loading.value = true;
        const params = { period: period.value };
        if (period.value === 'custom') {
            params.start_date = startDate.value;
            params.end_date = endDate.value;
        }
        const response = await api.reports.profitLoss(params);
        data.value = response.data;
    } catch (error) {
        console.error('Error loading P&L:', error);
    } finally {
        loading.value = false;
    }
};

const exportReport = () => {
    const params = new URLSearchParams({ period: period.value, format: 'excel' });
    window.open(`/api/reports/profit-loss/export?${params}`, '_blank');
};

const printReport = () => {
    window.print();
};

onMounted(() => loadReport());
</script>

<style scoped>
.page-header { margin-bottom: 1.5rem; }
.header-content { display: flex; flex-direction: column; gap: 1rem; }
@media (min-width: 768px) { .header-content { flex-direction: row; align-items: flex-end; justify-content: space-between; } }
.header-title h1 { font-size: 1.875rem; font-weight: 800; color: #111827; margin: 0; }
.header-subtitle { color: #6b7280; font-size: 0.9375rem; margin: 0.25rem 0 0; }
.header-actions { display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: flex-end; }

.filter-group { display: flex; flex-direction: column; gap: 0.25rem; }
.filter-group label { font-size: 0.6875rem; font-weight: 500; color: #6b7280; text-transform: uppercase; }
.filter-select, .date-input { padding: 0.5rem 0.75rem; font-size: 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem; background: white; }

.pl-summary { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
@media (min-width: 1024px) { .pl-summary { grid-template-columns: repeat(4, 1fr); } }
.pl-card { background: white; border-radius: 1rem; padding: 1.25rem; display: flex; align-items: center; gap: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
.pl-card.revenue { border-left: 4px solid #10b981; }
.pl-card.expenses { border-left: 4px solid #ef4444; }
.pl-card.profit { border-left: 4px solid #4f46e5; background: linear-gradient(135deg, #eef2ff, #e0e7ff); }
.pl-card.loss { border-left: 4px solid #dc2626; background: linear-gradient(135deg, #fef2f2, #fee2e2); }
.pl-card.margin { border-left: 4px solid #f59e0b; }
.pl-icon { width: 2.5rem; height: 2.5rem; border-radius: 0.625rem; background: #f3f4f6; display: flex; align-items: center; justify-content: center; font-size: 1.125rem; color: #6b7280; }
.pl-card.revenue .pl-icon { background: #d1fae5; color: #059669; }
.pl-card.expenses .pl-icon { background: #fee2e2; color: #dc2626; }
.pl-card.profit .pl-icon { background: #c7d2fe; color: #4f46e5; }
.pl-card.loss .pl-icon { background: #fecaca; color: #dc2626; }
.pl-card.margin .pl-icon { background: #fed7aa; color: #ea580c; }
.pl-content { display: flex; flex-direction: column; }
.pl-label { font-size: 0.75rem; color: #6b7280; }
.pl-value { font-size: 1.5rem; font-weight: 800; color: #111827; }

.card { background: white; border-radius: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden; }
.card-header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.5rem; background: linear-gradient(135deg, #1e3a5f, #2563eb); color: white; }
.card-header-orange { background: linear-gradient(135deg, #ea580c, #f97316); }
.header-left { display: flex; align-items: center; gap: 0.625rem; font-weight: 600; }
.period-label { font-size: 0.875rem; opacity: 0.9; }
.card-body { padding: 1.5rem; }
.p-0 { padding: 0 !important; }

.pl-section { margin-bottom: 1.5rem; }
.section-header { padding: 0.75rem 0; border-bottom: 2px solid #e5e7eb; margin-bottom: 0.5rem; }
.section-header span { font-weight: 700; color: #374151; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; }

.pl-row { display: flex; justify-content: space-between; padding: 0.625rem 0; border-bottom: 1px solid #f3f4f6; }
.pl-row .row-label { color: #6b7280; }
.pl-row .row-value { font-weight: 500; color: #111827; }
.pl-row.total { border-top: 1px solid #e5e7eb; border-bottom: none; padding-top: 0.75rem; margin-top: 0.25rem; }
.pl-row.total .row-label, .pl-row.total .row-value { font-weight: 700; color: #111827; }

.pl-row.gross-profit { background: #f0fdf4; margin: 1rem -1.5rem; padding: 1rem 1.5rem; border: none; }
.pl-row.gross-profit .row-label, .pl-row.gross-profit .row-value { font-weight: 700; color: #059669; font-size: 1.125rem; }

.pl-row.net-profit { background: linear-gradient(135deg, #eef2ff, #e0e7ff); margin: 1rem -1.5rem -1.5rem; padding: 1.25rem 1.5rem; border: none; border-radius: 0 0 1rem 1rem; }
.pl-row.net-profit .row-label, .pl-row.net-profit .row-value { font-weight: 800; color: #4f46e5; font-size: 1.25rem; }
.pl-row.net-profit.loss { background: linear-gradient(135deg, #fef2f2, #fee2e2); }
.pl-row.net-profit.loss .row-label, .pl-row.net-profit.loss .row-value { color: #dc2626; }

.table-container { overflow-x: auto; }
.table { width: 100%; font-size: 0.875rem; border-collapse: collapse; }
.table thead th { background: #f9fafb; color: #6b7280; font-weight: 600; font-size: 0.75rem; text-transform: uppercase; padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
.table tbody td { padding: 1rem; vertical-align: middle; border-bottom: 1px solid #f3f4f6; }

.category-cell { display: flex; align-items: center; gap: 0.75rem; }
.category-dot { width: 0.75rem; height: 0.75rem; border-radius: 50%; }
.percent-bar { display: flex; align-items: center; gap: 0.75rem; }
.percent-fill { height: 0.5rem; border-radius: 0.25rem; min-width: 4px; }
.percent-bar span { font-size: 0.75rem; color: #6b7280; }

.loading-container { display: flex; flex-direction: column; align-items: center; padding: 4rem; }
.loader { width: 3rem; height: 3rem; border: 3px solid #e5e7eb; border-top-color: #2563eb; border-radius: 50%; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.spin { animation: spin 1s linear infinite; }

.btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; border: none; cursor: pointer; }
.btn-primary { background: #2563eb; color: white; }
.btn-primary:hover { background: #1d4ed8; }
.btn-secondary { background: #f3f4f6; color: #374151; }
.btn-secondary:hover { background: #e5e7eb; }

.text-right { text-align: right; }

@media print {
    .page-header, .btn { display: none !important; }
    .pl-statement { box-shadow: none; border: 1px solid #e5e7eb; }
}
</style>
