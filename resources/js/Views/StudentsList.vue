<template>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 overflow-hidden font-sans">

    <!-- HEADER -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-slate-50 to-white">
      <div class="flex items-center gap-3">
        <div class="p-2 bg-gradient-to-br from-blue-500 to-blue-700 text-white rounded-xl text-lg shadow-md shadow-blue-500/20">🎓</div>
        <div>
          <h2 class="text-xl font-bold text-gray-800 tracking-tight">
            {{ activeTab === 'active' ? 'List of Enrolled Students' : activeTab === 'archived' ? 'Archived Students' : 'Rejection Logs' }}
          </h2>
          <p class="text-xs text-gray-500 mt-0.5">
            Showing <span class="font-bold text-blue-700">{{ activeTab === 'rejections' ? rejectionLogs.length : filteredStudents.length }}</span> record(s)
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2 relative">
        <!-- NOTIFICATION BELL -->
        <div class="relative">
          <button
            @click="toggleNotification"
            class="relative p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition border border-slate-200 active:scale-95"
            title="View Rejection Notifications"
          >
            <span class="text-lg">🔔</span>
            <span v-if="unreadRejectionCount > 0" class="absolute -top-1 -right-1 bg-rose-600 text-white text-[10px] font-bold h-5 w-5 rounded-full flex items-center justify-center border-2 border-white animate-bounce">
              {{ unreadRejectionCount }}
            </span>
          </button>

          <div v-if="isNotificationOpen" class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden">
            <div class="p-3.5 bg-rose-50 border-b border-rose-100 flex justify-between items-center">
              <div class="flex items-center gap-2">
                <span class="text-rose-600 font-bold text-sm">⚠️ Rejected Payment Alerts</span>
                <span class="bg-rose-200 text-rose-800 text-[10px] font-extrabold px-2 py-0.5 rounded-full">{{ rejectionLogs.length }}</span>
              </div>
              <button @click="isNotificationOpen = false" class="text-slate-400 hover:text-slate-600 text-xs font-bold">✕</button>
            </div>

            <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">
              <div
                v-for="log in rejectionLogs"
                :key="log.id"
                @click="openRejectionDetailsModal(log)"
                class="p-3.5 hover:bg-rose-50/50 cursor-pointer transition text-left group"
              >
                <div class="flex justify-between items-start">
                  <p class="text-xs font-bold text-slate-800 group-hover:text-rose-700">{{ log.student_name }} ({{ log.student_id }})</p>
                  <span class="text-[10px] text-slate-400">{{ formatDateTime(log.rejected_at) }}</span>
                </div>
                <p class="text-xs text-rose-800 mt-1 line-clamp-2 bg-rose-50 p-2 rounded-lg border border-rose-100 font-medium">
                  💬 <span class="font-semibold">Admin Note:</span> {{ log.reason }}
                </p>
              </div>

              <div v-if="rejectionLogs.length === 0" class="p-6 text-center text-slate-400 text-xs italic">
                🎉 No rejected payments recorded.
              </div>
              <div v-if="loadingRejectionLogs" class="p-6 text-center text-slate-400 text-xs">
                <div class="inline-block animate-spin rounded-full h-4 w-4 border-2 border-rose-500 border-t-transparent"></div>
                <p class="mt-2">Loading...</p>
              </div>
            </div>
          </div>
        </div>

        <!-- REJECTION LOGS TAB -->
        <button
          @click="switchToRejectionsTab"
          :class="[
            'px-3.5 py-2 rounded-xl text-sm font-semibold transition-all flex items-center gap-2 border active:scale-95',
            activeTab === 'rejections' ? 'bg-rose-600 text-white border-rose-700 shadow-md' : 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100'
          ]"
        >
          <span>📋 Rejection Logs</span>
          <span class="bg-rose-200 text-rose-900 text-[10px] px-1.5 py-0.5 rounded-full font-bold">{{ rejectionLogs.length }}</span>
        </button>

        <!-- ARCHIVE TAB -->
        <button
          v-if="isSuperAdmin"
          @click="toggleArchiveTab"
          :class="[
            'px-3.5 py-2 rounded-xl text-sm font-semibold transition-all flex items-center gap-2 shadow-sm border active:scale-95',
            activeTab === 'archived' ? 'bg-amber-100 text-amber-800 border-amber-300' : 'bg-slate-100 text-slate-700 border-slate-200'
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
    <div v-if="isAuthorized && pendingPaymentsCount > 0 && activeTab === 'active'" class="px-6 py-3 bg-gradient-to-r from-amber-50 to-orange-50 border-b border-amber-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
      <div class="flex items-center gap-2.5">
        <span class="w-8 h-8 rounded-full bg-amber-500 text-white text-sm font-bold flex items-center justify-center animate-pulse">!</span>
        <div>
          <p class="text-sm font-bold text-amber-900">{{ pendingPaymentsCount }} payment(s) awaiting your approval</p>
          <p class="text-xs text-amber-700">Encoded by the Encoder — review below.</p>
        </div>
      </div>
      <button @click="filterStatus = 'pending'" class="px-4 py-2 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white">📋 Review Pending</button>
    </div>

    <!-- SEARCH & FILTERS -->
    <div v-if="activeTab !== 'rejections'" class="px-6 py-4 bg-slate-50/60 border-b border-gray-100">
      <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
        <div class="md:col-span-6 relative">
          <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔍</span>
          <input v-model="searchQuery" type="text" placeholder="Search by Student ID, Name, or Course..." class="w-full pl-10 pr-9 py-2.5 border border-slate-300 rounded-xl text-sm bg-white shadow-sm focus:ring-2 focus:ring-blue-600 focus:outline-none" />
        </div>
        <div class="md:col-span-3">
          <select v-model="filterStatus" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm bg-white focus:ring-2 focus:ring-blue-600 focus:outline-none">
            <option value="">All Status</option>
            <option value="paid">Fully Paid</option>
            <option value="partial">Partial Paid</option>
            <option value="pending">Pending Review</option>
            <option value="rejected">Rejected Payment</option>
            <option value="unpaid">No Payment</option>
          </select>
        </div>
        <div class="md:col-span-3">
          <button @click="clearFilters" class="w-full px-3 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-sm font-semibold">Clear Filters</button>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading || loadingArchived" class="text-center py-16 text-gray-500">
      <div class="inline-block animate-spin rounded-full h-9 w-9 border-4 border-blue-600 border-t-transparent mb-3"></div>
      <p class="text-sm">Loading student records...</p>
    </div>

    <!-- TABLE 1: STUDENTS -->
    <div v-else-if="activeTab !== 'rejections'" class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-slate-50/80">
          <tr>
            <th class="px-4 py-3.5 text-left text-[11px] font-bold text-gray-500 uppercase">Student ID</th>
            <th class="px-4 py-3.5 text-left text-[11px] font-bold text-gray-500 uppercase">Name</th>
            <th class="px-4 py-3.5 text-left text-[11px] font-bold text-gray-500 uppercase">Course &amp; Level</th>
            <th class="px-4 py-3.5 text-left text-[11px] font-bold text-gray-500 uppercase">Total Assessment</th>
            <th class="px-4 py-3.5 text-left text-[11px] font-bold text-gray-500 uppercase">Paid / Status</th>
            <th class="px-4 py-3.5 text-left text-[11px] font-bold text-gray-500 uppercase">Remaining Balance</th>
            <th class="px-4 py-3.5 text-center text-[11px] font-bold text-gray-500 uppercase">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-sm bg-white">
          <tr
            v-for="student in filteredStudents"
            :key="student.id"
            :class="[
              'transition-colors duration-150',
              student.has_pending_payment ? 'bg-amber-50/40 hover:bg-amber-50/70' : 'hover:bg-blue-50/40'
            ]"
          >
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

            <!-- COURSE -->
            <td class="px-4 py-3.5 whitespace-nowrap text-gray-700">
              <span class="text-sm">{{ student.course }}</span>
              <span class="ml-1 text-[11px] font-semibold text-blue-700 bg-blue-50 px-1.5 py-0.5 rounded-md border border-blue-100">{{ student.year_level }}</span>
            </td>

            <!-- TOTAL -->
            <td class="px-4 py-3.5 whitespace-nowrap font-mono font-semibold">
              ₱{{ Number(student.total_fees || 0).toFixed(2) }}
            </td>

            <!-- PAID / STATUS -->
            <td class="px-4 py-3.5">
              <span class="font-mono text-emerald-700 font-bold">₱{{ Number(student.total_paid || 0).toFixed(2) }}</span>

              <div v-if="student.is_rejected || student.payment_status === 'rejected' || student.rejection_reason" class="mt-1">
                <button @click="openRejectionDetailsModal(student)" class="group text-left p-2 rounded-xl bg-rose-50 border border-rose-200 hover:bg-rose-100 transition w-full max-w-xs block shadow-sm">
                  <div class="flex items-center justify-between">
                    <span class="text-[10px] text-rose-800 font-extrabold uppercase">⚠️ Payment Rejected</span>
                    <span class="text-[10px] text-rose-600 underline group-hover:font-bold">View Note 🔍</span>
                  </div>
                  <p class="text-[11px] text-rose-900 font-semibold mt-0.5 truncate">
                    <span class="font-bold">Reason:</span> {{ student.rejection_reason || 'See logs' }}
                  </p>
                </button>
              </div>

              <div v-else-if="student.has_pending_payment" class="mt-1">
                <span class="inline-flex items-center gap-1 text-[10px] text-amber-800 font-bold bg-amber-50 px-2 py-0.5 rounded-full border border-amber-300">
                  <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                  Pending Review (₱{{ Number(student.pending_amount || 0).toFixed(2) }})
                </span>
                <div v-if="student.pending_date" class="text-[10px] text-gray-500 font-normal mt-0.5">
                  🕒 Submitted: {{ formatDateTime(student.pending_date) }}
                </div>
              </div>

              <div v-else-if="student.approved_at || student.last_paid_date" class="mt-1">
                <span class="text-[10px] text-emerald-800 font-semibold bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">✅ Approved</span>
                <div class="text-[10px] text-slate-500 font-normal mt-0.5 flex items-center gap-1">
                  <span>🕒</span>
                  <span>{{ formatDateTime(student.approved_at || student.last_paid_date) }}</span>
                </div>
              </div>
            </td>

            <!-- BALANCE -->
            <td class="px-4 py-3.5 whitespace-nowrap font-mono font-bold text-rose-600">
              ₱{{ calculateRemainingBalance(student).toFixed(2) }}
            </td>

            <!-- ACTIONS -->
            <td class="px-4 py-3.5 text-center whitespace-nowrap">
              <div class="flex items-center justify-center gap-1.5">

                <!-- ACTIVE -->
                <template v-if="activeTab === 'active'">
                  <template v-if="isAuthorized && student.has_pending_payment">
                    <button @click="approvePayment(student)" :disabled="actionLoading[student.id]" class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm flex items-center gap-1 disabled:opacity-50">
                      <span v-if="actionLoading[student.id]" class="inline-block animate-spin rounded-full h-3 w-3 border-2 border-white border-t-transparent"></span>
                      <span v-else>✓</span>
                      <span>Approve</span>
                    </button>
                    <button @click="openRejectModal(student)" :disabled="actionLoading[student.id]" class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-sm disabled:opacity-50">
                      ✕ Reject
                    </button>
                  </template>

                  <template v-else>
                    <button v-if="isSuperAdmin" @click="openEditModal(student)" title="Edit" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200">✏️</button>

                    <button
                      @click="$emit('open-payment', student)"
                      :disabled="student.has_pending_payment"
                      :class="[
                        'px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1 shadow-sm',
                        student.has_pending_payment
                          ? 'bg-amber-100 text-amber-700 border border-amber-300 cursor-not-allowed'
                          : (student.rejection_reason || student.is_rejected)
                          ? 'bg-gradient-to-r from-rose-600 to-rose-700 text-white shadow-rose-500/20'
                          : 'bg-gradient-to-r from-emerald-600 to-emerald-700 text-white shadow-emerald-500/20'
                      ]"
                    >
                      <span>💳</span>
                      <span>{{ student.has_pending_payment ? 'Pending Review' : (student.rejection_reason || student.is_rejected) ? 'Re-encode Pay' : 'Pay' }}</span>
                    </button>
                  </template>

                  <button @click="printSOA(student)" title="Print SOA" class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-gradient-to-r from-slate-700 to-slate-900 text-white shadow-md">
                    🖨️ SOA
                  </button>

                  <button v-if="isSuperAdmin" @click="archiveStudent(student)" title="Archive" class="p-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200">📦</button>
                </template>

                <!-- ARCHIVED -->
                <template v-else>
                  <button v-if="isSuperAdmin" @click="restoreStudent(student)" :disabled="actionLoading[student.id]" class="px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-300 disabled:opacity-50">
                    <span v-if="actionLoading[student.id]" class="inline-block animate-spin rounded-full h-3 w-3 border-2 border-emerald-500 border-t-transparent"></span>
                    <span v-else>♻️</span>
                    <span>Restore</span>
                  </button>

                  <button v-if="isSuperAdmin" @click="forceDeleteStudent(student)" :disabled="actionLoading[student.id]" class="px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-300 disabled:opacity-50">
                    <span v-if="actionLoading[student.id]" class="inline-block animate-spin rounded-full h-3 w-3 border-2 border-rose-500 border-t-transparent"></span>
                    <span v-else>🗑️</span>
                    <span>Delete</span>
                  </button>
                </template>

              </div>
            </td>
          </tr>

          <tr v-if="filteredStudents.length === 0">
            <td colspan="7" class="px-4 py-16 text-center">
              <div class="inline-flex flex-col items-center gap-2">
                <div class="text-4xl opacity-40">📭</div>
                <p class="text-gray-400 italic text-sm">
                  {{ activeTab === 'active' ? 'No enrolled students found.' : 'No archived students found.' }}
                </p>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- TABLE 2: REJECTION LOGS -->
    <div v-else class="overflow-x-auto">
      <div class="p-4 bg-rose-50/50 border-b border-rose-100 text-xs text-rose-800 font-semibold flex items-center gap-2">
        <span>📜</span> Showing history logs of <strong>REJECTED PAYMENTS ONLY</strong>.
      </div>

      <div v-if="loadingRejectionLogs" class="p-12 text-center text-slate-400">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-rose-500 border-t-transparent mb-3"></div>
        <p class="text-sm">Loading rejection logs...</p>
      </div>

      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3.5 text-left text-[11px] font-bold text-gray-500 uppercase">Student Info</th>
            <th class="px-4 py-3.5 text-left text-[11px] font-bold text-gray-500 uppercase">Rejected Amount</th>
            <th class="px-4 py-3.5 text-left text-[11px] font-bold text-gray-500 uppercase">Admin Reason / Notes</th>
            <th class="px-4 py-3.5 text-left text-[11px] font-bold text-gray-500 uppercase">Date Rejected</th>
            <th class="px-4 py-3.5 text-center text-[11px] font-bold text-gray-500 uppercase">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-sm bg-white">
          <tr v-for="log in rejectionLogs" :key="log.id" class="hover:bg-rose-50/30 transition">
            <td class="px-4 py-3.5">
              <div class="font-bold text-slate-800">{{ log.student_name }}</div>
              <div class="text-xs font-mono text-slate-500">{{ log.student_id }}</div>
            </td>
            <td class="px-4 py-3.5 font-mono font-bold text-rose-700">₱{{ Number(log.amount || 0).toFixed(2) }}</td>
            <td class="px-4 py-3.5 max-w-sm">
              <div class="bg-rose-50 border border-rose-200 p-2.5 rounded-xl text-xs text-rose-950 font-medium">
                💬 <span class="font-bold">Notes:</span> {{ log.reason }}
              </div>
            </td>
            <td class="px-4 py-3.5 text-xs text-slate-500 font-medium">{{ formatDateTime(log.rejected_at) }}</td>
            <td class="px-4 py-3.5 text-center">
              <button @click="openRejectionDetailsModal(log)" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg text-xs shadow">
                🔍 View &amp; Re-encode
              </button>
            </td>
          </tr>
          <tr v-if="rejectionLogs.length === 0">
            <td colspan="5" class="p-8 text-center text-slate-400 italic">No rejected payment logs found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- MODAL: REJECTION DETAILS -->
    <Teleport to="body">
      <div v-if="selectedRejectionDetails" class="fixed inset-0 z-[9999] overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
          <div @click="selectedRejectionDetails = null" class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm"></div>

          <div class="relative inline-block bg-white rounded-2xl text-left shadow-2xl sm:max-w-md sm:w-full overflow-hidden">
            <div class="px-6 py-4 bg-rose-600 text-white flex justify-between items-center">
              <h3 class="font-bold flex items-center gap-2">⚠️ Rejection Notes</h3>
              <button @click="selectedRejectionDetails = null" class="text-white font-bold">✕</button>
            </div>

            <div class="p-6 space-y-4">
              <div class="bg-slate-50 p-3.5 rounded-xl border text-xs space-y-1">
                <div><span class="text-slate-500 font-semibold">Student Name:</span> <strong>{{ selectedRejectionDetails.full_name || selectedRejectionDetails.student_name }}</strong></div>
                <div><span class="text-slate-500 font-semibold">Student ID:</span> <span class="font-mono font-bold">{{ selectedRejectionDetails.student_id }}</span></div>
                <div><span class="text-slate-500 font-semibold">Rejected Amount:</span> <strong class="text-rose-600 font-mono">₱{{ Number(selectedRejectionDetails.pending_amount || selectedRejectionDetails.amount || 0).toFixed(2) }}</strong></div>
                <div><span class="text-slate-500 font-semibold">Date Rejected:</span> <span>{{ formatDateTime(selectedRejectionDetails.rejected_at) }}</span></div>
              </div>

              <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Admin Reason / Notes:</label>
                <div class="bg-rose-50 border-2 border-rose-200 p-4 rounded-xl text-sm text-rose-950 font-semibold">
                  "{{ selectedRejectionDetails.rejection_reason || selectedRejectionDetails.reason }}"
                </div>
              </div>

              <div class="text-[11px] text-slate-500 italic bg-amber-50 p-2.5 rounded-lg border border-amber-200">
                💡 <strong>Encoder Instruction:</strong> Correct the receipt and click Re-encode Pay below.
              </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t flex justify-end gap-2">
              <button @click="selectedRejectionDetails = null" class="px-4 py-2 bg-slate-200 rounded-xl text-xs font-bold">Close</button>
              <button @click="proceedToRePay(selectedRejectionDetails)" class="px-4 py-2 bg-rose-600 text-white rounded-xl text-xs font-bold">💳 Re-encode Pay Now</button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- MODAL: REJECT PAYMENT -->
    <Teleport to="body">
      <div v-if="isRejectModalOpen" class="fixed inset-0 z-[9999] overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
          <div @click="closeRejectModal" class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm"></div>

          <div class="relative inline-block bg-white rounded-2xl text-left shadow-2xl sm:max-w-md sm:w-full overflow-hidden">
            <div class="px-6 py-4 bg-rose-50 border-b flex justify-between items-center">
              <h3 class="text-lg font-bold text-rose-800">🚫 Reject Payment</h3>
              <button @click="closeRejectModal" class="text-gray-400 hover:text-rose-500 font-bold">✕</button>
            </div>

            <form @submit.prevent="confirmRejectPayment">
              <div class="p-6 space-y-4">
                <p class="text-sm text-gray-600">
                  Reject payment of <strong>₱{{ Number(selectedRejectStudent?.pending_amount || 0).toFixed(2) }}</strong>
                  for <strong>{{ selectedRejectStudent?.full_name }}</strong>?
                </p>
                <div>
                  <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Reason for Encoder <span class="text-rose-500">*</span></label>
                  <textarea v-model="rejectionReason" required rows="3" placeholder="e.g., Invalid reference number..." class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-rose-500 resize-none"></textarea>
                </div>
              </div>

              <div class="px-6 py-4 bg-slate-50 border-t flex justify-end gap-3">
                <button type="button" @click="closeRejectModal" class="px-4 py-2 bg-white border rounded-xl text-sm font-semibold">Cancel</button>
                <button type="submit" :disabled="isSubmittingReject" class="px-5 py-2 bg-rose-600 text-white rounded-xl text-sm font-semibold hover:bg-rose-700 disabled:opacity-60">
                  {{ isSubmittingReject ? 'Rejecting...' : 'Confirm Reject' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- MODAL: EDIT STUDENT -->
    <Teleport to="body">
      <div v-if="isEditModalOpen" class="fixed inset-0 z-[9999] overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
          <div @click="closeEditModal" class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm"></div>

          <div class="relative z-10 inline-block overflow-hidden text-left align-bottom bg-white rounded-2xl shadow-2xl sm:max-w-lg sm:w-full">
            <div class="px-6 py-4 border-b bg-slate-50 flex justify-between items-center">
              <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">✏️ Edit Student Details</h3>
              <button @click="closeEditModal" class="text-gray-400 hover:text-rose-500 font-bold">✕</button>
            </div>

            <form @submit.prevent="updateStudent">
              <div class="px-6 py-5 space-y-4">
                <div>
                  <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Student ID</label>
                  <input v-model="editForm.student_id" type="text" required class="w-full px-3.5 py-2 border border-slate-300 rounded-lg text-sm font-mono" />
                </div>
                <div>
                  <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Full Name</label>
                  <input v-model="editForm.full_name" type="text" required class="w-full px-3.5 py-2 border border-slate-300 rounded-lg text-sm" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Course</label>
                    <input v-model="editForm.course" type="text" required class="w-full px-3.5 py-2 border border-slate-300 rounded-lg text-sm" />
                  </div>
                  <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Year Level</label>
                    <select v-model="editForm.year_level" required class="w-full px-3.5 py-2 border border-slate-300 rounded-lg text-sm">
                      <option value="1st Year">1st Year</option>
                      <option value="2nd Year">2nd Year</option>
                      <option value="3rd Year">3rd Year</option>
                      <option value="4th Year">4th Year</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="px-6 py-4 bg-slate-50 border-t flex flex-col sm:flex-row-reverse gap-3">
                <button type="submit" :disabled="isUpdating" class="w-full sm:w-auto bg-gradient-to-r from-blue-700 to-blue-900 text-white px-5 py-2 rounded-xl text-sm font-semibold disabled:opacity-60">
                  {{ isUpdating ? 'Saving...' : '💾 Save Changes' }}
                </button>
                <button type="button" @click="closeEditModal" class="w-full sm:w-auto bg-white border border-slate-300 px-5 py-2 rounded-xl text-sm font-semibold">
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  students: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  userRole: { type: String, default: '' }
});

