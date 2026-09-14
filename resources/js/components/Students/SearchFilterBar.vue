<template>
  <div class="px-6 py-4 bg-slate-50/60 border-b border-gray-100">
    <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
      <div class="md:col-span-6 relative">
        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm">🔍</span>
        <input
          :value="searchQuery"
          @input="$emit('update:searchQuery', $event.target.value)"
          type="text"
          placeholder="Search by Student ID, Name, or Course..."
          class="w-full pl-10 pr-9 py-2.5 border border-slate-300 rounded-xl text-sm bg-white shadow-sm focus:ring-2 focus:ring-blue-600 focus:outline-none transition"
        />
      </div>
      <div class="md:col-span-3">
        <select
          :value="filterStatus"
          @change="$emit('update:filterStatus', $event.target.value)"
          class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm bg-white focus:ring-2 focus:ring-blue-600 focus:outline-none"
        >
          <option value="">All Status</option>
          <option value="paid">Fully Paid</option>
          <option value="partial">Partial Paid</option>
          <option value="pending">Pending Review</option>
          <option value="rejected">Rejected Payment</option>
          <option value="unpaid">No Payment</option>
        </select>
      </div>
      <div class="md:col-span-3">
        <button @click="$emit('clear')" class="w-full px-3 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-sm font-semibold transition">
          Clear Filters
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  searchQuery: { type: String, default: '' },
  filterStatus: { type: String, default: '' }
});
defineEmits(['update:searchQuery', 'update:filterStatus', 'clear']);
</script>