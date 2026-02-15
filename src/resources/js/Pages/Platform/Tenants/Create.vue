<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import Button from '@/Components/UI/Button.vue';
import FormInput from '@/Components/UI/FormInput.vue';
import FormSelect from '@/Components/UI/FormSelect.vue';
import FormTextarea from '@/Components/UI/FormTextarea.vue';

defineOptions({ layout: PlatformLayout });

const form = useForm({
  name: '',
  education_level: 'SMP',
  email: '',
  npsn: '',
  phone: '',
  address: '',
  subscription_plan: 'starter',
  admin_name: '',
  admin_password: '',
});

const plans = [
  {
    value: 'starter',
    name: 'Starter',
    price: 'Rp 150.000',
    period: '/bulan',
    desc: 'Untuk sekolah kecil dengan kebutuhan dasar',
    features: ['Maks. 100 siswa', 'Rapor digital', 'Cetak PDF'],
    icon: '🚀',
    color: 'orange',
  },
  {
    value: 'professional',
    name: 'Professional',
    price: 'Rp 350.000',
    period: '/bulan',
    desc: 'Untuk sekolah menengah yang berkembang',
    features: ['Maks. 500 siswa', 'Semua fitur Starter', 'Analisis data', 'Dukungan prioritas'],
    icon: '⭐',
    color: 'blue',
    popular: true,
  },
  {
    value: 'enterprise',
    name: 'Enterprise',
    price: 'Rp 750.000',
    period: '/bulan',
    desc: 'Untuk sekolah besar tanpa batas',
    features: ['Siswa unlimited', 'Semua fitur Pro', 'API access', 'Dedikasi support'],
    icon: '🏢',
    color: 'purple',
  },
];

const selectedPlan = computed(() => plans.find(p => p.value === form.subscription_plan));

const submit = () => {
  form.post('/platform/tenants');
};
</script>

