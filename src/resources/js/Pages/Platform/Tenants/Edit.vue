<script setup>
import { useForm, Link } from '@inertiajs/vue3';

const props = defineProps({ tenant: Object });

const form = useForm({
  name: props.tenant.name,
  education_level: props.tenant.education_level,
  email: props.tenant.email,
  phone: props.tenant.phone || '',
  address: props.tenant.address || '',
  subscription_plan: props.tenant.subscription_plan,
  max_students: props.tenant.max_students,
  status: props.tenant.status,
});

const submit = () => {
  form.put(`/platform/tenants/${props.tenant.id}`);
};
</script>

<template>
  <div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center gap-4">
      <Link :href="`/platform/tenants/${tenant.id}`" class="btn btn-ghost btn-sm">← Kembali</Link>
      <h2 class="text-2xl font-bold text-base-content">Edit {{ tenant.name }}</h2>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-300">
      <div class="card-body">
        <form @submit.prevent="submit" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="form-control md:col-span-2">
              <label class="label"><span class="label-text">Nama *</span></label>
              <input v-model="form.name" type="text" class="input input-bordered" required />
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text">Jenjang</span></label>
              <select v-model="form.education_level" class="select select-bordered">
                <option value="SD">SD</option><option value="SMP">SMP</option><option value="SMA">SMA</option>
              </select>
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text">Status</span></label>
              <select v-model="form.status" class="select select-bordered">
                <option value="active">Aktif</option>
                <option value="suspended">Ditangguhkan</option>
                <option value="inactive">Nonaktif</option>
              </select>
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text">Email</span></label>
              <input v-model="form.email" type="email" class="input input-bordered" />
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text">Telepon</span></label>
              <input v-model="form.phone" type="text" class="input input-bordered" />
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text">Paket</span></label>
              <select v-model="form.subscription_plan" class="select select-bordered">
                <option value="starter">Starter</option><option value="professional">Professional</option><option value="enterprise">Enterprise</option>
              </select>
            </div>
            <div class="form-control">
              <label class="label"><span class="label-text">Maks. Siswa</span></label>
              <input v-model="form.max_students" type="number" class="input input-bordered" min="1" />
            </div>
            <div class="form-control md:col-span-2">
              <label class="label"><span class="label-text">Alamat</span></label>
              <textarea v-model="form.address" class="textarea textarea-bordered" rows="2"></textarea>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-4">
            <Link :href="`/platform/tenants/${tenant.id}`" class="btn btn-ghost">Batal</Link>
            <button type="submit" :disabled="form.processing" class="btn btn-primary" :class="{ loading: form.processing }">Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
