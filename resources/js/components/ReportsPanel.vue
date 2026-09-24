<template>
  <div class="space-y-6 font-sans">

    <!-- HEADER -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
      <div class="px-6 py-5 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-gradient-to-br from-indigo-500 to-indigo-700 text-white rounded-xl text-lg shadow-md">📊</div>
          <div>
            <h2 class="text-xl font-bold text-gray-800">Reports Generation</h2>
            <p class="text-xs text-gray-500 mt-0.5">Generate standard accounting reports</p>
          </div>
        </div>
      </div>

      <!-- FILTERS -->
      <div class="p-6 bg-slate-50/60 border-b border-slate-100">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">

          <!-- PERIOD -->
          <div class="md:col-span-3">
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5 tracking-wider">Period</label>
            <select v-model="period" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none">
              <option value="weekly">📅 Weekly</option>
              <option value="monthly">📆 Monthly</option>
              <option value="yearly">🗓️ Yearly</option>
              <option value="custom">⚙️ Custom Range</option>
            </select>
          </div>

          <!-- TERM -->
          <div class="md:col-span-2">
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5 tracking-wider">Term</label>
            <select v-model="term" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none">
              <option value="all">📚 All Terms</option>
              <option value="prelim">1️⃣ Prelim</option>
              <option value="midterm">2️⃣ Midterm</option>
              <option value="semi-final">3️⃣ Semi-Final</option>
              <option value="final">4️⃣ Final</option>
            </select>
          </div>

          <!-- COURSE -->
          <div class="md:col-span-3">
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5 tracking-wider">Course</label>
            <select v-model="selectedCourse" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none">
              <option value="">🏫 All Courses</option>
              <optgroup label="🎓 Undergraduate Programs">
                <option v-for="c in undergraduateCourses" :key="c.value" :value="c.value">
                  {{ c.label }}
                </option>
              </optgroup>
            </select>
          </div>

          <!-- CUSTOM FROM -->
          <div v-if="period === 'custom'" class="md:col-span-2">
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5 tracking-wider">From Date</label>
            <input v-model="fromDate" type="date" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
          </div>

          <!-- CUSTOM TO -->
          <div v-if="period === 'custom'" class="md:col-span-2">
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1.5 tracking-wider">To Date</label>
            <input v-model="toDate" type="date" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm bg-white focus:ring-2 focus:ring-indigo-500 focus:outline-none" />
          </div>

          <!-- GENERATE BUTTON -->
          <div :class="period === 'custom' ? 'md:col-span-2' : 'md:col-span-4'" class="flex items-end">
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

        <!-- REPORT INFO BAR -->
        <div v-if="report" class="mt-4 flex flex-wrap items-center gap-3">
          <span class="px-3 py-1.5 rounded-full bg-indigo-100 text-indigo-800 text-xs font-bold">
            📅 {{ formatDate(report.from) }} → {{ formatDate(report.to) }}
          </span>
          <span class="px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 text-xs font-bold uppercase">
            {{ report.period }} Report
          </span>

          <!-- TERM BADGE -->
          <span v-if="report.term_filter && report.term_filter !== 'all'"
                class="px-3 py-1.5 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold uppercase flex items-center gap-1">
            {{ termLabel(report.term_filter) }} Term
            <button @click="term = 'all'; generateReport()" class="ml-1 hover:text-rose-600 font-bold">✕</button>
          </span>
          <span v-else class="px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 text-xs font-bold">
            📚 All Terms
          </span>

          <!-- COURSE BADGE -->
          <span v-if="report.course_filter" class="px-3 py-1.5 rounded-full bg-purple-100 text-purple-800 text-xs font-bold flex items-center gap-1">
            🎓 {{ report.course_filter }}
            <button @click="selectedCourse = ''; generateReport()" class="ml-1 hover:text-rose-600 font-bold">✕</button>
          </span>
          <span v-else class="px-3 py-1.5 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold">
            🏫 All Courses
          </span>

          <button @click="printReport" class="ml-auto px-4 py-1.5 rounded-xl bg-gradient-to-r from-slate-700 to-slate-900 text-white text-xs font-bold shadow-sm active:scale-95 flex items-center gap-1">
            🖨️ Print
          </button>
        </div>
      </div>
    </div>

    <!-- REPORT CONTENT -->
    <div v-if="report" id="report-print-area">

      <!-- ACCOUNTING SUMMARY -->
      <div v-if="report.accounting_summary" class="mb-6">
        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider mb-3 flex items-center gap-2">
          <span>💰</span> Overall Summary
        </h3>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="bg-gradient-to-br from-emerald-600 to-emerald-800 rounded-2xl p-5 text-white shadow-lg">
            <p class="text-[10px] font-bold uppercase tracking-widest text-emerald-200">Total Payments</p>
            <p class="text-xl font-bold font-mono mt-2">₱{{ formatMoney(report.accounting_summary.total_collection) }}</p>
            <p class="text-xs text-emerald-200 mt-1">{{ report.accounting_summary.collection_rate }}% of total fees</p>
          </div>

          <div class="bg-gradient-to-br from-rose-600 to-rose-800 rounded-2xl p-5 text-white shadow-lg">
            <p class="text-[10px] font-bold uppercase tracking-widest text-rose-200">Total Unpaid</p>
            <p class="text-xl font-bold font-mono mt-2">₱{{ formatMoney(report.accounting_summary.total_receivable) }}</p>
            <p class="text-xs text-rose-200 mt-1">{{ report.accounting_summary.students_with_balance }} student(s) with balance</p>
          </div>

          <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-2xl p-5 text-white shadow-lg">
            <p class="text-[10px] font-bold uppercase tracking-widest text-blue-200">Total Fees</p>
            <p class="text-xl font-bold font-mono mt-2">₱{{ formatMoney(report.accounting_summary.total_assessment) }}</p>
            <p class="text-xs text-blue-200 mt-1">All enrolled students</p>
          </div>

          <div class="bg-gradient-to-br from-amber-600 to-amber-800 rounded-2xl p-5 text-white shadow-lg">
            <p class="text-[10px] font-bold uppercase tracking-widest text-amber-200">Pending Payments</p>
            <p class="text-xl font-bold font-mono mt-2">₱{{ formatMoney(report.accounting_summary.total_pending) }}</p>
            <p class="text-xs text-amber-200 mt-1">{{ report.accounting_summary.fully_paid_students }} fully paid</p>
          </div>
        </div>
      </div>

      <!-- TERM SUMMARY (only when a specific term is selected) -->
      <div v-if="report.term_summary && report.term_filter && report.term_filter !== 'all'"
           class="bg-gradient-to-r from-indigo-50 to-white rounded-2xl shadow-sm border border-indigo-200 p-6 mb-6">
        <h3 class="text-sm font-bold text-indigo-900 uppercase tracking-wider mb-4 flex items-center gap-2">
          <span>📊</span> {{ termLabel(report.term_filter) }} — Summary
        </h3>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="bg-white rounded-xl p-4 border border-indigo-100">
            <p class="text-[10px] font-bold uppercase tracking-widest text-indigo-600">Fees This Term</p>
            <p class="text-xl font-bold font-mono mt-2 text-indigo-900">₱{{ formatMoney(report.term_summary.amount) }}</p>
            <p class="text-xs text-slate-500 mt-1">Amount to be paid</p>
          </div>

          <div class="bg-white rounded-xl p-4 border border-indigo-100">
            <p class="text-[10px] font-bold uppercase tracking-widest text-emerald-600">Paid</p>
            <p class="text-xl font-bold font-mono mt-2 text-emerald-700">₱{{ formatMoney(report.term_summary.collected) }}</p>
            <p class="text-xs text-slate-500 mt-1">{{ report.term_summary.collection_rate }}% of fees</p>
          </div>

          <div class="bg-white rounded-xl p-4 border border-indigo-100">
            <p class="text-[10px] font-bold uppercase tracking-widest text-rose-600">Total Unpaid</p>
            <p class="text-xl font-bold font-mono mt-2 text-rose-700">₱{{ formatMoney(report.term_summary.balance) }}</p>
            <p class="text-xs text-slate-500 mt-1">Up to this term</p>
          </div>

          <div class="bg-white rounded-xl p-4 border border-indigo-100">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-600">Students</p>
            <p class="text-xl font-bold font-mono mt-2 text-slate-800">{{ report.term_summary.student_count }}</p>
            <p class="text-xs text-slate-500 mt-1">Enrolled this term</p>
          </div>
        </div>
      </div>

      <!-- TERM BREAKDOWN (shown when "All Terms" is selected) -->
      <div v-if="report.term_breakdown && report.term_breakdown.length > 0 && (!report.term_filter || report.term_filter === 'all')"
           class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
          <h3 class="font-bold text-slate-800 flex items-center gap-2">
            <span>🧾</span> Term Breakdown
          </h3>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-slate-50/80">
              <tr>
                <th class="px-4 py-3 text-left text-[11px] font-bold text-gray-500 uppercase">Term</th>
                <th class="px-4 py-3 text-right text-[11px] font-bold text-gray-500 uppercase">Fees</th>
                <th class="px-4 py-3 text-right text-[11px] font-bold text-gray-500 uppercase">Paid</th>
                <th class="px-4 py-3 text-right text-[11px] font-bold text-gray-500 uppercase">Total Unpaid</th>
                <th class="px-4 py-3 text-center text-[11px] font-bold text-gray-500 uppercase">% Paid</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm bg-white">
              <tr v-for="t in report.term_breakdown" :key="t.term" class="hover:bg-indigo-50/30 transition">
                <td class="px-4 py-3 font-semibold text-slate-800 text-xs">{{ termLabel(t.term) }}</td>
                <td class="px-4 py-3 text-right font-mono text-slate-700">₱{{ formatMoney(t.assessment) }}</td>
                <td class="px-4 py-3 text-right font-mono font-bold text-emerald-700">₱{{ formatMoney(t.collected) }}</td>
                <td class="px-4 py-3 text-right font-mono font-bold text-rose-700">₱{{ formatMoney(t.balance) }}</td>
                <td class="px-4 py-3 text-center">
                  <span :class="rateBadgeClass(t.rate)" class="px-2.5 py-1 rounded-full text-xs font-bold">{{ t.rate }}%</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- I. MONTHLY COLLECTION SUMMARY PER COURSE -->
      <div v-if="report.per_course && report.per_course.length > 0" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white flex justify-between items-center">
          <h3 class="font-bold text-slate-800 flex items-center gap-2">
            <span>📊</span> I. Collection Summary per Course
          </h3>
          <span class="text-xs text-slate-500">{{ report.per_course.length }} course(s)</span>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-slate-50/80">
              <tr>
                <th class="px-4 py-3 text-left text-[11px] font-bold text-gray-500 uppercase">Course</th>
                <th class="px-4 py-3 text-center text-[11px] font-bold text-gray-500 uppercase">Students</th>
                <th class="px-4 py-3 text-right text-[11px] font-bold text-gray-500 uppercase">Fees</th>
                <th class="px-4 py-3 text-right text-[11px] font-bold text-gray-500 uppercase">Paid</th>
                <th class="px-4 py-3 text-right text-[11px] font-bold text-gray-500 uppercase">Total Unpaid</th>
                <th class="px-4 py-3 text-center text-[11px] font-bold text-gray-500 uppercase">% Paid</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm bg-white">
              <tr v-for="course in report.per_course" :key="course.course" class="hover:bg-indigo-50/30 transition">
                <td class="px-4 py-3 font-semibold text-slate-800 text-xs">{{ course.course }}</td>
                <td class="px-4 py-3 text-center">
                  <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 text-xs font-bold">{{ course.student_count }}</span>
                </td>
                <td class="px-4 py-3 text-right font-mono text-slate-700">₱{{ formatMoney(course.total_assessment) }}</td>
                <td class="px-4 py-3 text-right font-mono font-bold text-emerald-700">₱{{ formatMoney(course.collection_in_range) }}</td>
                <td class="px-4 py-3 text-right font-mono font-bold text-rose-700">₱{{ formatMoney(course.receivable) }}</td>
                <td class="px-4 py-3 text-center">
                  <span :class="rateBadgeClass(course.collection_rate)" class="px-2.5 py-1 rounded-full text-xs font-bold">{{ course.collection_rate }}%</span>
                </td>
              </tr>
              <tr class="bg-slate-100 font-bold">
                <td class="px-4 py-3 text-xs uppercase">Total</td>
                <td class="px-4 py-3 text-center font-mono">{{ totalCourseStudents }}</td>
                <td class="px-4 py-3 text-right font-mono">₱{{ formatMoney(totalCourseAssessment) }}</td>
                <td class="px-4 py-3 text-right font-mono text-emerald-800">₱{{ formatMoney(totalCourseCollection) }}</td>
                <td class="px-4 py-3 text-right font-mono text-rose-800">₱{{ formatMoney(totalCourseReceivable) }}</td>
                <td class="px-4 py-3 text-center">
                  <span :class="rateBadgeClass(Number(overallCourseRate))" class="px-2.5 py-1 rounded-full text-xs font-bold">{{ overallCourseRate }}%</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- II. ENROLLMENT AND FEE REALIZATION REPORT -->
      <div v-if="report.enrollment_realization && report.enrollment_realization.length > 0" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
          <h3 class="font-bold text-slate-800 flex items-center gap-2">
            <span>🎯</span> II. Enrollment and Payment Report
          </h3>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-slate-50/80">
              <tr>
                <th class="px-4 py-3 text-left text-[11px] font-bold text-gray-500 uppercase">Course</th>
                <th class="px-4 py-3 text-center text-[11px] font-bold text-gray-500 uppercase">Students</th>
                <th class="px-4 py-3 text-right text-[11px] font-bold text-gray-500 uppercase">Expected</th>
                <th class="px-4 py-3 text-right text-[11px] font-bold text-gray-500 uppercase">Paid</th>
                <th class="px-4 py-3 text-right text-[11px] font-bold text-gray-500 uppercase">Shortfall</th>
                <th class="px-4 py-3 text-center text-[11px] font-bold text-gray-500 uppercase">% Paid</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm bg-white">
              <tr v-for="row in report.enrollment_realization" :key="row.course" class="hover:bg-indigo-50/30 transition">
                <td class="px-4 py-3 font-semibold text-slate-800 text-xs">{{ row.course }}</td>
                <td class="px-4 py-3 text-center font-mono">{{ row.student_count }}</td>
                <td class="px-4 py-3 text-right font-mono text-slate-700">₱{{ formatMoney(row.expected) }}</td>
                <td class="px-4 py-3 text-right font-mono font-bold text-emerald-700">₱{{ formatMoney(row.actual) }}</td>
                <td class="px-4 py-3 text-right font-mono text-rose-700">₱{{ formatMoney(row.variance) }}</td>
                <td class="px-4 py-3 text-center">
                  <span :class="rateBadgeClass(row.realization_rate)" class="px-2.5 py-1 rounded-full text-xs font-bold">{{ row.realization_rate }}%</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- III. STUDENT LIST -->
      <div v-if="report.student_list && report.student_list.students && report.student_list.students.length > 0" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-indigo-50 to-white flex justify-between items-center gap-3">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-gradient-to-br from-indigo-500 to-indigo-700 text-white rounded-xl text-sm shadow-md">🎓</div>
            <div>
              <h3 class="font-bold text-slate-800">
                III. Student List — {{ report.course_filter || 'All Courses' }}
                <span v-if="report.term_filter && report.term_filter !== 'all'" class="text-indigo-600">
                  ({{ termLabel(report.term_filter) }})
                </span>
              </h3>
              <p class="text-xs text-slate-500 mt-0.5">
                {{ report.student_list.student_count }} student(s) enrolled
              </p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-xs text-slate-500">
              Total Unpaid: <strong class="text-rose-700 font-mono">₱{{ formatMoney(report.student_list.total_balance) }}</strong>
            </span>
            <button
              @click="printStudentList"
              class="px-4 py-2 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-800 hover:from-indigo-700 hover:to-indigo-900 text-white text-xs font-bold shadow-md active:scale-95 flex items-center gap-1.5"
            >
              🖨️ Print Student List
            </button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-slate-50/80">
              <tr>
                <th class="px-4 py-3 text-left text-[11px] font-bold text-gray-500 uppercase">#</th>
                <th class="px-4 py-3 text-left text-[11px] font-bold text-gray-500 uppercase">Name</th>
                <th class="px-4 py-3 text-left text-[11px] font-bold text-gray-500 uppercase">Course</th>
                <th class="px-4 py-3 text-right text-[11px] font-bold text-gray-500 uppercase">Total Fees</th>
                <th class="px-4 py-3 text-right text-[11px] font-bold text-gray-500 uppercase">Fees This Term</th>
                <th class="px-4 py-3 text-right text-[11px] font-bold text-gray-500 uppercase">Paid</th>
                <th class="px-4 py-3 text-right text-[11px] font-bold text-gray-500 uppercase">Total Unpaid</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm bg-white">
              <tr
                v-for="(student, index) in report.student_list.students"
                :key="student.student_id"
                class="hover:bg-indigo-50/30 transition"
              >
                <td class="px-4 py-3 text-xs text-slate-500 font-mono">{{ index + 1 }}</td>
                <td class="px-4 py-3">
                  <div class="font-semibold text-slate-800">{{ student.full_name }}</div>
                  <div class="text-[10px] text-slate-500 font-mono">{{ student.student_id }} • {{ student.year_level }}</div>
                </td>
                <td class="px-4 py-3 text-xs text-slate-600">{{ student.course }}</td>
                <td class="px-4 py-3 text-right font-mono text-slate-700">₱{{ formatMoney(student.total_assessment) }}</td>
                <td class="px-4 py-3 text-right font-mono text-indigo-700">₱{{ formatMoney(student.term_amount) }}</td>
                <td class="px-4 py-3 text-right font-mono font-bold text-emerald-700">₱{{ formatMoney(student.term_paid) }}</td>
                <td class="px-4 py-3 text-right font-mono font-bold text-rose-700">
                  ₱{{ formatMoney(student.term_unpaid) }}
                </td>
              </tr>
            </tbody>
            <tfoot class="bg-slate-100 font-bold">
              <tr>
                <td colspan="3" class="px-4 py-3 text-xs uppercase text-slate-700">Total</td>
                <td class="px-4 py-3 text-right font-mono text-slate-800">₱{{ formatMoney(report.student_list.total_assessment) }}</td>
                <td class="px-4 py-3 text-right font-mono text-slate-500">—</td>
                <td class="px-4 py-3 text-right font-mono text-emerald-800">₱{{ formatMoney(report.student_list.total_paid) }}</td>
                <td class="px-4 py-3 text-right font-mono text-rose-800">₱{{ formatMoney(report.student_list.total_balance) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- IV. COLLECTION BY PAYMENT METHOD -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
          <h3 class="font-bold text-slate-800 flex items-center gap-2">
            <span>💵</span> IV. Payment Methods
          </h3>
        </div>
        <div class="p-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
          <div v-for="(m, i) in (report.method_breakdown || [])" :key="i" class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200">
            <div class="flex items-center gap-3">
              <span class="text-xl">{{ methodIcon(m.method) }}</span>
              <div>
                <p class="font-semibold text-slate-800 text-sm">{{ methodLabel(m.method) }}</p>
                <p class="text-xs text-slate-500">{{ m.count }} payment(s)</p>
              </div>
            </div>
            <span class="font-mono font-bold text-emerald-700 text-sm">₱{{ formatMoney(m.total) }}</span>
          </div>
          <div v-if="!report.method_breakdown || report.method_breakdown.length === 0" class="col-span-full p-6 text-center text-slate-400 italic text-sm">
            No payment data.
          </div>
        </div>
      </div>

      <!-- V. TRANSACTION DETAILS -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white flex justify-between items-center">
          <h3 class="font-bold text-slate-800 flex items-center gap-2">
            <span>📋</span> V. Payment Transactions
          </h3>
          <span class="text-xs text-slate-500">{{ (report.transactions || []).length }} record(s)</span>
        </div>
        <div class="overflow-x-auto max-h-96 overflow-y-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-slate-50/80 sticky top-0">
              <tr>
                <th class="px-4 py-3 text-left text-[11px] font-bold text-gray-500 uppercase">Date</th>
                <th class="px-4 py-3 text-left text-[11px] font-bold text-gray-500 uppercase">Student</th>
                <th class="px-4 py-3 text-left text-[11px] font-bold text-gray-500 uppercase">Course</th>
                <th class="px-4 py-3 text-left text-[11px] font-bold text-gray-500 uppercase">OR #</th>
                <th class="px-4 py-3 text-right text-[11px] font-bold text-gray-500 uppercase">Amount</th>
                <th class="px-4 py-3 text-center text-[11px] font-bold text-gray-500 uppercase">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm bg-white">
              <tr v-for="t in (report.transactions || [])" :key="t.id" class="hover:bg-indigo-50/30 transition">
                <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">{{ formatDate(t.encoded_at) }}</td>
                <td class="px-4 py-3">
                  <div class="font-semibold text-slate-800 text-xs">{{ t.student_name }}</div>
                  <div class="text-[10px] text-slate-500 font-mono">{{ t.student_id }}</div>
                </td>
                <td class="px-4 py-3 text-xs text-slate-600">{{ t.course }}</td>
                <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ t.or_number || '—' }}</td>
                <td class="px-4 py-3 text-right font-mono font-bold text-slate-800">₱{{ formatMoney(t.amount) }}</td>
                <td class="px-4 py-3 text-center">
                  <span :class="statusClass(t.status)" class="text-[10px] px-2 py-0.5 rounded-full font-bold uppercase">{{ statusLabel(t.status) }}</span>
                </td>
              </tr>
              <tr v-if="!report.transactions || report.transactions.length === 0">
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
import { ref, computed } from 'vue';
import axios from 'axios';

