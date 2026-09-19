<template>
  <div class="space-y-6 font-sans">

    <!-- SUPERADMIN UG ADMIN ONLY: Add Custom Textbox Section -->
    <div
      v-if="userRole === 'superadmin' || userRole === 'admin'"
      class="relative overflow-hidden bg-gradient-to-r from-blue-50 via-indigo-50 to-blue-50 border border-blue-200/80 p-5 rounded-2xl shadow-sm"
    >
      <div class="absolute -top-16 -right-16 w-48 h-48 bg-blue-400/10 rounded-full blur-3xl"></div>

      <div class="relative">
        <h3 class="font-bold text-blue-900 mb-3 flex items-center gap-2 text-sm tracking-wide">
          <span class="inline-flex items-center justify-center w-6 h-6 rounded-lg bg-blue-600 text-white text-xs">⚙️</span>
          Admin Control: Add Custom Form Field
        </h3>
        <div class="flex flex-col sm:flex-row gap-3">
          <input
            v-model="newFieldLabel"
            type="text"
            placeholder="e.g. LRN Number, Voucher Code, Guardian Phone"
            class="border border-slate-300 px-4 py-2.5 rounded-xl text-sm bg-white flex-1 shadow-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition-all"
            @keyup.enter="addCustomField"
          />
          <button
            type="button"
            @click="addCustomField"
            class="bg-gradient-to-r from-blue-700 to-blue-800 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:from-blue-800 hover:to-blue-900 active:scale-95 transition-all shadow-md shadow-blue-500/20 whitespace-nowrap"
          >
            + Add Field to Form
          </button>
        </div>
        <p v-if="fieldError" class="text-xs text-rose-600 font-semibold mt-3 flex items-center gap-1.5">
          <span>⚠️</span> {{ fieldError }}
        </p>
      </div>
    </div>

    <!-- 🆕 SUCCESS NOTIFICATION (TOP, VERY VISIBLE) -->
    <Transition name="slide-down">
      <div
        v-if="successMsg"
        class="relative overflow-hidden bg-gradient-to-r from-emerald-500 via-emerald-600 to-emerald-700 text-white rounded-2xl shadow-xl shadow-emerald-500/30 p-5 flex items-start gap-4"
      >
        <div class="absolute -top-8 -right-8 w-32 h-32 bg-white/10 rounded-full"></div>
        <div class="absolute -bottom-8 -left-8 w-24 h-24 bg-white/5 rounded-full"></div>

        <div class="relative flex-shrink-0 w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center text-2xl animate-bounce-in">
          ✅
        </div>

        <div class="relative flex-1">
          <p class="font-bold text-lg tracking-tight">Enrollment Successful!</p>
          <p class="text-sm text-emerald-50 mt-1">{{ successMsg }}</p>

          <!-- Student Info Summary -->
          <div v-if="lastEnrolled" class="mt-3 flex flex-wrap items-center gap-2 text-xs">
            <span class="px-2.5 py-1 rounded-full bg-white/20 backdrop-blur-sm font-bold">
              🎓 {{ lastEnrolled.full_name }}
            </span>
            <span class="px-2.5 py-1 rounded-full bg-white/20 backdrop-blur-sm font-mono">
              #{{ lastEnrolled.student_id }}
            </span>
            <span class="px-2.5 py-1 rounded-full bg-white/20 backdrop-blur-sm">
              📚 {{ lastEnrolled.course }}
            </span>
            <span class="px-2.5 py-1 rounded-full bg-white/20 backdrop-blur-sm font-bold">
              ₱{{ Number(lastEnrolled.total_fees || 0).toFixed(2) }}
            </span>
          </div>

          <button
            @click="successMsg = ''; lastEnrolled = null"
            class="mt-3 text-xs font-bold text-emerald-100 hover:text-white underline underline-offset-2"
          >
            Dismiss
          </button>
        </div>

        <button
          @click="successMsg = ''; lastEnrolled = null"
          class="relative flex-shrink-0 text-white/70 hover:text-white text-xl font-bold transition"
        >
          ✕
        </button>
      </div>
    </Transition>

    <!-- ENROLLMENT FORM -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
      <!-- Header strip -->
      <div class="px-6 py-5 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-gradient-to-br from-blue-500 to-blue-700 text-white rounded-xl text-lg shadow-md shadow-blue-500/20">
            📝
          </div>
          <div>
            <h2 class="text-xl font-bold text-gray-800 tracking-tight">Enroll New Student</h2>
            <p class="text-xs text-gray-500 mt-0.5">Fill in the required details and fee breakdown below.</p>
          </div>
        </div>
      </div>

      <form @submit.prevent="submitEnrollment" class="p-6 space-y-6">

        <!-- Basic Student Info Fields -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">Student ID</label>
            <input
              v-model="form.student_id"
              type="text"
              required
              class="w-full border border-slate-300 px-3.5 py-2.5 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition-all shadow-sm"
              placeholder="2026-0001"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">Full Name</label>
            <input
              v-model="form.full_name"
              type="text"
              required
              class="w-full border border-slate-300 px-3.5 py-2.5 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition-all shadow-sm"
              placeholder="Juan Dela Cruz"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">Email Address</label>
            <input
              v-model="form.email"
              type="email"
              required
              class="w-full border border-slate-300 px-3.5 py-2.5 rounded-xl text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition-all shadow-sm"
              placeholder="student@example.com"
            />
          </div>

          <!-- COURSE DROPDOWN -->
          <div>
            <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">Course</label>
            <select
              v-model="form.course"
              required
              class="w-full border border-slate-300 px-3.5 py-2.5 rounded-xl bg-white text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition-all shadow-sm"
            >
              <option value="" disabled>-- Select Course --</option>

              <optgroup label="🎓 Undergraduate Programs">
                <option v-for="c in undergraduateCourses" :key="c.value" :value="c.value">
                  {{ c.label }}
                </option>
              </optgroup>

              <optgroup label="🎓 Masteral Programs">
                <option v-for="c in masteralCourses" :key="c.value" :value="c.value">
                  {{ c.label }}
                </option>
              </optgroup>
            </select>
          </div>

          <!-- YEAR LEVEL SELECTOR -->
          <div>
            <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">Year Level</label>
            <select
              v-model="form.year_level"
              required
              class="w-full border border-slate-300 px-3.5 py-2.5 rounded-xl bg-white text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:outline-none transition-all shadow-sm"
            >
              <option value="" disabled>-- Select Year Level --</option>
              <option value="1st Year">1st Year</option>
              <option value="2nd Year">2nd Year</option>
              <option value="3rd Year">3rd Year</option>
              <option value="4th Year">4th Year</option>
            </select>
          </div>

          <!-- DYNAMIC CUSTOM FIELDS -->
          <div v-for="(field, index) in customFields" :key="field.id || index" class="relative">
            <div class="flex justify-between items-center mb-1.5">
              <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider">{{ field.field_label }}</label>
              <button
                v-if="userRole === 'superadmin' || userRole === 'admin'"
                type="button"
                @click="removeCustomField(index, field.id)"
                class="text-[11px] text-rose-500 hover:text-rose-700 font-bold hover:bg-rose-50 px-2 py-0.5 rounded-md transition"
                title="Remove this field"
              >
                ✕ Delete
              </button>
            </div>
            <input
              v-model="form.custom_values[field.field_label]"
              type="text"
              class="w-full border border-amber-200 px-3.5 py-2.5 rounded-xl bg-amber-50/40 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 focus:outline-none transition-all shadow-sm"
              :placeholder="'Enter ' + field.field_label"
            />
          </div>
        </div>

        <!-- FEE BREAKDOWN TABLE -->
        <div class="rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
          <div class="flex justify-between items-center px-5 py-4 bg-gradient-to-r from-slate-50 to-white border-b border-slate-200">
            <div class="flex items-center gap-2.5">
              <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 text-sm">₱</span>
              <h3 class="text-base font-bold text-gray-800 tracking-tight">Fee Breakdown</h3>
            </div>

            <button
              v-if="userRole === 'superadmin' || userRole === 'admin'"
              type="button"
              @click="addFeeRow"
              class="bg-gradient-to-r from-emerald-600 to-emerald-700 text-white px-3.5 py-1.5 rounded-lg text-xs font-semibold hover:from-emerald-700 hover:to-emerald-800 active:scale-95 transition-all shadow-md shadow-emerald-500/20"
            >
              + Add Fee Row
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="border-b border-slate-200 bg-slate-50/60 text-[11px] uppercase tracking-wider font-bold text-gray-500">
                  <th class="py-3 px-4">Fee Description</th>
                  <th class="py-3 px-4 w-48 text-right">Amount (PHP)</th>
                  <th v-if="userRole === 'superadmin' || userRole === 'admin'" class="py-3 px-4 w-16 text-center">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(fee, index) in form.fees"
                  :key="index"
                  class="border-b border-slate-100 hover:bg-blue-50/30 transition-colors"
                >
                  <td class="py-2.5 px-4">
                    <input
                      v-model="fee.fee_name"
                      type="text"
                      placeholder="Fee Name"
                      required
                      :readonly="userRole !== 'superadmin' && userRole !== 'admin'"
                      :class="[
                        'w-full border px-3 py-1.5 rounded-lg text-sm focus:outline-none transition-all',
                        (userRole !== 'superadmin' && userRole !== 'admin')
                          ? 'bg-gray-100 cursor-not-allowed text-gray-600 border-gray-200'
                          : 'bg-white border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500'
                      ]"
                    />
                  </td>
                  <td class="py-2.5 px-4">
                    <input
                      v-model.number="fee.amount"
                      type="number"
                      step="0.01"
                      placeholder="0.00"
                      required
                      class="w-full border border-slate-300 px-3 py-1.5 rounded-lg bg-white text-right text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none transition-all"
                    />
                  </td>

                  <td v-if="userRole === 'superadmin' || userRole === 'admin'" class="py-2.5 px-4 text-center">
                    <button
                      type="button"
                      @click="removeFeeRow(index)"
                      class="text-rose-500 hover:text-white hover:bg-rose-500 font-bold w-7 h-7 rounded-lg text-sm disabled:opacity-30 disabled:hover:bg-transparent disabled:hover:text-rose-500 transition-all"
                      :disabled="form.fees.length === 1"
                    >
                      ✕
                    </button>
                  </td>
                </tr>
              </tbody>

              <tfoot>
                <tr class="bg-gradient-to-r from-blue-900 to-slate-900 text-white">
                  <td class="py-4 px-4 text-xs font-bold uppercase tracking-widest text-blue-200">
                    Total Computation
                  </td>
                  <td class="py-4 px-4 text-right font-mono text-xl font-bold">
                    ₱ {{ totalComputedFees.toFixed(2) }}
                  </td>
                  <td v-if="userRole === 'superadmin' || userRole === 'admin'"></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- SUBMIT BUTTON -->
        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-gradient-to-r from-blue-700 to-blue-900 text-white py-3.5 rounded-xl font-bold tracking-wide hover:from-blue-800 hover:to-slate-900 transition-all active:scale-[0.99] disabled:opacity-70 shadow-lg shadow-blue-500/25 flex items-center justify-center gap-2"
        >
          <span v-if="loading" class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
          {{ loading ? 'Saving Enrollment...' : 'Confirm Enrollment & Save Breakdown' }}
        </button>
      </form>
    </div>

  </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  userRole: {
    type: String,
    default: 'superadmin'
  }
});