const emit = defineEmits(['refresh', 'open-breakdown', 'open-payment']);

// ===== STATE =====
const activeTab = ref('active');
const archivedStudents = ref([]);
const loadingArchived = ref(false);

const isNotificationOpen = ref(false);
const selectedRejectionDetails = ref(null);
const rejectionLogs = ref([]);
const loadingRejectionLogs = ref(false);

const searchQuery = ref('');
const filterYear = ref('');
const filterStatus = ref('');

const actionLoading = reactive({});

const isEditModalOpen = ref(false);
const isUpdating = ref(false);
const selectedStudentId = ref(null);
const editForm = reactive({
  student_id: '',
  full_name: '',
  course: '',
  year_level: ''
});

const isRejectModalOpen = ref(false);
const isSubmittingReject = ref(false);
const selectedRejectStudent = ref(null);
const rejectionReason = ref('');

// ===== ROLES =====
const formattedRole = computed(() => (props.userRole || '').toLowerCase().trim());
const isSuperAdmin = computed(() => formattedRole.value === 'superadmin');
const isAdmin = computed(() => formattedRole.value === 'admin');
const isAuthorized = computed(() => ['admin', 'superadmin'].includes(formattedRole.value));

// ===== COMPUTED =====
const currentSourceList = computed(() => activeTab.value === 'archived' ? archivedStudents.value : props.students);
const pendingPaymentsCount = computed(() => props.students.filter(s => s.has_pending_payment).length);
const unreadRejectionCount = computed(() => rejectionLogs.value.length);

