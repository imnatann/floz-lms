<script setup>
import TenantLayout from '@/Layouts/TenantLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';
import { Link } from '@inertiajs/vue3';

defineOptions({ layout: TenantLayout });

const props = defineProps({
  student: Object,
  stats: Object,
  recentAnnouncements: Array,
});

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });
};

const greeting = () => {
  const hour = new Date().getHours();
  if (hour < 12) return 'Selamat Pagi';
  if (hour < 15) return 'Selamat Siang';
  if (hour < 18) return 'Selamat Sore';
  return 'Selamat Malam';
};
</script>

<template>
  <div class="space-y-6">
    <!-- Welcome Header -->
    <div class="rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 p-6 text-white shadow-lg">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-2xl font-bold">{{ greeting() }}, {{ student.name }}! 👋</h2>
          <p class="mt-1 text-orange-50 opacity-90">Semangat belajar hari ini! Jangan lupa cek jadwal dan tugasmu.</p>
        </div>
        <div class="flex gap-2">
          <Button :href="`/tenant/students/${student.id}`" variant="secondary" size="sm" class="bg-white/10 text-white hover:bg-white/20 border-transparent">
            Profil Saya
          </Button>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <!-- Left Column: Stats & Quick Actions -->
      <div class="space-y-6 lg:col-span-2">
        <!-- Quick Stats Row -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
          <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
             <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Kehadiran</p>
             <p class="mt-1 text-2xl font-bold text-slate-700">{{ stats.attendance_percentage }}%</p>
             <p class="text-xs text-slate-400">Semester ini</p>
          </div>
          <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
             <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Kelas</p>
             <p class="mt-1 text-2xl font-bold text-slate-700">{{ student.class?.name || '-' }}</p>
             <p class="text-xs text-slate-400">Wali Kelas: {{ student.class?.homeroom_teacher?.name || '-' }}</p>
          </div>
          <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
             <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Status</p>
             <div class="mt-2">
                <Badge :color="student.status === 'active' ? 'emerald' : 'amber'">{{ student.status === 'active' ? 'Aktif' : 'Non-Aktif' }}</Badge>
             </div>
          </div>
        </div>

        <!-- Today's Schedule (Placeholder) -->
        <Card title="Jadwal Hari Ini" subtitle="Mata pelajaran yang harus diikuti">
           <div class="flex flex-col items-center justify-center py-8 text-center">
              <span class="text-4xl">📅</span>
              <p class="mt-2 text-sm font-medium text-slate-500">Jadwal pelajaran belum tersedia</p>
           </div>
        </Card>
      </div>

      <!-- Right Column: Announcements -->
      <div class="lg:col-span-1">
        <Card title="Pengumuman Sekolah" class="h-full">
          <div class="space-y-4">
            <div 
              v-for="announcement in recentAnnouncements" 
              :key="announcement.id"
              class="relative border-l-2 border-orange-500 pl-4 py-1"
            >
              <div class="text-xs text-slate-400">{{ formatDate(announcement.created_at) }}</div>
              <h4 class="text-sm font-semibold text-slate-800 line-clamp-1">{{ announcement.title }}</h4>
              <p class="mt-0.5 line-clamp-2 text-xs text-slate-500">{{ announcement.content }}</p>
            </div>
            <div v-if="!recentAnnouncements?.length" class="py-8 text-center">
              <p class="text-sm text-slate-400">Tidak ada pengumuman baru.</p>
            </div>
          </div>
           <div class="mt-6 border-t border-slate-100 pt-4">
             <Link href="/tenant/announcements" class="block text-center text-sm font-medium text-orange-600 hover:text-orange-700">
              Lihat Semua →
            </Link>
          </div>
        </Card>
      </div>
    </div>
  </div>
</template>
