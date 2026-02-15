<script setup>
import TenantLayout from '@/Layouts/TenantLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import { Link } from '@inertiajs/vue3';

defineOptions({ layout: TenantLayout });

const props = defineProps({
  teacher: Object,
  stats: Object, // { my_classes_count, my_students_count }
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
    <div class="rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 p-6 text-white shadow-lg">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-2xl font-bold">{{ greeting() }}, {{ teacher.name }}! 👨‍🏫</h2>
          <p class="mt-1 text-blue-50 opacity-90">Siap mengajar hari ini? Cek jadwal dan kelola kelas Anda.</p>
        </div>
        <div class="flex gap-2">
           <Button href="/tenant/attendance" variant="secondary" size="sm" class="bg-white/10 text-white hover:bg-white/20 border-transparent">
            Absensi Hari Ini
          </Button>
        </div>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div class="rounded-xl border border-blue-100 bg-blue-50 p-4">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-500 text-white">
                🏫
            </div>
            <div>
                <p class="text-xs font-semibold text-blue-600">Kelas Ajar</p>
                <p class="text-xl font-bold text-slate-800">{{ stats.my_classes_count }} Kelas</p>
            </div>
        </div>
      </div>
      
      <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-4">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-500 text-white">
                👨‍🎓
            </div>
            <div>
                <p class="text-xs font-semibold text-indigo-600">Total Siswa</p>
                <p class="text-xl font-bold text-slate-800">{{ stats.my_students_count }} Siswa</p>
            </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <!-- Left Column: Quick Actions & Schedule -->
      <div class="space-y-6 lg:col-span-2">
        <Card title="Aksi Cepat">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                <Link href="/tenant/grades" class="group flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-white p-4 transition-all hover:border-emerald-500 hover:shadow-md">
                    <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 transition-colors group-hover:bg-emerald-600 group-hover:text-white">
                    📊
                    </div>
                    <span class="text-xs font-medium text-slate-600 group-hover:text-emerald-600">Input Nilai</span>
                </Link>
                 <Link href="/tenant/attendance" class="group flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-white p-4 transition-all hover:border-blue-500 hover:shadow-md">
                    <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-600 transition-colors group-hover:bg-blue-600 group-hover:text-white">
                    📝
                    </div>
                    <span class="text-xs font-medium text-slate-600 group-hover:text-blue-600">Absensi</span>
                </Link>
                 <Link href="/tenant/classes" class="group flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-white p-4 transition-all hover:border-amber-500 hover:shadow-md">
                    <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 text-amber-600 transition-colors group-hover:bg-amber-600 group-hover:text-white">
                    📖
                    </div>
                    <span class="text-xs font-medium text-slate-600 group-hover:text-amber-600">Lihat Kelas</span>
                </Link>
            </div>
        </Card>

        <!-- Schedule Placeholder -->
         <Card title="Jadwal Mengajar Hari Ini">
           <div class="flex flex-col items-center justify-center py-8 text-center">
              <span class="text-4xl">🗓️</span>
              <p class="mt-2 text-sm font-medium text-slate-500">Jadwal belum tersedia</p>
           </div>
        </Card>
      </div>

      <!-- Right Column: Announcements -->
      <div class="lg:col-span-1">
        <Card title="Pengumuman Terbaru" class="h-full">
          <div class="space-y-4">
            <div 
              v-for="announcement in recentAnnouncements" 
              :key="announcement.id"
              class="relative border-l-2 border-blue-500 pl-4 py-1"
            >
              <div class="text-xs text-slate-400">{{ formatDate(announcement.created_at) }}</div>
              <h4 class="text-sm font-semibold text-slate-800 line-clamp-1">{{ announcement.title }}</h4>
              <p class="mt-0.5 line-clamp-2 text-xs text-slate-500">{{ announcement.content }}</p>
            </div>
             <div v-if="!recentAnnouncements?.length" class="py-8 text-center">
              <p class="text-sm text-slate-400">Tidak ada pengumuman.</p>
            </div>
          </div>
           <div class="mt-6 border-t border-slate-100 pt-4">
             <Link href="/tenant/announcements" class="block text-center text-sm font-medium text-blue-600 hover:text-blue-700">
              Lihat Semua →
            </Link>
          </div>
        </Card>
      </div>
    </div>
  </div>
</template>
