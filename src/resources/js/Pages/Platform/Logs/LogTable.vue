<script setup>
import { ref, computed } from 'vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
  logs: { type: Array, default: () => [] },
  type: { type: String, default: 'system' }, // 'system' or 'query'
});

const search = ref('');
const filterLevel = ref('all'); // for system logs

const filteredLogs = computed(() => {
  return props.logs.filter(log => {
    const matchesSearch = JSON.stringify(log).toLowerCase().includes(search.value.toLowerCase());
    
    if (props.type === 'system' && filterLevel.value !== 'all') {
      return matchesSearch && log.level.toLowerCase() === filterLevel.value.toLowerCase();
    }

    return matchesSearch;
  });
});

const getLevelColor = (level) => {
  switch (level.toLowerCase()) {
    case 'error': return 'danger';
    case 'warning': return 'warning';
    case 'info': return 'info';
    case 'debug': return 'success';
    default: return 'neutral';
  }
};
</script>

<template>
  <div class="space-y-4">
    <!-- Filters -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div class="relative w-full sm:w-64">
        <input
          v-model="search"
          type="text"
          placeholder="Cari logs..."
          class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm placeholder:text-slate-400 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500"
        >
      </div>

      <div v-if="type === 'system'" class="flex gap-2">
        <select
          v-model="filterLevel"
          class="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-600 focus:border-orange-500 focus:outline-none"
        >
          <option value="all">Semua Level</option>
          <option value="error">Error</option>
          <option value="warning">Warning</option>
          <option value="info">Info</option>
          <option value="debug">Debug</option>
        </select>
      </div>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="border-b border-slate-100 bg-slate-50/60">
            <tr>
              <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Waktu</th>
              <th v-if="type === 'system'" class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Level</th>
              <th v-if="type === 'system'" class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Environment</th>
              <th v-if="type === 'query'" class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Duration</th>
              <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Pesan / Query</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="(log, index) in filteredLogs" :key="index" class="transition-colors hover:bg-slate-50">
              <td class="whitespace-nowrap px-4 py-3 text-slate-500 font-mono text-xs">{{ log.timestamp }}</td>
              
              <template v-if="type === 'system'">
                <td class="whitespace-nowrap px-4 py-3">
                  <Badge :variant="getLevelColor(log.level)">{{ log.level }}</Badge>
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ log.environment }}</td>
                <td class="px-4 py-3 text-slate-700 font-mono text-xs break-all">{{ log.message }}</td>
              </template>

              <template v-if="type === 'query'">
                 <td class="whitespace-nowrap px-4 py-3">
                   <span :class="{'text-red-500 font-bold': parseInt(log.duration) > 1000, 'text-slate-500': parseInt(log.duration) <= 1000}">
                     {{ log.duration }}
                   </span>
                 </td>
                 <td class="px-4 py-3 text-slate-700 font-mono text-xs break-all">
                    {{ log.query }}
                 </td>
              </template>
            </tr>
            <tr v-if="filteredLogs.length === 0">
              <td :colspan="type === 'system' ? 4 : 3" class="px-4 py-8 text-center text-slate-400">
                Tidak ada data log yang ditemukan.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    
    <div class="text-xs text-slate-400 text-right">
      Menampilkan {{ filteredLogs.length }} baris log terbaru.
    </div>
  </div>
</template>