/* ------------------------------------------------------------------ */
/*  COURSES                                                            */
/* ------------------------------------------------------------------ */
const undergraduateCourses = [
  { value: 'BSTM (Bachelor of Science in Tourism Management)',      label: 'BSTM (Bachelor of Science in Tourism Management)' },
  { value: 'BSHM (Bachelor of Science in Hospitality Management)',  label: 'BSHM (Bachelor of Science in Hospitality Management)' },
  { value: 'BSBA (Bachelor of Science in Business Administration)', label: 'BSBA (Bachelor of Science in Business Administration)' },
  { value: 'BSCS (Bachelor of Science in Computer Science)',         label: 'BSCS (Bachelor of Science in Computer Science)' },
  { value: 'BSED (Bachelor of Science in Education)',                label: 'BSED (Bachelor of Science in Education)' },
  { value: 'BEED (Bachelor of Elementary Education)',                label: 'BEED (Bachelor of Elementary Education)' },
  { value: 'BSPT (Bachelor of Science in Physical Therapy)',         label: 'BSPT (Bachelor of Science in Physical Therapy)' },
  { value: 'BSM (Bachelor of Science in Midwifery)',                 label: 'BSM (Bachelor of Science in Midwifery)' },
];

/* ------------------------------------------------------------------ */
/*  STATE                                                              */
/* ------------------------------------------------------------------ */
const period = ref('monthly');
const term = ref('all');
const selectedCourse = ref('');
const fromDate = ref('');
const toDate = ref('');
const loading = ref(false);
const report = ref(null);

