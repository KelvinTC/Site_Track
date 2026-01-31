import { createRouter, createWebHistory } from 'vue-router';
import Login from '../views/Login.vue';
import Dashboard from '../views/Dashboard.vue';
import Customers from '../views/Customers.vue';
import Jobs from '../views/Jobs.vue';
import Invoices from '../views/Invoices.vue';
import Payments from '../views/Payments.vue';

const routes = [
    {
        path: '/login',
        name: 'Login',
        component: Login,
        meta: { guest: true },
    },
    {
        path: '/',
        name: 'Dashboard',
        component: Dashboard,
        meta: { requiresAuth: true },
    },
    {
        path: '/customers',
        name: 'Customers',
        component: Customers,
        meta: { requiresAuth: true },
    },
    {
        path: '/jobs',
        name: 'Jobs',
        component: Jobs,
        meta: { requiresAuth: true },
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
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Navigation guard for authentication
router.beforeEach((to, from, next) => {
    const isAuthenticated = localStorage.getItem('isAuthenticated') === 'true';

    if (to.meta.requiresAuth && !isAuthenticated) {
        next('/login');
    } else if (to.meta.guest && isAuthenticated) {
        next('/');
    } else {
        next();
    }
});

export default router;