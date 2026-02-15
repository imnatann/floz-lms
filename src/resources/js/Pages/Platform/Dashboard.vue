<script setup>
import { Link } from '@inertiajs/vue3';
import { usePage, Head } from '@inertiajs/vue3';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import StatCard from '@/Components/UI/StatCard.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';

defineOptions({ layout: PlatformLayout });

const props = defineProps({
  stats: Object,
  recentTenants: Array,
});

const statCards = [
  { label: 'Total Sekolah',     value: props.stats.total_tenants,      icon: '🏫', color: 'orange' },
  { label: 'Sekolah Aktif',     value: props.stats.active_tenants,     icon: '✅', color: 'green' },
  { label: 'Langganan Aktif',   value: props.stats.total_subscriptions,icon: '💳', color: 'blue' },
  {
    label: 'Pendapatan Bulanan',
    value: `Rp ${Number(props.stats.revenue_monthly || 0).toLocaleString('id-ID')}`,
    icon: '💰',
    color: 'purple',
  },
];
</script>

<template>
  <Head title="Dashboard Platform" />

  <div class="space-y-6">
    <!-- Greeting -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-slate-800">Dashboard Platform</h2>
        <p class="mt-1 text-sm text-slate-400">Kelola seluruh sekolah pada platform FLOZ</p>
      </div>
      <Button href="/platform/tenants/create" size="sm">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Tambah Sekolah
      </Button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <StatCard
        v-for="s in statCards"
        :key="s.label"
        :label="s.label"
        :value="s.value"
        :icon="s.icon"
        :color="s.color"
      />
    </div>

    <!-- Recent Tenants -->
    <Card title="Sekolah Terbaru" subtitle="5 sekolah terakhir yang terdaftar" :noPadding="true">
      <template #header-actions>
        <Button href="/platform/tenants" variant="ghost" size="xs">
          Lihat Semua →
        </Button>
      </template>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="border-b border-slate-100 bg-slate-50/60">
            <tr>
              <th class="whitespace-nowrap px-5 py-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Sekolah</th>
              <th class="whitespace-nowrap px-5 py-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Jenjang</th>
              <th class="whitespace-nowrap px-5 py-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Status</th>
              <th class="whitespace-nowrap px-5 py-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Paket</th>
              <th class="whitespace-nowrap px-5 py-3 text-xs font-semibold uppercase tracking-wider text-slate-400"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="t in recentTenants" :key="t.id" class="transition-colors hover:bg-orange-50/30">
              <td class="whitespace-nowrap px-5 py-3.5">
                <div class="flex items-center gap-3">
                  <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-50 text-sm font-bold text-orange-600">
                    {{ t.name.charAt(0) }}
                  </div>
                  <div>
                    <p class="text-sm font-medium text-slate-700">{{ t.name }}</p>
                    <p class="text-xs text-slate-400">{{ t.email }}</p>
                  </div>
                </div>
              </td>
              <td class="whitespace-nowrap px-5 py-3.5">
                <Badge variant="orange">{{ t.education_level }}</Badge>
              </td>
              <td class="whitespace-nowrap px-5 py-3.5">
                <Badge :variant="t.status === 'active' ? 'success' : 'danger'" :dot="true">
                  {{ t.status === 'active' ? 'Aktif' : 'Nonaktif' }}
                </Badge>
              </td>
              <td class="whitespace-nowrap px-5 py-3.5 text-sm capitalize text-slate-500">{{ t.subscription_plan }}</td>
              <td class="whitespace-nowrap px-5 py-3.5">
                <Button :href="`/platform/tenants/${t.id}`" variant="ghost" size="xs">Detail</Button>
              </td>
            </tr>
            <tr v-if="!recentTenants?.length">
              <td colspan="5" class="px-5 py-12 text-center">
                <div class="flex flex-col items-center gap-2">
                  <span class="text-3xl">🏫</span>
                  <p class="text-sm font-medium text-slate-500">Belum ada sekolah terdaftar</p>
                  <p class="text-xs text-slate-400">Mulai dengan menambah sekolah pertama</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </Card>
  </div>
</template>
