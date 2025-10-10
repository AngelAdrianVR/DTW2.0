<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.transform(data => ({
        ...data,
        remember: form.remember ? 'on' : '',
    })).post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in" />

    <AuthenticationCard>
        <template #logo>
            <ApplicationLogo height="20" />
        </template>

        <div v-if="status" class="mb-4 font-medium text-sm text-green-400 text-center">
            {{ status }}
        </div>

        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-white tracking-widest">Bienvenido</h1>
            <p class="text-sm text-gray-500 mt-1 uppercase">DTW</p>
        </div>


        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="block w-full px-4 py-3 bg-gray-900/70 border border-gray-700 text-white placeholder-gray-500 rounded-lg focus:outline-none focus:ring-1 focus:ring-fuchsia-500 focus:border-fuchsia-500"
                    placeholder="email"
                    required
                    autofocus
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="block w-full px-4 py-3 bg-gray-900/70 border border-gray-700 text-white placeholder-gray-500 rounded-lg focus:outline-none focus:ring-1 focus:ring-fuchsia-500 focus:border-fuchsia-500"
                    placeholder="password"
                    required
                    autocomplete="current-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <!-- Checkbox para "Mantener sesión abierta" -->
            <div class="block">
                <label class="flex items-center">
                    <Checkbox v-model:checked="form.remember" name="remember" />
                    <span class="ms-2 text-sm text-gray-400">Mantener sesión abierta</span>
                </label>
            </div>

            <div class="pt-4">
                 <PrimaryButton class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold tracking-wider text-white uppercase bg-gradient-to-r from-fuchsia-600 to-cyan-500 hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-fuchsia-500 focus:ring-offset-gray-900" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Iniciar sesión
                </PrimaryButton>
            </div>

            <div class="text-center mt-6">
                 <Link v-if="canResetPassword" :href="route('password.request')" class="text-xs font-medium text-gray-500 hover:text-fuchsia-400 uppercase">
                    Olvidé mi contraseña
                </Link>
            </div>
        </form>
    </AuthenticationCard>
</template>
