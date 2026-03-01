<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import { usePomodoro } from '@/Composables/usePomodoro';

defineProps({
    navigation: Array,
});

const { toggleModal } = usePomodoro();

// --- STATE MANAGEMENT ---
const isExpanded = ref(JSON.parse(localStorage.getItem('sidebarExpanded')) ?? true);
const isProfileCardVisible = ref(false);
const isSettingsCardVisible = ref(false);

const shadowBaseColor = ref(localStorage.getItem('sidebarShadowBaseColor') || '0, 0, 0');
const shadowIntensity = ref(parseInt(localStorage.getItem('sidebarShadowIntensity') || 15, 10));
const shadowBlur = ref(parseInt(localStorage.getItem('sidebarShadowBlur') || 33, 10));

// Colores de la app
const primaryColor = ref(localStorage.getItem('appPrimaryColor') || '#3b82f6');
const hoverColor = ref(localStorage.getItem('appHoverColor') || '#2563eb');
const textColor = ref(localStorage.getItem('appTextColor') || '#ffffff');

const openGroups = ref({});
const openCollapsedGroup = ref(null);
const dropdownPosition = ref({ top: 0, left: 0 }); 

// Referencias a inputs de color
const colorPickerInput = ref(null);
const primaryColorPickerInput = ref(null);
const hoverColorPickerInput = ref(null);
const textColorPickerInput = ref(null);

// --- WATCHERS ---
watch(shadowBaseColor, (newValue) => { localStorage.setItem('sidebarShadowBaseColor', newValue); });
watch(shadowIntensity, (newValue) => { localStorage.setItem('sidebarShadowIntensity', newValue.toString()); });
watch(shadowBlur, (newValue) => { localStorage.setItem('sidebarShadowBlur', newValue.toString()); });

watch(primaryColor, (newValue) => {
    localStorage.setItem('appPrimaryColor', newValue);
    document.documentElement.style.setProperty('--p-primary-color', newValue);
    document.documentElement.style.setProperty('--primary-color', newValue);
});

watch(hoverColor, (newValue) => {
    localStorage.setItem('appHoverColor', newValue);
    document.documentElement.style.setProperty('--p-primary-hover-color', newValue);
    document.documentElement.style.setProperty('--primary-hover-color', newValue);
});

watch(textColor, (newValue) => {
    localStorage.setItem('appTextColor', newValue);
    document.documentElement.style.setProperty('--p-primary-color-text', newValue);
    document.documentElement.style.setProperty('--primary-text-color', newValue);
});

// --- COMPUTED PROPERTIES ---
const fullShadowColor = computed(() => `rgba(${shadowBaseColor.value}, ${shadowIntensity.value / 100})`);
const sidebarStyle = computed(() => ({
  '--shadow-color': fullShadowColor.value,
  '--shadow-blur': `${shadowBlur.value}px`,
}));

// --- METHODS ---
const logout = () => { router.post(route('logout')); };

const toggleExpand = () => {
    isExpanded.value = !isExpanded.value;
    localStorage.setItem('sidebarExpanded', JSON.stringify(isExpanded.value));
    openCollapsedGroup.value = null;
};

const toggleProfileCard = () => {
    isProfileCardVisible.value = !isProfileCardVisible.value;
    if (isProfileCardVisible.value) isSettingsCardVisible.value = false;
};

const toggleSettingsCard = () => {
    isSettingsCardVisible.value = !isSettingsCardVisible.value;
    if (isSettingsCardVisible.value) isProfileCardVisible.value = false;
};

const hexToRgb = (hex) => {
    const sanitizedHex = hex.replace('#', '');
    const r = parseInt(sanitizedHex.substring(0, 2), 16);
    const g = parseInt(sanitizedHex.substring(2, 4), 16);
    const b = parseInt(sanitizedHex.substring(4, 6), 16);
    return `${r}, ${g}, ${b}`;
};

const updateShadowBaseColor = (color) => {
    const newRgb = color.startsWith('#') ? hexToRgb(color) : color;
    shadowBaseColor.value = newRgb;
};