// ===== FETCH =====
const fetchRejectionLogs = async () => {
  loadingRejectionLogs.value = true;
  try {
    const response = await axios.get('/payments/rejection-logs');
    const data = response.data;
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
    const response = await axios.get('/students/archived');
    let students = [];
    if (Array.isArray(response.data)) students = response.data;
    else if (response.data?.students) students = response.data.students;
    archivedStudents.value = students;
  } catch (error) {
    console.error('Error fetching archived students:', error);
    archivedStudents.value = [];
  } finally {
    loadingArchived.value = false;
  }
};

onMounted(() => fetchRejectionLogs());

// ===== NOTIFICATION / TABS =====
const toggleNotification = () => {
  isNotificationOpen.value = !isNotificationOpen.value;
  if (isNotificationOpen.value) fetchRejectionLogs();
};

const switchToRejectionsTab = () => {
  activeTab.value = activeTab.value === 'rejections' ? 'active' : 'rejections';
  if (activeTab.value === 'rejections') fetchRejectionLogs();
  isNotificationOpen.value = false;
};

const toggleArchiveTab = () => {
  if (activeTab.value === 'active') {
    activeTab.value = 'archived';
    fetchArchivedStudents();
  } else {
    activeTab.value = 'active';
  }
  clearFilters();
};

