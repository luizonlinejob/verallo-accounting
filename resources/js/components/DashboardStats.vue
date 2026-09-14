<template>
  <div class="mb-6">

    <!-- ============================================================
         ENCODER DASHBOARD — Simple view, walay financial totals
         ============================================================ -->
    <template v-if="isEncoder">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">

        <!-- My Encoded Payments (Today) -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-700 rounded-2xl p-5 text-white shadow-lg shadow-blue-500/20 relative overflow-hidden">
          <div class="absolute -top-8 -right-8 w-24 h-24 bg-white/10 rounded-full"></div>
          <div class="relative">
            <div class="flex items-center justify-between mb-3">
              <span class="text-2xl">💳</span>
              <span class="text-[10px] font-bold bg-white/20 px-2 py-0.5 rounded-full uppercase tracking-wider">My Encodes</span>
            </div>
            <p class="text-3xl font-bold font-mono">{{ stats.my_encoded_today || 0 }}</p>
            <p class="text-xs text-blue-100 mt-1">Encoded today</p>
          </div>
        </div>

        <!-- Pending -->
        <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-5 text-white shadow-lg shadow-amber-500/20 relative overflow-hidden">
          <div class="absolute -top-8 -right-8 w-24 h-24 bg-white/10 rounded-full"></div>
          <div class="relative">
            <div class="flex items-center justify-between mb-3">
              <span class="text-2xl">⏳</span>
              <span class="text-[10px] font-bold bg-white/20 px-2 py-0.5 rounded-full uppercase tracking-wider">Pending</span>
            </div>
            <p class="text-3xl font-bold font-mono">{{ stats.my_pending || 0 }}</p>
            <p class="text-xs text-amber-100 mt-1">Waiting for approval</p>
          </div>
        </div>

        <!-- Rejected -->
        <div class="bg-gradient-to-br from-rose-500 to-rose-700 rounded-2xl p-5 text-white shadow-lg shadow-rose-500/20 relative overflow-hidden">
          <div class="absolute -top-8 -right-8 w-24 h-24 bg-white/10 rounded-full"></div>
          <div class="relative">
            <div class="flex items-center justify-between mb-3">
              <span class="text-2xl">⚠️</span>
              <span class="text-[10px] font-bold bg-white/20 px-2 py-0.5 rounded-full uppercase tracking-wider">Rejected</span>
            </div>
            <p class="text-3xl font-bold font-mono">{{ stats.my_rejected || 0 }}</p>
            <p class="text-xs text-rose-100 mt-1">Need re-encoding</p>
          </div>
        </div>
      </div>

      <!-- EXTRA INFO BAR FOR ENCODER -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white rounded-xl p-3 border border-slate-200 flex items-center gap-3">
          <span class="text-xl">🎓</span>
          <div>
            <p class="text-[10px] font-bold text-slate-500 uppercase">Total Students</p>
            <p class="text-lg font-bold text-blue-700 font-mono">{{ stats.total_students }}</p>
          </div>
        </div>
        <div class="bg-white rounded-xl p-3 border border-slate-200 flex items-center gap-3">
          <span class="text-xl">📅</span>
          <div>
            <p class="text-[10px] font-bold text-slate-500 uppercase">Today</p>
            <p class="text-xs font-bold text-slate-700 mt-0.5">{{ todayDate }}</p>
          </div>
        </div>
        <div class="bg-white rounded-xl p-3 border border-slate-200 flex items-center gap-3">
          <span class="text-xl">👤</span>
          <div>
            <p class="text-[10px] font-bold text-slate-500 uppercase">Role</p>
            <p class="text-xs font-bold text-slate-700 mt-0.5 uppercase">{{ userRole }}</p>
          </div>
        </div>
        <div class="bg-white rounded-xl p-3 border border-slate-200 flex items-center gap-3">
          <span class="text-xl">🟢</span>
          <div>
            <p class="text-[10px] font-bold text-slate-500 uppercase">System</p>
            <p class="text-xs font-bold text-emerald-700 mt-0.5">Active</p>
          </div>
        </div>
      </div>

      <!-- QUICK ACTIONS FOR ENCODER -->
      <div class="mt-6 bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
        <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
          <span>⚡</span> Quick Actions
        </h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
          <button
            @click="$emit('navigate', 'students')"
            class="p-4 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-800 font-semibold text-sm transition text-left"
          >
            <div class="text-2xl mb-1">🎓</div>
            <div>View Students</div>
            <div class="text-[10px] text-blue-600 font-normal mt-0.5">Encode new payments</div>
          </button>
          <button
            @click="$emit('navigate', 'enroll')"
            class="p-4 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-semibold text-sm transition text-left"
          >
            <div class="text-2xl mb-1">📝</div>
            <div>Enroll Student</div>
            <div class="text-[10px] text-emerald-600 font-normal mt-0.5">Add new student</div>
          </button>
        </div>
      </div>
    </template>

    <!-- ============================================================
         SUPERADMIN / ADMIN / ACCOUNTING DASHBOARD — Full stats
         ============================================================ -->
    <template v-else>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        <!-- Total Students -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-700 rounded-2xl p-5 text-white shadow-lg shadow-blue-500/20 relative overflow-hidden">
          <div class="absolute -top-8 -right-8 w-24 h-24 bg-white/10 rounded-full"></div>
          <div class="relative">
            <div class="flex items-center justify-between mb-3">
              <span class="text-2xl">🎓</span>
              <span class="text-[10px] font-bold bg-white/20 px-2 py-0.5 rounded-full uppercase tracking-wider">Students</span>
            </div>
            <p class="text-3xl font-bold font-mono">{{ stats.total_students }}</p>
            <p class="text-xs text-blue-100 mt-1">Total Enrolled</p>
          </div>
        </div>

        <!-- 🆕 Pending (CLICKABLE + PULSE NOTIFICATION) -->
        <button
          @click="handlePendingClick"
          :disabled="stats.pending_payments === 0"
          :class="[
            'bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-5 text-white shadow-lg shadow-amber-500/20 relative overflow-hidden text-left transition-all duration-200',
            stats.pending_payments > 0 && canApprove
              ? 'cursor-pointer hover:shadow-2xl hover:scale-[1.02] ring-2 ring-amber-300/60 animate-pulse-slow'
              : 'cursor-default'
          ]"
        >
          <div class="absolute -top-8 -right-8 w-24 h-24 bg-white/10 rounded-full"></div>
          <div class="relative">
            <div class="flex items-center justify-between mb-3">
              <span class="text-2xl">⏳</span>
              <span class="text-[10px] font-bold bg-white/20 px-2 py-0.5 rounded-full uppercase tracking-wider">Pending</span>
            </div>
            <p class="text-3xl font-bold font-mono">{{ stats.pending_payments }}</p>
            <p class="text-xs text-amber-100 mt-1">
              <span v-if="canSeeFinancial">₱{{ formatMoney(stats.pending_amount) }} pending</span>
              <span v-else>Awaiting approval</span>
            </p>

            <!-- ✅ CLICK HINT (para maklaro nga clickable) -->
            <div
              v-if="stats.pending_payments > 0 && canApprove"
              class="mt-2 inline-flex items-center gap-1 text-[10px] font-bold bg-white/30 px-2 py-0.5 rounded-full backdrop-blur-sm"
            >
              <span>👆</span>
              <span>Click to review</span>
            </div>
          </div>
        </button>

        <!-- Approved -->
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-700 rounded-2xl p-5 text-white shadow-lg shadow-emerald-500/20 relative overflow-hidden">
          <div class="absolute -top-8 -right-8 w-24 h-24 bg-white/10 rounded-full"></div>
          <div class="relative">
            <div class="flex items-center justify-between mb-3">
              <span class="text-2xl">✅</span>
              <span class="text-[10px] font-bold bg-white/20 px-2 py-0.5 rounded-full uppercase tracking-wider">Approved</span>
            </div>
            <p class="text-3xl font-bold font-mono">{{ stats.approved_payments }}</p>
            <p class="text-xs text-emerald-100 mt-1">Verified payments</p>
          </div>
        </div>

        <!-- Archived -->
        <div class="bg-gradient-to-br from-slate-500 to-slate-700 rounded-2xl p-5 text-white shadow-lg shadow-slate-500/20 relative overflow-hidden">
          <div class="absolute -top-8 -right-8 w-24 h-24 bg-white/10 rounded-full"></div>
          <div class="relative">
            <div class="flex items-center justify-between mb-3">
              <span class="text-2xl">📦</span>
              <span class="text-[10px] font-bold bg-white/20 px-2 py-0.5 rounded-full uppercase tracking-wider">Archived</span>
            </div>
            <p class="text-3xl font-bold font-mono">{{ stats.archived_students }}</p>
            <p class="text-xs text-slate-200 mt-1">Inactive students</p>
          </div>
        </div>
      </div>

      <!-- EXTRA INFO BAR -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <!-- Rejected -->
        <div class="bg-white rounded-xl p-3 border border-slate-200 flex items-center gap-3">
          <span class="text-xl">❌</span>
          <div>
            <p class="text-[10px] font-bold text-slate-500 uppercase">Rejected</p>
            <p class="text-lg font-bold text-rose-700 font-mono">{{ stats.rejected_payments }}</p>
          </div>
        </div>

        <!-- Users (Superadmin only) -->
        <div v-if="isSuperAdmin" class="bg-white rounded-xl p-3 border border-slate-200 flex items-center gap-3">
          <span class="text-xl">👥</span>
          <div>
            <p class="text-[10px] font-bold text-slate-500 uppercase">Users</p>
            <p class="text-lg font-bold text-blue-700 font-mono">{{ stats.total_users }}</p>
          </div>
        </div>

        <!-- Financial access indicator (Admin/Accounting) -->
        <div v-if="!isSuperAdmin" class="bg-emerald-50 rounded-xl p-3 border border-emerald-200 flex items-center gap-3">
          <span class="text-xl">💰</span>
          <div>
            <p class="text-[10px] font-bold text-emerald-700 uppercase">Collection</p>
            <p class="text-xs font-bold text-emerald-800 font-mono mt-0.5">₱{{ formatMoney(stats.total_collection) }}</p>
          </div>
        </div>

        <!-- Today -->
        <div class="bg-white rounded-xl p-3 border border-slate-200 flex items-center gap-3">
          <span class="text-xl">📅</span>
          <div>
            <p class="text-[10px] font-bold text-slate-500 uppercase">Today</p>
            <p class="text-xs font-bold text-slate-700 mt-0.5">{{ todayDate }}</p>
          </div>
        </div>

        <!-- System -->
        <div class="bg-white rounded-xl p-3 border border-slate-200 flex items-center gap-3">
          <span class="text-xl">🟢</span>
          <div>
            <p class="text-[10px] font-bold text-slate-500 uppercase">System</p>
            <p class="text-xs font-bold text-emerald-700 mt-0.5">Active</p>
          </div>
        </div>
      </div>

      <!-- FINANCIAL SUMMARY (Admin + Accounting + Superadmin lang) -->
      <div v-if="canSeeFinancial" class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6">
        <div class="bg-gradient-to-br from-emerald-600 to-emerald-800 rounded-2xl p-5 text-white shadow-lg">
          <p class="text-[10px] font-bold uppercase tracking-widest text-emerald-200">Total Collection</p>
          <p class="text-2xl font-bold font-mono mt-2">₱{{ formatMoney(stats.total_collection) }}</p>
          <p class="text-xs text-emerald-200 mt-1">Approved payments</p>
        </div>

        <div class="bg-gradient-to-br from-amber-600 to-amber-800 rounded-2xl p-5 text-white shadow-lg">
          <p class="text-[10px] font-bold uppercase tracking-widest text-amber-200">Pending Amount</p>
          <p class="text-2xl font-bold font-mono mt-2">₱{{ formatMoney(stats.pending_amount) }}</p>
          <p class="text-xs text-amber-200 mt-1">Awaiting approval</p>
        </div>

        <div class="bg-gradient-to-br from-rose-600 to-rose-800 rounded-2xl p-5 text-white shadow-lg">
          <p class="text-[10px] font-bold uppercase tracking-widest text-rose-200">Outstanding Balance</p>
          <p class="text-2xl font-bold font-mono mt-2">₱{{ formatMoney(stats.total_outstanding) }}</p>
          <p class="text-xs text-rose-200 mt-1">Total receivable</p>
        </div>
      </div>
    </template>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  userRole: { type: String, default: '' },
});

