<script setup>
import TenantLayout from '@/Layouts/TenantLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';
import Button from '@/Components/UI/Button.vue';
import Label from '@/Components/UI/Label.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import FormSelect from '@/Components/UI/FormSelect.vue';
import TiptapEditor from '@/Components/Editor/TiptapEditor.vue';
import { ref } from 'vue';

defineOptions({ layout: TenantLayout });

const props = defineProps({
  announcement: {
    type: Object,
    default: null,
  },
});

const form = useForm({
  title: props.announcement?.title || '',
  content: props.announcement?.content || '',
  excerpt: props.announcement?.excerpt || '',
  cover_image: null, // For file upload
  cover_image_url: props.announcement?.cover_image_url || '',
  target_audience: props.announcement?.target_audience || 'all',
  type: props.announcement?.type || 'info',
  is_pinned: props.announcement?.is_pinned || false,
  is_published: props.announcement?.is_published !== undefined ? props.announcement.is_published : true,
});

const previewImage = ref(props.announcement?.cover_image_url || null);

const handleImageUpload = (event) => {
  const file = event.target.files[0];
  if (file) {
    form.cover_image = file;
    previewImage.value = URL.createObjectURL(file);
  }
};

const submit = () => {
  if (props.announcement) {
    form.post(`/tenant/announcements/${props.announcement.id}`, {
        _method: 'put',
        forceFormData: true,
    });
  } else {
    form.post('/tenant/announcements');
  }
};
</script>

<template>
  <div class="mx-auto max-w-4xl space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-800">
          {{ announcement ? 'Edit Pengumuman' : 'Buat Pengumuman Baru' }}
        </h1>
        <p class="text-sm text-slate-500">
           {{ announcement ? 'Perbarui informasi pengumuman.' : 'Bagikan informasi penting kepada warga sekolah.' }}
        </p>
      </div>
      <Button variant="secondary" href="/tenant/announcements">Batal</Button>
    </div>

    <!-- Form -->
    <form @submit.prevent="submit" class="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
      
      <!-- Title & Type -->
      <div class="grid gap-6 md:grid-cols-3">
        <div class="md:col-span-2">
            <FormInput 
              v-model="form.title" 
              label="Judul Pengumuman" 
              placeholder="Contoh: Jadwal Libur Semester Genap" 
              :error="form.errors.title"
              required
            />
        </div>
        <div>
            <FormSelect
                v-model="form.type"
                label="Tipe"
                :error="form.errors.type"
                :options="[
                    { value: 'info', label: 'Informasi' },
                    { value: 'event', label: 'Kegiatan' },
                    { value: 'alert', label: 'Penting/Darurat' },
                ]"
            />
        </div>
      </div>

      <!-- Settings -->
      <div class="grid gap-6 md:grid-cols-3">
         <div>
            <FormSelect
                v-model="form.target_audience"
                label="Target Audiens"
                :error="form.errors.target_audience"
                :options="[
                    { value: 'all', label: 'Semua Warga Sekolah' },
                    { value: 'teachers', label: 'Hanya Guru & Staff' },
                    { value: 'students', label: 'Hanya Siswa' },
                ]"
            />
         </div>
         <div class="flex items-center gap-4 pt-6">
             <label class="flex items-center gap-2 cursor-pointer">
                 <input type="checkbox" v-model="form.is_pinned" class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                 <span class="text-sm font-medium text-slate-700">Pin ke Atas</span>
             </label>
             <label class="flex items-center gap-2 cursor-pointer">
                 <input type="checkbox" v-model="form.is_published" class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                 <span class="text-sm font-medium text-slate-700">Publikasikan Langsung</span>
             </label>
         </div>
      </div>

      <!-- Cover Image -->
      <div>
         <Label>Gambar Sampul</Label>
         
         <!-- Image Preview -->
         <div v-if="previewImage" class="relative mb-3 aspect-video w-full overflow-hidden rounded-lg border border-slate-200 bg-slate-100 sm:w-1/2">
            <img :src="previewImage" class="h-full w-full object-cover" />
            <button 
                type="button" 
                @click="form.cover_image = null; previewImage = null" 
                class="absolute right-2 top-2 rounded-full bg-white/80 p-1 text-slate-600 hover:bg-white hover:text-red-500"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
         </div>

         <div class="flex items-center justify-center w-full">
            <label for="dropzone-file" class="flex flex-col items-center justify-center w-full h-32 border-2 border-slate-300 border-dashed rounded-lg cursor-pointer bg-slate-50 hover:bg-slate-100">
                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                    <svg class="w-8 h-8 mb-4 text-slate-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
                    </svg>
                    <p class="mb-2 text-sm text-slate-500"><span class="font-semibold">Klik untuk upload</span> atau drag and drop</p>
                    <p class="text-xs text-slate-500">SVG, PNG, JPG or GIF (MAX. 2MB)</p>
                </div>
                <input id="dropzone-file" type="file" class="hidden" accept="image/*" @change="handleImageUpload" />
            </label>
        </div> 
        <p v-if="form.errors.cover_image" class="mt-1 text-xs text-red-500">{{ form.errors.cover_image }}</p>
      </div>

      <!-- Excerpt -->
       <div>
         <Label>Ringkasan (Excerpt)</Label>
         <textarea 
            v-model="form.excerpt"
            rows="2"
            class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
            placeholder="Tulis ringkasan singkat untuk tampilan kartu..."
         ></textarea>
         <p v-if="form.errors.excerpt" class="mt-1 text-xs text-red-500">{{ form.errors.excerpt }}</p>
      </div>

      <!-- Rich Text Content -->
      <div>
         <Label required>Konten Lengkap</Label>
         <TiptapEditor v-model="form.content" placeholder="Tulis pengumuman lengkap di sini... (Supports Bold, Italic, H1, H2, Lists)" />
         <p v-if="form.errors.content" class="mt-1 text-xs text-red-500">{{ form.errors.content }}</p>
      </div>

      <!-- Actions -->
      <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
         <Button variant="secondary" href="/tenant/announcements">Batal</Button>
         <Button type="submit" :disabled="form.processing">
            {{ form.processing ? 'Menyimpan...' : (announcement ? 'Perbarui Pengumuman' : 'Buat Pengumuman') }}
         </Button>
      </div>
    </form>
  </div>
</template>