const handleRefresh = () => {
  if (activeTab.value === 'archived') fetchArchivedStudents();
  else if (activeTab.value === 'rejections') fetchRejectionLogs();
  else emit('refresh');
  fetchRejectionLogs();
};

// ===== HELPERS =====
const formatDateTime = (dateStr) => {
  if (!dateStr) return 'N/A';
  const date = new Date(dateStr);
  if (isNaN(date.getTime())) return dateStr;
  return new Intl.DateTimeFormat('en-PH', {
    month: 'short', day: 'numeric', year: 'numeric',
    hour: 'numeric', minute: '2-digit', hour12: true
  }).format(date);
};

const calculateRemainingBalance = (student) => {
  const totalFees = Number(student.total_fees || 0);
  const totalPaid = Number(student.total_paid || 0);
  return Math.max(0, totalFees - totalPaid);
};

const extractPaymentId = (student) => {
  return student.pending_payment_id || student.payment_id || student.pending_payment?.id || student.id;
};

// ===== APPROVE =====
const approvePayment = async (student) => {
  if (!isAuthorized.value) return alert('Not authorized.');
  const paymentId = extractPaymentId(student);
  if (!paymentId) return alert('Payment ID not found.');
  if (!confirm(`Approve payment of ₱${student.pending_amount || 0} for ${student.full_name}?`)) return;

  actionLoading[student.id] = true;
  try {
    const response = await axios.post(`/payments/${paymentId}/approve`);
    if (response.data.success) {
      alert('Payment approved!');
      emit('refresh');
    } else {
      alert(response.data.message || 'Failed.');
    }
  } catch (error) {
    alert(error.response?.data?.message || 'Error.');
  } finally {
    actionLoading[student.id] = false;
  }
};

