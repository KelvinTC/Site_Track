<template>
    <AppLayout>
        <!-- Toast Notification -->
        <Transition name="toast">
            <div v-if="successMessage" class="toast toast-success">
                <i class="bi bi-check-circle-fill"></i>
                {{ successMessage }}
            </div>
        </Transition>

        <!-- Page Header -->
        <div class="page-header">
            <h1>Settings</h1>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-container">
            <div class="loader"></div>
        </div>

        <!-- View Mode -->
        <div v-else-if="!editing" class="card">
            <div class="card-header">
                <span><i class="bi bi-building"></i> Company Profile</span>
                <button @click="editing = true" class="btn btn-sm btn-primary">
                    <i class="bi bi-pencil"></i> Edit
                </button>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Company Name</span>
                        <span class="info-value">{{ form.company_name || '-' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email</span>
                        <span class="info-value">{{ form.email || '-' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Phone</span>
                        <span class="info-value">{{ form.phone || '-' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Website</span>
                        <span class="info-value">{{ form.website || '-' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Tax ID / BP Number</span>
                        <span class="info-value">{{ form.tax_id || '-' }}</span>
                    </div>
                    <div class="info-item full">
                        <span class="info-label">Address</span>
                        <span class="info-value">{{ fullAddress || '-' }}</span>
                    </div>
                    <div class="info-item full">
                        <span class="info-label">Invoice Notes</span>
                        <span class="info-value">{{ form.invoice_notes || '-' }}</span>
                    </div>
                    <div class="info-item full">
                        <span class="info-label">Invoice Footer</span>
                        <span class="info-value">{{ form.invoice_footer || '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Mode -->
        <div v-else class="card">
            <div class="card-header">
                <span><i class="bi bi-pencil"></i> Edit Company Profile</span>
            </div>
            <form @submit.prevent="saveProfile" class="card-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Company Name *</label>
                        <input v-model="form.company_name" type="text" required />
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input v-model="form.email" type="email" />
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input v-model="form.phone" type="text" />
                    </div>
                    <div class="form-group">
                        <label>Website</label>
                        <input v-model="form.website" type="text" />
                    </div>
                    <div class="form-group">
                        <label>Tax ID / BP Number</label>
                        <input v-model="form.tax_id" type="text" />
                    </div>
                    <div class="form-group">
                        <label>Street Address</label>
                        <input v-model="form.address" type="text" />
                    </div>
                    <div class="form-group">
                        <label>City</label>
                        <input v-model="form.city" type="text" />
                    </div>
                    <div class="form-group">
                        <label>Province</label>
                        <input v-model="form.state" type="text" />
                    </div>
                    <div class="form-group full">
                        <label>Invoice Notes</label>
                        <textarea v-model="form.invoice_notes" rows="2"></textarea>
                    </div>
                    <div class="form-group full">
                        <label>Invoice Footer</label>
                        <textarea v-model="form.invoice_footer" rows="2"></textarea>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" @click="cancelEdit" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary" :disabled="saving">
                        {{ saving ? 'Saving...' : 'Save' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import api from '@/api';

const loading = ref(true);
const saving = ref(false);
const editing = ref(false);
const successMessage = ref('');
const originalForm = ref({});

const form = ref({
    company_name: '',
    email: '',
    phone: '',
    website: '',
    address: '',
    city: '',
    state: '',
    tax_id: '',
    invoice_notes: '',
    invoice_footer: '',
});

const fullAddress = computed(() => {
    const parts = [form.value.address, form.value.city, form.value.state].filter(Boolean);
    return parts.join(', ');
});

const fetchProfile = async () => {
    try {
        loading.value = true;
        const response = await api.companyProfile.get();
        Object.assign(form.value, response.data);
        originalForm.value = { ...response.data };
    } catch (error) {
        console.error('Error fetching company profile:', error);
    } finally {
        loading.value = false;
    }
};

const saveProfile = async () => {
    try {
        saving.value = true;
        await api.companyProfile.update(form.value);
        originalForm.value = { ...form.value };
        editing.value = false;
        successMessage.value = 'Saved!';
        setTimeout(() => { successMessage.value = ''; }, 2000);
    } catch (error) {
        console.error('Error saving:', error);
    } finally {
        saving.value = false;
    }
};

const cancelEdit = () => {
    Object.assign(form.value, originalForm.value);
    editing.value = false;
};

onMounted(() => {
    fetchProfile();
});
</script>

<style scoped>
.page-header {
    margin-bottom: 1rem;
}
.page-header h1 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
}

.loading-container {
    display: flex;
    justify-content: center;
    padding: 2rem;
}
.loader {
    width: 1.5rem;
    height: 1.5rem;
    border: 2px solid #e5e7eb;
    border-top-color: #6366f1;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    overflow: hidden;
}
.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0.75rem;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    font-size: 0.75rem;
    font-weight: 600;
}
.card-header i {
    margin-right: 0.25rem;
}
.card-body {
    padding: 0.75rem;
}

/* View Mode */
.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.5rem;
}
.info-item {
    padding: 0.25rem 0;
}
.info-item.full {
    grid-column: 1 / -1;
}
.info-label {
    display: block;
    font-size: 0.625rem;
    color: #6b7280;
    margin-bottom: 0.1rem;
}
.info-value {
    font-size: 0.75rem;
    color: #111827;
}

/* Edit Mode */
.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.4rem;
}
.form-group {
    display: flex;
    flex-direction: column;
}
.form-group.full {
    grid-column: 1 / -1;
}
.form-group label {
    font-size: 0.5625rem;
    color: #6b7280;
    margin-bottom: 0.1rem;
}
.form-group input,
.form-group textarea {
    padding: 0.2rem 0.3rem;
    font-size: 0.625rem;
    border: 1px solid #d1d5db;
    border-radius: 0.25rem;
    width: 100%;
}
.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #6366f1;
}
.form-group textarea {
    resize: vertical;
    min-height: 2rem;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.4rem;
    margin-top: 0.75rem;
    padding-top: 0.5rem;
    border-top: 1px solid #e5e7eb;
}

.btn {
    padding: 0.2rem 0.5rem;
    font-size: 0.625rem;
    font-weight: 500;
    border: none;
    border-radius: 0.25rem;
    cursor: pointer;
}
.btn-sm {
    padding: 0.15rem 0.4rem;
    font-size: 0.5625rem;
}
.btn-primary {
    background: #6366f1;
    color: white;
}
.btn-primary:hover {
    background: #4f46e5;
}
.btn-secondary {
    background: #e5e7eb;
    color: #374151;
}

/* Toast */
.toast {
    position: fixed;
    top: 0.5rem;
    right: 0.5rem;
    padding: 0.4rem 0.6rem;
    border-radius: 0.25rem;
    font-size: 0.625rem;
    font-weight: 500;
    z-index: 9999;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.toast-success {
    background: #10b981;
    color: white;
}
.toast-enter-active { animation: slideIn 0.2s ease; }
.toast-leave-active { animation: slideOut 0.2s ease; }
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
@keyframes slideOut {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
}
</style>
