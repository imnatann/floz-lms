<script setup>
import { ref, watch, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import SelectInput from '@/Components/UI/SelectInput.vue';
import Label from '@/Components/UI/Label.vue';

defineOptions({ layout: TenantLayout });

const props = defineProps({
  classes: Array,
  students: Array,
  filters: Object,
});

const form = useForm({
  date: props.filters.date,
  class_id: props.filters.class_id,
  attendances: [],
});

const statusOptions = [
  { value: 'present', label: 'Hadir', class: 'bg-emerald-100 text-emerald-800' },
  { value: 'sick', label: 'Sakit', class: 'bg-blue-100 text-blue-800' },
  { value: 'permit', label: 'Izin', class: 'bg-amber-100 text-amber-800' },
  { value: 'absent', label: 'Alpha', class: 'bg-rose-100 text-rose-800' },
];

// Initialize form attendances when students prop changes
watch(() => props.students, (newStudents) => {
  if (newStudents.length > 0) {
    form.attendances = newStudents.map(student => ({
      student_id: student.id,
      status: student.status || 'present',
      notes: student.notes || '',
    }));
  } else {
    form.attendances = [];
  }
}, { immediate: true });

const onFilterChange = () => {
    router.visit('/tenant/attendance', {
        method: 'get',
        data: {
            date: form.date,
            class_id: form.class_id,
        },
        preserveState: true,
        preserveScroll: true,
    });
};

const submit = () => {
  form.post('/tenant/attendance', {
    preserveScroll: true,
    onSuccess: () => {
      // Optional: Show toast
    },
  });
};

const markAll = (status) => {
  form.attendances.forEach(a => a.status = status);
};
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
      <div>
        <h2 class="text-xl font-bold text-slate-800">Absensi Siswa</h2>
        <p class="mt-0.5 text-sm text-slate-400">Kelola kehadiran harian siswa per kelas</p>
      </div>
      <div>
         <Button :disabled="!form.class_id || !students.length || form.processing" @click="submit">
            Simpan Absensi
         </Button>
      </div>
    </div>

    <Card class="p-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
             <div>
                <Label for="date">Tanggal</Label>
                <input type="date" id="date" v-model="form.date" @change="onFilterChange" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
             </div>
             <div>
                <Label for="class">Kelas</Label>
                <select id="class" v-model="form.class_id" @change="onFilterChange" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    <option value="" disabled>Pilih Kelas</option>
                    <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
             </div>
             <div class="flex items-end pb-0.5">
                <div class="text-sm text-slate-500" v-if="students.length">
                    {{ students.length }} Siswa terdaftar
                </div>
             </div>
        </div>
    </Card>

    <div v-if="students.length > 0">
        <div class="flex justify-end gap-2 mb-2">
            <button @click="markAll('present')" class="text-xs font-medium text-emerald-600 hover:text-emerald-800 hover:underline">Set Semua Hadir</button>
            <span class="text-slate-300">|</span>
             <button @click="markAll('sick')" class="text-xs font-medium text-blue-600 hover:text-blue-800 hover:underline">Set Semua Sakit</button>
        </div>

        <Card class="overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-3">NIS</th>
                            <th class="px-6 py-3">Nama Siswa</th>
                            <th class="px-6 py-3 text-center">Status Kehadiran</th>
                            <th class="px-6 py-3">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(student, index) in students" :key="student.id" class="bg-white border-b border-slate-50 hover:bg-slate-50/50">
                            <td class="px-6 py-4 font-mono text-slate-500">{{ student.nis }}</td>
                            <td class="px-6 py-4 font-medium text-slate-900">{{ student.name }}</td>
                            <td class="px-6 py-4">
                                <div class="flex justify-center gap-2">
                                     <label v-for="status in statusOptions" :key="status.value" 
                                        class="cursor-pointer select-none rounded-lg border px-3 py-1.5 text-xs font-medium transition-all"
                                        :class="form.attendances[index].status === status.value 
                                            ? `${status.class} border-transparent ring-2 ring-offset-1 ring-${status.class.split('-')[1]}-500/50` 
                                            : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
                                     >
                                        <input type="radio" :name="`status-${student.id}`" :value="status.value" v-model="form.attendances[index].status" class="sr-only">
                                        {{ status.label }}
                                     </label>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <input type="text" v-model="form.attendances[index].notes" class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-xs sm:leading-6" placeholder="Keterangan...">
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="p-4 border-t border-slate-100 bg-slate-50 flex justify-end">
                 <Button :disabled="form.processing" @click="submit">
                    Simpan Absensi
                 </Button>
            </div>
        </Card>
    </div>
    
    <div v-else-if="form.class_id" class="text-center py-12 rounded-xl border border-dashed border-slate-200">
        <p class="text-slate-500">Tidak ada siswa di kelas ini.</p>
    </div>
    
    <div v-else class="text-center py-12 rounded-xl border border-dashed border-slate-200 bg-slate-50/50">
        <p class="text-slate-500">Silakan pilih kelas untuk memulai absensi.</p>
    </div>

  </div>
</template>
