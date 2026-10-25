<template>
    <AppLayout>
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <h1>Audit Log</h1>
                    <p class="header-subtitle">Who created, changed or deleted records, and when</p>
                </div>
                <div class="header-actions">
                    <button @click="loadLogs(currentPage)" class="btn btn-secondary">
                        <i class="bi bi-arrow-clockwise"></i>
                        <span>Refresh</span>
                    </button>
                </div>
            </div>
        </div>

        <div v-if="notice" :class="['notice', `notice-${notice.type}`]">
            <i :class="notice.type === 'success' ? 'bi bi-check-circle-fill' : 'bi bi-exclamation-circle-fill'"></i>
            {{ notice.text }}
        </div>

        <!-- Filters -->
        <div class="filters-bar">
            <div class="filter-group">
                <label>Search</label>
                <div class="search-box">
                    <i class="bi bi-search search-icon"></i>
                    <input v-model="filters.search" @input="debouncedLoad" type="text" class="search-input" placeholder="Record or user...">
                </div>
            </div>
            <div class="filter-group">
                <label>Record type</label>
                <select v-model="filters.type" @change="loadLogs(1)" class="filter-select">
                    <option value="">All types</option>
                    <option v-for="(label, key) in types" :key="key" :value="key">{{ label }}</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Action</label>
                <select v-model="filters.event" @change="loadLogs(1)" class="filter-select">
                    <option value="">All actions</option>
                    <option value="created">Created</option>
                    <option value="updated">Updated</option>
                    <option value="deleted">Deleted</option>
                    <option value="restored">Restored</option>
                </select>
            </div>
            <div class="filter-group">
                <label>User</label>
                <select v-model="filters.user_id" @change="loadLogs(1)" class="filter-select">
                    <option value="">All users</option>
                    <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                </select>
            </div>
            <div class="filter-group">
                <label>From</label>
                <input v-model="filters.from_date" @change="loadLogs(1)" type="date" class="filter-select">
            </div>
            <div class="filter-group">
                <label>To</label>
                <input v-model="filters.to_date" @change="loadLogs(1)" type="date" class="filter-select">
            </div>
            <button v-if="hasActiveFilters" @click="clearFilters" class="btn btn-ghost">
                <i class="bi bi-x-lg"></i> Clear
            </button>
        </div>

        <!-- Log -->
        <div class="card">
            <div class="card-header">
                <div class="header-left">
                    <i class="bi bi-clock-history"></i>
                    <span>Activity</span>
                </div>
                <span class="record-count">{{ total }} {{ total === 1 ? 'entry' : 'entries' }}</span>
            </div>

            <div v-if="loading" class="loading-container">
                <div class="loader"></div>
                <p class="loading-text">Loading activity...</p>
            </div>

            <div v-else-if="!logs.length" class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-clock-history"></i>
                </div>
                <h3 class="empty-state-title">No activity found</h3>
                <p class="empty-state-description">
                    {{ hasActiveFilters ? 'Try changing the filters.' : 'Changes to records will appear here.' }}
                </p>
            </div>

            <div v-else class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Record</th>
                            <th>Changes</th>
                            <th class="text-right"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="log in logs" :key="log.id">
                            <tr>
                                <td class="nowrap">
                                    <div class="when-date">{{ formatDate(log.created_at) }}</div>
                                    <div class="when-time">{{ formatTime(log.created_at) }}</div>
                                </td>
                                <td>{{ log.user_name || 'System' }}</td>
                                <td><span :class="['event-badge', `event-${log.event}`]">{{ eventLabel(log.event) }}</span></td>
                                <td>
                                    <div class="record-label">{{ log.label }}</div>
                                    <div class="record-type">{{ log.type_label }}</div>
                                </td>
                                <td class="changes-cell">
                                    <template v-if="log.event === 'updated'">
                                        <div v-for="field in changedFields(log).slice(0, 3)" :key="field" class="change-line">
                                            <span class="field-name">{{ fieldLabel(field) }}:</span>
                                            <span class="old-value">{{ formatValue(log.old_values?.[field]) }}</span>
                                            <i class="bi bi-arrow-right"></i>
                                            <span class="new-value">{{ formatValue(log.new_values?.[field]) }}</span>
                                        </div>
                                        <div v-if="changedFields(log).length > 3" class="more-changes">
                                            + {{ changedFields(log).length - 3 }} more
                                        </div>
                                    </template>
                                    <span v-else-if="log.event === 'restored'" class="text-muted">Record restored</span>
                                    <span v-else class="text-muted">{{ fieldCount(log) }} fields</span>
                                </td>
                                <td class="text-right nowrap">
                                    <button v-if="log.can_restore" @click="restore(log)" :disabled="restoringId === log.id" class="btn btn-sm btn-secondary">
                                        <i class="bi bi-arrow-counterclockwise"></i> Restore
                                    </button>
                                    <button v-if="hasDetails(log)" @click="toggle(log.id)" class="btn btn-sm btn-ghost" :title="expanded[log.id] ? 'Hide details' : 'Show details'">
                                        <i :class="expanded[log.id] ? 'bi bi-chevron-up' : 'bi bi-chevron-down'"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="expanded[log.id]" class="details-row">
                                <td colspan="6">
                                    <table :class="['details-table', { 'details-deleted': log.event === 'deleted' }]">
                                        <thead>
                                            <tr>
                                                <th>Field</th>
                                                <th v-if="log.old_values">{{ log.event === 'deleted' ? 'Value when deleted' : 'Before' }}</th>
                                                <th v-if="log.new_values">{{ log.event === 'created' ? 'Value' : 'After' }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="field in detailFields(log)" :key="field">
                                                <td class="field-name">{{ fieldLabel(field) }}</td>
                                                <td v-if="log.old_values" class="old-value">{{ formatValue(log.old_values[field]) }}</td>
                                                <td v-if="log.new_values" class="new-value">{{ formatValue(log.new_values[field]) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <div v-if="log.ip_address" class="ip-address">IP address: {{ log.ip_address }}</div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="lastPage > 1" class="pagination-container">
            <div class="pagination-info">
                Showing {{ (currentPage - 1) * perPage + 1 }} to {{ Math.min(currentPage * perPage, total) }} of {{ total }} entries
            </div>
            <div class="pagination-controls">
                <button @click="loadLogs(currentPage - 1)" :disabled="currentPage === 1" class="pagination-btn">
                    <i class="bi bi-chevron-left"></i>
                    Previous
                </button>
                <div class="pagination-pages">
                    <button
                        v-for="page in visiblePages"
                        :key="page"
                        @click="loadLogs(page)"
                        :class="['pagination-page', { active: page === currentPage }]"
                    >
                        {{ page }}
                    </button>
                </div>
                <button @click="loadLogs(currentPage + 1)" :disabled="currentPage === lastPage" class="pagination-btn">
                    Next
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';
import api from '../api';

const types = {
    customer: 'Customer',
    job: 'Job',
    employee: 'Employee',
    invoice: 'Invoice',
    payment: 'Payment',
    expense: 'Expense',
    company: 'Company settings',
    team: 'Team member',
};

// Internal ids that mean nothing to a reader
const HIDDEN_DETAIL_FIELDS = ['id', 'company_id', 'current_company_id'];

const logs = ref([]);
const users = ref([]);
const loading = ref(true);
const restoringId = ref(null);
const expanded = reactive({});
const notice = ref(null);

const currentPage = ref(1);
const lastPage = ref(1);
const perPage = ref(25);
const total = ref(0);

const filters = reactive({ search: '', type: '', event: '', user_id: '', from_date: '', to_date: '' });

const hasActiveFilters = computed(() => Object.values(filters).some(Boolean));

const visiblePages = computed(() => {
    const start = Math.max(1, currentPage.value - 2);
    const end = Math.min(lastPage.value, start + 4);
    return Array.from({ length: end - start + 1 }, (_, i) => start + i);
});

const loadLogs = async (page = 1) => {
    try {
        loading.value = true;
        const params = { page, per_page: perPage.value };
        Object.entries(filters).forEach(([key, value]) => { if (value) params[key] = value; });

        const response = await api.auditLogs.getAll(params);
        logs.value = response.data.data;
        currentPage.value = response.data.current_page;
        lastPage.value = response.data.last_page;
        total.value = response.data.total;
    } catch (error) {
        console.error('Error loading audit log:', error);
    } finally {
        loading.value = false;
    }
};

let searchTimer = null;
const debouncedLoad = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => loadLogs(1), 300);
};

const clearFilters = () => {
    Object.keys(filters).forEach(key => { filters[key] = ''; });
    loadLogs(1);
};

const loadUsers = async () => {
    try {
        const response = await api.users.getAll();
        users.value = response.data.data || response.data || [];
    } catch (error) {
        console.error('Error loading users:', error);
    }
};

const restore = async (log) => {
    if (!confirm(`Restore ${log.type_label.toLowerCase()} "${log.label}"?`)) return;

    try {
        restoringId.value = log.id;
        const response = await api.auditLogs.restore(log.id);
        showNotice('success', response.data.message);
        await loadLogs(currentPage.value);
    } catch (error) {
        showNotice('error', error.response?.data?.message || 'Could not restore this record.');
    } finally {
        restoringId.value = null;
    }
};

const showNotice = (type, text) => {
    notice.value = { type, text };
    setTimeout(() => { notice.value = null; }, 4000);
};

const toggle = (id) => { expanded[id] = !expanded[id]; };

const changedFields = (log) => Object.keys(log.new_values || {}).filter(f => !HIDDEN_DETAIL_FIELDS.includes(f));
const detailFields = (log) => Object.keys(log.new_values || log.old_values || {}).filter(f => !HIDDEN_DETAIL_FIELDS.includes(f));
const fieldCount = (log) => detailFields(log).length;
const hasDetails = (log) => detailFields(log).length > 0;

const eventLabel = (event) => ({ created: 'Created', updated: 'Updated', deleted: 'Deleted', restored: 'Restored' }[event] || event);

const fieldLabel = (field) => field
    .replace(/_id$/, '')
    .replace(/_/g, ' ')
    .replace(/^\w/, c => c.toUpperCase());

const formatValue = (value) => {
    if (value === null || value === undefined || value === '') return '—';
    if (value === true) return 'Yes';
    if (value === false) return 'No';
    if (typeof value === 'object') return JSON.stringify(value);
    return String(value);
};

const formatDate = (date) => new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
const formatTime = (date) => new Date(date).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

onMounted(() => {
    loadLogs();
    loadUsers();
});
</script>

<style scoped>
.notice {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-radius: 0.75rem;
    margin-bottom: 1rem;
    font-weight: 500;
}

.notice-success {
    background: var(--success-100);
    color: var(--success-700);
}

.notice-error {
    background: var(--danger-100);
    color: var(--danger-700);
}

.filters-bar {
    margin-bottom: 1.5rem;
}

.nowrap {
    white-space: nowrap;
}

.when-date {
    font-weight: 600;
    color: var(--text-primary);
}

.when-time,
.record-type {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.record-label {
    font-weight: 600;
    color: var(--text-primary);
}

.event-badge {
    display: inline-block;
    padding: 0.25rem 0.625rem;
    border-radius: 0.375rem;
    font-size: 0.6875rem;
    font-weight: 600;
}

.event-created { background: var(--success-100); color: var(--success-700); }
.event-updated { background: var(--info-100); color: var(--info-700); }
.event-deleted { background: var(--danger-100); color: var(--danger-700); }
.event-restored { background: var(--accent-100); color: var(--primary-text); }

.changes-cell {
    font-size: 0.8125rem;
    max-width: 28rem;
}

.change-line {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.375rem;
}

.change-line i {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.field-name {
    color: var(--text-muted);
}

.old-value {
    color: var(--danger-600);
    text-decoration: line-through;
    word-break: break-word;
}

.new-value {
    color: var(--success-600);
    word-break: break-word;
}

.more-changes {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-top: 0.125rem;
}

.details-row > td {
    background: var(--gray-50);
}

.details-table {
    width: 100%;
    font-size: 0.8125rem;
}

.details-table th {
    text-align: left;
    font-size: 0.6875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    padding: 0.375rem 0.5rem;
}

.details-table td {
    padding: 0.375rem 0.5rem;
    border-top: 1px solid var(--border-light);
    vertical-align: top;
}

/* In the expanded view, values are listed plainly, not struck through */
.details-table .old-value {
    text-decoration: none;
}

/* A deleted record's last values are just a snapshot, not a "before" */
.details-deleted .old-value {
    color: var(--text-primary);
}

.ip-address {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-top: 0.5rem;
}
</style>
