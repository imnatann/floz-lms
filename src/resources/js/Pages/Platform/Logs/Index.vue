<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import LogTable from './LogTable.vue';

defineOptions({ layout: PlatformLayout });

const props = defineProps({
  systemLogs: Array,
  queryLogs: Array,
  queryLoggingEnabled: Boolean,
});

const activeTab = ref('system');

const toggleQueryLogging = () => {
  router.post(route('platform.logs.toggle-query'), {
    enabled: !props.queryLoggingEnabled,
  }, {
    preserveScroll: true,
  });
};

const clearLogs = (type) => {
  if (!confirm('Apakah Anda yakin ingin menghapus semua log ini?')) return;
  
  const routeName = type === 'system' ? 'platform.logs.clear-system' : 'platform.logs.clear-queries';
  router.post(route(routeName), {}, {
    preserveScroll: true,
  });
};
</script>

<template>
  <Head title="System Logs" />

  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-slate-800">System Logs</h2>
        <p class="mt-1 text-sm text-slate-400">Pantau aktivitas sistem dan query database.</p>
      </div>
    </div>

    <div class="space-y-4">
      <!-- Tabs -->
      <div class="border-b border-slate-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
          <button
            @click="activeTab = 'system'"
            :class="[
              activeTab === 'system'
                ? 'border-orange-500 text-orange-600'
                : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700',
              'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors'
            ]"
          >
            System Logs
          </button>
          <button
            @click="activeTab = 'query'"
            :class="[
              activeTab === 'query'
                ? 'border-orange-500 text-orange-600'
                : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700',
              'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors'
            ]"
          >
            Query Logs
          </button>
        </nav>
      </div>

      <!-- System Logs Content -->
      <div v-if="activeTab === 'system'" class="space-y-4">
        <div class="flex justify-end">
          <Button variant="danger" size="sm" @click="clearLogs('system')">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
            Bersihkan Log
          </Button>
        </div>
        <LogTable :logs="systemLogs" type="system" />
      </div>

      <!-- Query Logs Content -->
      <div v-if="activeTab === 'query'" class="space-y-4">
        <div class="flex items-center justify-between rounded-lg bg-slate-50 p-4 border border-slate-200">
          <div>
            <h3 class="text-sm font-medium text-slate-700">Database Query Logging</h3>
            <p class="text-xs text-slate-500 mt-1">
              Catat semua query database ke log file. 
              <span class="font-semibold text-orange-600">Perhatian:</span> Mengaktifkan ini dapat membebani penyimpanan.
            </p>
          </div>
          <div class="flex items-center gap-3">
             <div class="flex items-center">
                <span class="mr-2 text-sm text-slate-600">{{ queryLoggingEnabled ? 'Aktif' : 'Nonaktif' }}</span>
                <button 
                  @click="toggleQueryLogging"
                  :class="[queryLoggingEnabled ? 'bg-orange-600' : 'bg-slate-200', 'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-orange-600 focus:ring-offset-2']"
                >
                  <span 
                    aria-hidden="true" 
                    :class="[queryLoggingEnabled ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']"
                  />
                </button>
             </div>
             <div class="h-6 w-px bg-slate-200 mx-2"></div>
             <Button variant="danger" size="sm" @click="clearLogs('query')">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                Bersihkan Log
              </Button>
          </div>
        </div>

        <LogTable :logs="queryLogs" type="query" />
      </div>
    </div>
  </div>
</template>
