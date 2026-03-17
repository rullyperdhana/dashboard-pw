import { ref } from 'vue'

export const isSidebarOpen = ref(true)

export const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value
}
