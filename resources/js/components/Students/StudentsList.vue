<template>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 overflow-hidden font-sans">

    <!-- HEADER -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-slate-50 to-white">
      <div class="flex items-center gap-3">
        <div class="p-2 bg-gradient-to-br from-blue-500 to-blue-700 text-white rounded-xl text-lg shadow-md">🎓</div>
        <div>
          <h2 class="text-xl font-bold text-gray-800">
            {{ pageTitle }}
          </h2>
          <p class="text-xs text-gray-500 mt-0.5">
            Showing <span class="font-bold text-blue-700">{{ currentCount }}</span> record(s)
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2 relative">
        <NotificationBell
          :logs="rejectionLogs"
          @refresh-request="fetchRejectionLogs"
          @select="openRejectionDetailsModal"
        />

        <button
          @click="switchToRejectionsTab"
          :class="[
            'px-3.5 py-2 rounded-xl text-sm font-semibold flex items-center gap-2 border active:scale-95',
            activeTab === 'rejections'
              ? 'bg-rose-600 text-white border-rose-700 shadow-md'
              : 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100'
          ]"
        >
          <span>📋 Rejection Logs</span>
          <span class="bg-rose-200 text-rose-900 text-[10px] px-1.5 py-0.5 rounded-full font-bold">
            {{ rejectionLogs.length }}
          </span>
        </button>

        <button
          v-if="isSuperAdmin"
          @click="toggleArchiveTab"
          :class="[
            'px-3.5 py-2 rounded-xl text-sm font-semibold flex items-center gap-2 border active:scale-95',
            activeTab === 'archived'
              ? 'bg-amber-100 text-amber-800 border-amber-300'
              : 'bg-slate-100 text-slate-700 border-slate-200'
          ]"
        >
          <span>{{ activeTab === 'archived' ? '🎓 View Active' : '📦 View Archived' }}</span>
        </button>

        <button @click="handleRefresh" class="bg-gradient-to-r from-blue-700 to-blue-900 text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-md active:scale-95">
          🔄 Refresh
        </button>
      </div>
    </div>

    <!-- PENDING BANNER -->
    <div
      v-if="isAuthorized && pendingPaymentsCount > 0 && activeTab === 'active'"
      class="px-6 py-3 bg-gradient-to-r from-amber-50 to-orange-50 border-b border-amber-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3"
    >
      <div class="flex items-center gap-2.5">
        <span class="w-8 h-8 rounded-full bg-amber-500 text-white text-sm font-bold flex items-center justify-center animate-pulse">!</span>
        <div>
          <p class="text-sm font-bold text-amber-900">{{ pendingPaymentsCount }} payment(s) awaiting approval</p>
          <p class="text-xs text-amber-700">Review below.</p>
        </div>
      </div>
      <button @click="filterStatus = 'pending'" class="px-4 py-2 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white">
        📋 Review Pending
      </button>
    </div>

    <!-- SEARCH -->
    <SearchFilterBar
      v-if="activeTab !== 'rejections'"
      v-model:searchQuery="searchQuery"
      v-model:filterStatus="filterStatus"
      @clear="clearFilters"
    />

    <!-- LOADING -->
    <div v-if="loading || loadingArchived" class="text-center py-16 text-gray-500">
      <div class="inline-block animate-spin rounded-full h-9 w-9 border-4 border-blue-600 border-t-transparent mb-3"></div>
      <p class="text-sm">Loading...</p>
    </div>

    <!-- TABLE -->
    <template v-else>
      <StudentsTable
        v-if="activeTab !== 'rejections'"
        :students="filteredStudents"
        :mode="activeTab"
        :is-super-admin="isSuperAdmin"
        :is-authorized="isAuthorized"
        :action-loading="actionLoading"
        @open-breakdown="$emit('open-breakdown', $event)"
        @open-payment="$emit('open-payment', $event)"
        @open-rejection-details="openRejectionDetailsModal"
        @approve="approvePayment"
        @reject="openRejectModal"
        @edit="openEditModal"
        @archive="archiveStudent"
        @restore="restoreStudent"
        @force-delete="forceDeleteStudent"
        @print-soa="printSOA"
      />

      <RejectionLogsTable
        v-else
        :logs="rejectionLogs"
        :loading="loadingRejectionLogs"
        @select="openRejectionDetailsModal"
      />
    </template>

    <!-- MODALS -->
    <RejectionDetailsModal
      v-if="selectedRejectionDetails"
      :details="selectedRejectionDetails"
      @close="selectedRejectionDetails = null"
      @re-encode="proceedToRePay"
    />

    <RejectPaymentModal
      v-if="isRejectModalOpen"
      :student="selectedRejectStudent"
      :loading="isSubmittingReject"
      @close="closeRejectModal"
      @confirm="confirmRejectPayment"
    />

    <EditStudentModal
      v-if="isEditModalOpen"
      :form="editForm"
      :loading="isUpdating"
      @close="closeEditModal"
      @save="updateStudent"
    />
  </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted } from 'vue';
