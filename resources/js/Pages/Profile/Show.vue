<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DeleteUserForm from '@/Pages/Profile/Partials/DeleteUserForm.vue';
import LogoutOtherBrowserSessionsForm from '@/Pages/Profile/Partials/LogoutOtherBrowserSessionsForm.vue';
import TwoFactorAuthenticationForm from '@/Pages/Profile/Partials/TwoFactorAuthenticationForm.vue';
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from '@/Pages/Profile/Partials/UpdateProfileInformationForm.vue';

defineProps({
    confirmsTwoFactorAuthentication: Boolean,
    sessions: Array,
});
</script>

<template>
    <AppLayout title="Perfil de Usuario">
        <div class="p-4 sm:p-6 lg:p-8 min-h-screen">
            <div class="max-w-7xl mx-auto">
                
                <!-- Encabezado -->
                <header class="mb-10">
                    <h1 class="text-3xl font-bold text-gray-800 dark:text-zinc-100">Configuración de Cuenta</h1>
                    <p class="text-gray-500 dark:text-zinc-400 mt-2 text-lg">Administra tu información personal y la seguridad de tu cuenta.</p>
                </header>

                <div class="grid grid-cols-1 gap-10">
                    
                    <!-- Sección 1: Información Principal -->
                    <div v-if="$page.props.jetstream.canUpdateProfileInformation" class="bg-white dark:bg-zinc-900 rounded-[2rem] shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">
                        <div class="p-8">
                            <UpdateProfileInformationForm :user="$page.props.auth.user" class="form-section-zinc" />
                        </div>
                    </div>

                    <!-- Sección 2: Seguridad (Grid de 2 columnas) -->
                    <div v-if="$page.props.jetstream.canUpdatePassword || $page.props.jetstream.canManageTwoFactorAuthentication" class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        
                        <!-- Contraseña -->
                        <div v-if="$page.props.jetstream.canUpdatePassword" class="bg-white dark:bg-zinc-900 rounded-[2rem] shadow-sm border border-gray-100 dark:border-zinc-800 p-8 h-full">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-zinc-100 mb-6 flex items-center gap-3">
                                <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg"><i class="pi pi-key text-blue-500"></i></div>
                                Seguridad
                            </h3>
                            <UpdatePasswordForm class="form-section-zinc" />
                        </div>

                        <!-- 2FA -->
                        <div v-if="$page.props.jetstream.canManageTwoFactorAuthentication" class="bg-white dark:bg-zinc-900 rounded-[2rem] shadow-sm border border-gray-100 dark:border-zinc-800 p-8 h-full">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-zinc-100 mb-6 flex items-center gap-3">
                                <div class="p-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg"><i class="pi pi-shield text-emerald-500"></i></div>
                                Autenticación de Dos Pasos
                            </h3>
                            <TwoFactorAuthenticationForm
                                :requires-confirmation="confirmsTwoFactorAuthentication"
                                class="form-section-zinc"
                            />
                        </div>
                    </div>

                    <!-- Sección 3: Sesiones -->
                    <div class="bg-white dark:bg-zinc-900 rounded-[2rem] shadow-sm border border-gray-100 dark:border-zinc-800 p-8">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-zinc-100 mb-6 flex items-center gap-3">
                            <div class="p-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg"><i class="pi pi-desktop text-purple-500"></i></div>
                            Sesiones Activas
                        </h3>
                        <LogoutOtherBrowserSessionsForm :sessions="sessions" class="form-section-zinc" />
                    </div>

                    <!-- Sección 4: Zona de Peligro -->
                    <template v-if="$page.props.jetstream.hasAccountDeletionFeatures">
                        <div class="bg-red-50 dark:bg-red-900/10 rounded-[2rem] p-8 border border-red-100 dark:border-red-900/30">
                            <h3 class="text-xl font-bold text-red-600 dark:text-red-400 mb-6 flex items-center gap-3">
                                <i class="pi pi-exclamation-triangle"></i> Zona de Peligro
                            </h3>
                            <DeleteUserForm class="form-section-zinc" />
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Neutralizamos los estilos por defecto de Jetstream para usar nuestro contenedor Zinc */
:deep(.form-section-zinc .shadow-md) {
    box-shadow: none !important;
    background: transparent !important;
    padding: 0 !important;
    border-radius: 0 !important;
}

:deep(.form-section-zinc .grid) {
    gap: 1.5rem;
}

/* Forzamos estilos oscuros en inputs y labels dentro de los componentes hijos */
:deep(.dark .form-section-zinc label) {
    color: #d4d4d8; /* zinc-300 */
}

:deep(.dark .form-section-zinc input[type="text"]),
:deep(.dark .form-section-zinc input[type="email"]),
:deep(.dark .form-section-zinc input[type="password"]) {
    background-color: #09090b !important; /* zinc-950: más oscuro que el fondo de la tarjeta */
    border-color: #3f3f46 !important; /* zinc-700 */
    color: #f4f4f5 !important; /* zinc-100 */
}

:deep(.dark .form-section-zinc input:focus) {
    border-color: #a1a1aa !important; /* zinc-400 */
    box-shadow: 0 0 0 1px #a1a1aa !important;
}

:deep(.dark .form-section-zinc .text-gray-600) {
    color: #a1a1aa !important; /* zinc-400 */
}
</style>