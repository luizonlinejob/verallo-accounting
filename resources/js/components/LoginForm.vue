<template>
  <form @submit.prevent="submit" class="space-y-5">

    <!-- ERROR MESSAGE -->
    <div v-if="errorMessage" class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-sm text-rose-800 flex items-start gap-2">
      <span class="text-lg">⚠️</span>
      <span>{{ errorMessage }}</span>
    </div>

    <!-- SUCCESS MESSAGE -->
    <div v-if="successMessage" class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-sm text-emerald-800 flex items-start gap-2">
      <span class="text-lg">✅</span>
      <span>{{ successMessage }}</span>
    </div>

    <!-- EMAIL -->
    <div>
      <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5 tracking-wider">Email Address</label>
      <input
        v-model="form.email"
        type="email"
        required
        autocomplete="email"
        placeholder="you@system.com"
        class="w-full px-4 py-3 border border-slate-300 rounded-xl text-sm bg-white shadow-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition"
      />
    </div>

    <!-- PASSWORD -->
    <div>
      <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5 tracking-wider">Password</label>
      <div class="relative">
        <input
          v-model="form.password"
          :type="showPassword ? 'text' : 'password'"
          required
          autocomplete="current-password"
          placeholder="••••••••"
          class="w-full px-4 py-3 pr-11 border border-slate-300 rounded-xl text-sm bg-white shadow-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition"
        />
        <button
          type="button"
          @click="showPassword = !showPassword"
          class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-sm"
        >
          {{ showPassword ? '🙈' : '👁️' }}
        </button>
      </div>
    </div>

    <!-- REMEMBER ME -->
    <div class="flex items-center justify-between">
      <label class="inline-flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
        <input type="checkbox" v-model="form.remember" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
        <span>Remember me</span>
      </label>
    </div>

    <!-- SUBMIT -->
    <button
      type="submit"
      :disabled="loading"
      class="w-full py-3.5 rounded-xl bg-gradient-to-r from-blue-600 to-blue-800 hover:from-blue-700 hover:to-blue-900 text-white font-bold text-sm shadow-lg shadow-blue-500/30 active:scale-[0.98] transition disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2"
    >
      <span v-if="loading" class="inline-block animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
      <span>{{ loading ? 'Signing in...' : '🔐 Sign In' }}</span>
    </button>

    <!-- DEMO CREDENTIALS -->
    <div class="mt-4 p-3 rounded-xl bg-blue-50 border border-blue-200 text-xs text-blue-800">
      <!-- <p class="font-bold mb-1">🔑 Demo Credentials:</p>
      <p>Superadmin: <code class="font-mono">superadmin@system.com</code> / <code class="font-mono">password123</code></p>
      <p>Accounting: <code class="font-mono">accounting@system.com</code> / <code class="font-mono">password123</code></p>
      <p>Encoder: <code class="font-mono">encoder@system.com</code> / <code class="font-mono">password123</code></p> -->
    </div>
  </form>
</template>

<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';

const form = reactive({
  email: '',
  password: '',
  remember: false
});

const loading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const showPassword = ref(false);

const submit = async () => {
  loading.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    const response = await axios.post('/login', {
      email: form.email,
      password: form.password,
      remember: form.remember
    });

    if (response.data.success) {
      successMessage.value = `Welcome, ${response.data.user?.name || 'User'}! Redirecting...`;
      setTimeout(() => {
        window.location.href = '/dashboard';
      }, 800);
    } else {
      errorMessage.value = response.data.message || 'Login failed.';
    }
  } catch (error) {
    if (error.response?.status === 422) {
      const errors = error.response.data.errors || {};
      errorMessage.value = Object.values(errors).flat().join(' ') || 'Invalid credentials.';
    } else {
      errorMessage.value = error.response?.data?.message || 'Invalid email or password.';
    }
  } finally {
    loading.value = false;
  }
};
</script>