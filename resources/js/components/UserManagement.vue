<template>
  <div class="space-y-6 font-sans">

    <!-- HEADER -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
      <div class="px-6 py-5 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white flex justify-between items-center">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-gradient-to-br from-purple-500 to-purple-700 text-white rounded-xl text-lg shadow-md">👥</div>
          <div>
            <h2 class="text-xl font-bold text-gray-800">User Management</h2>
            <p class="text-xs text-gray-500 mt-0.5">Manage user accounts and roles</p>
          </div>
        </div>

        <button
          v-if="isSuperAdmin"
          @click="openAddModal"
          class="bg-gradient-to-r from-purple-600 to-purple-800 hover:from-purple-700 hover:to-purple-900 text-white px-4 py-2.5 rounded-xl text-sm font-bold shadow-md active:scale-95 flex items-center gap-2"
        >
          <span>➕</span>
          <span>Add User</span>
        </button>
      </div>

      <!-- USERS TABLE -->
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-slate-50/80">
            <tr>
              <th class="px-4 py-3.5 text-left text-[11px] font-bold text-gray-500 uppercase">Name</th>
              <th class="px-4 py-3.5 text-left text-[11px] font-bold text-gray-500 uppercase">Email</th>
              <th class="px-4 py-3.5 text-left text-[11px] font-bold text-gray-500 uppercase">Role</th>
              <th class="px-4 py-3.5 text-left text-[11px] font-bold text-gray-500 uppercase">Created</th>
              <th class="px-4 py-3.5 text-center text-[11px] font-bold text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 text-sm bg-white">
            <tr v-for="user in users" :key="user.id" class="hover:bg-purple-50/30 transition">
              <td class="px-4 py-3.5 font-semibold text-slate-800">{{ user.name }}</td>
              <td class="px-4 py-3.5 text-slate-600 font-mono text-xs">{{ user.email }}</td>
              <td class="px-4 py-3.5">
                <span :class="roleBadgeClass(user.role)">
                  {{ roleIcon(user.role) }} {{ user.role }}
                </span>
              </td>
              <td class="px-4 py-3.5 text-xs text-slate-500">{{ formatDateTime(user.created_at) }}</td>
              <td class="px-4 py-3.5 text-center">
                <button
                  v-if="isSuperAdmin && user.id !== currentUserId"
                  @click="confirmDelete(user)"
                  class="px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold active:scale-95"
                >
                  🗑️ Delete
                </button>
                <span v-else class="text-xs text-slate-400 italic">—</span>
              </td>
            </tr>
            <tr v-if="users.length === 0">
              <td colspan="5" class="p-12 text-center text-slate-400 italic">No users found.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ADD USER MODAL -->
    <Teleport to="body">
      <div v-if="isAddModalOpen" class="fixed inset-0 z-[9999] overflow-y-auto" role="dialog">
        <div class="flex items-center justify-center min-h-screen px-4">
          <div @click="closeAddModal" class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm"></div>

          <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
            <div class="px-6 py-4 bg-purple-50 border-b border-purple-100 flex justify-between items-center">
              <h3 class="text-lg font-bold text-purple-800 flex items-center gap-2">
                <span>➕</span> Add New User
              </h3>
              <button @click="closeAddModal" class="text-gray-400 hover:text-rose-500 font-bold">✕</button>
            </div>

            <form @submit.prevent="submitAddUser">
              <div class="p-6 space-y-4">

                <div v-if="formError" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-800 flex items-start gap-2">
                  <span>⚠️</span>
                  <span>{{ formError }}</span>
                </div>

                <div>
                  <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Full Name *</label>
                  <input v-model="form.name" type="text" required placeholder="Juan Dela Cruz" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none" />
                </div>

                <div>
                  <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Email *</label>
                  <input v-model="form.email" type="email" required placeholder="user@system.com" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none" />
                </div>

                <div>
                  <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Password *</label>
                  <input v-model="form.password" type="password" required minlength="6" placeholder="Min 6 characters" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-purple-500 focus:outline-none" />
                </div>

                <div>
                  <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Role *</label>
                  <select v-model="form.role" required class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm bg-white focus:ring-2 focus:ring-purple-500 focus:outline-none">
                    <option value="superadmin">👑 Superadmin — Full access</option>
                    <option value="admin">🛡️ Admin — Approve/reject payments</option>
                    <option value="accounting">📊 Accounting — Reports & processing</option>
                    <option value="encoder">💳 Encoder — Encode payments only</option>
                  </select>
                </div>

              </div>

              <div class="px-6 py-4 bg-slate-50 border-t flex flex-col sm:flex-row-reverse gap-3">
                <button type="submit" :disabled="isSubmitting" class="w-full sm:w-auto bg-gradient-to-r from-purple-600 to-purple-800 text-white px-5 py-2.5 rounded-xl text-sm font-bold disabled:opacity-60 flex items-center justify-center gap-2">
                  <span v-if="isSubmitting" class="inline-block animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
                  <span>{{ isSubmitting ? 'Creating...' : '💾 Create User' }}</span>
                </button>
                <button type="button" @click="closeAddModal" class="w-full sm:w-auto bg-white border border-slate-300 px-5 py-2.5 rounded-xl text-sm font-semibold">
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
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  userRole: { type: String, default: '' },
  currentUserId: { type: Number, default: 0 },
});

