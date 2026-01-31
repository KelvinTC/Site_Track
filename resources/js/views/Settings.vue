<template>
    <AppLayout>
        <!-- Toast Notification -->
        <Transition name="toast">
            <div v-if="successMessage" class="toast toast-success">
                <i class="bi bi-check-circle-fill"></i>
                {{ successMessage }}
            </div>
        </Transition>
        <Transition name="toast">
            <div v-if="errorMessage" class="toast toast-error">
                <i class="bi bi-exclamation-circle-fill"></i>
                {{ errorMessage }}
            </div>
        </Transition>

        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <h1>Settings</h1>
                    <p class="header-subtitle">Manage your company profile and invoice settings</p>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-container">
            <div class="loader"></div>
            <p class="loading-text">Loading settings...</p>
        </div>

        <!-- Settings Form -->
        <div v-else class="card">
            <div class="card-header card-header-primary">
                <div class="header-left">
                    <i class="bi bi-building"></i>
                    <span>Company Profile</span>
                </div>
            </div>
            <form @submit.prevent="saveProfile" class="card-body">

                <div class="form-section">
                    <h3 class="form-section-title">Basic Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Company Name *</label>
                            <input v-model="form.company_name" type="text" required class="form-control" />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input v-model="form.email" type="email" class="form-control" />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input v-model="form.phone" type="text" class="form-control" />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Website</label>
                            <input v-model="form.website" type="text" class="form-control" />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tax ID / BP Number</label>
                            <input v-model="form.tax_id" type="text" class="form-control" />
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="form-section-title">Address</h3>
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label class="form-label">Street Address</label>
                            <input v-model="form.address" type="text" class="form-control" />
                        </div>
                        <div class="form-group">
                            <label class="form-label">City</label>
                            <input v-model="form.city" type="text" class="form-control" />
                        </div>
                        <div class="form-group">
                            <label class="form-label">Province</label>
                            <input v-model="form.state" type="text" class="form-control" />
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="form-section-title">Invoice Settings</h3>
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label class="form-label">Default Invoice Notes</label>
                            <textarea v-model="form.invoice_notes" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="form-group full-width">
                            <label class="form-label">Invoice Footer Text</label>
                            <textarea v-model="form.invoice_footer" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" :disabled="saving">
                        <i v-if="saving" class="bi bi-arrow-repeat spinning"></i>
                        <i v-else class="bi bi-check-lg"></i>
                        {{ saving ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import api from '@/api';

const loading = ref(true);
const saving = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

const form = ref({
    company_name: '',
    email: '',
    phone: '',
    website: '',
    address: '',
    city: '',
    state: '',
    zip: '',
    tax_id: '',
    logo_path: '',
    invoice_notes: '',
    invoice_footer: '',
});

const fetchProfile = async () => {
    try {
        loading.value = true;
        const response = await api.companyProfile.get();
        Object.assign(form.value, response.data);
    } catch (error) {
        console.error('Error fetching company profile:', error);
        errorMessage.value = 'Failed to load company profile.';
    } finally {
        loading.value = false;
    }
};

const saveProfile = async () => {
    try {
        saving.value = true;
        successMessage.value = '';
        errorMessage.value = '';

        await api.companyProfile.update(form.value);
        successMessage.value = 'Company profile saved successfully!';

        setTimeout(() => {
            successMessage.value = '';
        }, 3000);
    } catch (error) {
        console.error('Error saving company profile:', error);
        errorMessage.value = error.response?.data?.message || 'Failed to save company profile.';
    } finally {
        saving.value = false;
    }
};

onMounted(() => {
    fetchProfile();
});
</script>

<style scoped>
.page-header {
    margin-bottom: 2rem;
}

.header-content {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

@media (min-width: 768px) {
    .header-content {
        flex-direction: row;
        justify-content: space-between;
        align-items: flex-start;
    }
}

.header-title h1 {
    font-size: 1.875rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
    letter-spacing: -0.025em;
}

.header-subtitle {
    color: var(--text-muted);
    margin: 0.25rem 0 0;
    font-size: 0.9375rem;
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
    width: 2.5rem;
    height: 2.5rem;
    border: 3px solid var(--border-light);
    border-top-color: var(--primary-500);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.loading-text {
    margin-top: 1rem;
    color: var(--text-muted);
    font-size: 0.9375rem;
}

/* Card */
.card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 1rem;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--card-border);
}

.card-header-primary {
    background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
    color: white;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 600;
}

.header-left i {
    font-size: 1.125rem;
}

.card-body {
    padding: 1.5rem;
}

/* Form Sections */
.form-section {
    margin-bottom: 2rem;
}

.form-section:last-of-type {
    margin-bottom: 0;
}

.form-section-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--border-light);
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
}

@media (min-width: 640px) {
    .form-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-secondary);
}

.form-control {
    width: 100%;
    padding: 0.625rem 0.875rem;
    font-size: 0.9375rem;
    border: 1px solid var(--input-border);
    border-radius: 0.5rem;
    background: var(--input-bg);
    color: var(--text-primary);
    transition: all 0.2s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-500);
    box-shadow: 0 0 0 3px var(--primary-100);
}

.form-control::placeholder {
    color: var(--text-muted);
}

textarea.form-control {
    resize: vertical;
    min-height: 80px;
}

/* Input with icon */
.input-wrapper {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 0.875rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    font-size: 1rem;
    pointer-events: none;
}

.form-control.with-icon {
    padding-left: 2.5rem;
}

/* Alerts */
.alert {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
    font-size: 0.9375rem;
}

.alert i {
    font-size: 1.125rem;
}

.alert-success {
    background: var(--success-50);
    color: var(--success-700);
    border: 1px solid var(--success-200);
}

.alert-error {
    background: var(--danger-50);
    color: var(--danger-700);
    border: 1px solid var(--danger-200);
}

/* Form Actions */
.form-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border-light);
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    font-size: 0.9375rem;
    font-weight: 500;
    border-radius: 0.5rem;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background: linear-gradient(135deg, var(--primary-600) 0%, var(--primary-700) 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

/* Spinning animation */
.spinning {
    animation: spin 1s linear infinite;
}

/* Toast Notification */
.toast {
    position: fixed;
    top: 1rem;
    right: 1rem;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
    font-weight: 500;
    z-index: 9999;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.toast-success {
    background: #10b981;
    color: white;
}

.toast-error {
    background: #ef4444;
    color: white;
}

.toast-enter-active {
    animation: slideIn 0.3s ease;
}

.toast-leave-active {
    animation: slideOut 0.3s ease;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOut {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}
</style>
