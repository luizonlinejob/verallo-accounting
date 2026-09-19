<template>
  <div class="flex items-center justify-center gap-1.5">

    <!-- ACTIVE MODE -->
    <template v-if="mode === 'active'">
      <template v-if="isAuthorized && student.has_pending_payment">
        <button @click="$emit('approve')" :disabled="loading" class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm flex items-center gap-1 disabled:opacity-50">
          <span v-if="loading" class="inline-block animate-spin rounded-full h-3 w-3 border-2 border-white border-t-transparent"></span>
          <span v-else>✓</span>
          <span>Approve</span>
        </button>
        <button @click="$emit('reject')" :disabled="loading" class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-sm disabled:opacity-50">
          ✕ Reject
        </button>
      </template>

      <template v-else>
        <button v-if="isSuperAdmin" @click="$emit('edit')" title="Edit" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200">✏️</button>

        <button @click="$emit('pay')" :disabled="student.has_pending_payment" :class="payClass">
          <span>💳</span>
          <span>{{ payText }}</span>
        </button>
      </template>

      <button @click="$emit('print-soa')" class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-gradient-to-r from-slate-700 to-slate-900 text-white shadow-md">
        🖨️ SOA
      </button>

      <button v-if="isSuperAdmin" @click="$emit('archive')" title="Archive" class="p-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200">📦</button>
    </template>

    <!-- ARCHIVED MODE -->
    <template v-else>
      <button v-if="isSuperAdmin" @click="$emit('restore')" :disabled="loading" class="px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-300 disabled:opacity-50">
        <span v-if="loading" class="inline-block animate-spin rounded-full h-3 w-3 border-2 border-emerald-500 border-t-transparent"></span>
        <span v-else>♻️</span>
        <span>Restore</span>
      </button>

      <button v-if="isSuperAdmin" @click="$emit('force-delete')" :disabled="loading" class="px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-300 disabled:opacity-50">
        <span v-if="loading" class="inline-block animate-spin rounded-full h-3 w-3 border-2 border-rose-500 border-t-transparent"></span>
        <span v-else>🗑️</span>
        <span>Delete</span>
      </button>
    </template>

  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  student: { type: Object, required: true },
  mode: { type: String, default: 'active' },
  isSuperAdmin: { type: Boolean, default: false },
  isAuthorized: { type: Boolean, default: false },
  loading: { type: Boolean, default: false }
});

defineEmits(['approve', 'reject', 'edit', 'pay', 'archive', 'restore', 'force-delete', 'print-soa']);

const payText = computed(() => {
  if (props.student.has_pending_payment) return 'Pending';
  if (props.student.rejection_reason || props.student.is_rejected) return 'Re-encode';
  return 'Pay';
});

const payClass = computed(() => {
  const base = 'px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1 shadow-sm';
  if (props.student.has_pending_payment) return base + ' bg-amber-100 text-amber-700 border border-amber-300 cursor-not-allowed';
  if (props.student.rejection_reason || props.student.is_rejected) return base + ' bg-gradient-to-r from-rose-600 to-rose-700 text-white shadow-rose-500/20';
  return base + ' bg-gradient-to-r from-emerald-600 to-emerald-700 text-white shadow-emerald-500/20';
});
</script>