/* ------------------------------------------------------------------ */
/*  COMPUTED TOTALS                                                    */
/* ------------------------------------------------------------------ */
const totalCourseStudents = computed(() =>
  report.value?.per_course?.reduce((s, c) => s + (Number(c.student_count) || 0), 0) || 0
);
const totalCourseAssessment = computed(() =>
  report.value?.per_course?.reduce((s, c) => s + (Number(c.total_assessment) || 0), 0) || 0
);
const totalCourseCollection = computed(() =>
  report.value?.per_course?.reduce((s, c) => s + (Number(c.collection_in_range) || 0), 0) || 0
);
const totalCourseReceivable = computed(() =>
  report.value?.per_course?.reduce((s, c) => s + (Number(c.receivable) || 0), 0) || 0
);
const overallCourseRate = computed(() => {
  if (totalCourseAssessment.value <= 0) return 0;
  return ((totalCourseCollection.value / totalCourseAssessment.value) * 100).toFixed(1);
});

/* ------------------------------------------------------------------ */
/*  HELPERS                                                            */
/* ------------------------------------------------------------------ */
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

const termLabel = (value) => {
  const map = {
    prelim: 'Prelim',
    midterm: 'Midterm',
    'semi-final': 'Semi-Final',
    final: 'Final',
    all: 'All Terms',
  };
  return map[value] ?? (value || '—');
};

