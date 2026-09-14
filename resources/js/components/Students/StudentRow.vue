<template>
  <tr :class="[
    'transition-colors duration-150',
    student.has_pending_payment ? 'bg-amber-50/40 hover:bg-amber-50/70' : 'hover:bg-blue-50/40'
  ]">
    <!-- STUDENT ID -->
    <td class="px-4 py-3.5 whitespace-nowrap">
      <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 font-mono text-xs font-bold border border-slate-200">
        {{ student.student_id }}
      </span>
    </td>

    <!-- NAME -->
    <td class="px-4 py-3.5 whitespace-nowrap">
      <button @click="$emit('open-breakdown', student)" class="group inline-flex items-center gap-1.5 text-blue-700 hover:text-blue-900 font-semibold text-sm hover:underline">
        <span>{{ student.full_name }}</span>
        <span class="text-xs text-blue-400 group-hover:text-blue-600">📋</span>
      </button>
    </td>

    <!-- COURSE & LEVEL -->
    <td class="px-4 py-3.5 whitespace-nowrap text-gray-700">
      <span class="text-sm">{{ student.course }}</span>
      <span class="ml-1 text-[11px] font-semibold text-blue-700 bg-blue-50 px-1.5 py-0.5 rounded-md border border-blue-100">
        {{ student.year_level }}
      </span>
    </td>

    <!-- TOTAL ASSESSMENT -->
    <td class="px-4 py-3.5 whitespace-nowrap font-mono font-semibold">
      ₱{{ Number(student.total_fees || 0).toFixed(2) }}
    </td>

    <!-- PAID / STATUS -->
    <td class="px-4 py-3.5">
      <span class="font-mono text-emerald-700 font-bold">₱{{ Number(student.total_paid || 0).toFixed(2) }}</span>

      <!-- REJECTED -->
      <div v-if="isRejected" class="mt-1">
        <button
          @click="$emit('open-rejection-details', student)"
          class="text-left p-2 rounded-xl bg-rose-50 border border-rose-200 hover:bg-rose-100 transition w-full max-w-xs block shadow-sm group"
        >
          <div class="flex items-center justify-between">
            <span class="text-[10px] text-rose-800 font-extrabold uppercase">⚠️ Payment Rejected</span>
            <span class="text-[10px] text-rose-600 underline">View Note 🔍</span>
          </div>
          <p class="text-[11px] text-rose-900 font-semibold mt-0.5 truncate">
            <span class="font-bold">Reason:</span> {{ student.rejection_reason || 'See logs' }}
          </p>

          <!-- 🆕 REJECTED TIMESTAMP BADGE -->
          <span
            v-if="student.rejected_at"
            class="mt-1.5 inline-flex items-center gap-1 text-[10px] text-rose-700 font-medium bg-rose-100/70 px-2 py-0.5 rounded-full border border-rose-200/70"
          >
            🕒 {{ formatDateTime(student.rejected_at) }}
          </span>
        </button>
      </div>

      <!-- PENDING -->
      <div v-else-if="student.has_pending_payment" class="mt-1">
        <div class="flex flex-col items-start gap-1">
          <span class="inline-flex items-center gap-1 text-[10px] text-amber-800 font-bold bg-amber-50 px-2 py-0.5 rounded-full border border-amber-300">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
            Pending (₱{{ Number(student.pending_amount || 0).toFixed(2) }})
          </span>

          <!-- 🆕 PENDING TIMESTAMP BADGE -->
          <span
            v-if="student.pending_date"
            class="inline-flex items-center gap-1 text-[10px] text-amber-700 font-medium bg-amber-50/70 px-2 py-0.5 rounded-full border border-amber-200/70"
          >
            🕒 {{ formatDateTime(student.pending_date) }}
          </span>
        </div>
      </div>

      <!-- APPROVED -->
      <div v-else-if="student.approved_at || student.last_paid_date" class="mt-1">
        <div class="flex flex-col items-start gap-1">
          <span class="inline-flex items-center gap-1 text-[10px] text-emerald-800 font-semibold bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
            ✅ Approved
          </span>

          <!-- 🆕 APPROVED TIMESTAMP BADGE -->
          <span
            v-if="student.approved_at || student.last_paid_date"
            class="inline-flex items-center gap-1 text-[10px] text-emerald-700 font-medium bg-emerald-50/70 px-2 py-0.5 rounded-full border border-emerald-200/70"
          >
            🕒 {{ formatDateTime(student.approved_at || student.last_paid_date) }}
          </span>
        </div>
      </div>
    </td>

    <!-- REMAINING BALANCE -->
    <td class="px-4 py-3.5 whitespace-nowrap font-mono font-bold text-rose-600">
      ₱{{ remainingBalance.toFixed(2) }}
    </td>

    <!-- ACTION -->
    <td class="px-4 py-3.5 text-center whitespace-nowrap">
      <StudentActionButtons
        :student="student"
        :mode="mode"
        :is-super-admin="isSuperAdmin"
        :is-authorized="isAuthorized"
        :loading="actionLoading[student.id]"
        @approve="$emit('approve', student)"
        @reject="$emit('reject', student)"
        @edit="$emit('edit', student)"
        @pay="$emit('open-payment', student)"
        @archive="$emit('archive', student)"
        @restore="$emit('restore', student)"
        @force-delete="$emit('force-delete', student)"
        @print-soa="$emit('print-soa', student)"
      />
    </td>
  </tr>
</template>

<script setup>
import { computed } from 'vue';
import StudentActionButtons from './StudentActionButtons.vue';

const props = defineProps({
  student: { type: Object, required: true },
  mode: { type: String, default: 'active' },
  isSuperAdmin: { type: Boolean, default: false },
  isAuthorized: { type: Boolean, default: false },
  actionLoading: { type: Object, default: () => ({}) }
});

defineEmits(['open-breakdown', 'open-payment', 'open-rejection-details', 'approve', 'reject', 'edit', 'archive', 'restore', 'force-delete', 'print-soa']);

const isRejected = computed(() =>
  props.student.is_rejected || props.student.payment_status === 'rejected' || !!props.student.rejection_reason
);

const remainingBalance = computed(() => {
  const t = Number(props.student.total_fees || 0);
  const p = Number(props.student.total_paid || 0);
  return Math.max(0, t - p);
});

// 🆕 FORMAT DATE TIME
const formatDateTime = (dateStr) => {
  if (!dateStr) return 'N/A';
  const date = new Date(dateStr);
  if (isNaN(date.getTime())) return dateStr;
  return new Intl.DateTimeFormat('en-PH', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    hour12: true
  }).format(date);
};
</script>