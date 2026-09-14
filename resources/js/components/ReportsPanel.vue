<template>
  <div class="space-y-6 font-sans">

    <!-- HEADER -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
      <div class="px-6 py-5 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-gradient-to-br from-indigo-500 to-indigo-700 text-white rounded-xl text-lg shadow-md">📊</div>
          <div>
            <h2 class="text-xl font-bold text-gray-800">Reports Generation</h2>
            <p class="text-xs text-gray-500 mt-0.5">Generate weekly, monthly, or yearly reports</p>
          </div>
        </div>
      </div>

      <!-- FILTERS -->
      <div class="p-6 bg-slate-50/60 border-b border-slate-100">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">

          <!-- PERIOD SELECTOR -->
          <div class="md:col-span-3">
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5 tracking-wider">Report Period</label>
            <select v-model="period" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none">
              <option value="weekly">📅 Weekly</option>
              <option value="monthly">📆 Monthly</option>
              <option value="yearly">🗓️ Yearly</option>
              <option value="custom">⚙️ Custom Range</option>
            </select>
          </div>

          <!-- CUSTOM FROM -->
          <div v-if="period === 'custom'" class="md:col-span-3">
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5 tracking-wider">From Date</label>
            <input v-model="fromDate" type="date" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
          </div>

          <!-- CUSTOM TO -->
          <div v-if="period === 'custom'" class="md:col-span-3">
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5 tracking-wider">To Date</label>
            <input v-model="toDate" type="date" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
          </div>

          <!-- GENERATE BUTTON -->
          <div :class="period === 'custom' ? 'md:col-span-3' : 'md:col-span-9'" class="flex items-end">
            <button
              @click="generateReport"
              :disabled="loading"
              class="w-full px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-800 hover:from-indigo-700 hover:to-indigo-900 text-white font-bold text-sm shadow-md active:scale-95 disabled:opacity-60 flex items-center justify-center gap-2"
            >
              <span v-if="loading" class="inline-block animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
              <span v-else>📊</span>
              <span>{{ loading ? 'Generating...' : 'Generate Report' }}</span>
            </button>
          </div>
        </div>

        <!-- REPORT PERIOD INFO -->
        <div v-if="report" class="mt-4 flex flex-wrap items-center gap-3">
          <span class="px-3 py-1.5 rounded-full bg-indigo-100 text-indigo-800 text-xs font-bold">
            📅 {{ formatDate(report.from) }} → {{ formatDate(report.to) }}
          </span>
          <span class="px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 text-xs font-bold uppercase">
            {{ report.period }} Report
          </span>
          <button
            @click="printReport"
            class="ml-auto px-4 py-1.5 rounded-xl bg-gradient-to-r from-slate-700 to-slate-900 text-white text-xs font-bold shadow-sm active:scale-95 flex items-center gap-1"
          >
            🖨️ Print Report
          </button>
        </div>
      </div>
    </div>

    <!-- REPORT CONTENT -->
    <div v-if="report">

      <!-- SUMMARY CARDS -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-700 rounded-2xl p-5 text-white shadow-lg shadow-emerald-500/20 relative overflow-hidden">
          <div class="absolute -top-8 -right-8 w-24 h-24 bg-white/10 rounded-full"></div>
          <div class="relative">
            <div class="flex items-center justify-between mb-3">
              <span class="text-2xl">💰</span>
              <span class="text-[10px] font-bold bg-white/20 px-2 py-0.5 rounded-full uppercase tracking-wider">Collected</span>
            </div>
            <p class="text-2xl font-bold font-mono">₱{{ formatMoney(report.summary.total_collection) }}</p>
            <p class="text-xs text-emerald-100 mt-1">{{ report.summary.approved_count }} approved payments</p>
          </div>
        </div>

        <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-5 text-white shadow-lg shadow-amber-500/20 relative overflow-hidden">
          <div class="absolute -top-8 -right-8 w-24 h-24 bg-white/10 rounded-full"></div>
          <div class="relative">
            <div class="flex items-center justify-between mb-3">
              <span class="text-2xl">⏳</span>
              <span class="text-[10px] font-bold bg-white/20 px-2 py-0.5 rounded-full uppercase tracking-wider">Pending</span>
            </div>
            <p class="text-2xl font-bold font-mono">₱{{ formatMoney(report.summary.total_pending) }}</p>
            <p class="text-xs text-amber-100 mt-1">{{ report.summary.pending_count }} pending payments</p>
          </div>
        </div>

        <div class="bg-gradient-to-br from-rose-500 to-rose-700 rounded-2xl p-5 text-white shadow-lg shadow-rose-500/20 relative overflow-hidden">
          <div class="absolute -top-8 -right-8 w-24 h-24 bg-white/10 rounded-full"></div>
          <div class="relative">
            <div class="flex items-center justify-between mb-3">
              <span class="text-2xl">❌</span>
              <span class="text-[10px] font-bold bg-white/20 px-2 py-0.5 rounded-full uppercase tracking-wider">Rejected</span>
            </div>
            <p class="text-2xl font-bold font-mono">₱{{ formatMoney(report.summary.total_rejected) }}</p>
            <p class="text-xs text-rose-100 mt-1">{{ report.summary.rejected_count }} rejected payments</p>
          </div>
        </div>
      </div>

      <!-- TOP STUDENTS -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
          <h3 class="font-bold text-slate-800 flex items-center gap-2">
            <span>🏆</span> Top Paying Students
          </h3>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-slate-50/80">
              <tr>
                <th class="px-4 py-3 text-left text-[11px] font-bold text-gray-500 uppercase">#</th>
                <th class="px-4 py-3 text-left text-[11px] font-bold text-gray-500 uppercase">Student</th>
                <th class="px-4 py-3 text-left text-[11px] font-bold text-gray-500 uppercase">Course</th>
                <th class="px-4 py-3 text-right text-[11px] font-bold text-gray-500 uppercase">Total Paid</th>
                <th class="px-4 py-3 text-center text-[11px] font-bold text-gray-500 uppercase">Payments</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm bg-white">
              <tr v-for="(s, i) in report.top_students" :key="i" class="hover:bg-indigo-50/30 transition">
                <td class="px-4 py-3 font-bold text-indigo-700">{{ i + 1 }}</td>
                <td class="px-4 py-3">
                  <div class="font-semibold text-slate-800">{{ s.student_name }}</div>
                  <div class="text-xs text-slate-500 font-mono">{{ s.student_id }}</div>
                </td>
                <td class="px-4 py-3 text-slate-600 text-xs">{{ s.course }}</td>
                <td class="px-4 py-3 text-right font-mono font-bold text-emerald-700">₱{{ formatMoney(s.total_paid) }}</td>
                <td class="px-4 py-3 text-center font-mono font-bold text-slate-700">{{ s.payment_count }}</td>
              </tr>
              <tr v-if="report.top_students.length === 0">
                <td colspan="5" class="p-8 text-center text-slate-400 italic">No payments in this period.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ENCODER PERFORMANCE + METHOD BREAKDOWN -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        <!-- ENCODER PERFORMANCE -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
          <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
              <span>💳</span> Encoder Performance
            </h3>
          </div>
          <div class="p-4 space-y-2">
            <div v-for="(e, i) in report.encoder_performance" :key="i" class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200">
              <div>
                <p class="font-semibold text-slate-800 text-sm">{{ e.encoder_name }}</p>
                <p class="text-xs text-slate-500">{{ e.count }} payments</p>
              </div>
              <span class="font-mono font-bold text-emerald-700">₱{{ formatMoney(e.total_encoded) }}</span>
            </div>
            <div v-if="report.encoder_performance.length === 0" class="p-6 text-center text-slate-400 italic text-sm">
              No encoder activity.
            </div>
          </div>
        </div>

        <!-- PAYMENT METHOD -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
          <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
              <span>💵</span> Payment Method Breakdown
            </h3>
          </div>
          <div class="p-4 space-y-2">
            <div v-for="(m, i) in report.method_breakdown" :key="i" class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200">
              <div class="flex items-center gap-3">
                <span class="text-xl">
                  {{ { cash: '💵', bank_transfer: '🏦', gcash: '📱', paymaya: '📱', check: '🧾' }[m.method] || '💳' }}
                </span>
                <div>
                  <p class="font-semibold text-slate-800 text-sm capitalize">{{ m.method.replace('_', ' ') }}</p>
                  <p class="text-xs text-slate-500">{{ m.count }} transactions</p>
                </div>
              </div>
              <span class="font-mono font-bold text-emerald-700">₱{{ formatMoney(m.total) }}</span>
            </div>
            <div v-if="report.method_breakdown.length === 0" class="p-6 text-center text-slate-400 italic text-sm">
              No payment data.
            </div>
          </div>
        </div>
      </div>

      <!-- DETAILED TRANSACTIONS -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white flex justify-between items-center">
          <h3 class="font-bold text-slate-800 flex items-center gap-2">
            <span>📋</span> Transaction Details
          </h3>
          <span class="text-xs text-slate-500">{{ report.transactions.length }} records</span>
        </div>
        <div class="overflow-x-auto max-h-96 overflow-y-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-slate-50/80 sticky top-0">
              <tr>
                <th class="px-4 py-3 text-left text-[11px] font-bold text-gray-500 uppercase">Date</th>
                <th class="px-4 py-3 text-left text-[11px] font-bold text-gray-500 uppercase">Student</th>
                <th class="px-4 py-3 text-left text-[11px] font-bold text-gray-500 uppercase">OR #</th>
                <th class="px-4 py-3 text-right text-[11px] font-bold text-gray-500 uppercase">Amount</th>
                <th class="px-4 py-3 text-center text-[11px] font-bold text-gray-500 uppercase">Status</th>
                <th class="px-4 py-3 text-left text-[11px] font-bold text-gray-500 uppercase">Encoder</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm bg-white">
              <tr v-for="t in report.transactions" :key="t.id" class="hover:bg-indigo-50/30 transition">
                <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">
                  {{ formatDate(t.encoded_at) }}
                </td>
                <td class="px-4 py-3">
                  <div class="font-semibold text-slate-800 text-xs">{{ t.student_name }}</div>
                  <div class="text-[10px] text-slate-500 font-mono">{{ t.student_id }}</div>
                </td>
                <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ t.or_number || '—' }}</td>
                <td class="px-4 py-3 text-right font-mono font-bold text-slate-800">₱{{ formatMoney(t.amount) }}</td>
                <td class="px-4 py-3 text-center">
                  <span :class="statusClass(t.status)" class="text-[10px] px-2 py-0.5 rounded-full font-bold uppercase">
                    {{ t.status }}
                  </span>
                </td>
                <td class="px-4 py-3 text-xs text-slate-600">{{ t.encoder || '—' }}</td>
              </tr>
              <tr v-if="report.transactions.length === 0">
                <td colspan="6" class="p-8 text-center text-slate-400 italic">No transactions in this period.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <!-- EMPTY STATE -->
    <div v-else class="bg-white rounded-2xl shadow-sm border border-slate-200 p-16 text-center">
      <div class="text-6xl opacity-40 mb-4">📊</div>
      <h3 class="text-lg font-bold text-slate-700 mb-1">No Report Generated Yet</h3>
      <p class="text-sm text-slate-500">Select a period above and click <strong>Generate Report</strong> to see data.</p>
    </div>

  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';