const methodLabel = (value) => {
  const map = {
    cash: 'Cash',
    bank_transfer: 'Bank Transfer',
    gcash: 'GCash',
    paymaya: 'PayMaya',
    check: 'Check',
  };
  return map[value] ?? (value || 'Cash');
};

const statusLabel = (value) => {
  const map = {
    approved: 'Approved',
    pending: 'Pending',
    rejected: 'Rejected',
  };
  return map[value] ?? (value || 'Unknown');
};

const statusClass = (status) => ({
  approved: 'bg-emerald-100 text-emerald-800 border border-emerald-200',
  pending:  'bg-amber-100 text-amber-800 border border-amber-200',
  rejected: 'bg-rose-100 text-rose-800 border border-rose-200',
}[status] || 'bg-slate-100 text-slate-700');

const rateBadgeClass = (rate) => {
  const r = Number(rate) || 0;
  if (r >= 80) return 'bg-emerald-100 text-emerald-800';
  if (r >= 50) return 'bg-amber-100 text-amber-800';
  if (r >= 20) return 'bg-orange-100 text-orange-800';
  return 'bg-rose-100 text-rose-800';
};

const methodIcon = (method) => ({
  cash: '💵',
  bank_transfer: '🏦',
  gcash: '📱',
  paymaya: '📱',
  check: '🧾',
}[method] || '💳');

