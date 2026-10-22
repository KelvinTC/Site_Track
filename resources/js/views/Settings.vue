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
                <!-- Logo Section -->
                <div class="logo-section">
                    <div class="logo-preview" v-if="form.logo_url">
                        <img :src="form.logo_url" alt="Company Logo" />
                    </div>
                    <div class="logo-placeholder" v-else>
                        <i class="bi bi-building"></i>
                    </div>
                    <div class="logo-actions">
                        <label class="btn btn-sm btn-secondary">
                            <i class="bi bi-upload"></i> {{ form.logo_url ? 'Change' : 'Upload' }}
                            <input type="file" accept="image/*" @change="handleLogoUpload" hidden />
                        </label>
                        <button v-if="form.logo_url" @click="removeLogo" class="btn btn-sm btn-danger">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>

                <div class="section-divider">
                    <span class="section-title"><i class="bi bi-info-circle"></i> Company Info</span>
                </div>
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
                        <span class="info-label">Tax ID / BP Number</span>
                        <span class="info-value">{{ form.tax_id || '-' }}</span>
                    </div>
                </div>

                <!-- VAT Section -->
                <div class="section-divider">
                    <span class="section-title"><i class="bi bi-percent"></i> VAT Settings</span>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">VAT Registered</span>
                        <span class="info-value">
                            <span :class="form.vat_registered ? 'status-yes' : 'status-no'">
                                {{ form.vat_registered ? 'Yes' : 'No' }}
                            </span>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">VAT Number</span>
                        <span class="info-value">{{ form.vat_number || '-' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">VAT Rate</span>
                        <span class="info-value">{{ form.vat_rate || 15 }}%</span>
                    </div>
                </div>

                <!-- Invoice Settings Section -->
                <div class="section-divider">
                    <span class="section-title"><i class="bi bi-receipt"></i> Invoice Settings</span>
                </div>
                <div class="info-grid">
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
                        <label>Tax ID / BP Number</label>
                        <input v-model="form.tax_id" type="text" />
                    </div>
                </div>

                <!-- VAT Settings Section -->
                <div class="section-divider">
                    <span class="section-title"><i class="bi bi-percent"></i> VAT Settings</span>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" v-model="form.vat_registered" />
                            <span>VAT Registered</span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label>VAT Number</label>
                        <input v-model="form.vat_number" type="text" placeholder="e.g. 10001234" :disabled="!form.vat_registered" />
                    </div>
                    <div class="form-group">
                        <label>VAT Rate (%)</label>
                        <input v-model.number="form.vat_rate" type="number" step="0.01" min="0" max="100" :disabled="!form.vat_registered" />
                    </div>
                </div>

                <!-- Address Section -->
                <div class="section-divider">
                    <span class="section-title"><i class="bi bi-geo-alt"></i> Address</span>
                </div>
                <div class="form-grid">
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
                </div>

                <!-- Invoice Settings Section -->
                <div class="section-divider">
                    <span class="section-title"><i class="bi bi-receipt"></i> Invoice Settings</span>
                </div>
                <div class="form-grid">
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
    address: '',
    city: '',
    state: '',
    tax_id: '',
    vat_number: '',
    vat_rate: 15,
    vat_registered: false,
    invoice_notes: '',
    invoice_footer: '',
    logo_url: null,
});

const uploadingLogo = ref(false);

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

const handleLogoUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    uploadingLogo.value = true;
    try {
        const response = await api.companyProfile.uploadLogo(file);
        form.value.logo_url = response.data.logo_url;
        successMessage.value = 'Logo uploaded!';
        setTimeout(() => { successMessage.value = ''; }, 2000);
    } catch (error) {
        console.error('Error uploading logo:', error);
    } finally {
        uploadingLogo.value = false;
        event.target.value = '';
    }
};

const removeLogo = async () => {
    try {
        await api.companyProfile.deleteLogo();
        form.value.logo_url = null;
        successMessage.value = 'Logo removed!';
        setTimeout(() => { successMessage.value = ''; }, 2000);
    } catch (error) {
        console.error('Error removing logo:', error);
    }
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
    padding: 0.75rem 1rem;
    background: transparent;
    border-bottom: 1px solid #e5e7eb;
    font-size: 0.875rem;
    font-weight: 600;
}
.card-header i {
    margin-right: 0.25rem;
}
.card-body {
    padding: 0.75rem;
}

/* Logo Section */
.logo-section {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding-bottom: 0.75rem;
}

.logo-preview {
    width: 4rem;
    height: 4rem;
    border-radius: 0.5rem;
    overflow: hidden;
    border: 1px solid #e5e7eb;
}

.logo-preview img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.logo-placeholder {
    width: 4rem;
    height: 4rem;
    border-radius: 0.5rem;
    background: #f3f4f6;
    border: 2px dashed #d1d5db;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    font-size: 1.5rem;
}

.logo-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-danger {
    background: #fee2e2;
    color: #dc2626;
}

.btn-danger:hover {
    background: #fecaca;
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
    gap: 0.75rem;
}
.form-group {
    display: flex;
    flex-direction: column;
}
.form-group.full {
    grid-column: 1 / -1;
}
.form-group label {
    font-size: 0.6875rem;
    color: #6b7280;
    margin-bottom: 0.25rem;
}
.form-group input,
.form-group textarea {
    padding: 0.5rem 0.625rem;
    font-size: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
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
.form-group input:disabled {
    background: #f3f4f6;
    cursor: not-allowed;
}

/* Section Divider */
.section-divider {
    margin: 1rem 0 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid #e5e7eb;
}
.section-title {
    font-size: 0.6875rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.section-title i {
    margin-right: 0.25rem;
}

/* Checkbox Label */
.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    padding: 0.5rem 0;
}
.checkbox-label input[type="checkbox"] {
    width: 1rem;
    height: 1rem;
    accent-color: #6366f1;
}
.checkbox-label span {
    font-size: 0.75rem;
    color: #374151;
}

/* Status Badges */
.status-yes {
    background: #d1fae5;
    color: #047857;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    font-size: 0.6875rem;
    font-weight: 600;
}
.status-no {
    background: #f3f4f6;
    color: #6b7280;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    font-size: 0.6875rem;
    font-weight: 600;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-top: 1rem;
    padding-top: 0.75rem;
    border-top: 1px solid #e5e7eb;
}

.btn {
    padding: 0.5rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 500;
    border: none;
    border-radius: 0.375rem;
    cursor: pointer;
}
.btn-sm {
    padding: 0.375rem 0.5rem;
    font-size: 0.6875rem;
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
