<template>
    <AppLayout>
        <div class="page-header">
            <div>
                <h1 class="page-title">Team Members</h1>
                <p class="page-subtitle">Manage users and their roles <span v-if="total > 0" class="total-count">({{ total }} total)</span></p>
            </div>
            <div class="header-actions">
                <div class="search-box">
                    <i class="bi bi-search search-icon"></i>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search users..."
                        class="search-input"
                        @keyup.enter="handleSearch"
                        @input="debouncedSearch"
                    />
                </div>
                <button @click="openModal()" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i>
                    <span>Add User</span>
                </button>
            </div>
        </div>

        <!-- Role Legend -->
        <div class="role-legend">
            <div class="role-item">
                <span class="role-badge owner">Owner</span>
                <span class="role-desc">Full access</span>
            </div>
            <div class="role-item">
                <span class="role-badge admin">Admin</span>
                <span class="role-desc">Manage all except settings</span>
            </div>
            <div class="role-item">
                <span class="role-badge finance">Finance</span>
                <span class="role-desc">Invoices, payments, expenses</span>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
            <p>Loading users...</p>
        </div>

        <!-- Users List -->
        <div v-else class="users-grid">
            <div v-for="user in users" :key="user.id" class="user-card">
                <div class="user-avatar">
                    {{ getInitials(user.name) }}
                </div>
                <div class="user-info">
                    <h3 class="user-name">
                        {{ user.name }}
                        <span v-if="user.id === currentUser?.id" class="you-badge">You</span>
                    </h3>
                    <p class="user-email">{{ user.email }}</p>
                    <span :class="['role-badge', user.role]">{{ user.role }}</span>
                </div>
                <div class="user-actions" v-if="user.id !== currentUser?.id">
                    <button @click="openModal(user)" class="btn-icon" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button @click="confirmDelete(user)" class="btn-icon danger" title="Remove">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>

            <div v-if="users.length === 0" class="empty-state">
                <i class="bi bi-people"></i>
                <p>{{ searchQuery ? 'No users found matching your search' : 'No team members yet' }}</p>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="lastPage > 1" class="pagination-container">
            <div class="pagination-info">
                Showing {{ (currentPage - 1) * perPage + 1 }} to {{ Math.min(currentPage * perPage, total) }} of {{ total }} users
            </div>
            <div class="pagination-controls">
                <button
                    @click="goToPage(currentPage - 1)"
                    :disabled="currentPage === 1"
                    class="pagination-btn"
                >
                    <i class="bi bi-chevron-left"></i>
                    Previous
                </button>
                <div class="pagination-pages">
                    <button
                        v-for="page in visiblePages"
                        :key="page"
                        @click="goToPage(page)"
                        :class="['pagination-page', { active: page === currentPage }]"
                    >
                        {{ page }}
                    </button>
                </div>
                <button
                    @click="goToPage(currentPage + 1)"
                    :disabled="currentPage === lastPage"
                    class="pagination-btn"
                >
                    Next
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
                <div class="modal">
                    <div class="modal-header">
                        <h2>{{ editingUser ? 'Edit User' : 'Add User' }}</h2>
                        <button @click="closeModal" class="modal-close">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <form @submit.prevent="saveUser" class="modal-body">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input v-model="form.name" type="text" class="form-control" required />
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input v-model="form.email" type="email" class="form-control" required />
                        </div>

                        <div class="form-group" v-if="!editingUser">
                            <label class="form-label">Password</label>
                            <input v-model="form.password" type="password" class="form-control" minlength="8" required />
                            <span class="form-hint">Minimum 8 characters</span>
                        </div>

                        <div class="form-group" v-if="editingUser">
                            <label class="form-label">New Password <span class="optional">(leave blank to keep current)</span></label>
                            <input v-model="form.password" type="password" class="form-control" minlength="8" />
                        </div>

                        <div class="form-group">
                            <label class="form-label">Role</label>
                            <select v-model="form.role" class="form-control" required>
                                <option value="admin">Admin</option>
                                <option value="finance">Finance</option>
                                <option value="member">Member</option>
                            </select>
                        </div>

                        <div class="form-group" v-if="editingUser">
                            <label class="checkbox-label">
                                <input type="checkbox" v-model="form.is_active" class="form-checkbox" />
                                <span>Active account</span>
                            </label>
                        </div>

                        <div v-if="formError" class="alert alert-danger">
                            {{ formError }}
                        </div>

                        <div class="modal-footer">
                            <button type="button" @click="closeModal" class="btn btn-secondary">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <span v-if="saving" class="spinner-sm"></span>
                                <span v-else>{{ editingUser ? 'Update' : 'Create' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Delete Confirmation -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal = false">
                <div class="modal modal-sm">
                    <div class="modal-header">
                        <h2>Remove User</h2>
                        <button @click="showDeleteModal = false" class="modal-close">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Remove <strong>{{ deletingUser?.name }}</strong> from this company?</p>
                        <p class="text-muted">They will lose access to all company data.</p>
                    </div>
                    <div class="modal-footer">
                        <button @click="showDeleteModal = false" class="btn btn-secondary">Cancel</button>
                        <button @click="deleteUser" class="btn btn-danger" :disabled="deleting">
                            <span v-if="deleting" class="spinner-sm"></span>
                            <span v-else>Remove</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import api from '@/api';

const users = ref([]);
const loading = ref(true);
const showModal = ref(false);
const showDeleteModal = ref(false);
const editingUser = ref(null);
const deletingUser = ref(null);
const saving = ref(false);
const deleting = ref(false);
const formError = ref('');
const searchQuery = ref('');

// Pagination
const currentPage = ref(1);
const lastPage = ref(1);
const perPage = ref(15);
const total = ref(0);

const currentUser = ref(JSON.parse(localStorage.getItem('user') || '{}'));

const form = reactive({
    name: '',
    email: '',
    password: '',
    role: 'member',
    is_active: true,
});

const visiblePages = computed(() => {
    const pages = [];
    const maxVisible = 5;
    let start = Math.max(1, currentPage.value - Math.floor(maxVisible / 2));
    let end = Math.min(lastPage.value, start + maxVisible - 1);

    if (end - start + 1 < maxVisible) {
        start = Math.max(1, end - maxVisible + 1);
    }

    for (let i = start; i <= end; i++) {
        pages.push(i);
    }
    return pages;
});

const fetchUsers = async (page = 1) => {
    loading.value = true;
    try {
        const params = {
            page,
            per_page: perPage.value,
        };
        if (searchQuery.value) {
            params.search = searchQuery.value;
        }
        const response = await api.users.getAll(params);

        // Handle paginated response
        if (response.data.current_page !== undefined) {
            users.value = response.data.data;
            currentPage.value = response.data.current_page;
            lastPage.value = response.data.last_page;
            total.value = response.data.total;
        } else {
            users.value = response.data.data || response.data;
            total.value = users.value.length;
        }
    } catch (err) {
        console.error('Failed to fetch users:', err);
    } finally {
        loading.value = false;
    }
};

const goToPage = (page) => {
    if (page >= 1 && page <= lastPage.value) {
        fetchUsers(page);
    }
};

let searchTimeout = null;

const handleSearch = () => {
    currentPage.value = 1;
    fetchUsers(1);
};

const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        handleSearch();
    }, 300);
};

