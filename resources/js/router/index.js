import { createRouter, createWebHistory } from 'vue-router';
import Login from '../views/Login.vue';
import Register from '../views/Register.vue';
import Dashboard from '../views/Dashboard.vue';
import Users from '../views/Users.vue';
import Clients from '../views/Customers.vue';
import Jobs from '../views/Jobs.vue';
import Employees from '../views/Casuals.vue';
import Invoices from '../views/Invoices.vue';
import Payments from '../views/Payments.vue';
import Expenses from '../views/Expenses.vue';
import AgingReport from '../views/AgingReport.vue';
import Statements from '../views/Statements.vue';
import Settings from '../views/Settings.vue';

// Accounting - only Cost Centres active for now
import CostCentres from '../views/CostCentres.vue';
// Commented out - future features
// import ChartOfAccounts from '../views/ChartOfAccounts.vue';
// import JournalEntries from '../views/JournalEntries.vue';
// import BankAccounts from '../views/BankAccounts.vue';

// Reports
import EmployeeJobsReport from '../views/reports/EmployeeJobsReport.vue';
import JobsSummaryReport from '../views/reports/JobsSummaryReport.vue';
import RevenueReport from '../views/reports/RevenueReport.vue';
import ProfitLossReport from '../views/reports/ProfitLossReport.vue';

const routes = [
    {
        path: '/login',
        name: 'Login',
        component: Login,
        meta: { guest: true },
    },
    {
        path: '/register',
        name: 'Register',
        component: Register,
        meta: { guest: true },
    },
    {
        path: '/',
        name: 'Dashboard',
        component: Dashboard,
        meta: { requiresAuth: true },
    },
    {
        path: '/users',
        name: 'Users',
        component: Users,
        meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
        path: '/clients',
        name: 'Clients',
        component: Clients,
        meta: { requiresAuth: true, requiresAdmin: true },
    },
    // Redirect old path
    {
        path: '/customers',
        redirect: '/clients',
    },
    {
        path: '/jobs',
        name: 'Jobs',
        component: Jobs,
        meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
        path: '/employees',
        name: 'Employees',
        component: Employees,
        meta: { requiresAuth: true, requiresAdmin: true },
    },
    // Redirect old path
    {
        path: '/casuals',
        redirect: '/employees',
    },
    {
        path: '/invoices',
        name: 'Invoices',
        component: Invoices,
        meta: { requiresAuth: true },
    },
    {
        path: '/payments',
        name: 'Payments',
        component: Payments,
        meta: { requiresAuth: true },
    },
    {
        path: '/expenses',
        name: 'Expenses',
        component: Expenses,
        meta: { requiresAuth: true },
    },
    {
        path: '/statements',
        name: 'Statements',
        component: Statements,
        meta: { requiresAuth: true },
    },
    // Accounting - only Cost Centres active for now
    {
        path: '/cost-centres',
        name: 'CostCentres',
        component: CostCentres,
        meta: { requiresAuth: true },
    },
    // Commented out - future features
    // {
    //     path: '/chart-of-accounts',
    //     name: 'ChartOfAccounts',
    //     component: ChartOfAccounts,
    //     meta: { requiresAuth: true },
    // },
    // {
    //     path: '/journal-entries',
    //     name: 'JournalEntries',
    //     component: JournalEntries,
    //     meta: { requiresAuth: true },
    // },
    // {
    //     path: '/bank-accounts',
    //     name: 'BankAccounts',
    //     component: BankAccounts,
    //     meta: { requiresAuth: true },
    // },
    // Reports
    {
        path: '/aging',
        name: 'AgingReport',
        component: AgingReport,
        meta: { requiresAuth: true },
    },
    {
        path: '/reports/employee-jobs',
        name: 'EmployeeJobsReport',
        component: EmployeeJobsReport,
        meta: { requiresAuth: true },
    },
    {
        path: '/reports/jobs-summary',
        name: 'JobsSummaryReport',
        component: JobsSummaryReport,
        meta: { requiresAuth: true },
    },
    {
        path: '/reports/revenue',
        name: 'RevenueReport',
        component: RevenueReport,
        meta: { requiresAuth: true },
    },
    {
        path: '/reports/profit-loss',
        name: 'ProfitLossReport',
        component: ProfitLossReport,
        meta: { requiresAuth: true },
    },
    {
        path: '/settings',
        name: 'Settings',
        component: Settings,
        meta: { requiresAuth: true, requiresOwner: true },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Helper to get user role from localStorage
const getUserRole = () => {
    try {
        const userStr = localStorage.getItem('user');
        if (userStr) {
            const user = JSON.parse(userStr);
            return user.role;
        }
    } catch (e) {
        console.error('Failed to parse user from localStorage', e);
    }
    return null;
};

// Navigation guard for authentication and authorization
router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('token');
    const isAuthenticated = !!token;

    if (to.meta.requiresAuth && !isAuthenticated) {
        next('/login');
        return;
    }

    if (to.meta.guest && isAuthenticated) {
        next('/');
        return;
    }

    // Check role-based access
    if (to.meta.requiresOwner) {
        const role = getUserRole();
        if (role !== 'owner') {
            next('/'); // Redirect to dashboard
            return;
        }
    }

    if (to.meta.requiresAdmin) {
        const role = getUserRole();
        if (role !== 'owner' && role !== 'admin') {
            next('/'); // Redirect to dashboard
            return;
        }
    }

    next();
});

export default router;
