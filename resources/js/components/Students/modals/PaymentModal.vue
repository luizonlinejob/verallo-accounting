<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[9999] overflow-y-auto" role="dialog" aria-modal="true">
      <div class="flex items-center justify-center min-h-screen px-4 py-8">
        <div @click="close" class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm"></div>

        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
          <!-- HEADER -->
          <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-emerald-50 to-white flex justify-between items-center">
            <h3 class="text-lg font-bold text-emerald-800 flex items-center gap-2">
              <span>💳</span> Encode Payment
            </h3>
            <button @click="close" class="text-gray-400 hover:text-rose-500 font-bold text-lg">✕</button>
          </div>

          <!-- STUDENT INFO -->
          <div class="px-6 py-4 bg-slate-50 border-b border-slate-100">
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Student</p>
                <p class="font-bold text-slate-800 mt-0.5">{{ student?.full_name }}</p>
              </div>
              <div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Student ID</p>
                <p class="font-mono font-bold text-slate-800 mt-0.5">{{ student?.student_id }}</p>
              </div>
              <div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Remaining Balance</p>
                <p class="font-mono font-bold text-rose-600 mt-0.5">
                  ₱{{ Number(student?.total_balance || 0).toFixed(2) }}
                </p>
              </div>
              <div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Course</p>
                <p class="font-semibold text-slate-700 mt-0.5">{{ student?.course }}</p>
              </div>
            </div>
          </div>

          <!-- FORM -->
          <form @submit.prevent="submit">
            <div class="px-6 py-5 space-y-4">

              <!-- AMOUNT -->
              <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5 tracking-wider">
                  Amount Paid <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                  <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 font-bold">₱</span>
                  <input
                    v-model.number="form.amount_paid"
                    type="number"
                    step="0.01"
                    min="1"
                    required
                    placeholder="0.00"
                    class="w-full pl-9 pr-3.5 py-2.5 border border-slate-300 rounded-xl text-sm font-mono focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:outline-none transition"
                  />
                </div>
                <p class="text-[11px] text-slate-500 mt-1">
                  Maximum: ₱{{ Number(student?.total_balance || 0).toFixed(2) }}
                </p>
              </div>

              <!-- PAYMENT METHOD -->
              <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5 tracking-wider">Payment Method</label>
                <select
                  v-model="form.payment_method"
                  class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:outline-none transition"
                >
                  <option value="cash">💵 Cash</option>
                  <option value="bank_transfer">🏦 Bank Transfer</option>
                  <option value="gcash">📱 GCash</option>
                  <option value="paymaya">📱 PayMaya</option>
                  <option value="check">🧾 Check</option>
                </select>
              </div>

              <!-- OR NUMBER -->
              <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5 tracking-wider">OR Number</label>
                <input
                  v-model="form.or_number"
                  type="text"
                  placeholder="e.g. 1234567"
                  class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:outline-none transition"
                />
              </div>

              <!-- REMARKS -->
              <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5 tracking-wider">Remarks</label>
                <textarea
                  v-model="form.remarks"
                  rows="2"
                  placeholder="Optional notes..."
                  class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 focus:outline-none transition resize-none"
                ></textarea>
              </div>

              <!-- ERROR -->
              <div v-if="errorMessage" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-800 flex items-start gap-2">
                <span>⚠️</span>
                <span>{{ errorMessage }}</span>
              </div>

            </div>

            <!-- ACTIONS -->
            <div class="px-6 py-4 bg-slate-50 border-t flex flex-col sm:flex-row-reverse gap-3">
              <button
                type="submit"
                :disabled="loading"
                class="w-full sm:w-auto bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-md shadow-emerald-500/20 active:scale-95 transition disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              >
                <span v-if="loading" class="inline-block animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
                <span>{{ loading ? 'Encoding...' : '💾 Submit Payment' }}</span>
              </button>
              <button
                type="button"
                @click="close"
                class="w-full sm:w-auto bg-white hover:bg-slate-100 text-slate-700 px-5 py-2.5 rounded-xl text-sm font-semibold border border-slate-300 transition active:scale-95"
              >
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';

const props = defineProps({
  student: { type: Object, required: true }
});

const emit = defineEmits(['close', 'success']);

const form = reactive({
  amount_paid: '',
  payment_method: 'cash',
  or_number: '',
  remarks: ''
});

const loading = ref(false);
const errorMessage = ref('');

const close = () => emit('close');

const submit = async () => {
  loading.value = true;
  errorMessage.value = '';

  // Validation
  const amount = Number(form.amount_paid);
  const balance = Number(props.student.total_balance || 0);

  if (amount <= 0) {
    errorMessage.value = 'Amount must be greater than 0.';
    loading.value = false;
    return;
  }

  if (amount > balance) {
    errorMessage.value = `Amount cannot exceed remaining balance (₱${balance.toFixed(2)}).`;
    loading.value = false;
    return;
  }

  try {
    const response = await axios.post('/payments', {
      student_id: props.student.id,
      amount_paid: amount,
      payment_method: form.payment_method,
      or_number: form.or_number || null,
      remarks: form.remarks || null
    });

    if (response.data.success) {
      alert('✅ Payment encoded! Waiting for Admin approval.');
      emit('success');
      close();
    } else {
      errorMessage.value = response.data.message || 'Failed to submit payment.';
    }
  } catch (error) {
    console.error('Payment error:', error);
    if (error.response?.status === 422) {
      const errors = error.response.data.errors || {};
      errorMessage.value = Object.values(errors).flat().join(' ') || 'Validation error.';
    } else {
      errorMessage.value = error.response?.data?.message || 'An error occurred.';
    }
  } finally {
    loading.value = false;
  }
};
</script>