import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

export default {
    // Customers
    customers: {
        getAll() {
            return api.get('/customers');
        },
        get(id) {
            return api.get(`/customers/${id}`);
        },
        create(data) {
            return api.post('/customers', data);
        },
        update(id, data) {
            return api.put(`/customers/${id}`, data);
        },
        delete(id) {
            return api.delete(`/customers/${id}`);
        },
    },

    // Jobs
    jobs: {
        getAll(params = {}) {
            return api.get('/site-jobs', { params });
        },
        get(id) {
            return api.get(`/site-jobs/${id}`);
        },
        create(data) {
            return api.post('/site-jobs', data);
        },
        update(id, data) {
            return api.put(`/site-jobs/${id}`, data);
        },
        updateStatus(id, status) {
            return api.patch(`/site-jobs/${id}/status`, { status });
        },
        delete(id) {
            return api.delete(`/site-jobs/${id}`);
        },
    },

    // Invoices
    invoices: {
        getAll(params = {}) {
            return api.get('/invoices', { params });
        },
        get(id) {
            return api.get(`/invoices/${id}`);
        },
        create(data) {
            return api.post('/invoices', data);
        },
        update(id, data) {
            return api.put(`/invoices/${id}`, data);
        },
        delete(id) {
            return api.delete(`/invoices/${id}`);
        },
    },

    // Payments
    payments: {
        getAll(params = {}) {
            return api.get('/payments', { params });
        },
        get(id) {
            return api.get(`/payments/${id}`);
        },
        create(data) {
            return api.post('/payments', data);
        },
        update(id, data) {
            return api.put(`/payments/${id}`, data);
        },
        delete(id) {
            return api.delete(`/payments/${id}`);
        },
    },

    // Company Profile
    companyProfile: {
        get() {
            return api.get('/company-profile');
        },
        update(data) {
            return api.put('/company-profile', data);
        },
    },
};