// 🎓 UNDERGRADUATE PROGRAMS
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

// 🎓 MASTERAL PROGRAMS
const masteralCourses = [
  { value: 'MAED (Master of Arts in Education)',                      label: 'MAED (Master of Arts in Education)' },
];

const customFields = ref([]);
const newFieldLabel = ref('');
const fieldError = ref('');
const loading = ref(false);
const successMsg = ref('');
const lastEnrolled = ref(null);   // 🆕 Last enrolled student info

const form = reactive({
  student_id: '',
  full_name: '',
  email: '',
  course: 'BSTM (Bachelor of Science in Tourism Management)',
  year_level: '1st Year',
  custom_values: {},
  fees: [
    { fee_name: 'Tuition', amount: 9007.20 },
    { fee_name: 'Entrance', amount: 998.40 },
    { fee_name: 'TTS', amount: 583.39 },
    { fee_name: 'Insurance', amount: 187.20 },
    { fee_name: 'Misc.', amount: 187.00 },
    { fee_name: 'NSTP', amount: 475.15 }
  ]
});

// Awtomatikong Pag-compute sa Total
const totalComputedFees = computed(() => {
  return form.fees.reduce((sum, item) => sum + (Number(item.amount) || 0), 0);
});

// Add ug Remove Fee Row Functions
const addFeeRow = () => {
  if (props.userRole === 'superadmin' || props.userRole === 'admin') {
    form.fees.push({ fee_name: '', amount: 0 });
  }
};

