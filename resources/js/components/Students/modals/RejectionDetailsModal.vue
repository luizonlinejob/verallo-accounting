<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[9999] overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-4">
        <div @click="$emit('close')" class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm"></div>

        <div class="relative bg-white rounded-2xl shadow-2xl sm:max-w-md sm:w-full overflow-hidden">
          <div class="px-6 py-4 bg-rose-600 text-white flex justify-between items-center">
            <h3 class="font-bold flex items-center gap-2">⚠️ Rejection Notes</h3>
            <button @click="$emit('close')" class="text-white font-bold">✕</button>
          </div>

          <div class="p-6 space-y-4">
            <div class="bg-slate-50 p-3.5 rounded-xl border text-xs space-y-1">
              <div><strong>Student:</strong> {{ details.full_name || details.student_name }}</div>
              <div><strong>ID:</strong> {{ details.student_id }}</div>
              <div><strong>Amount:</strong> ₱{{ Number(details.pending_amount || details.amount || 0).toFixed(2) }}</div>
            </div>
            <div class="bg-rose-50 border-2 border-rose-200 p-4 rounded-xl text-sm text-rose-950 font-semibold">
              "{{ details.rejection_reason || details.reason }}"
            </div>
            <div class="text-[11px] text-slate-500 italic bg-amber-50 p-2.5 rounded-lg border border-amber-200">
              💡 Correct the receipt and click Re-encode Pay below.
            </div>
          </div>

          <div class="px-6 py-4 bg-slate-50 border-t flex justify-end gap-2">
            <button @click="$emit('close')" class="px-4 py-2 bg-slate-200 rounded-xl text-xs font-bold">Close</button>
            <button @click="$emit('re-encode', details)" class="px-4 py-2 bg-rose-600 text-white rounded-xl text-xs font-bold">💳 Re-encode Pay</button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
defineProps({ details: { type: Object, required: true } });
defineEmits(['close', 're-encode']);
</script>