const openModal = (user = null) => {
    editingUser.value = user;
    formError.value = '';
    if (user) {
        form.name = user.name;
        form.email = user.email;
        form.password = '';
        form.role = user.role;
        form.is_active = user.is_active;
    } else {
        form.name = '';
        form.email = '';
        form.password = '';
        form.role = 'member';
        form.is_active = true;
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingUser.value = null;
};

const saveUser = async () => {
    saving.value = true;
    formError.value = '';

    try {
        const data = {
            name: form.name,
            email: form.email,
            role: form.role,
        };

        if (form.password) {
            data.password = form.password;
        }

        if (editingUser.value) {
            data.is_active = form.is_active;
            await api.users.update(editingUser.value.id, data);
        } else {
            await api.users.create(data);
        }

        closeModal();
        fetchUsers();
    } catch (err) {
        if (err.response?.data?.errors) {
            formError.value = Object.values(err.response.data.errors).flat().join('. ');
        } else if (err.response?.data?.message) {
            formError.value = err.response.data.message;
        } else {
            formError.value = 'Failed to save user. Please try again.';
        }
    } finally {
        saving.value = false;
    }
};

const confirmDelete = (user) => {
    deletingUser.value = user;
    showDeleteModal.value = true;
};

const deleteUser = async () => {
    deleting.value = true;
    try {
        await api.users.delete(deletingUser.value.id);
        showDeleteModal.value = false;
        deletingUser.value = null;
        fetchUsers();
    } catch (err) {
        console.error('Failed to delete user:', err);
    } finally {
        deleting.value = false;
    }
};

const getInitials = (name) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

onMounted(() => {
    fetchUsers();
});

onUnmounted(() => {
    clearTimeout(searchTimeout);
});
</script>

<style scoped>

.page-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
}