const removeFeeRow = (index) => {
  if ((props.userRole === 'superadmin' || props.userRole === 'admin') && form.fees.length > 1) {
    form.fees.splice(index, 1);
  }
};

// Superadmin & Admin Dynamic Custom Fields Logic
const addCustomField = async () => {
  fieldError.value = '';
  const labelText = newFieldLabel.value.trim();

  if (!labelText) {
    fieldError.value = 'Please enter a field name.';
    return;
  }

  const exists = customFields.value.some(f => f.field_label.toLowerCase() === labelText.toLowerCase());
  if (exists) {
    fieldError.value = 'Niana na kini nga field sa form.';
    return;
  }

  try {
    const res = await axios.post('/custom-fields', { field_label: labelText });
    if (res.data && res.data.field) {
      customFields.value.push(res.data.field);
    } else {
      customFields.value.push({ id: Date.now(), field_label: labelText });
    }
  } catch (err) {
    customFields.value.push({ id: Date.now(), field_label: labelText });
  } finally {
    newFieldLabel.value = '';
  }
};

const removeCustomField = async (index, id) => {
  const targetField = customFields.value[index];
  if (targetField) {
    delete form.custom_values[targetField.field_label];
    customFields.value.splice(index, 1);
  }

  if (id) {
    try {
      await axios.delete(`/custom-fields/${id}`);
    } catch (err) {
      console.warn('Field removed locally.');
    }
  }
};

