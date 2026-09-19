<template>
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-slate-50/80">
        <tr>
          <th v-for="h in headers" :key="h" class="px-4 py-3.5 text-left text-[11px] font-bold text-gray-500 uppercase" :class="{ 'text-center': h === 'Action' }">
            {{ h }}
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 text-sm bg-white">
        <StudentRow
          v-for="student in students"
          :key="student.id"
          :student="student"
          :mode="mode"
          :is-super-admin="isSuperAdmin"
          :is-authorized="isAuthorized"
          :action-loading="actionLoading"
          @open-breakdown="$emit('open-breakdown', $event)"
          @open-payment="$emit('open-payment', $event)"
          @open-rejection-details="$emit('open-rejection-details', $event)"
          @approve="$emit('approve', $event)"
          @reject="$emit('reject', $event)"
          @edit="$emit('edit', $event)"
          @archive="$emit('archive', $event)"
          @restore="$emit('restore', $event)"
          @force-delete="$emit('force-delete', $event)"
          @print-soa="$emit('print-soa', $event)"
        />
        <tr v-if="students.length === 0">
          <td colspan="7" class="px-4 py-16 text-center">
            <div class="inline-flex flex-col items-center gap-2">
              <div class="text-4xl opacity-40">📭</div>
              <p class="text-gray-400 italic text-sm">
                {{ mode === 'active' ? 'No enrolled students found.' : 'No archived students found.' }}
              </p>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import StudentRow from './StudentRow.vue';

const headers = ['Student ID', 'Name', 'Course & Level', 'Total Assessment', 'Paid / Status', 'Remaining Balance', 'Action'];

defineProps({
  students: { type: Array, default: () => [] },
  mode: { type: String, default: 'active' },
  isSuperAdmin: { type: Boolean, default: false },
  isAuthorized: { type: Boolean, default: false },
  actionLoading: { type: Object, default: () => ({}) }
});

defineEmits(['open-breakdown', 'open-payment', 'open-rejection-details', 'approve', 'reject', 'edit', 'archive', 'restore', 'force-delete', 'print-soa']);
</script>