// ===== REJECT =====
const openRejectModal = (student) => {
  if (!isAuthorized.value) return alert('Not authorized.');
  selectedRejectStudent.value = student;
  rejectionReason.value = '';
  isRejectModalOpen.value = true;
};

const closeRejectModal = () => {
  isRejectModalOpen.value = false;
  selectedRejectStudent.value = null;
  rejectionReason.value = '';
};

const confirmRejectPayment = async () => {
  if (!isAuthorized.value) return;
  if (!selectedRejectStudent.value) return;

  const student = selectedRejectStudent.value;
  const paymentId = extractPaymentId(student);
  if (!paymentId) return alert('Payment ID not found.');
  if (!rejectionReason.value.trim()) return alert('Enter a reason.');

  isSubmittingReject.value = true;
  actionLoading[student.id] = true;

  try {
    const response = await axios.post(`/payments/${paymentId}/reject`, {
      reason: rejectionReason.value.trim()
    });
    if (response.data.success) {
      alert('Payment rejected.');
      closeRejectModal();
      emit('refresh');
      fetchRejectionLogs();
    } else {
      alert(response.data.message || 'Failed.');
    }
  } catch (error) {
    alert(error.response?.data?.message || 'Error.');
  } finally {
    isSubmittingReject.value = false;
    actionLoading[student.id] = false;
  }
};

