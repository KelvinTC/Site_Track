<template>
    <AppLayout>
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <h1>Revenue Report</h1>
                    <p class="header-subtitle">Revenue analysis by client and period</p>
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
                    <button @click="loadReport" class="btn btn-primary" :disabled="loading">
                        <i class="bi bi-arrow-clockwise" :class="{ 'spin': loading }"></i>
                    </button>
                </div>
            </div>
        </div>

        <div v-if="loading" class="loading-container">
            <div class="loader"></div>
            <p>Loading revenue data...</p>
        </div>

        <template v-else>
            <!-- Revenue Cards -->
            <div class="revenue-cards">
                <div class="revenue-card total">
                    <div class="revenue-header">
                        <i class="bi bi-graph-up-arrow"></i>
                        <span>Total Revenue</span>
                    </div>
                    <div class="revenue-amount">${{ formatNumber(summary.total_revenue) }}</div>
                    <div class="revenue-change" :class="{ positive: summary.change >= 0, negative: summary.change < 0 }">
                        <i :class="summary.change >= 0 ? 'bi bi-arrow-up' : 'bi bi-arrow-down'"></i>
                        {{ Math.abs(summary.change).toFixed(1) }}% vs previous period
                    </div>
                </div>
                <div class="revenue-card">
                    <div class="revenue-header">
                        <i class="bi bi-receipt"></i>
                        <span>Invoices</span>
                    </div>
                    <div class="revenue-amount">{{ summary.invoice_count }}</div>
                    <div class="revenue-sub">Total invoiced</div>
                </div>
                <div class="revenue-card">
                    <div class="revenue-header">
                        <i class="bi bi-cash-stack"></i>
                        <span>Collected</span>
                    </div>
                    <div class="revenue-amount">${{ formatNumber(summary.collected) }}</div>
                    <div class="revenue-sub">{{ summary.collection_rate.toFixed(1) }}% collection rate</div>
                </div>
                <div class="revenue-card">
                    <div class="revenue-header">
                        <i class="bi bi-clock-history"></i>
                        <span>Outstanding</span>
                    </div>
                    <div class="revenue-amount outstanding">${{ formatNumber(summary.outstanding) }}</div>
                    <div class="revenue-sub">{{ summary.outstanding_count }} invoices</div>
                </div>
            </div>

            <!-- Revenue by Client -->
            <div class="card">
                <div class="card-header">
                    <div class="header-left">
                        <i class="bi bi-people-fill"></i>
                        <span>Revenue by Client</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div v-if="!revenueByClient.length" class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <h3>No Revenue Data</h3>
                        <p>No revenue recorded for this period.</p>
                    </div>
                    <div v-else class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th class="text-center">Invoices</th>
                                    <th class="text-right">Revenue</th>
                                    <th class="text-right">Collected</th>
                                    <th class="text-right">Outstanding</th>
                                    <th>Share</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="client in revenueByClient" :key="client.id">
                                    <td>
                                        <div class="client-cell">
                                            <div class="client-avatar">{{ getInitials(client.name) }}</div>
                                            <span>{{ client.name }}</span>
                                        </div>
                                    </td>
                                    <td class="text-center">{{ client.invoice_count }}</td>
                                    <td class="text-right"><strong>${{ formatNumber(client.revenue) }}</strong></td>
                                    <td class="text-right text-success">${{ formatNumber(client.collected) }}</td>
                                    <td class="text-right text-danger">${{ formatNumber(client.outstanding) }}</td>
                                    <td>
                                        <div class="share-bar">
                                            <div class="share-fill" :style="{ width: client.share + '%' }"></div>
                                            <span>{{ client.share.toFixed(1) }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Monthly Trend -->
            <div class="card" style="margin-top: 1.5rem;">
                <div class="card-header card-header-purple">
                    <div class="header-left">
                        <i class="bi bi-bar-chart-fill"></i>
                        <span>Monthly Trend</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <div v-for="month in monthlyTrend" :key="month.month" class="chart-bar-group">
                            <div class="chart-bar-wrapper">
                                <div class="chart-bar" :style="{ height: getBarHeight(month.revenue) + '%' }">
                                    <span class="chart-value">${{ formatCompact(month.revenue) }}</span>
                                </div>
                            </div>
                            <span class="chart-label">{{ month.label }}</span>
                        </div>
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
const period = ref('year');
const startDate = ref('');
const endDate = ref('');
const summary = ref({ total_revenue: 0, collected: 0, outstanding: 0, invoice_count: 0, outstanding_count: 0, collection_rate: 0, change: 0 });
const revenueByClient = ref([]);
const monthlyTrend = ref([]);

const formatNumber = (num) => new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0);
const formatCompact = (num) => {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
    return num.toFixed(0);
};
const getInitials = (name) => name ? name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '?';

const getBarHeight = (value) => {
    const max = Math.max(...monthlyTrend.value.map(m => m.revenue), 1);
    return (value / max) * 100;
};