import axios from 'axios';

import NotificationBell from './NotificationBell.vue';
import SearchFilterBar from './SearchFilterBar.vue';
import StudentsTable from './StudentsTable.vue';
import RejectionLogsTable from './RejectionLogsTable.vue';
import RejectionDetailsModal from './modals/RejectionDetailsModal.vue';
import RejectPaymentModal from './modals/RejectPaymentModal.vue';
import EditStudentModal from './modals/EditStudentModal.vue';

const props = defineProps({
  students: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  userRole: { type: String, default: '' }
});

const emit = defineEmits(['refresh', 'open-breakdown', 'open-payment']);

// STATE
const activeTab = ref('active');
const archivedStudents = ref([]);
const loadingArchived = ref(false);
const rejectionLogs = ref([]);
const loadingRejectionLogs = ref(false);
const actionLoading = reactive({});

const searchQuery = ref('');
const filterYear = ref('');
const filterStatus = ref('');

const selectedRejectionDetails = ref(null);
const isRejectModalOpen = ref(false);
const isSubmittingReject = ref(false);
const selectedRejectStudent = ref(null);

const isEditModalOpen = ref(false);
const isUpdating = ref(false);
const selectedStudentId = ref(null);
const editForm = reactive({
  student_id: '',
  full_name: '',
  course: '',
  year_level: ''
});

// ROLE
const formattedRole = computed(() => (props.userRole || '').toLowerCase().trim());
const isSuperAdmin = computed(() => formattedRole.value === 'superadmin');
const isAuthorized = computed(() => ['admin', 'superadmin'].includes(formattedRole.value));

// COMPUTED
const pageTitle = computed(() => ({
  active: 'List of Enrolled Students',
  archived: 'Archived Students',
  rejections: 'Rejection Logs'
}[activeTab.value]));

const currentSourceList = computed(() =>
  activeTab.value === 'archived' ? archivedStudents.value : props.students
);

const currentCount = computed(() =>
  activeTab.value === 'rejections' ? rejectionLogs.value.length : filteredStudents.value.length
);

const pendingPaymentsCount = computed(() =>
  props.students.filter(s => s.has_pending_payment).length
);

const filteredStudents = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  return currentSourceList.value.filter((s) => {
    if (q) {
      const haystack = [s.student_id, s.full_name, s.course].filter(Boolean).join(' ').toLowerCase();
      if (!haystack.includes(q)) return false;
    }
    if (filterYear.value && s.year_level !== filterYear.value) return false;
    if (filterStatus.value && getPaymentStatus(s) !== filterStatus.value) return false;
    return true;
  });
});

const getPaymentStatus = (s) => {
  if (s.has_pending_payment) return 'pending';
  if (s.rejection_reason || s.payment_status === 'rejected' || s.is_rejected) return 'rejected';
  const paid = Number(s.total_paid || 0);
  const total = Number(s.total_fees || 0);
  if (paid <= 0) return 'unpaid';
  if (paid >= total) return 'paid';
  return 'partial';
};

const clearFilters = () => {
  searchQuery.value = '';
  filterYear.value = '';
  filterStatus.value = '';
};