// ===== REJECTION DETAILS MODAL =====
const openRejectionDetailsModal = (item) => {
  selectedRejectionDetails.value = item;
  isNotificationOpen.value = false;
};

const proceedToRePay = (item) => {
  const student = props.students.find(s => s.id === item.student_id || s.student_id === item.student_id) || item;
  selectedRejectionDetails.value = null;
  emit('open-payment', student);
};

// ===== ARCHIVE / RESTORE / FORCE DELETE =====
const archiveStudent = async (student) => {
  if (!confirm(`Archive student ${student.full_name}?`)) return;
  try {
    const response = await axios.delete(`/students/${student.id}/archive`);
    if (response.data.success) {
      alert('Archived.');
      emit('refresh');
      if (isSuperAdmin.value) fetchArchivedStudents();
    }
  } catch (error) {
    alert('Error archiving.');
  }
};

const restoreStudent = async (student) => {
  if (!confirm(`Restore student ${student.full_name}?`)) return;
  actionLoading[student.id] = true;
  try {
    const response = await axios.post(`/students/${student.id}/restore`);
    if (response.data.success) {
      alert('Restored.');
      fetchArchivedStudents();
      emit('refresh');
    }
  } catch (error) {
    alert('Error restoring.');
  } finally {
    actionLoading[student.id] = false;
  }
};