/* ------------------------------------------------------------------ */
/*  GENERATE REPORT                                                    */
/* ------------------------------------------------------------------ */
const generateReport = async () => {
  loading.value = true;
  report.value = null;

  try {
    const params = { period: period.value };

    if (term.value && term.value !== 'all') {
      params.term = term.value;
    }

    if (period.value === 'custom') {
      if (!fromDate.value || !toDate.value) {
        alert('Please select both From and To dates.');
        loading.value = false;
        return;
      }
      params.from = fromDate.value;
      params.to = toDate.value;
    }

    if (selectedCourse.value) {
      params.course = selectedCourse.value;
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

/* ------------------------------------------------------------------ */
/*  PRINT MAIN REPORT                                                  */
/* ------------------------------------------------------------------ */
const printReport = () => {
  if (!report.value) return;
  window.print();
};

/* ------------------------------------------------------------------ */
/*  PRINT STUDENT LIST (standalone window)                             */
/* ------------------------------------------------------------------ */
const printStudentList = () => {
  if (!report.value || !report.value.student_list) return;

  const students = report.value.student_list.students || [];
  const courseName = report.course_filter || 'All Courses';
  const termName = (report.value.term_filter && report.value.term_filter !== 'all')
    ? `${termLabel(report.value.term_filter)} Term`
    : 'All Terms';
  const today = new Date().toLocaleDateString('en-PH', {
    year: 'numeric', month: 'long', day: 'numeric'
  });

  const rows = students.map((s, i) => `
    <tr>
      <td style="padding:8px;border:1px solid #cbd5e1;text-align:center;font-family:monospace;font-size:11px;">${i + 1}</td>
      <td style="padding:8px;border:1px solid #cbd5e1;font-size:11px;">
        <strong>${s.full_name}</strong><br>
        <span style="font-size:9px;color:#64748b;font-family:monospace;">${s.student_id} • ${s.year_level}</span>
      </td>
      <td style="padding:8px;border:1px solid #cbd5e1;font-size:11px;">${s.course}</td>
      <td style="padding:8px;border:1px solid #cbd5e1;text-align:right;font-family:monospace;font-size:11px;">₱${formatMoney(s.total_assessment)}</td>
      <td style="padding:8px;border:1px solid #cbd5e1;text-align:right;font-family:monospace;font-size:11px;">₱${formatMoney(s.term_amount)}</td>
      <td style="padding:8px;border:1px solid #cbd5e1;text-align:right;font-family:monospace;font-size:11px;color:#059669;">₱${formatMoney(s.term_paid)}</td>
      <td style="padding:8px;border:1px solid #cbd5e1;text-align:right;font-family:monospace;font-size:11px;font-weight:bold;color:#dc2626;">₱${formatMoney(s.term_unpaid)}</td>
    </tr>
  `).join('');

  const html = `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <title>Student List - ${courseName}</title>
      <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        @page { size: A4 landscape; margin: 12mm; }
        body {
          font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
          color: #1f2937;
          padding: 20px;
          -webkit-print-color-adjust: exact;
          print-color-adjust: exact;
        }
        .header {
          display: flex; justify-content: space-between; align-items: center;
          border-bottom: 3px double #1e3a8a; padding-bottom: 12px; margin-bottom: 16px;
        }
        .school-name { font-size: 16px; font-weight: 800; color: #1e3a8a; text-transform: uppercase; letter-spacing: 0.5px; }
        .school-sub { font-size: 10px; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; margin-top: 2px; }
        .doc-title { text-align: right; }
        .doc-title h1 { font-size: 14px; color: #1e3a8a; margin: 0; text-transform: uppercase; letter-spacing: 1px; }
        .doc-title p { font-size: 10px; color: #6b7280; margin-top: 2px; }
        .report-title { text-align: center; margin: 20px 0 16px 0; }
        .report-title h2 { font-size: 18px; font-weight: 800; color: #111827; text-transform: uppercase; letter-spacing: 1px; }
        .report-title p { font-size: 12px; color: #6b7280; margin-top: 4px; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 20px; }
        thead { background: #1e3a8a; color: white; }
        thead th {
          padding: 10px 8px; text-align: left; font-size: 10px;
          text-transform: uppercase; letter-spacing: 0.5px;
          border: 1px solid #1e3a8a; font-weight: 700;
        }
        thead th:nth-child(n+4) { text-align: right; }
        .footer {
          margin-top: 24px; padding-top: 12px;
          border-top: 1px dashed #cbd5e1;
          display: flex; justify-content: space-between;
          font-size: 9px; color: #9ca3af;
        }
        @media print { body { padding: 0; } .no-print { display: none !important; } }
      </style>
    </head>
    <body>

      <div class="header">
        <div>
          <div class="school-name">Felipe Verallo Foundation College Inc.</div>
          <div class="school-sub">Office of the Treasurer &amp; Accountancy</div>
        </div>
        <div class="doc-title">
          <h1>Student List Report</h1>
          <p>Date: ${today}</p>
        </div>
      </div>

      <div class="report-title">
        <h2>🎓 Student List — ${courseName}</h2>
        <p>${termName} • ${report.value.student_list.student_count} student(s) enrolled</p>
      </div>

      <table>
        <thead>
          <tr>
            <th style="text-align:center;width:40px;">#</th>
            <th>Name</th>
            <th>Course</th>
            <th>Total Fees</th>
            <th>Fees This Term</th>
            <th>Paid</th>
            <th>Total Unpaid</th>
          </tr>
        </thead>
        <tbody>
          ${rows || '<tr><td colspan="7" style="padding:20px;text-align:center;color:#9ca3af;font-style:italic;">No students found.</td></tr>'}
        </tbody>
      </table>

      <div style="margin-top:60px;display:grid;grid-template-columns:1fr 1fr;gap:80px;">
        <div style="text-align:center;">
          <div style="border-top:1.5px solid #1f2937;margin-bottom:6px;"></div>
          <div style="font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;">Prepared by</div>
          <div style="font-size:9px;color:#6b7280;margin-top:2px;">Accounting / Authorized Personnel</div>
        </div>
        <div style="text-align:center;">
          <div style="border-top:1.5px solid #1f2937;margin-bottom:6px;"></div>
          <div style="font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:0.5px;">Received by</div>
          <div style="font-size:9px;color:#6b7280;margin-top:2px;">Department Chairman</div>
        </div>
      </div>

      <div class="footer">
        <div>FVFC Accounting System — Student List Report</div>
        <div>Printed on ${new Date().toLocaleString('en-PH')}</div>
      </div>

      <script>
        window.onload = function() { window.print(); };
      <\/script>
    </body>
    </html>
  `;

  const printWindow = window.open('', '_blank', 'width=1200,height=800');
  if (printWindow) {
    printWindow.document.open();
    printWindow.document.write(html);
    printWindow.document.close();
  }
};
</script>

<style scoped>
@media print {
  @page {
    size: A4 landscape;
    margin: 10mm;
  }

  button,
  select,
  input {
    display: none !important;
  }

  .bg-white {
    background: white !important;
    box-shadow: none !important;
    border: 1px solid #ddd !important;
    page-break-inside: avoid;
    margin-bottom: 12px !important;
  }

  .from-emerald-600,
  .from-rose-600,
  .from-blue-600,
  .from-amber-600 {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  body {
    font-size: 10px !important;
  }
}
</style>