// FETCH
const fetchRejectionLogs = async () => {
  loadingRejectionLogs.value = true;
  try {
    const { data } = await axios.get('/payments/rejection-logs');
    rejectionLogs.value = data.logs || data.rejections || data.data || (Array.isArray(data) ? data : []);
  } catch (error) {
    console.error('Error fetching rejection logs:', error);
  } finally {
    loadingRejectionLogs.value = false;
  }
};

const fetchArchivedStudents = async () => {
  if (!isSuperAdmin.value) return;
  loadingArchived.value = true;
  try {
    const { data } = await axios.get('/students/archived');
    archivedStudents.value = Array.isArray(data) ? data : (data.students || []);
  } catch (error) {
    console.error('Error fetching archived:', error);
    archivedStudents.value = [];
  } finally {
    loadingArchived.value = false;
  }
};

onMounted(() => fetchRejectionLogs());

// TABS
const switchToRejectionsTab = () => {
  activeTab.value = activeTab.value === 'rejections' ? 'active' : 'rejections';
  if (activeTab.value === 'rejections') fetchRejectionLogs();
};

const toggleArchiveTab = () => {
  activeTab.value = activeTab.value === 'active' ? 'archived' : 'active';
  if (activeTab.value === 'archived') fetchArchivedStudents();
  clearFilters();
};

const handleRefresh = () => {
  if (activeTab.value === 'archived') fetchArchivedStudents();
  else if (activeTab.value === 'rejections') fetchRejectionLogs();
  else emit('refresh');
  fetchRejectionLogs();
};

// HELPERS
const extractPaymentId = (s) => s.pending_payment_id || s.payment_id || s.id;

const calculateRemainingBalance = (s) => {
  const t = Number(s.total_fees || 0);
  const p = Number(s.total_paid || 0);
  return Math.max(0, t - p);
};

const formatDateTime = (d) => {
  if (!d) return 'N/A';
  const date = new Date(d);
  if (isNaN(date.getTime())) return d;
  return new Intl.DateTimeFormat('en-PH', {
    month: 'short', day: 'numeric', year: 'numeric',
    hour: 'numeric', minute: '2-digit', hour12: true
  }).format(date);
};

// ACTIONS
const approvePayment = async (student) => {
  const paymentId = extractPaymentId(student);
  if (!paymentId || !confirm(`Approve ₱${student.pending_amount || 0}?`)) return;
  actionLoading[student.id] = true;
  try {
    const { data } = await axios.post(`/payments/${paymentId}/approve`);
    if (data.success) {
      alert('Approved!');
      emit('refresh');
    }
  } catch (error) {
    alert(error.response?.data?.message || 'Error');
  } finally {
    actionLoading[student.id] = false;
  }
};

const openRejectModal = (student) => {
  selectedRejectStudent.value = student;
  isRejectModalOpen.value = true;
};

const closeRejectModal = () => {
  isRejectModalOpen.value = false;
  selectedRejectStudent.value = null;
};

const confirmRejectPayment = async (reason) => {
  const student = selectedRejectStudent.value;
  const paymentId = extractPaymentId(student);
  if (!paymentId || !reason.trim()) return;

  isSubmittingReject.value = true;
  try {
    const { data } = await axios.post(`/payments/${paymentId}/reject`, { reason: reason.trim() });
    if (data.success) {
      alert('Rejected.');
      closeRejectModal();
      emit('refresh');
      fetchRejectionLogs();
    }
  } catch (error) {
    alert('Error');
  } finally {
    isSubmittingReject.value = false;
  }
};

const openRejectionDetailsModal = (item) => {
  selectedRejectionDetails.value = item;
};

const proceedToRePay = (item) => {
  const student = props.students.find(s => s.id === item.student_pk || s.student_id === item.student_id) || item;
  selectedRejectionDetails.value = null;
  emit('open-payment', student);
};

const archiveStudent = async (student) => {
  if (!confirm(`Archive ${student.full_name}?`)) return;
  try {
    const { data } = await axios.delete(`/students/${student.id}/archive`);
    if (data.success) {
      alert('Archived.');
      emit('refresh');
      fetchArchivedStudents();
    }
  } catch (error) {
    alert('Error');
  }
};

