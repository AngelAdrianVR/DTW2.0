<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';

defineProps({
    navigation: Array,
});

// --- STATE MANAGEMENT ---

const isExpanded = ref(JSON.parse(localStorage.getItem('sidebarExpanded')) ?? true);
const isProfileCardVisible = ref(false);
const isSettingsCardVisible = ref(false);

// State for shadow customization
const shadowBaseColor = ref(localStorage.getItem('sidebarShadowBaseColor') || '0, 0, 0');
const shadowIntensity = ref(parseInt(localStorage.getItem('sidebarShadowIntensity') || 15, 10));
const shadowBlur = ref(parseInt(localStorage.getItem('sidebarShadowBlur') || 33, 10));

// --- NEW: State for PrimeVue primary color ---
const primaryColor = ref(localStorage.getItem('appPrimaryColor') || '#3b82f6'); // Default to Tailwind blue-500

const openGroups = ref({}); // State for expanded navigation groups
const openCollapsedGroup = ref(null); // State for collapsed navigation groups (on click)
const colorPickerInput = ref(null); // Ref for the shadow color input
const primaryColorPickerInput = ref(null); // Ref for the primary color input

// --- WATCHERS for localStorage persistence ---

watch(shadowBaseColor, (newValue) => {
    localStorage.setItem('sidebarShadowBaseColor', newValue);
});
watch(shadowIntensity, (newValue) => {
    localStorage.setItem('sidebarShadowIntensity', newValue.toString());
});
watch(shadowBlur, (newValue) => {
    localStorage.setItem('sidebarShadowBlur', newValue.toString());
});

// --- NEW: Watcher for primary color changes ---
watch(primaryColor, (newValue) => {
    localStorage.setItem('appPrimaryColor', newValue);
    updateThemeColor(newValue);
});


// --- COMPUTED PROPERTIES ---

const fullShadowColor = computed(() => `rgba(${shadowBaseColor.value}, ${shadowIntensity.value / 100})`);

const sidebarStyle = computed(() => ({
  '--shadow-color': fullShadowColor.value,
  '--shadow-blur': `${shadowBlur.value}px`,
}));

// --- METHODS ---

const logout = () => {
    router.post(route('logout'));
};

const toggleExpand = () => {
    isExpanded.value = !isExpanded.value;
    localStorage.setItem('sidebarExpanded', JSON.stringify(isExpanded.value));
    openCollapsedGroup.value = null; // Close any open group when expanding/collapsing
};

const toggleProfileCard = () => {
    isProfileCardVisible.value = !isProfileCardVisible.value;
    if (isProfileCardVisible.value) {
        isSettingsCardVisible.value = false;
    }
};

const toggleSettingsCard = () => {
    isSettingsCardVisible.value = !isSettingsCardVisible.value;
    if (isSettingsCardVisible.value) {
        isProfileCardVisible.value = false;
    }
};

// Converts HEX color to RGB string
const hexToRgb = (hex) => {
    const sanitizedHex = hex.replace('#', '');
    const r = parseInt(sanitizedHex.substring(0, 2), 16);
    const g = parseInt(sanitizedHex.substring(2, 4), 16);
    const b = parseInt(sanitizedHex.substring(4, 6), 16);
    return `${r}, ${g}, ${b}`;
};

// Updates shadow base color from presets or color picker
const updateShadowBaseColor = (color) => {
    const newRgb = color.startsWith('#') ? hexToRgb(color) : color;
    shadowBaseColor.value = newRgb;
};

const openColorPicker = () => {
    colorPickerInput.value.click();
};

// --- NEW: Methods to update and manage the primary color ---

/**
 * Updates the CSS variables for the PrimeVue theme.
 * @param {string} hex - The new primary color in hex format.
 */