const users = ref([]);
const isAddModalOpen = ref(false);
const isSubmitting = ref(false);
const formError = ref('');

const form = reactive({
  name: '',
  email: '',
  password: '',
  role: 'encoder',
});

const isSuperAdmin = computed(() => (props.userRole || '').toLowerCase().trim() === 'superadmin');

const roleIcon = (role) => ({
  superadmin: '👑',
  admin: '🛡️',
  accounting: '📊',
  encoder: '💳',
}[role] || '👤');

const roleBadgeClass = (role) => {
  const base = 'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold border';
  return {
    superadmin: base + ' bg-purple-50 text-purple-800 border-purple-200',
    admin: base + ' bg-blue-50 text-blue-800 border-blue-200',
    accounting: base + ' bg-emerald-50 text-emerald-800 border-emerald-200',
    encoder: base + ' bg-amber-50 text-amber-800 border-amber-200',
  }[role] || base + ' bg-slate-100 text-slate-700 border-slate-200';
};

const formatDateTime = (dateStr) => {
  if (!dateStr) return 'N/A';
  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return dateStr;
  return new Intl.DateTimeFormat('en-PH', {
    month: 'short', day: 'numeric', year: 'numeric',
  }).format(d);
};

const fetchUsers = async () => {
  try {
    const res = await axios.get('/users');
    users.value = res.data.users || res.data || [];
  } catch (err) {
    console.error('Error fetching users:', err);
  }
};

const openAddModal = () => {
  form.name = '';
  form.email = '';
  form.password = '';
  form.role = 'encoder';
  formError.value = '';
  isAddModalOpen.value = true;
};

const closeAddModal = () => {
  isAddModalOpen.value = false;
  formError.value = '';
};

const submitAddUser = async () => {
  isSubmitting.value = true;
  formError.value = '';

  try {
    const res = await axios.post('/users', form);
    if (res.data.success) {
      alert('✅ User created successfully!');
      closeAddModal();
      fetchUsers();
    } else {
      formError.value = res.data.message || 'Failed to create user.';
    }
  } catch (err) {
    if (err.response?.status === 422) {
      const errors = err.response.data.errors || {};
      formError.value = Object.values(errors).flat().join(' ');
    } else {
      formError.value = err.response?.data?.message || 'An error occurred.';
    }
  } finally {
    isSubmitting.value = false;
  }
};

const confirmDelete = async (user) => {
  if (!confirm(`Delete user "${user.name}"?\n\nThis cannot be undone!`)) return;
  try {
    const res = await axios.delete(`/users/${user.id}`);
    if (res.data.success) {
      alert('✅ User deleted.');
      fetchUsers();
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to delete user.');
  }
};

onMounted(fetchUsers);
</script>