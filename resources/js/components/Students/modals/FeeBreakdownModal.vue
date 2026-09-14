<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[9999] overflow-y-auto" role="dialog" aria-modal="true">
      <div class="flex items-center justify-center min-h-screen px-4 py-8">
        <div @click="$emit('close')" class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm"></div>

        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl overflow-hidden max-h-[90vh] flex flex-col">

          <!-- HEADER -->
          <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-blue-50 to-white flex justify-between items-center">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-gradient-to-br from-blue-500 to-blue-700 text-white rounded-xl text-lg shadow-md">📋</div>
              <div>
                <h3 class="text-lg font-bold text-slate-800">Fee Breakdown</h3>
                <p class="text-xs text-slate-500 mt-0.5">{{ student?.full_name }} ({{ student?.student_id }})</p>
              </div>
            </div>
            <button @click="$emit('close')" class="text-gray-400 hover:text-rose-500 font-bold text-lg">✕</button>
          </div>

          <!-- STUDENT INFO -->
          <div class="px-6 py-4 bg-slate-50 border-b border-slate-100">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
              <div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Course</p>
                <p class="font-semibold text-slate-800 mt-0.5">{{ student?.course }}</p>
              </div>
              <div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Year Level</p>
                <p class="font-semibold text-slate-800 mt-0.5">{{ student?.year_level }}</p>
              </div>
              <div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Total Assessment</p>
                <p class="font-mono font-bold text-blue-700 mt-0.5">
                  ₱{{ Number(localFees.reduce((s, f) => s + Number(f.amount || 0), 0)).toFixed(2) }}
                </p>
              </div>
              <div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Remaining Balance</p>
                <p class="font-mono font-bold text-rose-600 mt-0.5">
                  ₱{{ calculateBalance().toFixed(2) }}
                </p>
              </div>
            </div>
          </div>

          <!-- FEE TABLE -->
          <div class="flex-1 overflow-y-auto">

            <div v-if="loading" class="p-12 text-center">
              <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-600 border-t-transparent"></div>
              <p class="text-sm text-slate-500 mt-3">Loading fees...</p>
            </div>

            <table v-else class="min-w-full divide-y divide-gray-200">
              <thead class="bg-slate-50/80 sticky top-0">
                <tr>
                  <th class="px-4 py-3 text-left text-[11px] font-bold text-gray-500 uppercase">Fee Description</th>
                  <th class="px-4 py-3 text-right text-[11px] font-bold text-gray-500 uppercase">Amount</th>
                  <th v-if="isSuperAdmin" class="px-4 py-3 text-center text-[11px] font-bold text-gray-500 uppercase w-32">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 text-sm bg-white">
                <tr v-for="(fee, index) in localFees" :key="fee.id || index" class="hover:bg-blue-50/30 transition">
                  <td class="px-4 py-3">
                    <input
                      v-if="isSuperAdmin && editingIndex === index"
                      v-model="fee.fee_name"
                      type="text"
                      class="w-full px-2.5 py-1.5 border border-blue-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    />
                    <span v-else class="font-semibold text-slate-800">{{ fee.fee_name }}</span>
                  </td>
                  <td class="px-4 py-3 text-right font-mono">
                    <div v-if="isSuperAdmin && editingIndex === index" class="flex items-center justify-end gap-1">
                      <span class="text-slate-500 text-xs">₱</span>
                      <input
                        v-model.number="fee.amount"
                        type="number"
                        step="0.01"
                        class="w-28 px-2.5 py-1.5 border border-blue-300 rounded-lg text-sm text-right focus:ring-2 focus:ring-blue-500 focus:outline-none"
                      />
                    </div>
                    <span v-else class="font-bold text-slate-800">₱{{ Number(fee.amount || 0).toFixed(2) }}</span>
                  </td>
                  <td v-if="isSuperAdmin" class="px-4 py-3 text-center">
                    <!-- EDIT MODE -->
                    <template v-if="editingIndex === index">
                      <div class="flex items-center justify-center gap-1">
                        <button
                          @click="saveFeeEdit(index)"
                          class="px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold active:scale-95"
                        >
                          💾 Save
                        </button>
                        <button
                          @click="cancelEdit"
                          class="px-2.5 py-1.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold active:scale-95"
                        >
                          ✕
                        </button>
                      </div>
                    </template>

                    <!-- VIEW MODE -->
                    <template v-else>
                      <div class="flex items-center justify-center gap-1">
                        <button
                          @click="startEdit(index)"
                          title="Edit fee"
                          class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 active:scale-95"
                        >
                          ✏️
                        </button>
                        <button
                          @click="deleteFee(fee, index)"
                          title="Delete fee"
                          class="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 active:scale-95"
                        >
                          🗑️
                        </button>
                      </div>
                    </template>
                  </td>
                </tr>

                <tr v-if="localFees.length === 0">
                  <td :colspan="isSuperAdmin ? 3 : 2" class="p-12 text-center text-slate-400 italic">
                    <div class="text-4xl opacity-40 mb-2">📭</div>
                    No fee breakdown available.
                  </td>
                </tr>
              </tbody>

              <!-- TOTAL ROW -->
              <tfoot v-if="localFees.length > 0">
                <tr class="bg-gradient-to-r from-blue-900 to-slate-900 text-white">
                  <td class="px-4 py-4 text-xs font-bold uppercase tracking-widest text-blue-200">
                    Total Assessment
                  </td>
                  <td class="px-4 py-4 text-right font-mono text-xl font-bold">
                    ₱{{ Number(localFees.reduce((s, f) => s + Number(f.amount || 0), 0)).toFixed(2) }}
                  </td>
                  <td v-if="isSuperAdmin"></td>
                </tr>
              </tfoot>
            </table>

            <!-- SUPERADMIN: ADD FEE SECTION -->
            <div v-if="isSuperAdmin && !loading" class="p-4 bg-slate-50 border-t border-slate-200">
              <p class="text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">➕ Add New Fee</p>
              <div class="flex flex-col sm:flex-row gap-2">
                <input
                  v-model="newFee.fee_name"
                  type="text"
                  placeholder="Fee description (e.g. Tuition)"
                  class="flex-1 px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                />
                <input
                  v-model.number="newFee.amount"
                  type="number"
                  step="0.01"
                  placeholder="Amount"
                  class="sm:w-32 px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm text-right focus:ring-2 focus:ring-blue-500 focus:outline-none"
                />
                <button
                  @click="addFee"
                  :disabled="!newFee.fee_name || !newFee.amount"
                  class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white text-sm font-bold shadow-md active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-1.5"
                >
                  <span>➕</span>
                  <span>Add Fee</span>
                </button>
              </div>
            </div>

          </div>

          <!-- FOOTER -->
          <div class="px-6 py-4 bg-slate-50 border-t flex justify-between items-center">
            <div class="text-xs text-slate-500">
              <span v-if="isSuperAdmin">💡 Superadmin: You can edit fees above</span>
              <span v-else>🔒 View-only mode</span>
            </div>
            <button
              @click="$emit('close')"
              class="px-5 py-2.5 bg-gradient-to-r from-slate-700 to-slate-900 text-white rounded-xl text-sm font-semibold shadow-md active:scale-95"
            >
              Close
            </button>
          </div>

        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  student: { type: Object, required: true },
  userRole: { type: String, default: '' }
});

