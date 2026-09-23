<template>
  <div class="min-h-screen bg-gray-100">

    <!-- TABS NAVIGATION -->
    <div class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-6">
        <div class="flex items-center gap-1 overflow-x-auto">
          <button
            v-for="tab in visibleTabs"
            :key="tab.key"
            @click="activeSection = tab.key"
            :class="[
              'px-5 py-4 text-sm font-semibold transition-all flex items-center gap-2 whitespace-nowrap border-b-3',
              activeSection === tab.key
                ? 'text-blue-700 border-blue-700 bg-blue-50/50'
                : 'text-gray-600 border-transparent hover:text-blue-700 hover:bg-blue-50/30'
            ]"
          >
            <span class="text-lg">{{ tab.icon }}</span>
            <span>{{ tab.label }}</span>
          </button>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto p-6">

      <!-- DASHBOARD TAB -->
      <DashboardStats
        v-if="activeSection === 'dashboard'"
        :user-role="userRole"
        @navigate="handleNavigate"
      />

      <!-- ENROLL TAB -->
      <EnrollForm
        v-else-if="activeSection === 'enroll'"
        :user-role="userRole"
        @enrolled="handleEnrollSuccess"
      />

      <!-- STUDENTS TAB -->
      <StudentsList
        v-else-if="activeSection === 'students'"
        :students="students"
        :loading="loading"
        :user-role="userRole"
        :initial-filter-status="initialStudentFilter"
        @refresh="fetchStudents"
        @open-breakdown="handleBreakdown"
        @open-payment="handlePayment"
        @filter-applied="initialStudentFilter = ''"
      />

      <!-- REPORTS TAB -->
      <ReportsPanel
        v-else-if="activeSection === 'reports'"
        :user-role="userRole"
      />

      <!-- USERS TAB -->
      <UserManagement
        v-else-if="activeSection === 'users'"
        :user-role="userRole"
        :current-user-id="currentUserId"
      />

    </div>

    <!-- PAYMENT MODAL -->
    <PaymentModal
      v-if="selectedStudent"
      :student="selectedStudent"
      @close="selectedStudent = null"
      @success="handlePaymentSuccess"
    />

    <!-- FEE BREAKDOWN MODAL -->
    <FeeBreakdownModal
      v-if="breakdownStudent"
      :student="breakdownStudent"
      :user-role="userRole"
      @close="breakdownStudent = null"
      @updated="handleBreakdownUpdate"
    />

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import StudentsList from './Students/StudentsList.vue';
import EnrollForm from './EnrollForm.vue';
import PaymentModal from './Students/modals/PaymentModal.vue';
import FeeBreakdownModal from './Students/modals/FeeBreakdownModal.vue';
import DashboardStats from './DashboardStats.vue';
import UserManagement from './UserManagement.vue';
import ReportsPanel from './ReportsPanel.vue';

const students = ref([]);
const loading = ref(false);
const userRole = ref('');
const currentUserId = ref(0);
const activeSection = ref('dashboard');
const selectedStudent = ref(null);
const breakdownStudent = ref(null);

// For auto-filter when clicking Pending card
const initialStudentFilter = ref('');

const allTabs = [
  { key: 'dashboard', label: 'Dashboard', icon: '📊', roles: ['superadmin', 'admin', 'accounting', 'encoder'] },
  { key: 'enroll', label: 'Enroll New Student', icon: '📝', roles: ['superadmin', 'admin', 'accounting', 'encoder'] },
  { key: 'students', label: 'View Students', icon: '🎓', roles: ['superadmin', 'admin', 'accounting', 'encoder'] },
  { key: 'reports', label: 'Reports', icon: '📈', roles: ['superadmin', 'admin', 'accounting'] },
  { key: 'users', label: 'User Management', icon: '👥', roles: ['superadmin'] },
];

const visibleTabs = computed(() => {
  const role = (userRole.value || '').toLowerCase().trim();
  return allTabs.filter(tab => !tab.roles || tab.roles.includes(role) || !role);
});

const fetchStudents = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/students-json');
    students.value = res.data;
    console.log('✅ Students loaded:', students.value.length);
  } catch (err) {
    console.error('❌ Error fetching students:', err);
  } finally {
    loading.value = false;
  }
};

// ✅ AUTO-REFRESH students list when new student enrolled
const handleEnrollSuccess = () => {
  console.log('🎉 New student enrolled — refreshing students list...');
  fetchStudents();
};

// HANDLE NAVIGATION
const handleNavigate = (section) => {
  console.log('🧭 Navigate to:', section);

  if (section === 'students-pending') {
    activeSection.value = 'students';
    initialStudentFilter.value = 'pending';
    return;
  }

  activeSection.value = section;
};

// HANDLE BREAKDOWN
const handleBreakdown = (student) => {
  console.log('📋 Open breakdown for:', student);
  breakdownStudent.value = student;
};

const handleBreakdownUpdate = () => {
  fetchStudents();
};

const handlePayment = (student) => {
  selectedStudent.value = student;
};

const handlePaymentSuccess = () => {
  selectedStudent.value = null;
  fetchStudents();
};

onMounted(() => {
  const el = document.getElementById('app');
  userRole.value = el?.dataset?.userRole || '';
  currentUserId.value = parseInt(el?.dataset?.userId || '0');
  console.log('👤 User:', userRole.value);
  fetchStudents();
});
</script>