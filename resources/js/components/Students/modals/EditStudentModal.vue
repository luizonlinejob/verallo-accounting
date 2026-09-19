<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[9999] overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen px-4">
        <div @click="$emit('close')" class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm"></div>

        <div class="relative bg-white rounded-2xl shadow-2xl sm:max-w-lg sm:w-full overflow-hidden">
          <div class="px-6 py-4 border-b bg-slate-50 flex justify-between items-center">
            <h3 class="text-lg font-bold text-slate-800">✏️ Edit Student</h3>
            <button @click="$emit('close')" class="text-gray-400 font-bold">✕</button>
          </div>

          <form @submit.prevent="$emit('save')">
            <div class="px-6 py-5 space-y-4">
              <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Student ID</label>
                <input v-model="form.student_id" required class="w-full px-3.5 py-2 border rounded-lg text-sm font-mono" />
              </div>
              <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Full Name</label>
                <input v-model="form.full_name" required class="w-full px-3.5 py-2 border rounded-lg text-sm" />
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Course</label>
                  <input v-model="form.course" required class="w-full px-3.5 py-2 border rounded-lg text-sm" />
                </div>
                <div>
                  <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Year Level</label>
                  <select v-model="form.year_level" required class="w-full px-3.5 py-2 border rounded-lg text-sm">
                    <option>1st Year</option>
                    <option>2nd Year</option>
                    <option>3rd Year</option>
                    <option>4th Year</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t flex flex-col sm:flex-row-reverse gap-3">
              <button type="submit" :disabled="loading" class="w-full sm:w-auto bg-blue-700 text-white px-5 py-2 rounded-xl text-sm font-semibold disabled:opacity-60">
                {{ loading ? 'Saving...' : '💾 Save' }}
              </button>
              <button type="button" @click="$emit('close')" class="w-full sm:w-auto bg-white border px-5 py-2 rounded-xl text-sm font-semibold">Cancel</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
defineProps({
  form: { type: Object, required: true },
  loading: { type: Boolean, default: false }
});
defineEmits(['close', 'save']);
</script>