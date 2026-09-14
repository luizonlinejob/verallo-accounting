<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[9999] overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-4">
        <div @click="$emit('close')" class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm"></div>

        <div class="relative bg-white rounded-2xl shadow-2xl sm:max-w-md sm:w-full overflow-hidden">
          <div class="px-6 py-4 bg-rose-50 border-b flex justify-between items-center">
            <h3 class="text-lg font-bold text-rose-800">🚫 Reject Payment</h3>
            <button @click="$emit('close')" class="text-gray-400 font-bold">✕</button>
          </div>

          <form @submit.prevent="submit">
            <div class="p-6 space-y-4">
              <p class="text-sm text-gray-600">
                Reject payment of <strong>₱{{ Number(student?.pending_amount || 0).toFixed(2) }}</strong>
                for <strong>{{ student?.full_name }}</strong>?
              </p>
              <div>
                <label class="block text-xs font-bold uppercase mb-1">Reason <span class="text-rose-500">*</span></label>
                <textarea v-model="reason" required rows="3" class="w-full px-3.5 py-2.5 border rounded-xl text-sm focus:ring-2 focus:ring-rose-500 resize-none" placeholder="e.g., Invalid reference..."></textarea>
              </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t flex justify-end gap-3">
              <button type="button" @click="$emit('close')" class="px-4 py-2 bg-white border rounded-xl text-sm font-semibold">Cancel</button>
              <button type="submit" :disabled="loading || !reason.trim()" class="px-5 py-2 bg-rose-600 text-white rounded-xl text-sm disabled:opacity-60">
                {{ loading ? 'Rejecting...' : 'Confirm Reject' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
  student: { type: Object, default: null },
  loading: { type: Boolean, default: false }
});
const emit = defineEmits(['close', 'confirm']);

const reason = ref('');
const submit = () => {
  if (reason.value.trim()) emit('confirm', reason.value);
};
</script>