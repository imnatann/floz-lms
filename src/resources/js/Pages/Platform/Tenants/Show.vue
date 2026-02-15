<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  tenant: Object,
});
</script>

<template>
  <div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <Link href="/platform/tenants" class="btn btn-ghost btn-sm">← Kembali</Link>
        <div>
          <h2 class="text-2xl font-bold text-base-content">{{ tenant.name }}</h2>
          <p class="text-sm text-base-content/60 mt-1">Detail dan informasi sekolah</p>
        </div>
      </div>
      <Link :href="`/platform/tenants/${tenant.id}/edit`" class="btn btn-primary btn-sm">Edit</Link>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Main Info -->
      <div class="lg:col-span-2 card bg-base-100 shadow-sm border border-base-300">
        <div class="card-body">
          <h3 class="font-semibold text-base-content mb-4">Informasi Sekolah</h3>
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div><span class="text-base-content/50">Jenjang</span><p class="font-medium mt-1">{{ tenant.education_level }}</p></div>
            <div><span class="text-base-content/50">NPSN</span><p class="font-medium mt-1">{{ tenant.npsn || '—' }}</p></div>
            <div><span class="text-base-content/50">Email</span><p class="font-medium mt-1">{{ tenant.email }}</p></div>
            <div><span class="text-base-content/50">Telepon</span><p class="font-medium mt-1">{{ tenant.phone || '—' }}</p></div>
            <div class="col-span-2"><span class="text-base-content/50">Alamat</span><p class="font-medium mt-1">{{ tenant.address || '—' }}</p></div>
            <div><span class="text-base-content/50">Slug</span><p class="font-medium mt-1">{{ tenant.slug }}.floz.id</p></div>
            <div><span class="text-base-content/50">Database</span><p class="font-medium mt-1 font-mono text-xs">{{ tenant.database_name }}</p></div>
          </div>
        </div>
      </div>

      <!-- Sidebar Info -->
      <div class="space-y-4">
        <div class="card bg-base-100 shadow-sm border border-base-300">
          <div class="card-body">
            <h3 class="font-semibold text-base-content mb-3">Status</h3>
            <span :class="['badge', tenant.status === 'active' ? 'badge-success' : 'badge-error']">
              {{ tenant.status === 'active' ? 'Aktif' : 'Nonaktif' }}
            </span>
          </div>
        </div>
        <div class="card bg-base-100 shadow-sm border border-base-300">
          <div class="card-body">
            <h3 class="font-semibold text-base-content mb-3">Langganan</h3>
            <p class="capitalize font-medium">{{ tenant.subscription_plan }}</p>
            <p class="text-sm text-base-content/50">Maks. {{ tenant.max_students }} siswa</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
