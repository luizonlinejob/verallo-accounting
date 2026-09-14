<template>
  <div class="relative">
    <button
      @click="toggle"
      class="relative p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition border border-slate-200 active:scale-95"
      title="View Rejection Notifications"
    >
      <span class="text-lg">🔔</span>
      <span
        v-if="logs.length > 0"
        class="absolute -top-1 -right-1 bg-rose-600 text-white text-[10px] font-bold h-5 w-5 rounded-full flex items-center justify-center border-2 border-white animate-bounce"
      >
        {{ logs.length }}
      </span>
    </button>

    <div
      v-if="isOpen"
      class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden"
    >
      <div class="p-3.5 bg-rose-50 border-b border-rose-100 flex justify-between items-center">
        <div class="flex items-center gap-2">
          <span class="text-rose-600 font-bold text-sm">⚠️ Rejected Payment Alerts</span>
          <span class="bg-rose-200 text-rose-800 text-[10px] font-extrabold px-2 py-0.5 rounded-full">
            {{ logs.length }}
          </span>
        </div>
        <button @click="isOpen = false" class="text-slate-400 hover:text-slate-600 text-xs font-bold">✕</button>
      </div>

      <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">
        <div
          v-for="log in logs"
          :key="log.id"
          @click="$emit('select', log); isOpen = false"
          class="p-3.5 hover:bg-rose-50/50 cursor-pointer transition text-left group"
        >
          <div class="flex justify-between items-start">
            <p class="text-xs font-bold text-slate-800 group-hover:text-rose-700">
              {{ log.student_name }} ({{ log.student_id }})
            </p>
            <span class="text-[10px] text-slate-400">{{ formatDateTime(log.rejected_at) }}</span>
          </div>
          <p class="text-xs text-rose-800 mt-1 line-clamp-2 bg-rose-50 p-2 rounded-lg border border-rose-100 font-medium">
            💬 <span class="font-semibold">Admin Note:</span> {{ log.reason }}
          </p>
        </div>

        <div v-if="logs.length === 0" class="p-6 text-center text-slate-400 text-xs italic">
          🎉 No rejected payments recorded.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

defineProps({ logs: { type: Array, default: () => [] } });
const emit = defineEmits(['select', 'refresh-request']);

const isOpen = ref(false);

const toggle = () => {
  isOpen.value = !isOpen.value;
  if (isOpen.value) emit('refresh-request');
};

const formatDateTime = (dateStr) => {
  if (!dateStr) return 'N/A';
  const date = new Date(dateStr);
  if (isNaN(date.getTime())) return dateStr;
  return new Intl.DateTimeFormat('en-PH', {
    month: 'short', day: 'numeric', year: 'numeric',
    hour: 'numeric', minute: '2-digit', hour12: true
  }).format(date);
};
</script>