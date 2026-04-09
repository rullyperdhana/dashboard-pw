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
            path: '/ess/login',
            name: 'EssLogin',
            component: () => import('../views/ESS/EssLogin.vue'),
            meta: { essGuest: true },
        },
        {
            path: '/ess/dashboard',
            name: 'EssDashboard',
            component: () => import('../views/ESS/EssDashboard.vue'),
            meta: { requiresEssAuth: true, layout: 'empty' },
        },
        {
            path: '/',
            redirect: '/welcome',
        },
        {
            path: '/welcome',
            name: 'Welcome',
            component: () => import('../views/Welcome.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Selamat Datang' },
        },
        {
            path: '/dashboard-pppk-pw',
            name: 'DashboardPPPKPW',
            component: Dashboard,
            meta: { requiresAuth: true, breadcrumb: 'Dashboard PPPK-PW', app_access: 'pppk-pw-thr' },
        },
        {
            path: '/employees',
            name: 'Employees',
            component: EmployeeList,
            meta: { requiresAuth: true, breadcrumb: 'Daftar Pegawai', app_access: 'employees' },
        },
        {
            path: '/payments',
            name: 'Payments',
            component: PaymentList,
            meta: { requiresAuth: true, breadcrumb: 'Pembayaran', app_access: 'payments' },
        },
        {
            path: '/skpd',
            name: 'Skpd',
            component: SkpdList,
            meta: { requiresAuth: true, breadcrumb: 'Daftar SKPD', app_access: 'skpd' },
        },
        {
            path: '/reports/skpd-monthly',
            name: 'SkpdMonthlyReport',
            component: () => import('../views/SkpdMonthlyReport.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Laporan Bulanan SKPD', app_access: 'skpd-monthly' },
        },
        {
            path: '/reports/periodic',
            name: 'PeriodicReport',
            component: () => import('../views/PeriodicReport.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Laporan Periodik', app_access: 'skpd-monthly' },
        },
        {
            path: '/pns',
            name: 'PnsDashboard',
            component: () => import('../views/PnsDashboard.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Dashboard PNS & PPPK', app_access: 'pns' },
        },
        {
            path: '/reports/pppk-pw-monthly',
            name: 'PppkPwMonthlyReport',
            component: () => import('../views/PppkPwMonthlyReport.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Laporan Bulanan PW', app_access: 'pppk-pw-thr' },
        },
        {
            path: '/reports/pppk-pw-periodic',
            name: 'PppkPwPeriodicReport',
            component: () => import('../views/PeriodicReport.vue'),
            props: { forcedType: 'pw' },
            meta: { requiresAuth: true, breadcrumb: 'Laporan Periodik PW', app_access: 'pppk-pw-thr' },
        },
        {
            path: '/tpp/upload',
            name: 'TppUpload',
            component: () => import('../views/TppUpload.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Upload TPP', app_access: 'tpp-upload' },
        },
        {
            path: '/gaji-pns',
            name: 'GajiPns',
            component: () => import('../views/GajiPnsList.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Upload Gaji PNS', app_access: 'gaji-pns' },
        },
        {
            path: '/gaji-pppk',
            name: 'GajiPppk',
            component: () => import('../views/GajiPppkList.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Upload Gaji PPPK', app_access: 'gaji-pppk' },
        },
        {
            path: '/settings/pppk',
            name: 'PppkSettings',
            component: () => import('../views/Settings/PppkSettings.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Estimasi JKK/JKM/JKN', app_access: 'pppk-settings' },
        },
        {
            path: '/settings/skpd-mapping',
            name: 'SkpdMapping',
            component: () => import('../views/Settings/SkpdMapping.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Pemetaan SKPD', app_access: 'skpd-mapping' },
        },
        {
            path: '/settings/tax-status',
            name: 'TaxStatusList',
            component: () => import('../views/Settings/TaxStatusList.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Status Pajak (PTKP)', app_access: 'tax-status' },
        },
        {
            path: '/tpg-upload',
            name: 'TpgUpload',
            component: () => import('../views/TpgUpload.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Upload TPG', app_access: 'tpg-upload' },
        },
        {
            path: '/tpg-dashboard',
            name: 'TpgDashboard',
            component: () => import('../views/TpgDashboard.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Dashboard TPG', app_access: 'tpg-dashboard' },
        },
        {
            path: '/analytics/tapd',
            name: 'TapdDashboard',
            component: () => import('../views/TapdDashboard.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Analitik & Prediksi TAPD', app_access: 'tapd-dashboard' },
        },
        {
            path: '/executive/mobile',
            name: 'ExecutiveMobile',
            component: () => import('../views/ExecutiveMobile.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Executive Mobile', app_access: 'executive-mobile' },
        },
        {
            path: '/settings/users',
            name: 'UserManagement',
            component: () => import('../views/Settings/UserManagement.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Manajemen User', roles: ['superadmin'] },
        },
        {
            path: '/settings/groups',
            name: 'UserGroupManagement',
            component: () => import('../views/Settings/UserGroups.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Manajemen Group', roles: ['superadmin'] },
        },
        {
            path: '/settings/satker',
            name: 'SatkerSetting',
            component: () => import('../views/Settings/SatkerSetting.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Upload Referensi', app_access: 'satker-setting' },
        },
        {
            path: '/employees/:id/history',
            name: 'EmployeeHistory',
            component: () => import('../views/EmployeeHistory.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Riwayat Pegawai', app_access: 'employees' },
        },
        {
            path: '/employee-trace',
            name: 'EmployeeTrace',
            component: () => import('../views/EmployeeHistory.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Trace Gaji Pegawai', app_access: 'employee-trace' },
        },
        {
            path: '/bpjs-rekon',
            name: 'BpjsRekon',
            component: () => import('../views/BpjsRekon.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Rekon BPJS 4%', app_access: 'bpjs-rekon' },
        },
        {
            path: '/settings/sumber-dana',
            name: 'SumberDanaSetting',
            component: () => import('../views/SumberDanaSetting.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Sumber Dana SKPD', app_access: 'sumber-dana' },
        },
        {
            path: '/posting-data',
            name: 'PostingData',
            component: () => import('../views/PostingData.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Posting Data', app_access: 'posting-data' },
        },
        {
            path: '/master-pegawai',
            name: 'MasterPegawai',
            component: () => import('../views/MasterPegawai.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Master Pegawai', app_access: 'master-pegawai' },
        },
        {
            path: '/reports/thr-pppk-pw',
            name: 'ThrPppkPw',
            component: () => import('../views/PppkPwThr.vue'),
            meta: { requiresAuth: true, breadcrumb: 'THR PPPK-PW', app_access: 'pppk-pw-thr' },
        },
        {
            path: '/reports/gaji-13-pppk-pw',
            name: 'Gaji13PppkPw',
            component: () => import('../views/Gaji13PppkPw.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Gaji 13 PPPK-PW', app_access: 'pppk-pw-thr' },
        },
        {
            path: '/settings/maintenance',
            name: 'DataMaintenance',
            component: () => import('../views/Settings/DataMaintenance.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Pemeliharaan Data', roles: ['superadmin'] },
        },
        {
            path: '/settings/api-keys',
            name: 'ApiKeyManagement',
            component: () => import('../views/Settings/ApiKeyManagement.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Manajemen API Key', roles: ['superadmin'] },
        },
        {
            path: '/sp2d-verification',
            name: 'Sp2dVerification',
            component: () => import('../views/Sp2dVerification.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Verifikasi SP2D', app_access: 'sp2d-verification' },
        },
        {
            path: '/master-pegawai/export',
            name: 'MasterPegawaiExport',
            component: () => import('../views/Master/MasterPegawaiExport.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Export Master Pegawai', app_access: 'master-pegawai-export' },
        },
        {
            path: '/employees/import-nik',
            name: 'EmployeeNikImport',
            component: () => import('../views/Master/EmployeeNikImport.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Update NIK Massal', app_access: 'employees' },
        },
        {
            path: '/budget/input',
            name: 'BudgetInput',
            component: () => import('../views/Budget/Input.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Input Anggaran Belanja' },
        },
        {
            path: '/budget/report',
            name: 'BudgetReport',
            component: () => import('../views/Budget/Laporan.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Laporan Realisasi Belanja' },
        },
        {
            path: '/settings/export-logs',
            name: 'ExportLogs',
            component: () => import('../views/Settings/ExportLogs.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Riwayat Ekspor', roles: ['superadmin'] },
        },
        {
            path: '/help',
            name: 'HelpCenter',
            component: () => import('../views/Settings/HelpCenter.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Pusat Bantuan' },
        },
        {
            path: '/tpp/discrepancy-history',
            name: 'TppDiscrepancyHistory',
            component: () => import('../views/TppDiscrepancyHistory.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Riwayat Selisih TPP', app_access: 'tpp-upload' },
        },
        {
            path: '/reports/pph21',
            name: 'PPh21Report',
            component: () => import('../views/PPh21Report.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Pajak TER (A2)', app_access: 'pph21-report' },
        },
        {
            path: '/settings/login-logs',
            name: 'LoginLogs',
            component: () => import('../views/Settings/LoginLogs.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Log Login Pengguna', roles: ['superadmin'] },
        },
        {
            path: '/settings/audit-logs',
            name: 'AuditLogs',
            component: () => import('../views/Settings/AuditLogs.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Log Aktivitas Sistem', roles: ['superadmin'] },
        },
        {
            path: '/settings/bkd-recon',
            name: 'BkdRecon',
            component: () => import('../views/Settings/BkdRecon.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Rekon Data BKD', roles: ['superadmin', 'operator'], app_access: 'bkd-recon' },
        },
        {
            path: '/settings/announcements',
            name: 'AnnouncementManagement',
            component: () => import('../views/AnnouncementManagement.vue'),
            meta: { requiresAuth: true, breadcrumb: 'Kelola Pengumuman', roles: ['superadmin'] },
        },
    ],
})

// Navigation guard
router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('token')
    const userStr = localStorage.getItem('user')
    const user = userStr ? JSON.parse(userStr) : {}

    const essToken = localStorage.getItem('ess_token')

    // ESS Guards
    if (to.meta.requiresEssAuth && !essToken) {
        return next('/ess/login')
    } else if (to.meta.essGuest && essToken) {
        return next('/ess/dashboard')
    }

    // Admin Guards
    if (to.meta.requiresAuth && !token) {
        return next('/login')
    } else if (to.meta.guest && token) {
        return next('/welcome')
    }

    // Role and App Access check for protected routes
    if (to.meta.requiresAuth && token) {
        // Superadmin bypass
        if (user.role === 'superadmin') {
            return next()
        }

        // Check strict roles (e.g. only superadmin allowed)
        if (to.meta.roles && !to.meta.roles.includes(user.role)) {
            return next('/')
        }

        // Check app_access for operator
        if (to.meta.app_access && user.app_access && Array.isArray(user.app_access)) {
            if (!user.app_access.includes(to.meta.app_access)) {
                return next('/') // redirect to dashboard if access denied
            }
        }
    }

    next()
})

export default router