const restoreStudent = async (student) => {
  if (!confirm(`Restore ${student.full_name}?`)) return;
  actionLoading[student.id] = true;
  try {
    const { data } = await axios.post(`/students/${student.id}/restore`);
    if (data.success) {
      alert('Restored.');
      fetchArchivedStudents();
      emit('refresh');
    }
  } catch (error) {
    alert('Error');
  } finally {
    actionLoading[student.id] = false;
  }
};

const forceDeleteStudent = async (student) => {
  if (!confirm(`⚠️ PERMANENTLY DELETE ${student.full_name}?\n\nCannot be undone!`)) return;
  if (!confirm(`Final warning: Click OK to DELETE ${student.full_name}.`)) return;

  actionLoading[student.id] = true;
  try {
    const { data } = await axios.delete(`/students/${student.id}/force-delete`);
    if (data.success) {
      alert('Deleted.');
      fetchArchivedStudents();
      emit('refresh');
    }
  } catch (error) {
    alert(error.response?.data?.message || 'Error');
  } finally {
    actionLoading[student.id] = false;
  }
};

const openEditModal = (student) => {
  selectedStudentId.value = student.id;
  editForm.student_id = student.student_id || '';
  editForm.full_name = student.full_name || '';
  editForm.course = student.course || '';
  editForm.year_level = student.year_level || '1st Year';
  isEditModalOpen.value = true;
};

const closeEditModal = () => {
  isEditModalOpen.value = false;
  selectedStudentId.value = null;
};

const updateStudent = async () => {
  isUpdating.value = true;
  try {
    const { data } = await axios.put(`/students/${selectedStudentId.value}`, editForm);
    if (data.success) {
      alert('Updated!');
      closeEditModal();
      emit('refresh');
    }
  } catch (error) {
    alert('Error');
  } finally {
    isUpdating.value = false;
  }
};

