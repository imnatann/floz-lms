<script setup>
import { Link, router, Head } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';
import SearchInput from '@/Components/UI/SearchInput.vue';
import SelectInput from '@/Components/UI/SelectInput.vue';
import Card from '@/Components/UI/Card.vue';

defineOptions({ layout: PlatformLayout });

const props = defineProps({
  tenants: Object,
  filters: Object,
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
const educationLevel = ref(props.filters?.education_level || '');

let debounceTimer;
watch([search, status, educationLevel], () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    router.get('/platform/tenants', {
      search: search.value,
      status: status.value,
      education_level: educationLevel.value,
    }, { preserveState: true, replace: true });
  }, 300);
});
</script>

<template>
  <Head title="Manajemen Sekolah" />

  <div class="space-y-5">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-slate-800">Manajemen Sekolah</h2>
        <p class="mt-1 text-sm text-slate-400">Kelola pendaftaran dan pengelolaan sekolah</p>
      </div>
      <Button href="/platform/tenants/create" size="sm">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Tambah Sekolah
      </Button>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap items-center gap-3">
      <SearchInput v-model="search" placeholder="Cari nama sekolah..." class="w-72" />
      <SelectInput v-model="status">
        <option value="">Semua Status</option>
        <option value="active">Aktif</option>
        <option value="suspended">Ditangguhkan</option>
      </SelectInput>
      <SelectInput v-model="educationLevel">
        <option value="">Semua Jenjang</option>
        <option value="SD">SD</option>
        <option value="SMP">SMP</option>
        <option value="SMA">SMA</option>
      </SelectInput>
    </div>

    <!-- Table -->
    <Card :noPadding="true">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="border-b border-slate-100 bg-slate-50/60">
            <tr>
              <th class="whitespace-nowrap px-5 py-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Sekolah</th>
              <th class="whitespace-nowrap px-5 py-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Jenjang</th>
              <th class="whitespace-nowrap px-5 py-3 text-xs font-semibold uppercase tracking-wider text-slate-400">NPSN</th>
              <th class="whitespace-nowrap px-5 py-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Status</th>
              <th class="whitespace-nowrap px-5 py-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Paket</th>
              <th class="whitespace-nowrap px-5 py-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Maks. Siswa</th>
              <th class="whitespace-nowrap px-5 py-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="tenant in tenants?.data" :key="tenant.id" class="transition-colors hover:bg-orange-50/30">
              <td class="whitespace-nowrap px-5 py-3.5">
                <div class="flex items-center gap-3">
                  <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-orange-100 to-orange-50 text-sm font-bold text-orange-600">
                    {{ tenant.name?.charAt(0) }}
                  </div>
                  <div>
                    <p class="font-medium text-slate-700">{{ tenant.name }}</p>
                    <p class="text-xs text-slate-400">{{ tenant.email }}</p>
                  </div>
                </div>
              </td>
              <td class="whitespace-nowrap px-5 py-3.5">
                <Badge variant="orange">{{ tenant.education_level }}</Badge>
              </td>
              <td class="whitespace-nowrap px-5 py-3.5 text-slate-500">{{ tenant.npsn || '—' }}</td>
              <td class="whitespace-nowrap px-5 py-3.5">
                <Badge :variant="tenant.status === 'active' ? 'success' : 'danger'" :dot="true">
                  {{ tenant.status === 'active' ? 'Aktif' : 'Nonaktif' }}
                </Badge>
              </td>
              <td class="whitespace-nowrap px-5 py-3.5 capitalize text-slate-500">{{ tenant.subscription_plan }}</td>
              <td class="whitespace-nowrap px-5 py-3.5 text-slate-500">{{ tenant.max_students }}</td>
              <td class="whitespace-nowrap px-5 py-3.5">
                <div class="flex items-center gap-1">
                  <Button :href="`/platform/tenants/${tenant.id}`" variant="ghost" size="xs">Detail</Button>
                  <Button :href="`/platform/tenants/${tenant.id}/edit`" variant="ghost" size="xs">Edit</Button>
                </div>
              </td>
            </tr>
            <tr v-if="!tenants?.data?.length">
              <td colspan="7" class="px-5 py-16 text-center">
                <div class="flex flex-col items-center gap-3">
                  <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-2xl">🏫</div>
                  <div>
                    <p class="text-sm font-medium text-slate-600">Belum ada sekolah terdaftar</p>
                    <p class="mt-0.5 text-xs text-slate-400">Mulai dengan mendaftarkan sekolah pertama</p>
                  </div>
                  <Button href="/platform/tenants/create" size="sm" class="mt-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Sekolah
                  </Button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </Card>

    <!-- Pagination -->
    <div v-if="tenants?.links?.length > 3" class="flex justify-center gap-1">
      <template v-for="link in tenants.links" :key="link.label">
        <Link
          v-if="link.url"
          :href="link.url"
          :class="[
            'inline-flex h-8 min-w-[2rem] items-center justify-center rounded-lg px-3 text-xs font-medium transition-colors',
            link.active
              ? 'bg-orange-600 text-white shadow-sm shadow-orange-600/20'
              : 'text-slate-500 hover:bg-slate-100',
          ]"
          v-html="link.label"
          preserve-state
        />
        <span v-else class="inline-flex h-8 min-w-[2rem] items-center justify-center px-3 text-xs text-slate-300" v-html="link.label" />
      </template>
    </div>
  </div>
</template>