const loadReport = async () => {
    try {
        loading.value = true;
        const params = { period: period.value };
        if (period.value === 'custom') {
            params.start_date = startDate.value;
            params.end_date = endDate.value;
        }
        const response = await api.reports.revenue(params);
        summary.value = response.data.summary || {};
        revenueByClient.value = response.data.by_client || [];
        monthlyTrend.value = response.data.monthly_trend || [];
    } catch (error) {
        console.error('Error loading report:', error);
    } finally {
        loading.value = false;
    }
};

const exportReport = () => {
    const params = new URLSearchParams({ period: period.value });
    if (period.value === 'custom') {
        params.append('start_date', startDate.value);
        params.append('end_date', endDate.value);
    }
    window.open(`/api/reports/revenue/export?${params}`, '_blank');
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

.revenue-cards { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
@media (min-width: 1024px) { .revenue-cards { grid-template-columns: repeat(4, 1fr); } }
.revenue-card { background: white; border-radius: 1rem; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
.revenue-card.total { background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white; }
.revenue-header { display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; margin-bottom: 0.75rem; opacity: 0.9; }
.revenue-amount { font-size: 1.75rem; font-weight: 800; margin-bottom: 0.25rem; }
.revenue-amount.outstanding { color: #dc2626; }
.revenue-sub { font-size: 0.75rem; color: #6b7280; }
.revenue-card.total .revenue-sub { color: rgba(255,255,255,0.8); }
.revenue-change { font-size: 0.75rem; display: flex; align-items: center; gap: 0.25rem; }
.revenue-change.positive { color: #10b981; }
.revenue-change.negative { color: #ef4444; }
.revenue-card.total .revenue-change { color: rgba(255,255,255,0.9); }

.card { background: white; border-radius: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden; }
.card-header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.5rem; background: linear-gradient(135deg, #059669, #10b981); color: white; }
.card-header-purple { background: linear-gradient(135deg, #7c3aed, #a855f7); }
.header-left { display: flex; align-items: center; gap: 0.625rem; font-weight: 600; }
.card-body { padding: 1.5rem; }
.p-0 { padding: 0 !important; }

.table-container { overflow-x: auto; }
.table { width: 100%; font-size: 0.875rem; border-collapse: collapse; }
.table thead th { background: #f9fafb; color: #6b7280; font-weight: 600; font-size: 0.75rem; text-transform: uppercase; padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
.table tbody td { padding: 1rem; vertical-align: middle; border-bottom: 1px solid #f3f4f6; }
.table tbody tr:hover { background: #f9fafb; }

.client-cell { display: flex; align-items: center; gap: 0.75rem; }
.client-avatar { width: 2.25rem; height: 2.25rem; border-radius: 0.5rem; background: linear-gradient(135deg, #e0e7ff, #c7d2fe); display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; color: #4f46e5; }

.share-bar { display: flex; align-items: center; gap: 0.75rem; }
.share-fill { height: 0.5rem; background: linear-gradient(90deg, #4f46e5, #7c3aed); border-radius: 0.25rem; min-width: 4px; }
.share-bar span { font-size: 0.75rem; color: #6b7280; white-space: nowrap; }

.text-success { color: #059669; }
.text-danger { color: #dc2626; }

.chart-container { display: flex; justify-content: space-between; align-items: flex-end; height: 200px; gap: 0.5rem; padding-top: 2rem; }
.chart-bar-group { display: flex; flex-direction: column; align-items: center; flex: 1; }
.chart-bar-wrapper { height: 150px; display: flex; align-items: flex-end; width: 100%; }
.chart-bar { width: 100%; max-width: 3rem; margin: 0 auto; background: linear-gradient(180deg, #7c3aed, #4f46e5); border-radius: 0.375rem 0.375rem 0 0; position: relative; min-height: 4px; transition: height 0.5s ease; }
.chart-value { position: absolute; top: -1.5rem; left: 50%; transform: translateX(-50%); font-size: 0.6875rem; font-weight: 600; color: #374151; white-space: nowrap; }
.chart-label { margin-top: 0.5rem; font-size: 0.6875rem; color: #6b7280; }

.empty-state { text-align: center; padding: 3rem; color: #6b7280; }
.empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.5; }
.empty-state h3 { font-size: 1.125rem; font-weight: 700; margin: 0 0 0.5rem; color: #374151; }

.loading-container { display: flex; flex-direction: column; align-items: center; padding: 4rem; }
.loader { width: 3rem; height: 3rem; border: 3px solid #e5e7eb; border-top-color: #4f46e5; border-radius: 50%; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.spin { animation: spin 1s linear infinite; }

.btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: 0.5rem; border: none; cursor: pointer; }
.btn-primary { background: #4f46e5; color: white; }
.btn-primary:hover { background: #4338ca; }
.btn-secondary { background: #f3f4f6; color: #374151; }
.btn-secondary:hover { background: #e5e7eb; }

.text-center { text-align: center; }
.text-right { text-align: right; }
</style>
