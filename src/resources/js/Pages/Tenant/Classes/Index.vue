<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';
import SearchInput from '@/Components/UI/SearchInput.vue';
import FormSelect from '@/Components/UI/FormSelect.vue';
import Pagination from '@/Components/UI/Pagination.vue';

defineOptions({ layout: TenantLayout });

const props = defineProps({
  classes: Object,
  academicYears: Array,
  filters: Object,
});

const page = usePage();
const flash = computed(() => page.props.flash || {});

const search = ref(props.filters?.search || '');
const yearFilter = ref(props.filters?.academic_year_id || '');
const statusFilter = ref(props.filters?.status || '');

const applyFilters = () => {
  router.get('/tenant/classes', {
    search: search.value || undefined,
    academic_year_id: yearFilter.value || undefined,
    status: statusFilter.value || undefined,
  }, { preserveState: true, replace: true });
};

const confirmDelete = (cls) => {
  if (confirm(`Yakin ingin menghapus kelas ${cls.name}?`)) {
    router.delete(`/tenant/classes/${cls.id}`);
  }
};
</script>

<template>
  <Head title="Manajemen Kelas" />

  <div>
    <!-- Header -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h2 class="text-xl font-bold text-slate-800">Manajemen Kelas</h2>
        <p class="text-sm text-slate-500 mt-0.5">Kelola kelas dan wali kelas</p>
      </div>
      <Button href="/tenant/classes/create">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Tambah Kelas
      </Button>
    </div>

    <!-- Flash -->
    <div v-if="flash.success" class="mb-4 flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      {{ flash.success }}
    </div>
    <div v-if="flash.error" class="mb-4 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      {{ flash.error }}
    </div>

    <!-- Filters -->
    <div class="mb-5 flex flex-col gap-3 sm:flex-row">
      <div class="relative flex-1">
        <SearchInput v-model="search" @input="applyFilters" placeholder="Cari nama kelas..." />
      </div>
      <div class="w-48">
        <FormSelect v-model="yearFilter" @change="applyFilters">
          <option value="">Semua Tahun Ajaran</option>
          <option v-for="y in academicYears" :key="y.id" :value="y.id">{{ y.name }} {{ y.is_active ? '(Aktif)' : '' }}</option>
        </FormSelect>
      </div>
      <div class="w-48">
        <FormSelect v-model="statusFilter" @change="applyFilters">
          <option value="">Semua Status</option>
          <option value="active">Aktif</option>
          <option value="inactive">Nonaktif</option>
        </FormSelect>
      </div>
    </div>

    <!-- Table -->
    <div v-if="classes?.data?.length > 0" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Kelas</th>
            <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Tingkat</th>
            <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Tahun Ajaran</th>
            <th class="px-5 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">Wali Kelas</th>
            <th class="px-5 py-3 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Siswa</th>
            <th class="px-5 py-3 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Status</th>
            <th class="px-5 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-slate-500">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="cls in classes.data" :key="cls.id" class="transition-colors hover:bg-slate-50/50">
            <td class="px-5 py-3.5">
              <span class="text-sm font-semibold text-slate-800">{{ cls.name }}</span>
            </td>
            <td class="px-5 py-3.5 text-sm text-slate-600">Kelas {{ cls.grade_level }}</td>
            <td class="px-5 py-3.5 text-sm text-slate-600">{{ cls.academic_year?.name || '-' }}</td>
            <td class="px-5 py-3.5 text-sm text-slate-600">{{ cls.homeroom_teacher?.name || '-' }}</td>
            <td class="px-5 py-3.5 text-center">
              <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700">
                {{ cls.students_count }} / {{ cls.max_students }}
              </span>
            </td>
            <td class="px-5 py-3.5 text-center">
              <Badge :variant="cls.status === 'active' ? 'success' : 'default'">
                {{ cls.status === 'active' ? 'Aktif' : 'Nonaktif' }}
              </Badge>
            </td>
            <td class="px-5 py-3.5 text-right">
              <div class="flex items-center justify-end gap-1">
                <Link :href="`/tenant/classes/${cls.id}/edit`" class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-orange-50 hover:text-orange-600" title="Edit">
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </Link>
                <button @click="confirmDelete(cls)" class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-red-50 hover:text-red-500" title="Hapus">
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Empty -->
    <div v-else class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 bg-white py-16">
      <svg class="h-12 w-12 text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
      <h3 class="text-sm font-semibold text-slate-600 mb-1">Belum ada data kelas</h3>
      <p class="text-xs text-slate-400 mb-4">Mulai tambahkan kelas untuk sekolah Anda</p>
      <Button href="/tenant/classes/create">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Tambah Kelas Pertama
      </Button>
    </div>

    <!-- Pagination -->
    <!-- Pagination -->
    <div class="mt-6">
      <Pagination :links="classes.links" />
    </div>
  </div>
</template>
