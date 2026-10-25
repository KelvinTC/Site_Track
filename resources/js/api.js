import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// Add token to requests if available
api.interceptors.request.use((config) => {
    const token = localStorage.getItem('token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// Handle 401 responses
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            localStorage.removeItem('isAuthenticated');
            if (window.location.pathname !== '/login' && window.location.pathname !== '/register') {
                window.location.href = '/login';
            }
        }
        return Promise.reject(error);
    }
);

export default {
    // Authentication
    auth: {
        register(data) {
            return api.post('/register', data);
        },
        login(data) {
            return api.post('/login', data);
        },
        logout() {
            return api.post('/logout');
        },
        user() {
            return api.get('/user');
        },
        switchCompany(companyId) {
            return api.post('/switch-company', { company_id: companyId });
        },
        companies() {
            return api.get('/companies');
        },
    },

    // Users
    users: {
        getAll(params = {}) {
            return api.get('/users', { params });
        },
        get(id) {
            return api.get(`/users/${id}`);
        },
        create(data) {
            return api.post('/users', data);
        },
        update(id, data) {
            return api.put(`/users/${id}`, data);
        },
        delete(id) {
            return api.delete(`/users/${id}`);
        },
    },

    // Audit trail
    auditLogs: {
        getAll(params = {}) {
            return api.get('/audit-logs', { params });
        },
        restore(id) {
            return api.post(`/audit-logs/${id}/restore`);
        },
    },

    // Clients (formerly Customers)
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
        getStatement(id, fromDate, toDate) {
            return api.get(`/customers/${id}/statement`, { params: { from_date: fromDate, to_date: toDate } });
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
        getAging(asOfDate = null) {
            const params = asOfDate ? { as_of_date: asOfDate } : {};
            return api.get('/invoices-aging', { params });
        },
        sendEmail(id, email, message = null) {
            return api.post(`/invoices/${id}/send-email`, { email, message });
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

    // Departments
    departments: {
        list() {
            return api.get('/departments');
        },
        getAll() {
            return api.get('/departments');
        },
    },

    // Expense Categories
    expenseCategories: {
        getAll() {
            return api.get('/expense-categories');
        },
        get(id) {
            return api.get(`/expense-categories/${id}`);
        },
        create(data) {
            return api.post('/expense-categories', data);
        },
        update(id, data) {
            return api.put(`/expense-categories/${id}`, data);
        },
        delete(id) {
            return api.delete(`/expense-categories/${id}`);
        },
    },

    // Expenses
    expenses: {
        getAll(params = {}) {
            return api.get('/expenses', { params });
        },
        get(id) {
            return api.get(`/expenses/${id}`);
        },
        create(data) {
            return api.post('/expenses', data);
        },
        update(id, data) {
            return api.put(`/expenses/${id}`, data);
        },
        delete(id) {
            return api.delete(`/expenses/${id}`);
        },
        updateStatus(id, status) {
            return api.patch(`/expenses/${id}/status`, { status });
        },
        getSummary(params = {}) {
            return api.get('/expenses-summary', { params });
        },
    },

    // Employees (formerly Casuals)
    casuals: {
        getAll(params = {}) {
            return api.get('/casuals', { params });
        },
        get(id) {
            return api.get(`/casuals/${id}`);
        },
        create(data) {
            return api.post('/casuals', data);
        },
        update(id, data) {
            return api.put(`/casuals/${id}`, data);
        },
        delete(id) {
            return api.delete(`/casuals/${id}`);
        },
        getJobsReport(fromDate, toDate) {
            return api.get('/casuals-report/jobs', { params: { from_date: fromDate, to_date: toDate } });
        },
    },

    // Site Job Casuals
    siteJobCasuals: {
        getForJob(jobId) {
            return api.get(`/site-jobs/${jobId}/casuals`);
        },
        assign(jobId, casualIds, workDate = null, notes = null) {
            return api.post(`/site-jobs/${jobId}/casuals`, { casual_ids: casualIds, work_date: workDate, notes: notes });
        },
        unassign(jobId, casualId) {
            return api.delete(`/site-jobs/${jobId}/casuals/${casualId}`);
        },
    },

    // Cost Centres
    costCentres: {
        list() {
            return api.get('/cost-centres');
        },
        get(id) {
            return api.get(`/cost-centres/${id}`);
        },
        create(data) {
            return api.post('/cost-centres', data);
        },
        update(id, data) {
            return api.put(`/cost-centres/${id}`, data);
        },
        delete(id) {
            return api.delete(`/cost-centres/${id}`);
        },
    },

    // Commented out - future features
    // // Chart of Accounts
    // chartOfAccounts: {
    //     list(params = {}) {
    //         return api.get('/chart-of-accounts', { params });
    //     },
    //     get(id) {
    //         return api.get(`/chart-of-accounts/${id}`);
    //     },
    //     create(data) {
    //         return api.post('/chart-of-accounts', data);
    //     },
    //     update(id, data) {
    //         return api.put(`/chart-of-accounts/${id}`, data);
    //     },
    //     delete(id) {
    //         return api.delete(`/chart-of-accounts/${id}`);
    //     },
    // },

    // // Journal Entries
    // journalEntries: {
    //     list(params = {}) {
    //         return api.get('/journal-entries', { params });
    //     },
    //     get(id) {
    //         return api.get(`/journal-entries/${id}`);
    //     },
    //     create(data) {
    //         return api.post('/journal-entries', data);
    //     },
    //     update(id, data) {
    //         return api.put(`/journal-entries/${id}`, data);
    //     },
    //     delete(id) {
    //         return api.delete(`/journal-entries/${id}`);
    //     },
    //     post(id) {
    //         return api.post(`/journal-entries/${id}/post`);
    //     },
    // },

    // // Bank Accounts
    // bankAccounts: {
    //     list() {
    //         return api.get('/bank-accounts');
    //     },
    //     get(id) {
    //         return api.get(`/bank-accounts/${id}`);
    //     },
    //     create(data) {
    //         return api.post('/bank-accounts', data);
    //     },
    //     update(id, data) {
    //         return api.put(`/bank-accounts/${id}`, data);
    //     },
    //     delete(id) {
    //         return api.delete(`/bank-accounts/${id}`);
    //     },
    //     recentTransactions() {
    //         return api.get('/bank-accounts/recent-transactions');
    //     },
    // },

    // Reports
    reports: {
        employeeJobs(params = {}) {
            return api.get('/reports/employee-jobs', { params });
        },
        jobsSummary(params = {}) {
            return api.get('/reports/jobs-summary', { params });
        },
        revenue(params = {}) {
            return api.get('/reports/revenue', { params });
        },
        profitLoss(params = {}) {
            return api.get('/reports/profit-loss', { params });
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
        uploadLogo(file) {
            const formData = new FormData();
            formData.append('logo', file);
            return api.post('/company-profile/logo', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
        },
        deleteLogo() {
            return api.delete('/company-profile/logo');
        },
    },

    // Download a file through axios so the auth token is sent
    async download(url) {
        try {
            const response = await api.get(url, { baseURL: '', responseType: 'blob' });
            const disposition = response.headers['content-disposition'] || '';
            const match = disposition.match(/filename\*?=(?:UTF-8'')?"?([^";]+)"?/i);
            const filename = match ? decodeURIComponent(match[1]) : url.split('/').pop().split('?')[0];

            const objectUrl = URL.createObjectURL(response.data);
            const link = document.createElement('a');
            link.href = objectUrl;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(objectUrl);
        } catch (error) {
            console.error('Download failed:', error);
            alert('Download failed. Please try again.');
        }
    },

    // Exports
    exports: {
        invoicePdf(id) {
            return `/api/export/invoices/${id}/pdf`;
        },
        invoicePdfPreview(id) {
            return `/api/export/invoices/${id}/pdf/preview`;
        },
        invoicesExcel() {
            return '/api/export/invoices/excel';
        },
        customersExcel() {
            return '/api/export/customers/excel';
        },
        clientsExcel() {
            return '/api/export/customers/excel';
        },
        jobsExcel() {
            return '/api/export/jobs/excel';
        },
        paymentsExcel() {
            return '/api/export/payments/excel';
        },
        casualsExcel() {
            return '/api/export/casuals/excel';
        },
        employeesExcel() {
            return '/api/export/casuals/excel';
        },
        casualsReportExcel(fromDate, toDate) {
            let url = '/api/export/casuals-report/excel';
            const params = [];
            if (fromDate) params.push(`from_date=${fromDate}`);
            if (toDate) params.push(`to_date=${toDate}`);
            if (params.length > 0) url += '?' + params.join('&');
            return url;
        },
        casualsImportTemplate() {
            return '/api/export/casuals/import-template';
        },
        statementExcel(customerId, fromDate, toDate) {
            let url = `/api/export/customers/${customerId}/statement/excel`;
            const params = [];
            if (fromDate) params.push(`from_date=${fromDate}`);
            if (toDate) params.push(`to_date=${toDate}`);
            if (params.length > 0) url += '?' + params.join('&');
            return url;
        },
        statementCsv(customerId, fromDate, toDate) {
            let url = `/api/export/customers/${customerId}/statement/csv`;
            const params = [];
            if (fromDate) params.push(`from_date=${fromDate}`);
            if (toDate) params.push(`to_date=${toDate}`);
            if (params.length > 0) url += '?' + params.join('&');
            return url;
        },
        statementPdf(customerId, fromDate, toDate) {
            let url = `/api/export/customers/${customerId}/statement/pdf`;
            const params = [];
            if (fromDate) params.push(`from_date=${fromDate}`);
            if (toDate) params.push(`to_date=${toDate}`);
            if (params.length > 0) url += '?' + params.join('&');
            return url;
        },
    },

    // Imports
    imports: {
        casuals(file) {
            const formData = new FormData();
            formData.append('file', file);
            return api.post('/import/casuals', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
        },
    },
};