const openColorPicker = () => { colorPickerInput.value.click(); };

// Función para oscurecer el color primario y crear el hover
const getDarkerColor = (hex, percent = 15) => {
    let cleanHex = hex.replace('#', '');
    if (cleanHex.length === 3) cleanHex = cleanHex.split('').map(c => c + c).join('');
    
    let r = parseInt(cleanHex.substring(0, 2), 16);
    let g = parseInt(cleanHex.substring(2, 4), 16);
    let b = parseInt(cleanHex.substring(4, 6), 16);
    
    r = Math.floor(r * (100 - percent) / 100);
    g = Math.floor(g * (100 - percent) / 100);
    b = Math.floor(b * (100 - percent) / 100);
    
    r = r < 0 ? 0 : r;
    g = g < 0 ? 0 : g;
    b = b < 0 ? 0 : b;
    
    return `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${b.toString(16).padStart(2, '0')}`;
};

const openPrimaryColorPicker = () => { primaryColorPickerInput.value.click(); };

const setPrimaryColor = (color) => { 
    primaryColor.value = color; 

    // Auto-calcular color de texto (blanco o negro según contraste)
    let cleanHex = color.replace('#', '');
    if (cleanHex.length === 3) cleanHex = cleanHex.split('').map(c => c + c).join('');
    const r = parseInt(cleanHex.substring(0, 2), 16);
    const g = parseInt(cleanHex.substring(2, 4), 16);
    const b = parseInt(cleanHex.substring(4, 6), 16);
    const brightness = ((r * 299) + (g * 587) + (b * 114)) / 1000;
    textColor.value = brightness > 125 ? '#000000' : '#ffffff';

    // Auto-calcular color hover (15% más oscuro)
    hoverColor.value = getDarkerColor(color, 15);
};

const openHoverColorPicker = () => { hoverColorPickerInput.value.click(); };
const setHoverColor = (color) => { hoverColor.value = color; };

const openTextColorPicker = () => { textColorPickerInput.value.click(); };
const setTextColor = (color) => { textColor.value = color; };

const toggleGroup = (groupName) => { openGroups.value[groupName] = !openGroups.value[groupName]; };

const toggleCollapsedGroup = (groupName, event) => { 
    if (openCollapsedGroup.value === groupName) {
        openCollapsedGroup.value = null;
    } else {
        openCollapsedGroup.value = groupName;
        if (event && event.currentTarget) {
            const rect = event.currentTarget.getBoundingClientRect();
            dropdownPosition.value = {
                top: rect.top, 
                left: rect.right + 12 
            };
        }
    }
};

const isGroupActive = (group) => { return group.children && group.children.some(child => child.active); };

const handleNavScroll = () => {
    if (openCollapsedGroup.value) {
        openCollapsedGroup.value = null;
    }
};

onMounted(() => { 
    // Inicializar colores al cargar
    document.documentElement.style.setProperty('--p-primary-color', primaryColor.value);
    document.documentElement.style.setProperty('--primary-color', primaryColor.value);
    document.documentElement.style.setProperty('--p-primary-hover-color', hoverColor.value);
    document.documentElement.style.setProperty('--primary-hover-color', hoverColor.value);
    document.documentElement.style.setProperty('--p-primary-color-text', textColor.value);
    document.documentElement.style.setProperty('--primary-text-color', textColor.value);
});
</script>