const period = ref('monthly');
const fromDate = ref('');
const toDate = ref('');
const loading = ref(false);
const report = ref(null);

const formatMoney = (val) => {
  return Number(val || 0).toLocaleString('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

const formatDate = (dateStr) => {
  if (!dateStr) return 'N/A';
  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return dateStr;
  return new Intl.DateTimeFormat('en-PH', {
    month: 'short', day: 'numeric', year: 'numeric',
  }).format(d);
};

const statusClass = (status) => ({
  approved: 'bg-emerald-100 text-emerald-800 border border-emerald-200',
  pending: 'bg-amber-100 text-amber-800 border border-amber-200',
  rejected: 'bg-rose-100 text-rose-800 border border-rose-200',
}[status] || 'bg-slate-100 text-slate-700');

const generateReport = async () => {
  loading.value = true;
  report.value = null;

  try {
    const params = { period: period.value };
    if (period.value === 'custom') {
      if (!fromDate.value || !toDate.value) {
        alert('Please select both From and To dates.');
        loading.value = false;
        return;
      }
      params.from = fromDate.value;
      params.to = toDate.value;
    }

    const res = await axios.get('/reports/generate', { params });
    if (res.data.success) {
      report.value = res.data;
    } else {
      alert(res.data.message || 'Failed to generate report.');
    }
  } catch (err) {
    console.error('Report error:', err);
    alert(err.response?.data?.message || 'Failed to generate report.');
  } finally {
    loading.value = false;
  }
};

const printReport = () => {
  if (!report.value) return;

  const r = report.value;
  const rows = r.transactions.map(t => `
    <tr>
      <td>${formatDate(t.encoded_at)}</td>
      <td>${t.student_name}<br><span style="font-size:10px;color:#666;">${t.student_id}</span></td>
      <td>${t.or_number || '—'}</td>
      <td style="text-align:right;font-family:monospace;">₱${formatMoney(t.amount)}</td>
      <td style="text-align:center;text-transform:uppercase;font-size:10px;">${t.status}</td>
      <td>${t.encoder || '—'}</td>
    </tr>
  `).join('');

  const topRows = r.top_students.map((s, i) => `
    <tr>
      <td style="text-align:center;">${i + 1}</td>
      <td>${s.student_name}<br><span style="font-size:10px;color:#666;">${s.student_id}</span></td>
      <td>${s.course}</td>
      <td style="text-align:right;font-family:monospace;">₱${formatMoney(s.total_paid)}</td>
      <td style="text-align:center;">${s.payment_count}</td>
    </tr>
  `).join('');

  const html = `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <title>${r.period.toUpperCase()} Report — ${formatDate(r.from)} to ${formatDate(r.to)}</title>
      <style>
        body { font-family: 'Segoe UI', sans-serif; padding: 30px; color: #1f2937; }
        h1 { color: #1e3a8a; font-size: 22px; margin-bottom: 4px; }
        .subtitle { font-size: 12px; color: #6b7280; margin-bottom: 24px; }
        .summary { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 24px; }
        .card { padding: 16px; border-radius: 12px; color: white; }
        .card.green { background: linear-gradient(135deg, #10b981, #047857); }
        .card.amber { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .card.rose { background: linear-gradient(135deg, #f43f5e, #be123c); }
        .card .label { font-size: 10px; text-transform: uppercase; opacity: 0.8; font-weight: bold; }
        .card .value { font-size: 20px; font-weight: 800; font-family: monospace; margin-top: 4px; }
        h2 { font-size: 15px; margin-top: 24px; margin-bottom: 8px; color: #1e3a8a; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 20px; }
        thead { background: #1e3a8a; color: white; }
        th { padding: 8px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #e5e7eb; }
        .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #9ca3af; }
      </style>
    </head>
    <body>
      <h1>Felipe Verallo Foundation College Inc. Accounting System</h1>
      <p class="subtitle">${r.period.toUpperCase()} Report | Period: ${formatDate(r.from)} → ${formatDate(r.to)}</p>

      <div class="summary">
        <div class="card green">
          <div class="label">Collected</div>
          <div class="value">₱${formatMoney(r.summary.total_collection)}</div>
        </div>
        <div class="card amber">
          <div class="label">Pending</div>
          <div class="value">₱${formatMoney(r.summary.total_pending)}</div>
        </div>
        <div class="card rose">
          <div class="label">Rejected</div>
          <div class="value">₱${formatMoney(r.summary.total_rejected)}</div>
        </div>
      </div>

      <h2>🏆 Top Paying Students</h2>
      <table>
        <thead><tr><th>#</th><th>Student</th><th>Course</th><th style="text-align:right;">Total Paid</th><th style="text-align:center;">Payments</th></tr></thead>
        <tbody>${topRows || '<tr><td colspan="5" style="text-align:center;">No data</td></tr>'}</tbody>
      </table>

      <h2>📋 Transaction Details</h2>
      <table>
        <thead><tr><th>Date</th><th>Student</th><th>OR #</th><th style="text-align:right;">Amount</th><th style="text-align:center;">Status</th><th>Encoder</th></tr></thead>
        <tbody>${rows || '<tr><td colspan="6" style="text-align:center;">No data</td></tr>'}</tbody>
      </table>

      <div class="footer">
        Generated on ${new Date().toLocaleString('en-PH')} | Felipe Verallo Foundation College Inc. Accounting System
      </div>

      <script>window.onload = function() { window.print(); }<\/script>
    </body>
    </html>
  `;

  const win = window.open('', '_blank');
  if (win) {
    win.document.write(html);
    win.document.close();
  }
};
</script>