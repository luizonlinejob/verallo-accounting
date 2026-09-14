<template>
  <div class="p-6">
    <StudentsList
      :students="students"
      :loading="loading"
      :user-role="userRole"
      @refresh="fetchStudents"
      @open-breakdown="handleBreakdown"
      @open-payment="handlePayment"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import StudentsList from './Students/StudentsList.vue';

const students = ref([]);
const loading = ref(false);
const userRole = ref('');

const fetchStudents = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/students-json');
    students.value = res.data;
  } catch (err) {
    console.error('Error fetching students:', err);
  } finally {
    loading.value = false;
  }
};

const handleBreakdown = (student) => {
  console.log('Open breakdown for:', student);
  // TODO: Buksan ang fee breakdown modal
};

const handlePayment = (student) => {
  console.log('Open payment for:', student);
  // TODO: Buksan ang payment modal
};

onMounted(() => {
  const el = document.getElementById('app');
  userRole.value = el?.dataset?.userRole || '';
  fetchStudents();
});
</script><template>
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

      <!-- DASHBOARD TAB — ✅ Idugang ang @navigate -->
      <DashboardStats
        v-if="activeSection === 'dashboard'"
        :user-role="userRole"
        @navigate="activeSection = $event"
      />

      <EnrollForm
        v-else-if="activeSection === 'enroll'"
        :user-role="userRole"
      />

      <StudentsList
        v-else-if="activeSection === 'students'"
        :students="students"
        :loading="loading"
        :user-role="userRole"
        @refresh="fetchStudents"
        @open-breakdown="handleBreakdown"
        @open-payment="handlePayment"
      />

      <ReportsPanel
        v-else-if="activeSection === 'reports'"
        :user-role="userRole"
      />

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
  } catch (err) {
    console.error('❌ Error fetching students:', err);
  } finally {
    loading.value = false;
  }
};

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