const emit = defineEmits(['close', 'updated']);

const loading = ref(false);
const localFees = ref([]);
const editingIndex = ref(null);
const originalFee = ref(null);

const newFee = reactive({
  fee_name: '',
  amount: null
});

const isSuperAdmin = computed(() =>
  (props.userRole || '').toLowerCase().trim() === 'superadmin'
);

// ===== FETCH FEES =====
const fetchFees = async () => {
  loading.value = true;
  try {
    const res = await axios.get(`/students/${props.student.id}/fees`);
    localFees.value = res.data.fees || res.data || [];
    console.log('📋 Fees loaded:', localFees.value.length);
  } catch (err) {
    console.error('Error fetching fees:', err);

    // Fallback: use props.student.fees kung naa
    if (props.student.fees && Array.isArray(props.student.fees)) {
      localFees.value = [...props.student.fees];
    }
  } finally {
    loading.value = false;
  }
};

// ===== EDIT MODE =====
const startEdit = (index) => {
  editingIndex.value = index;
  originalFee.value = { ...localFees.value[index] };
};

const cancelEdit = () => {
  if (editingIndex.value !== null && originalFee.value) {
    localFees.value[editingIndex.value] = { ...originalFee.value };
  }
  editingIndex.value = null;
  originalFee.value = null;
};

// ===== SAVE FEE EDIT =====
const saveFeeEdit = async (index) => {
  const fee = localFees.value[index];

  if (!fee.fee_name || !fee.amount) {
    alert('Please fill in both fee name and amount.');
    return;
  }

  try {
    const res = await axios.put(`/fees/${fee.id}`, {
      fee_name: fee.fee_name,
      amount: fee.amount
    });

    if (res.data.success || res.data) {
      editingIndex.value = null;
      originalFee.value = null;
      emit('updated');
      console.log('✅ Fee updated');
    }
  } catch (err) {
    console.error('Error saving fee:', err);
    alert(err.response?.data?.message || 'Failed to update fee.');
  }
};

// ===== DELETE FEE =====
const deleteFee = async (fee, index) => {
  if (!isSuperAdmin.value) return;
  if (!confirm(`Delete fee "${fee.fee_name}"?\n\nThis cannot be undone!`)) return;

  try {
    await axios.delete(`/fees/${fee.id}`);
    localFees.value.splice(index, 1);
    emit('updated');
    console.log('✅ Fee deleted');
  } catch (err) {
    console.error('Error deleting fee:', err);
    alert(err.response?.data?.message || 'Failed to delete fee.');
  }
};

// ===== ADD NEW FEE =====
const addFee = async () => {
  if (!isSuperAdmin.value) return;
  if (!newFee.fee_name || !newFee.amount) return;

  try {
    const res = await axios.post(`/students/${props.student.id}/fees`, {
      fee_name: newFee.fee_name,
      amount: newFee.amount
    });

    if (res.data.success || res.data.fee) {
      localFees.value.push(res.data.fee || {
        id: Date.now(),
        fee_name: newFee.fee_name,
        amount: newFee.amount
      });

      newFee.fee_name = '';
      newFee.amount = null;
      emit('updated');
      console.log('✅ Fee added');
    }
  } catch (err) {
    console.error('Error adding fee:', err);
    alert(err.response?.data?.message || 'Failed to add fee.');
  }
};

// ===== CALCULATE BALANCE =====
const calculateBalance = () => {
  const totalFees = localFees.value.reduce((s, f) => s + Number(f.amount || 0), 0);
  const totalPaid = Number(props.student.total_paid || 0);
  return Math.max(0, totalFees - totalPaid);
};

onMounted(fetchFees);
</script>