<template>
    <!-- Sidebar con paleta Zinc -->
    <aside
        :style="sidebarStyle"
        class="hidden lg:flex flex-col h-[calc(100vh-2rem)] m-4 bg-white dark:bg-zinc-900 text-gray-700 dark:text-zinc-300 transition-all duration-300 ease-in-out rounded-2xl shadow-[0_0_var(--shadow-blur)_-3px_var(--shadow-color)] z-50 overflow-x-hidden"
        :class="isExpanded ? 'w-64' : 'w-24'"
    >
        <!-- Logo Section -->
        <div class="flex items-center justify-center h-20 border-b border-gray-200 dark:border-zinc-800 shrink-0 transition-all duration-300"
             :class="isExpanded ? 'px-4' : 'px-0'">
            <Link :href="route('dashboard')" class="shrink-0 flex items-center justify-center w-full h-full">
                <ApplicationMark class="block h-9 w-auto transition-all duration-300" />
            </Link>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 py-4 space-y-1 overflow-y-auto overflow-x-hidden custom-scrollbar" @scroll="handleNavScroll">
            <template v-for="item in navigation" :key="item.name">
                <div v-if="item.show" class="px-4">
                    <!-- Grouped Navigation Item -->
                    <div v-if="item.children" class="relative">
                        <button @click="isExpanded ? toggleGroup(item.name) : toggleCollapsedGroup(item.name, $event)" class="flex items-center justify-between w-full p-2 rounded-lg transition-colors duration-200"
                            :class="{ 
                                'bg-purple-50 text-purple-700 shadow-sm dark:bg-white dark:text-gray-600 dark:border dark:border-gray-200': isGroupActive(item), 
                                'hover:bg-gray-100 dark:hover:bg-zinc-800 text-gray-600 dark:text-zinc-400': !isGroupActive(item) 
                            }">
                            <div class="flex items-center" :class="{'mx-auto': !isExpanded }">
                                <div v-html="item.icon" class="size-6" 
                                     :class="{
                                        'mx-auto': !isExpanded,
                                        'text-purple-700 dark:text-gray-600': isGroupActive(item),
                                        'text-gray-500 dark:text-zinc-400': !isGroupActive(item)
                                     }">
                                </div>
                                <span v-if="isExpanded" class="ml-4 font-medium">{{ item.name }}</span>
                            </div>
                            <svg v-if="isExpanded" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-transform duration-200" :class="{'rotate-90': openGroups[item.name], 'text-purple-400 dark:text-gray-400': isGroupActive(item), 'text-gray-400': !isGroupActive(item)}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                        </button>

                         <!-- Expanded Dropdown -->
                        <transition name="slide-down">
                            <div v-show="openGroups[item.name] && isExpanded" class="overflow-hidden pl-6 mt-1 space-y-1 border-l-2 border-gray-200 dark:border-zinc-700 ml-3">
                                <Link v-for="child in item.children" :key="child.name" :href="child.route ? route(child.route) : '#'" class="flex items-center p-2 rounded-lg transition-colors duration-200" 
                                    :class="{
                                        'bg-purple-50 text-purple-700 font-medium dark:bg-white dark:text-gray-600 dark:border dark:border-gray-200': child.active, 
                                        'text-gray-600 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-800': !child.active 
                                    }">
                                    <span class="font-medium text-sm">{{ child.name }}</span>
                                </Link>
                            </div>
                        </transition>

                        <!-- Collapsed Pop-out Menu (Floating Fixed) -->
                        <transition name="fade">
                            <div v-if="!isExpanded && openCollapsedGroup === item.name" 
                                 class="fixed w-52 bg-white dark:bg-zinc-900 rounded-lg shadow-xl p-2 z-[9999] border border-gray-100 dark:border-zinc-700"
                                 :style="{ top: `${dropdownPosition.top}px`, left: `${dropdownPosition.left}px` }">
                                <h4 class="font-semibold text-sm px-2 py-2 text-gray-500 dark:text-zinc-400 border-b border-gray-100 dark:border-zinc-700 mb-1 flex items-center">
                                    {{ item.name }}
                                </h4>
                                <Link v-for="child in item.children" :key="child.name" :href="child.route ? route(child.route) : '#'" class="flex items-center p-2 rounded-lg transition-colors duration-200 text-sm" 
                                    :class="{
                                        'bg-purple-50 text-purple-700 font-medium dark:bg-white dark:text-gray-600 dark:border dark:border-gray-200': child.active, 
                                        'text-gray-600 dark:text-zinc-400 hover:bg-gray-50 dark:hover:bg-zinc-800': !child.active 
                                    }">
                                    <span>{{ child.name }}</span>
                                </Link>
                            </div>
                        </transition>
                    </div>

                    <!-- Single Navigation Item -->
                    <Link v-else :href="item.route ? route(item.route) : '#'" class="flex items-center p-2 rounded-lg transition-colors duration-200" 
                        :class="{ 
                            'bg-purple-50 text-purple-700 shadow-sm dark:bg-white dark:text-gray-600 dark:border dark:border-gray-200': item.active, 
                            'hover:bg-gray-100 dark:hover:bg-zinc-800 text-gray-600 dark:text-zinc-400': !item.active 
                        }">
                        <div class="flex items-center" :class="{'mx-auto': !isExpanded }">
                            <div v-html="item.icon" class="size-6" 
                                 :class="{
                                    'mx-auto': !isExpanded,
                                    'text-purple-700 dark:text-gray-600': item.active,
                                    'text-gray-500 dark:text-zinc-400': !item.active
                                 }">
                            </div>
                            <span v-if="isExpanded" class="ml-4 font-medium">{{ item.name }}</span>
                        </div>
                    </Link>
                </div>
            </template>
            
             <div class="px-4 mt-4">
                <button @click="toggleModal(true)" class="flex items-center p-2 rounded-lg transition-colors duration-200 w-full hover:bg-gray-100 dark:hover:bg-zinc-800 text-gray-600 dark:text-zinc-400">
                    <div class="size-6 text-gray-500 dark:text-zinc-400" :class="{'mx-auto': !isExpanded }">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <span v-if="isExpanded" class="ml-4 font-medium">Pomodoro</span>
                </button>
            </div>
        </nav>

        <!-- Footer Actions -->
        <div class="flex items-center p-4 border-t border-gray-200 dark:border-zinc-800" :class="isExpanded ? 'justify-between' : 'flex-col space-y-4'">
             <div class="flex items-center" :class="isExpanded ? 'gap-x-2' : 'flex-col space-y-4'">
                <button @click="toggleSettingsCard" class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-zinc-800 focus:outline-none transition-colors text-gray-500 dark:text-zinc-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                </button>
                <button @click="toggleProfileCard" class="rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-zinc-900 transition-all hover:opacity-80">
                    <img class="size-10 rounded-full object-cover shadow-sm" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
                </button>
            </div>
            <button @click="toggleExpand" class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-zinc-800 focus:outline-none transition-colors text-gray-500 dark:text-zinc-400">
                 <svg v-if="isExpanded" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" /></svg>
                 <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7" /></svg>
            </button>
        </div>
    </aside>

    <!-- MODERN PROFILE CARD -->
    <transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-2"
    >
        <div v-if="isProfileCardVisible" 
             class="fixed bottom-4 z-[9999] border border-gray-200 dark:border-zinc-700 bg-white/95 dark:bg-zinc-900/95 backdrop-blur-xl text-gray-800 dark:text-zinc-100 rounded-3xl w-80 p-0 shadow-2xl overflow-hidden ring-1 ring-black/5 dark:ring-white/5"
             :class="isExpanded ? 'left-[18rem]' : 'left-[6.5rem]'">
            
            <div class="relative h-24 bg-gradient-to-r from-blue-500 to-indigo-600 dark:from-zinc-800 dark:to-zinc-900">
                <div class="absolute top-2 right-2">
                    <button @click="toggleProfileCard" class="p-1 rounded-full bg-black/20 hover:bg-black/40 text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>

            <div class="px-6 pb-6 -mt-10 relative">
                <div class="flex justify-center mb-3">
                    <div class="p-1 bg-white dark:bg-zinc-900 rounded-full">
                        <img class="size-20 rounded-full object-cover shadow-lg border-2 border-white dark:border-zinc-800" 
                             :src="$page.props.auth.user.profile_photo_url" 
                             :alt="$page.props.auth.user.name">
                    </div>
                </div>

                <div class="text-center mb-5">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-zinc-100">{{ $page.props.auth.user.name }}</h3>
                    <div class="flex items-center justify-center gap-1 text-sm text-gray-500 dark:text-zinc-400 mt-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                        <span>{{ $page.props.auth.user.email }}</span>
                    </div>
                    <span class="inline-block mt-2 px-3 py-1 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300 text-xs font-semibold rounded-full">
                        Super Administrador
                    </span>
                </div>

                <div class="space-y-3">
                    <button @click="$inertia.visit(route('profile.show'))" class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-zinc-800/50 hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-xl text-sm font-medium transition-colors group">
                        <span class="flex items-center gap-3">
                            <span class="p-2 bg-white dark:bg-zinc-700 rounded-lg text-gray-600 dark:text-zinc-300 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            </span>
                            Mi Perfil
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </button>

                    <form @submit.prevent="logout">
                        <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-3 border border-red-200 dark:border-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl text-sm font-semibold transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                            Cerrar Sesión
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </transition>
    
    <!-- Settings Card -->
    <transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-2"
    >
        <div v-if="isSettingsCardVisible" 
             class="fixed bottom-4 z-[9999] bg-white/95 dark:bg-zinc-900/95 backdrop-blur-xl border border-gray-200 dark:border-zinc-700 dark:text-zinc-100 rounded-3xl w-72 p-5 shadow-2xl ring-1 ring-black/5 dark:ring-white/5" 
             :class="isExpanded ? 'left-[18rem]' : 'left-[6.5rem]'">
            
            <div class="flex justify-between items-center mb-4">
                <h4 class="font-bold text-gray-800 dark:text-zinc-100 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" /></svg>
                    Ajustes de Interfaz
                </h4>
                <button @click="toggleSettingsCard" class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
            </div>
            
            <div class="space-y-4">
                <!-- Color Primario -->
                <div>
                    <h5 class="font-semibold text-xs text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-2">Color Primario</h5>
                    <div class="flex gap-3">
                        <button @click="setPrimaryColor('#3b82f6')" class="w-8 h-8 rounded-full bg-blue-500 ring-2 ring-offset-2 dark:ring-offset-zinc-800 transition-all hover:scale-110" :class="primaryColor === '#3b82f6' ? 'ring-blue-500' : 'ring-transparent'"></button>
                        <button @click="setPrimaryColor('#ef4444')" class="w-8 h-8 rounded-full bg-red-500 ring-2 ring-offset-2 dark:ring-offset-zinc-800 transition-all hover:scale-110" :class="primaryColor === '#ef4444' ? 'ring-red-500' : 'ring-transparent'"></button>
                        <button @click="setPrimaryColor('#10b981')" class="w-8 h-8 rounded-full bg-emerald-500 ring-2 ring-offset-2 dark:ring-offset-zinc-800 transition-all hover:scale-110" :class="primaryColor === '#10b981' ? 'ring-emerald-500' : 'ring-transparent'"></button>
                        <button @click="openPrimaryColorPicker" class="w-8 h-8 rounded-full bg-gradient-to-tr from-pink-500 via-purple-500 to-indigo-500 ring-2 ring-offset-2 dark:ring-offset-zinc-800 flex items-center justify-center transition-all hover:scale-110 ring-transparent">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                        </button>
                        <input type="color" ref="primaryColorPickerInput" @input="setPrimaryColor($event.target.value)" class="absolute invisible" :value="primaryColor">
                    </div>
                </div>

                <!-- Personalización de Texto y Hover (Opcionales / Manuales) -->
                <div class="pt-3 border-t border-gray-100 dark:border-zinc-800 flex justify-between gap-4">
                    <div class="flex-1">
                        <h5 class="font-semibold text-[10px] text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-2" title="Se calcula auto 15% más oscuro que el primario, pero puedes cambiarlo">Color Hover</h5>
                        <button @click="openHoverColorPicker" class="w-8 h-8 rounded-full border border-gray-200 dark:border-zinc-700 flex items-center justify-center transition-all hover:scale-110 shadow-sm" :style="{ backgroundColor: hoverColor }">
                            <!-- Icono sutil de edición -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white drop-shadow-md mix-blend-difference" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                        </button>
                        <input type="color" ref="hoverColorPickerInput" @input="setHoverColor($event.target.value)" class="absolute invisible" :value="hoverColor">
                    </div>
                    
                    <div class="flex-1">
                        <h5 class="font-semibold text-[10px] text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-2" title="Se auto-ajusta para ser legible, pero puedes cambiarlo">Color Texto</h5>
                        <button @click="openTextColorPicker" class="w-8 h-8 rounded-full border border-gray-200 dark:border-zinc-700 flex items-center justify-center transition-all hover:scale-110 shadow-sm" :style="{ backgroundColor: textColor }">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white drop-shadow-md mix-blend-difference" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                        </button>
                        <input type="color" ref="textColorPickerInput" @input="setTextColor($event.target.value)" class="absolute invisible" :value="textColor">
                    </div>
                </div>

                <!-- Sombra -->
                <div class="pt-4 border-t border-gray-100 dark:border-zinc-800">
                    <h5 class="font-semibold text-xs text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-3">Resplandor (Sombra)</h5>
                    
                    <div class="flex gap-3 mb-4">
                        <button @click="updateShadowBaseColor('0, 0, 0')" class="w-6 h-6 rounded-full bg-zinc-800 ring-1 ring-gray-300 dark:ring-zinc-600 transition-all hover:scale-110" :class="shadowBaseColor === '0, 0, 0' ? 'ring-2 ring-offset-1 ring-blue-500' : ''"></button>
                        <button @click="updateShadowBaseColor('59, 130, 246')" class="w-6 h-6 rounded-full bg-blue-500 transition-all hover:scale-110" :class="shadowBaseColor === '59, 130, 246' ? 'ring-2 ring-offset-1 ring-blue-500' : ''"></button>
                        <button @click="updateShadowBaseColor('239, 68, 68')" class="w-6 h-6 rounded-full bg-red-500 transition-all hover:scale-110" :class="shadowBaseColor === '239, 68, 68' ? 'ring-2 ring-offset-1 ring-red-500' : ''"></button>
                        <button @click="openColorPicker" class="w-6 h-6 rounded-full bg-gradient-to-r from-yellow-400 to-orange-500 flex items-center justify-center transition-all hover:scale-110">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                        </button>
                        <input type="color" ref="colorPickerInput" @input="updateShadowBaseColor($event.target.value)" class="absolute invisible">
                    </div>

                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between text-xs mb-1 text-gray-600 dark:text-zinc-400">
                                <span>Intensidad</span>
                                <span class="font-mono">{{ shadowIntensity }}%</span>
                            </div>
                            <input type="range" min="0" max="100" v-model.number="shadowIntensity" class="w-full h-1.5 bg-gray-200 dark:bg-zinc-700 rounded-full appearance-none cursor-pointer accent-blue-500">
                        </div>
                        <div>
                            <div class="flex justify-between text-xs mb-1 text-gray-600 dark:text-zinc-400">
                                <span>Difuminado</span>
                                <span class="font-mono">{{ shadowBlur }}px</span>
                            </div>
                            <input type="range" min="10" max="60" v-model.number="shadowBlur" class="w-full h-1.5 bg-gray-200 dark:bg-zinc-700 rounded-full appearance-none cursor-pointer accent-blue-500">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<style scoped>
.slide-down-enter-active, .slide-down-leave-active { transition: all 0.3s ease-in-out; max-height: 200px; }
.slide-down-enter-from, .slide-down-leave-to { max-height: 0; opacity: 0; transform: translateY(-10px); }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

/* Custom scrollbar para la navegación */
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: rgba(156, 163, 175, 0.3); border-radius: 20px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background-color: rgba(161, 161, 170, 0.2); }

/* Estilizar inputs de rango webkit */
input[type=range]::-webkit-slider-thumb {
    -webkit-appearance: none;
    height: 14px;
    width: 14px;
    border-radius: 50%;
    background: var(--p-primary-color, #3b82f6);
    cursor: pointer;
    margin-top: 0px; 
    box-shadow: 0 0 2px rgba(0,0,0,0.2);
}
</style>