const forceDeleteStudent = async (student) => {
  if (!isSuperAdmin.value) return alert('Only Superadmin.');

  if (!confirm(`⚠️ PERMANENTLY DELETE ${student.full_name} (${student.student_id})?\n\nCannot be undone!`)) return;
  if (!confirm(`Final warning: Click OK to DELETE ${student.full_name}.`)) return;

  actionLoading[student.id] = true;
  try {
    const response = await axios.delete(`/students/${student.id}/force-delete`);
    if (response.data.success) {
      alert(`Permanently deleted.`);
      fetchArchivedStudents();
      emit('refresh');
    }
  } catch (error) {
    alert(error.response?.data?.message || 'Error deleting.');
  } finally {
    actionLoading[student.id] = false;
  }
};

// ===== FILTER =====
const getPaymentStatus = (student) => {
  if (student.has_pending_payment) return 'pending';
  if (student.rejection_reason || student.payment_status === 'rejected' || student.is_rejected) return 'rejected';
  const paid = Number(student.total_paid || 0);
  const total = Number(student.total_fees || 0);
  if (paid <= 0) return 'unpaid';
  if (paid >= total) return 'paid';
  return 'partial';
};

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

const clearFilters = () => {
  searchQuery.value = '';
  filterYear.value = '';
  filterStatus.value = '';
};