const emit = defineEmits(['navigate']);

const stats = ref({
  total_students: 0,
  archived_students: 0,
  pending_payments: 0,
  approved_payments: 0,
  rejected_payments: 0,
  total_collection: 0,
  pending_amount: 0,
  total_outstanding: 0,
  total_users: 0,
  my_encoded_today: 0,
  my_pending: 0,
  my_rejected: 0,
});

const role = computed(() => (props.userRole || '').toLowerCase().trim());
const isEncoder = computed(() => role.value === 'encoder');
const isSuperAdmin = computed(() => role.value === 'superadmin');

// ✅ ADMIN + ACCOUNTING + SUPERADMIN maka-kita sa financial data
const canSeeFinancial = computed(() => ['superadmin', 'admin', 'accounting'].includes(role.value));

// ✅ Check kung maka-approve ba ang user (Admin + Superadmin lang)
const canApprove = computed(() => ['superadmin', 'admin'].includes(role.value));

const todayDate = new Date().toLocaleDateString('en-PH', {
  month: 'short',
  day: 'numeric',
  year: 'numeric',
});

const formatMoney = (val) => {
  return Number(val || 0).toLocaleString('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

const fetchStats = async () => {
  try {
    const res = await axios.get('/dashboard/stats');
    if (res.data.success) {
      stats.value = res.data.stats;
    }
  } catch (err) {
    console.error('Error fetching stats:', err);
  }
};

// ✅ Handle click sa Pending card
const handlePendingClick = () => {
  if (stats.value.pending_payments > 0 && canApprove.value) {
    emit('navigate', 'students-pending');
  }
};

onMounted(fetchStats);

defineExpose({ fetchStats });
</script>

<style scoped>
@keyframes pulse-slow {
  0%, 100% {
    box-shadow: 0 10px 15px -3px rgba(245, 158, 11, 0.2), 0 0 0 0 rgba(245, 158, 11, 0.4);
  }
  50% {
    box-shadow: 0 10px 15px -3px rgba(245, 158, 11, 0.4), 0 0 0 10px rgba(245, 158, 11, 0);
  }
}

.animate-pulse-slow {
  animation: pulse-slow 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>