const fetchCustomFields = async () => {
  try {
    const res = await axios.get('/custom-fields');
    if (Array.isArray(res.data)) {
      customFields.value = res.data;
    }
  } catch (err) {
    console.warn('API endpoint not ready. Using local state.');
  }
};

// 🆕 Submit Enrollment Event
const submitEnrollment = async () => {
  loading.value = true;
  successMsg.value = '';
  lastEnrolled.value = null;

  // Save current form data before reset
  const currentForm = {
    full_name: form.full_name,
    student_id: form.student_id,
    course: form.course,
    year_level: form.year_level,
    email: form.email,
    total_fees: totalComputedFees.value
  };

  try {
    const res = await axios.post('/enroll-student', form);
    successMsg.value = res.data?.message || 'Student successfully enrolled!';
    lastEnrolled.value = currentForm;

    // Auto-dismiss after 10 seconds
    setTimeout(() => {
      if (successMsg.value) {
        successMsg.value = '';
        lastEnrolled.value = null;
      }
    }, 10000);

    // Reset form
    form.student_id = '';
    form.full_name = '';
    form.email = '';
    form.custom_values = {};

    // Scroll to top para makita ang success message
    window.scrollTo({ top: 0, behavior: 'smooth' });

  } catch (err) {
    alert(err.response?.data?.message || 'Error saving enrollment');
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchCustomFields();
});
</script>

<style scoped>
/* 🆕 Slide down animation */
.slide-down-enter-active {
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-down-leave-active {
  transition: all 0.3s ease;
}
.slide-down-enter-from {
  opacity: 0;
  transform: translateY(-20px);
}
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* 🆕 Bounce-in animation for check icon */
@keyframes bounce-in {
  0% {
    transform: scale(0);
    opacity: 0;
  }
  50% {
    transform: scale(1.15);
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}
.animate-bounce-in {
  animation: bounce-in 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}
</style>