// ===== EDIT MODAL =====
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
  if (isUpdating.value) return;
  isUpdating.value = true;
  try {
    const response = await axios.put(`/students/${selectedStudentId.value}`, editForm);
    if (response.data.success) {
      alert('Updated!');
      closeEditModal();
      emit('refresh');
    }
  } catch (error) {
    alert(error.response?.data?.message || 'Error.');
  } finally {
    isUpdating.value = false;
  }
};

// ===== PRINT SOA =====
const printSOA = (student) => {
  const fees = student.fees || student.fee_breakdown || [];
  const totalFees = Number(student.total_fees || 0);
  const totalPaid = Number(student.total_paid || 0);
  const balance = calculateRemainingBalance(student);
  const today = new Date().toLocaleDateString('en-PH', {
    year: 'numeric', month: 'long', day: 'numeric'
  });

  const feesRows = fees.length
    ? fees.map(f => `
        <tr>
          <td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;">${f.fee_name || f.description || ''}</td>
          <td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;text-align:right;font-family:monospace;">₱${Number(f.amount || 0).toFixed(2)}</td>
        </tr>`).join('')
    : `<tr><td colspan="2" style="padding:14px;text-align:center;color:#9ca3af;font-style:italic;">No fee breakdown available</td></tr>`;

  const statusText = balance <= 0 ? 'Fully Paid' : totalPaid > 0 ? 'Partial Paid' : 'Unpaid';

  const html = `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8" />
      <title>Statement of Account - ${student.full_name}</title>
      <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Arial, sans-serif; color: #1f2937; margin: 0; padding: 40px; background: #fff; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #1e3a8a; padding-bottom: 20px; margin-bottom: 24px; }
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
      </style>
    </head>
    <body>
      <div class="header">
        <div>
          <div class="school-name">FELPE VERALLO FOUNDATION COLLEGE INC.</div>
          <div class="school-sub">Office of the Treasurer & Accountancy</div>
        </div>
        <div style="text-align:right;">
          <h1 style="font-size:16px;margin:0;color:#1e3a8a;">STATEMENT OF ACCOUNT</h1>
          <p style="font-size:11px;color:#6b7280;margin-top:4px;">Date: ${today}</p>
        </div>
      </div>

      <div class="info-grid">
        <div><div class="label">Student Name</div><div class="value">${student.full_name}</div></div>
        <div><div class="label">Student ID</div><div class="value">${student.student_id}</div></div>
        <div><div class="label">Course & Year</div><div class="value">${student.course} - ${student.year_level}</div></div>
        <div><div class="label">Account Status</div><div class="value">${statusText}</div></div>
      </div>

      <table>
        <thead>
          <tr><th>Fee Description</th><th style="text-align:right;">Amount</th></tr>
        </thead>
        <tbody>
          ${feesRows}
        </tbody>
      </table>

      <div class="summary">
        <div class="item"><div class="lbl">Total Assessment</div><div class="val">₱${totalFees.toFixed(2)}</div></div>
        <div class="item"><div class="lbl">Approved Paid</div><div class="val">₱${totalPaid.toFixed(2)}</div></div>
        <div class="item"><div class="lbl">Remaining Balance</div><div class="val" style="color:#fca5a5;">₱${balance.toFixed(2)}</div></div>
      </div>

      <script>
        window.onload = function() { window.print(); window.close(); };
      <\/script>
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