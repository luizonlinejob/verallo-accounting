<template>
  <div class="px-6 py-4 bg-slate-50/60 border-b border-gray-100">
    <div class="grid grid-cols-1 md:grid-cols-12 gap-3">

      <!-- SEARCH -->
      <div class="md:col-span-4 relative">
        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔍</span>
        <input
          :value="searchQuery"
          @input="$emit('update:searchQuery', $event.target.value)"
          type="text"
          :placeholder="searchPlaceholder"
          class="w-full pl-10 pr-9 py-2.5 border border-slate-300 rounded-xl text-sm bg-white shadow-sm focus:ring-2 focus:ring-blue-600 focus:outline-none transition"
        />
      </div>

      <!-- COURSE FILTER -->
      <div class="md:col-span-3">
        <select
          :value="filterCourse"
          @change="$emit('update:filterCourse', $event.target.value)"
          class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm bg-white shadow-sm focus:ring-2 focus:ring-blue-600 focus:outline-none transition"
        >
          <option value="">🏫 All Courses</option>
          <optgroup label="🎓 Undergraduate Programs">
            <option v-for="c in undergraduateCourses" :key="c.value" :value="c.value">
              {{ c.label }}
            </option>
          </optgroup>
        </select>
      </div>

      <!-- STATUS FILTER -->
      <div class="md:col-span-3">
        <select
          :value="filterStatus"
          @change="$emit('update:filterStatus', $event.target.value)"
          class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm bg-white shadow-sm focus:ring-2 focus:ring-blue-600 focus:outline-none transition"
        >
          <option value="">All Status</option>
          <option value="paid">Fully Paid</option>
          <option value="partial">Partial Paid</option>
          <option value="pending">Pending Review</option>
          <option value="rejected">Rejected Payment</option>
          <option value="unpaid">No Payment</option>
        </select>
      </div>

      <!-- CLEAR -->
      <div class="md:col-span-2">
        <button
          @click="$emit('clear')"
          class="w-full px-3 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-sm font-semibold transition"
        >
          ✕ Clear
        </button>
      </div>
    </div>

    <!-- ACTIVE COURSE INDICATOR -->
    <div v-if="filterCourse" class="mt-3 flex items-center gap-2">
      <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Filtered by:</span>
      <span class="inline-flex items-center gap-1.5 text-[11px] bg-purple-50 text-purple-800 px-2.5 py-1 rounded-full border border-purple-200 font-bold">
        🎓 {{ courseAbbr(filterCourse) }}
        <button @click="$emit('update:filterCourse', '')" class="hover:text-rose-600 font-bold">✕</button>
      </span>
      <span class="text-[11px] text-slate-500">
        — Search below to find specific students
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  searchQuery: { type: String, default: '' },
  filterStatus: { type: String, default: '' },
  filterCourse: { type: String, default: '' }
});

defineEmits(['update:searchQuery', 'update:filterStatus', 'update:filterCourse', 'clear']);

// 🎓 UNDERGRADUATE
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

// Extract abbreviation
const courseAbbr = (courseStr) => {
  if (!courseStr) return '';
  const match = courseStr.match(/^([A-Z]+)\s*\(/);
  return match ? match[1] : courseStr;
};

// Dynamic search placeholder
const searchPlaceholder = computed(() => {
  if (props.filterCourse) {
    return `Search ${courseAbbr(props.filterCourse)} student by name or ID...`;
  }
  return 'Search by Student ID, Name, or Course...';
});
</script>