import { createRouter, createWebHistory } from 'vue-router'
import Login from '../views/Login.vue'
import Dashboard from '../views/Dashboard.vue'
import EmployeeList from '../views/EmployeeList.vue'
import SkpdList from '../views/SkpdList.vue'
import PaymentList from '../views/PaymentList.vue'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/login',
            name: 'Login',
            component: Login,
            meta: { guest: true },
        },
        {
            path: '/',
            name: 'DashboardPPPKPW',
            component: Dashboard,
            meta: { requiresAuth: true },
        },
        {
            path: '/employees',
            name: 'Employees',
            component: EmployeeList,
            meta: { requiresAuth: true },
        },
        {
            path: '/payments',
            name: 'Payments',
            component: PaymentList,
            meta: { requiresAuth: true },
        },
        {
            path: '/skpd',
            name: 'Skpd',
            component: SkpdList,
            meta: { requiresAuth: true },
        },
        // /reports route removed - merged into main Dashboard PPPK-PW
        {
            path: '/reports/skpd-monthly',
            name: 'SkpdMonthlyReport',
            component: () => import('../views/SkpdMonthlyReport.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/pns',
            name: 'PnsDashboard',
            component: () => import('../views/PnsDashboard.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/tpp/upload',
            name: 'TppUpload',
            component: () => import('../views/TppUpload.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/gaji-pns',
            name: 'GajiPns',
            component: () => import('../views/GajiPnsList.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/gaji-pppk',
            name: 'GajiPppk',
            component: () => import('../views/GajiPppkList.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/settings/pppk',
            name: 'PppkSettings',
            component: () => import('../views/Settings/PppkSettings.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/settings/skpd-mapping',
            name: 'SkpdMapping',
            component: () => import('../views/Settings/SkpdMapping.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/tpg-upload',
            name: 'TpgUpload',
            component: () => import('../views/TpgUpload.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/tpg-dashboard',
            name: 'TpgDashboard',
            component: () => import('../views/TpgDashboard.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/settings/users',
            name: 'UserManagement',
            component: () => import('../views/Settings/UserManagement.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/employees/:id/history',
            name: 'EmployeeHistory',
            component: () => import('../views/EmployeeHistory.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/employee-trace',
            name: 'EmployeeTrace',
            component: () => import('../views/EmployeeHistory.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/bpjs-rekon',
            name: 'BpjsRekon',
            component: () => import('../views/BpjsRekon.vue'),
            meta: { requiresAuth: true },
        },
    ],
})

// Navigation guard
router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('token')

    if (to.meta.requiresAuth && !token) {
        next('/login')
    } else if (to.meta.guest && token) {
        next('/')
    } else {
        next()
    }
})

export default router