<template>
  <Head title="Tambah Sekolah" />

  <div class="mx-auto max-w-3xl space-y-6">
    <!-- Page Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <Button href="/platform/tenants" variant="outline" size="sm">
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
          Kembali
        </Button>
        <div>
          <h2 class="text-xl font-bold text-slate-800">Tambah Sekolah Baru</h2>
          <p class="mt-0.5 text-sm text-slate-400">Daftarkan sekolah baru ke platform FLOZ</p>
        </div>
      </div>
    </div>

    <form @submit.prevent="submit" class="space-y-6">
      <!-- ═══════ Section 1: School Info ═══════ -->
      <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 bg-slate-50/60 px-6 py-4">
          <div class="flex items-center gap-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-100 text-sm">🏫</div>
            <div>
              <h3 class="text-sm font-semibold text-slate-700">Informasi Sekolah</h3>
              <p class="text-xs text-slate-400">Data dasar yang diperlukan untuk pendaftaran</p>
            </div>
          </div>
        </div>
        <div class="space-y-4 p-6">
          <FormInput
            v-model="form.name"
            label="Nama Sekolah"
            placeholder="Contoh: SMP Negeri 1 Jakarta"
            :required="true"
            :error="form.errors.name"
          />

          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <FormSelect
              v-model="form.education_level"
              label="Jenjang Pendidikan"
              :required="true"
              :error="form.errors.education_level"
            >
              <option value="SD">SD — Sekolah Dasar</option>
              <option value="SMP">SMP — Sekolah Menengah Pertama</option>
              <option value="SMA">SMA — Sekolah Menengah Atas</option>
            </FormSelect>

            <FormInput
              v-model="form.npsn"
              label="NPSN"
              placeholder="8 digit nomor NPSN"
              hint="Opsional"
              :error="form.errors.npsn"
            />
          </div>

          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <FormInput
              v-model="form.email"
              label="Email Sekolah"
              type="email"
              placeholder="sekolah@email.com"
              :required="true"
              :error="form.errors.email"
            />
            <FormInput
              v-model="form.phone"
              label="Telepon"
              type="tel"
              placeholder="0812-xxxx-xxxx"
              hint="Opsional"
              :error="form.errors.phone"
            />
          </div>

          <FormTextarea
            v-model="form.address"
            label="Alamat Sekolah"
            placeholder="Jl. Contoh No. 123, Kota, Provinsi"
            :rows="2"
            :error="form.errors.address"
          />
        </div>
      </div>

      <!-- ═══════ Section 2: Subscription Plan ═══════ -->
      <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 bg-slate-50/60 px-6 py-4">
          <div class="flex items-center gap-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-sm">💎</div>
            <div>
              <h3 class="text-sm font-semibold text-slate-700">Paket Langganan</h3>
              <p class="text-xs text-slate-400">Pilih paket yang sesuai kebutuhan sekolah</p>
            </div>
          </div>
        </div>
        <div class="p-6">
          <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <label
              v-for="plan in plans"
              :key="plan.value"
              :class="[
                'relative flex cursor-pointer flex-col rounded-xl border-2 p-4 transition-all duration-200',
                form.subscription_plan === plan.value
                  ? 'border-orange-400 bg-orange-50/50 shadow-sm shadow-orange-500/10'
                  : 'border-slate-200 bg-white hover:border-slate-300 hover:shadow-sm',
              ]"
            >
              <input
                type="radio"
                :value="plan.value"
                v-model="form.subscription_plan"
                class="sr-only"
              />
              <!-- Popular Badge -->
              <span
                v-if="plan.popular"
                class="absolute -top-2.5 right-3 rounded-full bg-gradient-to-r from-orange-500 to-orange-600 px-2.5 py-0.5 text-[10px] font-semibold text-white shadow-sm"
              >
                Populer
              </span>
              <!-- Plan icon -->
              <div class="mb-3 text-2xl">{{ plan.icon }}</div>
              <!-- Plan name & price -->
              <div class="mb-1 text-sm font-semibold text-slate-700">{{ plan.name }}</div>
              <div class="mb-2 flex items-baseline gap-0.5">
                <span class="text-lg font-bold text-slate-800">{{ plan.price }}</span>
                <span class="text-xs text-slate-400">{{ plan.period }}</span>
              </div>
              <p class="mb-3 text-xs leading-relaxed text-slate-400">{{ plan.desc }}</p>
              <!-- Features -->
              <ul class="space-y-1">
                <li v-for="feat in plan.features" :key="feat" class="flex items-center gap-1.5 text-xs text-slate-500">
                  <svg class="h-3.5 w-3.5 shrink-0 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                  {{ feat }}
                </li>
              </ul>
              <!-- Selected indicator -->
              <div
                :class="[
                  'mt-3 flex items-center justify-center rounded-lg py-1.5 text-xs font-medium transition-all',
                  form.subscription_plan === plan.value
                    ? 'bg-orange-600 text-white'
                    : 'bg-slate-100 text-slate-400',
                ]"
              >
                {{ form.subscription_plan === plan.value ? '✓ Dipilih' : 'Pilih Paket' }}
              </div>
            </label>
          </div>
        </div>
      </div>

      <!-- ═══════ Section 3: Admin Account ═══════ -->
      <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 bg-slate-50/60 px-6 py-4">
          <div class="flex items-center gap-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-sm">👤</div>
            <div>
              <h3 class="text-sm font-semibold text-slate-700">Akun Admin Sekolah</h3>
              <p class="text-xs text-slate-400">Admin ini akan mengelola sekolah di platform</p>
            </div>
          </div>
        </div>
        <div class="space-y-4 p-6">
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <FormInput
              v-model="form.admin_name"
              label="Nama Admin"
              placeholder="Nama lengkap admin"
              :error="form.errors.admin_name"
            />
            <FormInput
              v-model="form.admin_password"
              label="Password Admin"
              type="password"
              placeholder="Minimal 8 karakter"
              hint="Min. 8 karakter"
              :error="form.errors.admin_password"
            />
          </div>
          <!-- Info box -->
          <div class="flex gap-3 rounded-lg border border-blue-100 bg-blue-50/50 p-3.5">
            <svg class="h-5 w-5 shrink-0 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
            <div class="text-xs leading-relaxed text-blue-700">
              Admin sekolah akan mendapatkan akses penuh untuk mengelola data guru, siswa, dan rapor di lingkup sekolahnya.
            </div>
          </div>
        </div>
      </div>

      <!-- ═══════ Actions ═══════ -->
      <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-6 py-4 shadow-sm">
        <p class="text-xs text-slate-400">
          Paket terpilih: <span class="font-medium text-slate-600">{{ selectedPlan?.name }}</span> · {{ selectedPlan?.price }}{{ selectedPlan?.period }}
        </p>
        <div class="flex items-center gap-3">
          <Button href="/platform/tenants" variant="ghost">Batal</Button>
          <Button type="submit" :loading="form.processing">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Simpan Sekolah
          </Button>
        </div>
      </div>
    </form>
  </div>
</template>