// ===================== PRINT SOA (WITH SIGNATURE) =====================
const printSOA = (student) => {
  const fees = student.fees || student.fee_breakdown || [];
  const totalFees = Number(student.total_fees || 0);
  const totalPaid = Number(student.total_paid || 0);
  const balance = calculateRemainingBalance(student);
  const today = new Date().toLocaleDateString('en-PH', {
    year: 'numeric', month: 'long', day: 'numeric'
  });

  // ✅ Kuhaon ang info sa nag-print
  const appEl = document.getElementById('app');
  const printedBy = appEl?.dataset?.userName || 'Authorized Personnel';
  const printedByRole = (appEl?.dataset?.userRole || '').toUpperCase();

  const feesRows = fees.length
    ? fees.map(f => `
        <tr>
          <td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;">${f.fee_name || f.description || ''}</td>
          <td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;text-align:right;font-family:monospace;">₱${Number(f.amount || 0).toFixed(2)}</td>
        </tr>`).join('')
    : `<tr><td colspan="2" style="padding:14px;text-align:center;color:#9ca3af;font-style:italic;">No fee breakdown available</td></tr>`;

  const statusText = balance <= 0 ? 'Fully Paid' : totalPaid > 0 ? 'Partial Paid' : 'Unpaid';
  const statusColor = balance <= 0 ? '#10b981' : totalPaid > 0 ? '#f59e0b' : '#ef4444';

  const html = `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8" />
      <title>Statement of Account - ${student.full_name}</title>
      <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Arial, sans-serif; color: #1f2937; margin: 0; padding: 40px; }
        .header { display: flex; justify-content: space-between; border-bottom: 3px solid #1e3a8a; padding-bottom: 20px; margin-bottom: 24px; }
        .school-name { font-size: 22px; font-weight: 800; color: #1e3a8a; }
        .school-sub { font-size: 11px; color: #6b7280; margin-top: 4px; text-transform: uppercase; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 32px; background: #f8fafc; padding: 18px 22px; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 24px; font-size: 13px; }
        .info-grid .label { color: #64748b; font-size: 10px; text-transform: uppercase; font-weight: 700; }
        .info-grid .value { font-weight: 600; color: #0f172a; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 20px; }
        thead { background: #1e3a8a; color: white; }
        thead th { padding: 12px; text-align: left; font-size: 11px; text-transform: uppercase; }
        thead th:last-child { text-align: right; }
        .summary { background: #1e3a8a; color: white; border-radius: 12px; padding: 20px 24px; margin-top: 20px; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
        .summary .item .lbl { font-size: 10px; text-transform: uppercase; color: #93c5fd; font-weight: 700; }
        .summary .item .val { font-size: 18px; font-weight: 800; font-family: monospace; margin-top: 4px; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 10px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; color: white; }

        /* ✅ SIGNATURE SECTION */
        .signature-section {
          margin-top: 70px;
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 60px;
          page-break-inside: avoid;
        }
        .signature-block {
          text-align: center;
        }
        .signature-line {
          border-top: 1.5px solid #1f2937;
          margin-bottom: 6px;
        }
        .signature-name {
          font-weight: 700;
          font-size: 13px;
          color: #0f172a;
        }
        .signature-title {
          font-size: 10px;
          color: #6b7280;
          text-transform: uppercase;
          letter-spacing: 0.5px;
          margin-top: 2px;
        }
        .signature-date {
          font-size: 10px;
          color: #9ca3af;
          margin-top: 10px;
        }

        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px dashed #cbd5e1; display: flex; justify-content: space-between; font-size: 10px; color: #9ca3af; }
      </style>
    </head>
    <body>

      <!-- HEADER -->
      <div class="header">
        <div>
          <div class="school-name">FELIPE VERALLO FOUNDATION COLLEGE INC.</div>
          <div class="school-sub">Office of the Treasurer & Accountancy</div>
        </div>
        <div style="text-align:right;">
          <h1 style="font-size:16px;margin:0;color:#1e3a8a;">STATEMENT OF ACCOUNT</h1>
          <p style="font-size:11px;color:#6b7280;margin-top:4px;">Date: ${today}</p>
        </div>
      </div>

      <!-- STUDENT INFO -->
      <div class="info-grid">
        <div><div class="label">Student Name</div><div class="value">${student.full_name}</div></div>
        <div><div class="label">Student ID</div><div class="value">${student.student_id}</div></div>
        <div><div class="label">Course & Year</div><div class="value">${student.course} - ${student.year_level}</div></div>
        <div><div class="label">Status</div><div class="value">
          <span class="status-badge" style="background:${statusColor};">${statusText}</span>
        </div></div>
      </div>

      <!-- FEE TABLE -->
      <table>
        <thead><tr><th>Fee Description</th><th style="text-align:right;">Amount</th></tr></thead>
        <tbody>${feesRows}</tbody>
      </table>

      <!-- SUMMARY -->
      <div class="summary">
        <div class="item"><div class="lbl">Total Assessment</div><div class="val">₱${totalFees.toFixed(2)}</div></div>
        <div class="item"><div class="lbl">Approved Paid</div><div class="val">₱${totalPaid.toFixed(2)}</div></div>
        <div class="item"><div class="lbl">Remaining</div><div class="val" style="color:#fca5a5;">₱${balance.toFixed(2)}</div></div>
      </div>

      <!-- ✅ SIGNATURE SECTION -->
      <div class="signature-section">
        <div class="signature-block">
          <div class="signature-line"></div>
          <div class="signature-name">${printedBy}</div>
         <!-- div class="signature-title">Printed & Verified by (${printedByRole})</div -->
          <div class="signature-date">Date: ${today}</div>
        </div>

        <div class="signature-block">
          <div class="signature-line"></div>
          <div class="signature-name">&nbsp;</div>
          <div class="signature-title">Received by (Student / Guardian)</div>
          <div class="signature-date">Date: ______________________</div>
        </div>
      </div>

      <!-- FOOTER -->
      <div class="footer">
        <div>Generated automatically by FVFC Accounting System</div>
        <div>Printed on ${new Date().toLocaleString('en-PH')}</div>
      </div>

      <script>window.onload = function() { window.print(); window.close(); };<\/script>
    </body>
    </html>
  `;

  const printWindow = window.open('', '_blank');
  if (printWindow) {
    printWindow.document.write(html);
    printWindow.document.close();
  }
};
</script>