const updateThemeColor = (hex) => {
    if (!hex || !hex.startsWith('#')) return;

    // Set the main primary color variable for PrimeVue
    document.documentElement.style.setProperty('--p-primary-color', hex);

    // Also set a common variable, some components might use this.
    document.documentElement.style.setProperty('--primary-color', hex);
    
    // Simple algorithm to determine if text on this color should be light or dark for readability
    const r = parseInt(hex.substring(1, 3), 16);
    const g = parseInt(hex.substring(3, 5), 16);
    const b = parseInt(hex.substring(5, 7), 16);
    const brightness = ((r * 299) + (g * 587) + (b * 114)) / 1000;
    const textColor = brightness > 125 ? '#000000' : '#ffffff';
    
    document.documentElement.style.setProperty('--p-primary-color-text', textColor);
};

const openPrimaryColorPicker = () => {
    primaryColorPickerInput.value.click();
};

const setPrimaryColor = (color) => {
    primaryColor.value = color;
    // The watcher will handle the update
};


// Toggles group in expanded mode
const toggleGroup = (groupName) => {
    openGroups.value[groupName] = !openGroups.value[groupName];
};

// Toggles group in collapsed mode
const toggleCollapsedGroup = (groupName) => {
    openCollapsedGroup.value = openCollapsedGroup.value === groupName ? null : groupName;
};

// Checks if any child in a group is active
const isGroupActive = (group) => {
    return group.children && group.children.some(child => child.active);
};

// --- LIFECYCLE HOOKS ---

onMounted(() => {
    // Apply theme color on initial load
    updateThemeColor(primaryColor.value);
});

</script>