.page-subtitle {
    color: var(--text-muted);
    font-size: 0.875rem;
    margin: 0.25rem 0 0;
}

/* Role Legend */
.role-legend {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.role-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.role-desc {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.role-badge {
    display: inline-block;
    padding: 0.25rem 0.625rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 1rem;
    text-transform: capitalize;
}

.role-badge.owner {
    background: var(--warning-100);
    color: var(--warning-800);
}

.role-badge.admin {
    background: var(--info-100);
    color: var(--info-800);
}

.role-badge.finance {
    background: var(--success-100);
    color: var(--success-800);
}

.role-badge.member {
    background: var(--gray-100);
    color: var(--text-muted);
}

/* Users Grid */
.users-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
}

.user-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--bg-card);
    border: 1px solid var(--border-light);
    border-radius: 0.75rem;
    transition: all 0.2s;
}

.user-card:hover {
    border-color: var(--primary-200);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.user-avatar {
    width: 3rem;
    height: 3rem;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-500), var(--accent-500));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.875rem;
    flex-shrink: 0;
}

.user-info {
    flex: 1;
    min-width: 0;
}

.user-name {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 0.125rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.you-badge {
    font-size: 0.625rem;
    font-weight: 600;
    padding: 0.125rem 0.375rem;
    background: var(--primary-100);
    color: var(--primary-700);
    border-radius: 0.25rem;
}

.user-email {
    font-size: 0.8125rem;
    color: var(--text-muted);
    margin: 0 0 0.5rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.user-actions {
    display: flex;
    gap: 0.25rem;
}

.btn-icon {
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: transparent;
    color: var(--text-muted);
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-icon:hover {
    background: var(--gray-100);
    color: var(--primary-600);
}

.btn-icon.danger:hover {
    background: var(--danger-100);
    color: var(--danger-600);
}

/* Loading & Empty States */
.loading-state, .empty-state {
    text-align: center;
    padding: 3rem;
    color: var(--text-muted);
}

.loading-state .spinner {
    width: 2rem;
    height: 2rem;
    border: 3px solid var(--gray-200);
    border-top-color: var(--primary-600);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.modal-sm {
    max-width: 360px;
}

.modal-header h2 {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0;
}

.modal-close:hover {
    background: var(--gray-100);
}

.modal-body p {
    margin: 0 0 0.5rem;
}

.form-label .optional {
    font-weight: 400;
    color: var(--text-muted);
}

.form-hint {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-top: 0.25rem;
}

.form-checkbox {
    width: 1rem;
    height: 1rem;
    accent-color: var(--primary-600);
}

.alert {
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    margin-bottom: 1rem;
}

.alert-danger {
    background: var(--danger-100);
    color: var(--danger-800);
    border: 1px solid var(--danger-200);
}

.spinner-sm {
    width: 1rem;
    height: 1rem;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

.search-input::placeholder {
    color: var(--text-muted);
}

.total-count {
    font-weight: 500;
    color: var(--text-muted);
}

/* Pagination */
.pagination-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem 0;
    margin-top: 1rem;
    border-top: 1px solid var(--border-light);
}

.pagination-info {
    font-size: 0.875rem;
    color: var(--text-muted);
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.pagination-btn {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-primary);
    background: var(--bg-card);
    border: 1px solid var(--border-light);
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.pagination-btn:hover:not(:disabled) {
    background: var(--gray-100);
    border-color: var(--gray-300);
}

.pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.pagination-pages {
    display: flex;
    gap: 0.25rem;
}

.pagination-page {
    min-width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-primary);
    background: var(--bg-card);
    border: 1px solid var(--border-light);
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.pagination-page:hover {
    background: var(--gray-100);
    border-color: var(--gray-300);
}

.pagination-page.active {
    background: var(--primary-600);
    border-color: var(--primary-600);
    color: white;
}

@media (max-width: 768px) {

    .search-box {
        flex: 1;
    }

    .pagination-container {
        flex-direction: column;
        gap: 1rem;
    }

    .pagination-info {
        text-align: center;
    }
}

@media (max-width: 480px) {
    .pagination-pages {
        display: none;
    }
}
</style>
