<template>
  <div class="overflow-x-auto">
    <div class="p-4 bg-rose-50/50 border-b border-rose-100 text-xs text-rose-800 font-semibold">
      📜 Showing <strong>REJECTED PAYMENTS ONLY</strong>
    </div>

    <div v-if="loading" class="p-12 text-center text-slate-400">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-rose-500 border-t-transparent mb-3"></div>
      <p class="text-sm">Loading...</p>
    </div>

    <table v-else class="min-w-full divide-y divide-gray-200">
      <thead class="bg-slate-50">
        <tr>
          <th class="px-4 py-3.5 text-left text-[11px] font-bold text-gray-500 uppercase">Student Info</th>
          <th class="px-4 py-3.5 text-left text-[11px] font-bold text-gray-500 uppercase">Amount</th>
          <th class="px-4 py-3.5 text-left text-[11px] font-bold text-gray-500 uppercase">Admin Notes</th>
          <th class="px-4 py-3.5 text-left text-[11px] font-bold text-gray-500 uppercase">Date</th>
          <th class="px-4 py-3.5 text-center text-[11px] font-bold text-gray-500 uppercase">Action</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 text-sm bg-white">
        <tr v-for="log in logs" :key="log.id" class="hover:bg-rose-50/30 transition">
          <td class="px-4 py-3.5">
            <div class="font-bold text-slate-800">{{ log.student_name }}</div>
            <div class="text-xs font-mono text-slate-500">{{ log.student_id }}</div>
          </td>
          <td class="px-4 py-3.5 font-mono font-bold text-rose-700">₱{{ Number(log.amount || 0).toFixed(2) }}</td>
          <td class="px-4 py-3.5 max-w-sm">
            <div class="bg-rose-50 border border-rose-200 p-2.5 rounded-xl text-xs text-rose-950 font-medium">
              💬 {{ log.reason }}
            </div>
          </td>
          <td class="px-4 py-3.5 text-xs text-slate-500">{{ formatDateTime(log.rejected_at) }}</td>
          <td class="px-4 py-3.5 text-center">
            <button @click="$emit('select', log)" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg text-xs shadow">
              🔍 View & Re-encode
            </button>
          </td>
        </tr>
        <tr v-if="logs.length === 0">
          <td colspan="5" class="p-8 text-center text-slate-400 italic">No rejected payment logs found.</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
defineProps({
  logs: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false }
});
defineEmits(['select']);

const formatDateTime = (d) => {
  if (!d) return 'N/A';
  const date = new Date(d);
  if (isNaN(date.getTime())) return d;
  return new Intl.DateTimeFormat('en-PH', {
    month: 'short', day: 'numeric', year: 'numeric',
    hour: 'numeric', minute: '2-digit', hour12: true
  }).format(date);
};
</script>