<template>
    <!-- Main Sidebar Container -->
    <aside
        :style="sidebarStyle"
        class="hidden lg:flex flex-col h-[calc(100vh-2rem)] m-4 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 transition-all duration-300 ease-in-out rounded-2xl shadow-[0_0_var(--shadow-blur)_-3px_var(--shadow-color)]"
        :class="isExpanded ? 'w-64' : 'w-24'"
    >
        <!-- Logo Section -->
        <div class="flex items-center justify-center h-20 px- border-b border-gray-200 dark:border-gray-800">
            <Link :href="route('dashboard')">
                <ApplicationMark class="block h-9 w-auto" />
            </Link>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 py-4 space-y-1">
            <template v-for="item in navigation" :key="item.name">
                <div v-if="item.show" class="px-4">
                    <!-- Grouped Navigation Item -->
                    <div v-if="item.children" class="relative">
                        <button @click="isExpanded ? toggleGroup(item.name) : toggleCollapsedGroup(item.name)" class="flex items-center justify-between w-full p-2 rounded-lg transition-colors duration-200"
                            :class="{ 'bg-blue-500/10 border-blue-400 text-blue-600 dark:text-white shadow-lg': isGroupActive(item), 'hover:bg-gray-100 dark:hover:bg-gray-800': !isGroupActive(item) }">
                            <div class="flex items-center" :class="{'mx-auto': !isExpanded }">
                                <div v-html="item.icon" class="size-6" :class="{'mx-auto': !isExpanded }"></div>
                                <span v-if="isExpanded" class="ml-4 font-medium">{{ item.name }}</span>
                            </div>
                            <svg v-if="isExpanded" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-transform duration-200" :class="{'rotate-90': openGroups[item.name]}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                        </button>

                         <!-- Expanded Dropdown with Transition -->
                        <transition name="slide-down">
                            <div v-show="openGroups[item.name] && isExpanded" class="overflow-hidden pl-6 mt-1 space-y-1 border-l-2 border-gray-200 dark:border-gray-700 ml-3">
                                <Link v-for="child in item.children" :key="child.name" :href="child.route ? route(child.route) : '#'" class="flex items-center p-2 rounded-lg transition-colors duration-200" :class="{'text-blue-600 dark:text-blue-400': child.active, 'hover:bg-gray-100 dark:hover:bg-gray-800': !child.active }">
                                    <span class="font-medium text-sm">{{ child.name }}</span>
                                </Link>
                            </div>
                        </transition>

                        <!-- Collapsed Pop-out Menu with Transition -->
                        <transition name="fade">
                            <div v-if="!isExpanded && openCollapsedGroup === item.name" class="absolute left-full top-0 ml-3 w-48 bg-white dark:bg-gray-900 rounded-lg shadow-lg p-2 z-50 border dark:border-gray-700">
                                <h4 class="font-semibold text-sm px-2 py-1 text-gray-500 dark:text-gray-400 border-b dark:border-gray-700 mb-1">{{ item.name }}</h4>
                                <Link v-for="child in item.children" :key="child.name" :href="child.route ? route(child.route) : '#'" class="flex items-center p-2 rounded-lg transition-colors duration-200 text-sm" :class="{'text-blue-600 dark:text-blue-400': child.active, 'hover:bg-gray-100 dark:hover:bg-gray-800': !child.active }">
                                    <span>{{ child.name }}</span>
                                </Link>
                            </div>
                        </transition>
                    </div>

                    <!-- Single Navigation Item -->
                    <Link v-else :href="item.route ? route(item.route) : '#'" class="flex items-center p-2 rounded-lg transition-colors duration-200" :class="{ 'bg-blue-500/10 border-blue-400 text-blue-600 dark:text-white shadow-lg': item.active, 'hover:bg-gray-100 dark:hover:bg-gray-800': !item.active }">
                        <div v-html="item.icon" class="size-6" :class="{'mx-auto': !isExpanded }"></div>
                        <span v-if="isExpanded" class="ml-4 font-medium">{{ item.name }}</span>
                    </Link>
                </div>
            </template>
        </nav>

        <!-- Footer Actions -->
        <div class="flex items-center p-4 border-t border-gray-200 dark:border-gray-800" :class="isExpanded ? 'justify-between' : 'flex-col space-y-4'">
             <div class="flex items-center" :class="isExpanded ? 'gap-x-2' : 'flex-col space-y-4'">
                <button @click="toggleSettingsCard" class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                </button>
                <button @click="toggleProfileCard" class="rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-900">
                    <img class="size-10 rounded-full object-cover" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
                </button>
            </div>
            <button @click="toggleExpand" class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none">
                 <svg v-if="isExpanded" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" /></svg>
                 <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7" /></svg>
            </button>
        </div>
    </aside>

    <!-- Profile Card -->
    <div v-if="isProfileCardVisible" class="fixed bottom-4 border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-white rounded-2xl w-80 p-6 shadow-2xl transition-all duration-300 ease-in-out" :class="isExpanded ? 'left-[18rem]' : 'left-[6.5rem]'">
        <div class="flex justify-between items-center mb-4">
            <button @click="$inertia.visit(route('profile.show'))" class="text-gray-400 hover:text-white"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" /></svg></button>
            <button @click="toggleProfileCard" class="text-gray-400 hover:text-white"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
        </div>

        <div class="flex flex-col items-center">
            <img class="size-24 rounded-full object-cover border-4 border-gray-700" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
            <h3 class="mt-4 text-xl font-semibold">{{ $page.props.auth.user.name }}</h3>
            <p class="text-gray-400">Puesto no asignado</p>
            <p class="text-gray-400 text-sm">Rol: Super Administrador</p>
            <div class="w-full flex justify-center items-center mt-4 text-sm text-gray-400"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg><span>{{ $page.props.auth.user.email }}</span></div>
            <form @submit.prevent="logout" class="w-full mt-6"><button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg">Cerrar sesión</button></form>
        </div>
    </div>
    
    <!-- Settings Card -->
    <div v-if="isSettingsCardVisible" class="fixed bottom-4 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 dark:text-white rounded-2xl w-64 p-4 shadow-2xl transition-all duration-300 ease-in-out" :class="isExpanded ? 'left-[18rem]' : 'left-[6.5rem]'">
        <div class="flex justify-between items-center mb-2">
            <h4 class="font-semibold">Ajustes de Interfaz</h4>
            <button @click="toggleSettingsCard" class="text-gray-400 hover:text-white"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
        </div>
        
        <!-- Primary Color Section -->
        <div class="mt-4">
            <h5 class="font-semibold text-sm">Color Primario</h5>
            <p class="text-xs text-gray-400 mb-3">Cambia el color de acento de la aplicación.</p>
            <div class="flex space-x-2">
                <button @click="setPrimaryColor('#3b82f6')" class="w-8 h-8 rounded-full bg-blue-500 border-2" :class="primaryColor === '#3b82f6' ? 'border-white' : 'border-transparent'"></button>
                <button @click="setPrimaryColor('#ef4444')" class="w-8 h-8 rounded-full bg-red-500 border-2" :class="primaryColor === '#ef4444' ? 'border-white' : 'border-transparent'"></button>
                <button @click="setPrimaryColor('#10b981')" class="w-8 h-8 rounded-full bg-emerald-500 border-2" :class="primaryColor === '#10b981' ? 'border-white' : 'border-transparent'"></button>
                <button @click="openPrimaryColorPicker" class="w-8 h-8 rounded-full bg-gradient-to-r from-pink-500 via-red-500 to-yellow-500 border-2 border-transparent flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" /></svg>
                </button>
                <input type="color" ref="primaryColorPickerInput" @input="setPrimaryColor($event.target.value)" class="absolute invisible" :value="primaryColor">
            </div>
        </div>

        <!-- Shadow Section -->
        <div class="mt-4 pt-4 border-t border-gray-700">
            <h5 class="font-semibold text-sm">Sombra de la Barra</h5>
            <p class="text-xs text-gray-400 mb-3">Personaliza el resplandor exterior.</p>
            <div class="flex space-x-2">
                <button @click="updateShadowBaseColor('0, 0, 0')" class="w-8 h-8 rounded-full bg-gray-500/50 border-2" :class="shadowBaseColor === '0, 0, 0' ? 'border-blue-400' : 'border-transparent'"></button>
                <button @click="updateShadowBaseColor('59, 130, 246')" class="w-8 h-8 rounded-full bg-blue-500/50 border-2" :class="shadowBaseColor === '59, 130, 246' ? 'border-blue-400' : 'border-transparent'"></button>
                <button @click="updateShadowBaseColor('239, 68, 68')" class="w-8 h-8 rounded-full bg-red-500/50 border-2" :class="shadowBaseColor === '239, 68, 68' ? 'border-red-400' : 'border-transparent'"></button>
                <button @click="openColorPicker" class="w-8 h-8 rounded-full bg-gradient-to-r from-pink-500 via-red-500 to-yellow-500 border-2 border-transparent flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" /></svg>
                </button>
                <input type="color" ref="colorPickerInput" @input="updateShadowBaseColor($event.target.value)" class="absolute invisible">
            </div>

            <!-- Sliders for shadow -->
            <div class="mt-4 space-y-3">
                <div>
                    <label for="shadow-intensity" class="flex justify-between items-center text-xs text-gray-400 mb-1">
                        <span>Intensidad</span>
                        <span>{{ shadowIntensity }}%</span>
                    </label>
                    <input id="shadow-intensity" type="range" min="0" max="100" v-model.number="shadowIntensity" class="w-full h-1.5 bg-gray-600 rounded-lg appearance-none cursor-pointer range-thumb">
                </div>
                <div>
                    <label for="shadow-blur" class="flex justify-between items-center text-xs text-gray-400 mb-1">
                        <span>Tamaño</span>
                        <span>{{ shadowBlur }}px</span>
                    </label>
                    <input id="shadow-blur" type="range" min="10" max="60" v-model.number="shadowBlur" class="w-full h-1.5 bg-gray-600 rounded-lg appearance-none cursor-pointer range-thumb">
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease-in-out;
  max-height: 200px;
}

.slide-down-enter-from,
.slide-down-leave-to {
  max-height: 0;
  opacity: 0;
  transform: translateY(-10px);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Custom styling for range input thumb */
.range-thumb::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 14px;
    height: 14px;
    background: var(--p-primary-color, #3b82f6); /* Use theme color */
    cursor: pointer;
    border-radius: 9999px;
    transition: background-color 0.2s;
}

.range-thumb:hover::-webkit-slider-thumb {
    opacity: 0.8;
}

.range-thumb::-moz-range-thumb {
    width: 14px;
    height: 14px;
    background: var(--p-primary-color, #3b82f6); /* Use theme color */
    cursor: pointer;
    border-radius: 9999px;
    border: none;
    transition: background-color 0.2s;
}

.range-thumb:hover::-moz-range-thumb {
    opacity: 0.8;
}

</style>
