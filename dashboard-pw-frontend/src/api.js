import axios from 'axios'

const api = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
    },
})

// ====== Session Timeout (30 menit inactivity) ======
const SESSION_TIMEOUT = 30 * 60 * 1000 // 30 minutes
let inactivityTimer = null

const resetInactivityTimer = () => {
    if (inactivityTimer) clearTimeout(inactivityTimer)
    const token = localStorage.getItem('token')
    if (token) {
        inactivityTimer = setTimeout(() => {
            localStorage.removeItem('token')
            localStorage.removeItem('user')
            window.location.href = '/login?expired=1'
        }, SESSION_TIMEOUT)
    }
}

// Listen for user activity
if (typeof window !== 'undefined') {
    const events = ['mousedown', 'keydown', 'scroll', 'touchstart']
    events.forEach(event => {
        document.addEventListener(event, resetInactivityTimer, { passive: true })
    })
    resetInactivityTimer()
}

// ====== Interceptors ======
api.interceptors.request.use((config) => {
    const token = localStorage.getItem('token')
    if (token) {
        config.headers.Authorization = `Bearer ${token}`
    }
    resetInactivityTimer()
    return config
})

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response && error.response.status === 401) {
            localStorage.removeItem('token')
            localStorage.removeItem('user')
            window.location.href = '/login'
        }
        return Promise.reject(error)
    }
)

export default api
