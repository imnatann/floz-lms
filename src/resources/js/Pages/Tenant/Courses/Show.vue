<script setup>
import { ref, reactive, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';

defineOptions({ layout: TenantLayout });

const props = defineProps({
  course: Object,
  meetings: Array,
  is_teacher: Boolean,
  is_student: Boolean,
});

// ---- ACCORDION STATE ----
const expandedMeetings = ref({});
const toggleMeeting = (id) => {
  expandedMeetings.value[id] = !expandedMeetings.value[id];
};

// ---- MEETING EDIT STATE (teacher) ----
const editingMeetingId = ref(null);
const editForm = reactive({ title: '', description: '' });

const startEditMeeting = (meeting) => {
  editingMeetingId.value = meeting.id;
  editForm.title = meeting.title;
  editForm.description = meeting.description || '';
};

const cancelEditMeeting = () => { editingMeetingId.value = null; };

const saveEditMeeting = (meeting) => {
  router.put(`/tenant/meetings/${meeting.id}`, {
    title: editForm.title,
    description: editForm.description,
  }, { 
    preserveScroll: true,
    onSuccess: () => { editingMeetingId.value = null; },
  });
};

// ---- LOCK/UNLOCK ----
const toggleLock = (meeting) => {
  router.put(`/tenant/meetings/${meeting.id}`, {
    is_locked: !meeting.is_locked,
  }, { preserveScroll: true });
};

// ---- ADD MATERIAL MODAL ----
const showMaterialModal = ref(null); // meeting id
const materialForm = useForm({
  title: '',
  type: 'file',
  content: '',
  url: '',
  file: null,
});

const openMaterialModal = (meetingId) => {
  showMaterialModal.value = meetingId;
  materialForm.reset();
};

const closeMaterialModal = () => {
  showMaterialModal.value = null;
  materialForm.reset();
};

const submitMaterial = (meetingId) => {
  materialForm.post(`/tenant/meetings/${meetingId}/materials`, {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => closeMaterialModal(),
  });
};

const deleteMaterial = (materialId) => {
  if (!confirm('Hapus materi ini?')) return;
  router.delete(`/tenant/materials/${materialId}`, { preserveScroll: true });
};

// ---- HELPERS ----
const getMeetingLabel = (meeting) => {
  if (meeting.meeting_number === 15) return 'UTS';
  if (meeting.meeting_number === 16) return 'UAS';
  return `P${meeting.meeting_number}`;
};

const getMeetingColor = (meeting) => {
  if (meeting.meeting_number === 15) return 'bg-amber-500';
  if (meeting.meeting_number === 16) return 'bg-red-500';
  return 'bg-blue-500';
};

const getMeetingBorderColor = (meeting) => {
  if (meeting.meeting_number === 15) return 'border-amber-200 bg-amber-50/30';
  if (meeting.meeting_number === 16) return 'border-red-200 bg-red-50/30';
  return 'border-slate-200';
};

const getFileIcon = (fileName) => {
  if (!fileName) return '📄';
  const ext = fileName.split('.').pop()?.toLowerCase();
  if (['pdf'].includes(ext)) return '📕';
  if (['doc', 'docx'].includes(ext)) return '📘';
  if (['xls', 'xlsx'].includes(ext)) return '📗';
  if (['ppt', 'pptx'].includes(ext)) return '📙';
  if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) return '🖼️';
  if (['mp4', 'avi', 'mov'].includes(ext)) return '🎬';
  if (['zip', 'rar', '7z'].includes(ext)) return '📦';
  return '📄';
};

const formatBytes = (bytes) => {
  if (!bytes) return '';
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / 1048576).toFixed(1) + ' MB';
};

const getAssignmentBadge = (assignment) => {
  if (assignment.type === 'quiz') return { text: 'Quiz', variant: 'info' };
  return { text: 'Tugas', variant: 'secondary' };
};

const visibleMeetings = computed(() => {
  if (props.is_teacher) return props.meetings;
  // Students see all meetings, but locked ones show as locked
  return props.meetings;
});
</script>

<template>
  <Head :title="course.subject?.name" />

  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
      <div class="h-2 bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500"></div>
      <div class="p-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
          <div>
            <nav class="flex text-xs text-slate-400 mb-2">
              <Link href="/tenant/courses" class="hover:text-blue-600">Kursus</Link>
              <span class="mx-1.5">/</span>
              <span class="text-slate-600">{{ course.subject?.name }}</span>
            </nav>
            <h1 class="text-2xl font-bold text-slate-800">{{ course.subject?.name }}</h1>
            <div class="flex flex-wrap items-center gap-3 mt-2">
              <Badge variant="info">{{ course.school_class?.name }}</Badge>
              <span class="text-sm text-slate-500">{{ course.teacher?.name }}</span>
              <span class="text-xs text-slate-400">{{ course.academic_year?.name }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Meetings List -->
    <div class="space-y-3">
      <div
        v-for="meeting in visibleMeetings"
        :key="meeting.id"
        class="bg-white rounded-xl shadow-sm border overflow-hidden transition-all duration-200"
        :class="getMeetingBorderColor(meeting)"
      >
        <!-- Meeting Header (Accordion Trigger) -->
        <div
          @click="(is_student && meeting.is_locked) ? null : toggleMeeting(meeting.id)"
          class="flex items-center gap-3 px-5 py-4 select-none"
          :class="(is_student && meeting.is_locked) ? 'cursor-not-allowed opacity-60' : 'cursor-pointer hover:bg-slate-50/80'"
        >
          <!-- Meeting Number Badge -->
          <span class="flex-shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-lg text-white text-xs font-bold shadow-sm"
            :class="getMeetingColor(meeting)">
            {{ getMeetingLabel(meeting) }}
          </span>

          <!-- Title & Meta -->
          <div class="flex-1 min-w-0">
            <div v-if="editingMeetingId !== meeting.id" class="flex items-center gap-2">
              <h3 class="font-semibold text-slate-800 truncate">{{ meeting.title }}</h3>
              <Badge v-if="meeting.is_locked && is_teacher" variant="secondary" class="flex-shrink-0">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                Terkunci
              </Badge>
              <Badge v-if="meeting.is_locked && is_student" variant="secondary" class="flex-shrink-0">🔒 Terkunci</Badge>
            </div>
            <!-- Inline edit mode -->
            <div v-else class="flex items-center gap-2" @click.stop>
              <input v-model="editForm.title" class="text-sm font-semibold border border-blue-300 rounded-lg px-3 py-1.5 w-full focus:ring-1 focus:ring-blue-400 outline-none" />
            </div>
            
            <div class="flex items-center gap-3 mt-0.5 text-xs text-slate-400">
              <span v-if="meeting.materials?.length">{{ meeting.materials.length }} materi</span>
              <span v-if="meeting.assignments?.length">{{ meeting.assignments.length }} tugas</span>
              <span v-if="meeting.description && editingMeetingId !== meeting.id" class="truncate max-w-[200px]">{{ meeting.description }}</span>
            </div>
          </div>

          <!-- Teacher Actions -->
          <div v-if="is_teacher" class="flex items-center gap-1 flex-shrink-0" @click.stop>
            <!-- Lock/Unlock -->
            <button @click="toggleLock(meeting)" class="p-2 rounded-lg hover:bg-slate-100 transition-colors" :title="meeting.is_locked ? 'Buka kunci' : 'Kunci'">
              <svg v-if="meeting.is_locked" class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
              <svg v-else class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>
            </button>
            <!-- Edit -->
            <button v-if="editingMeetingId !== meeting.id" @click="startEditMeeting(meeting)" class="p-2 rounded-lg hover:bg-slate-100 transition-colors" title="Edit">
              <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
            </button>
            <template v-else>
              <button @click="saveEditMeeting(meeting)" class="p-2 rounded-lg hover:bg-emerald-50 text-emerald-600 transition-colors" title="Simpan">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
              </button>
              <button @click="cancelEditMeeting()" class="p-2 rounded-lg hover:bg-red-50 text-red-500 transition-colors" title="Batal">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
              </button>
            </template>
          </div>

          <!-- Expand/Collapse Arrow -->
          <div v-if="!(is_student && meeting.is_locked)" class="flex-shrink-0">
            <svg class="w-5 h-5 text-slate-400 transition-transform duration-200"
              :class="{ 'rotate-180': expandedMeetings[meeting.id] }"
              fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
          </div>
        </div>

        <!-- Meeting Content (Expanded) -->
        <div v-if="expandedMeetings[meeting.id] && !(is_student && meeting.is_locked)" class="border-t border-slate-100">
          <!-- Description edit (if editing) -->
          <div v-if="editingMeetingId === meeting.id" class="px-5 py-3 bg-blue-50/50">
            <label class="text-xs font-medium text-slate-500 mb-1 block">Deskripsi</label>
            <textarea v-model="editForm.description" rows="2" class="w-full text-sm border border-blue-200 rounded-lg px-3 py-2 focus:ring-1 focus:ring-blue-400 outline-none resize-none" placeholder="Deskripsi pertemuan..."></textarea>
          </div>

          <!-- Description display -->
          <div v-else-if="meeting.description" class="px-5 py-3 bg-slate-50/50 border-b border-slate-100">
            <p class="text-sm text-slate-600 whitespace-pre-wrap">{{ meeting.description }}</p>
          </div>

          <div class="p-5 space-y-4">
            <!-- MATERIALS -->
            <div v-if="meeting.materials?.length > 0 || is_teacher">
              <div class="flex items-center justify-between mb-3">
                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Materi</h4>
                <button v-if="is_teacher" @click="openMaterialModal(meeting.id)" class="text-xs text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                  Tambah Materi
                </button>
              </div>

              <div v-if="meeting.materials?.length > 0" class="space-y-2">
                <div v-for="material in meeting.materials" :key="material.id" class="flex items-center gap-3 p-3 rounded-lg border border-slate-100 hover:border-slate-200 hover:bg-slate-50/50 transition-colors group">
                  <!-- Icon -->
                  <span class="text-lg flex-shrink-0">
                    <template v-if="material.type === 'link'">🔗</template>
                    <template v-else-if="material.type === 'text'">📝</template>
                    <template v-else>{{ getFileIcon(material.file_name) }}</template>
                  </span>
                  
                  <!-- Info -->
                  <div class="flex-1 min-w-0">
                    <div class="font-medium text-sm text-slate-700 truncate">{{ material.title }}</div>
                    <div class="text-xs text-slate-400">
                      <template v-if="material.type === 'file'">{{ material.file_name }} · {{ formatBytes(material.file_size) }}</template>
                      <template v-else-if="material.type === 'link'">{{ material.url }}</template>
                      <template v-else>Catatan teks</template>
                    </div>
                    <!-- Show text content inline -->
                    <div v-if="material.type === 'text' && material.content" class="mt-1.5 text-xs text-slate-500 bg-white border border-slate-100 rounded p-2 whitespace-pre-wrap">
                      {{ material.content }}
                    </div>
                  </div>

                  <!-- Actions -->
                  <div class="flex items-center gap-1 flex-shrink-0">
                    <a v-if="material.type === 'file'" :href="`/storage/${material.file_path}`" target="_blank" class="p-1.5 rounded hover:bg-blue-50 text-blue-500" title="Download">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </a>
                    <a v-if="material.type === 'link'" :href="material.url" target="_blank" class="p-1.5 rounded hover:bg-blue-50 text-blue-500" title="Buka Link">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    </a>
                    <button v-if="is_teacher" @click="deleteMaterial(material.id)" class="p-1.5 rounded hover:bg-red-50 text-red-400 opacity-0 group-hover:opacity-100 transition-opacity" title="Hapus">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                  </div>
                </div>
              </div>

              <div v-else class="text-center py-4 text-xs text-slate-400">
                Belum ada materi
              </div>
            </div>

            <!-- ASSIGNMENTS (Tugas/Quiz) -->
            <div v-if="meeting.assignments?.length > 0 || is_teacher">
              <div class="flex items-center justify-between mb-3">
                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tugas & Quiz</h4>
                <Link v-if="is_teacher" :href="`/tenant/assignments/create?meeting_id=${meeting.id}`" class="text-xs text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                  Buat Tugas
                </Link>
              </div>

              <div v-if="meeting.assignments?.length > 0" class="space-y-2">
                <Link
                  v-for="assignment in meeting.assignments"
                  :key="assignment.id"
                  :href="`/tenant/assignments/${assignment.id}`"
                  class="flex items-center gap-3 p-3 rounded-lg border border-slate-100 hover:border-blue-200 hover:bg-blue-50/30 transition-all group"
                >
                  <span class="flex-shrink-0 w-9 h-9 rounded-lg flex items-center justify-center text-white shadow-sm"
                    :class="assignment.type === 'quiz' ? 'bg-purple-500' : 'bg-indigo-500'">
                    <svg v-if="assignment.type === 'quiz'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                  </span>
                  <div class="flex-1 min-w-0">
                    <div class="font-medium text-sm text-slate-700 group-hover:text-blue-700 truncate">{{ assignment.title }}</div>
                    <div class="flex items-center gap-2 mt-0.5">
                      <Badge :variant="getAssignmentBadge(assignment).variant" class="text-[10px]">{{ getAssignmentBadge(assignment).text }}</Badge>
                      <span v-if="assignment.due_date" class="text-xs text-slate-400">
                        Deadline: {{ new Date(assignment.due_date).toLocaleDateString('id-ID') }}
                      </span>
                    </div>
                  </div>
                  <!-- Student submission status -->
                  <div v-if="is_student" class="flex-shrink-0">
                    <Badge v-if="assignment.student_submission?.grade" variant="success">{{ assignment.student_submission.grade }}</Badge>
                    <Badge v-else-if="assignment.student_submission" variant="info">Dikirim</Badge>
                    <Badge v-else variant="secondary">Belum</Badge>
                  </div>
                  <svg class="w-4 h-4 text-slate-300 group-hover:text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </Link>
              </div>

              <div v-else class="text-center py-4 text-xs text-slate-400">
                Belum ada tugas
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ADD MATERIAL MODAL -->
  <Teleport to="body">
    <div v-if="showMaterialModal" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="closeMaterialModal"></div>
      <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden">
        <div class="p-6 border-b border-slate-100">
          <h3 class="text-lg font-bold text-slate-800">Tambah Materi</h3>
          <p class="text-xs text-slate-400 mt-1">Tambahkan file, link, atau catatan teks</p>
        </div>
        <form @submit.prevent="submitMaterial(showMaterialModal)" class="p-6 space-y-4">
          <!-- Title -->
          <div>
            <label class="text-sm font-semibold text-slate-700 mb-1 block">Judul Materi *</label>
            <input v-model="materialForm.title" type="text" class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:border-blue-400 focus:ring-1 focus:ring-blue-400 outline-none" placeholder="e.g. Slide Presentasi BAB 1" required />
            <div v-if="materialForm.errors.title" class="text-red-500 text-xs mt-1">{{ materialForm.errors.title }}</div>
          </div>

          <!-- Type Selector -->
          <div>
            <label class="text-sm font-semibold text-slate-700 mb-2 block">Jenis Materi</label>
            <div class="grid grid-cols-3 gap-2">
              <button type="button" @click="materialForm.type = 'file'"
                class="py-2.5 rounded-lg border-2 text-sm font-medium transition-all text-center"
                :class="materialForm.type === 'file' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-200 text-slate-500 hover:border-blue-200'">
                📄 File
              </button>
              <button type="button" @click="materialForm.type = 'link'"
                class="py-2.5 rounded-lg border-2 text-sm font-medium transition-all text-center"
                :class="materialForm.type === 'link' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-200 text-slate-500 hover:border-blue-200'">
                🔗 Link
              </button>
              <button type="button" @click="materialForm.type = 'text'"
                class="py-2.5 rounded-lg border-2 text-sm font-medium transition-all text-center"
                :class="materialForm.type === 'text' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-200 text-slate-500 hover:border-blue-200'">
                📝 Teks
              </button>
            </div>
          </div>

          <!-- File upload -->
          <div v-if="materialForm.type === 'file'">
            <label class="text-sm font-semibold text-slate-700 mb-1 block">Upload File</label>
            <label class="flex flex-col items-center justify-center w-full h-24 border-2 border-dashed border-slate-200 rounded-xl cursor-pointer bg-slate-50/50 hover:bg-blue-50/50 hover:border-blue-300 transition-all">
              <svg v-if="!materialForm.file" class="w-6 h-6 text-slate-300 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
              <p class="text-xs text-slate-400"><span class="font-semibold">Klik untuk pilih file</span></p>
              <p v-if="materialForm.file" class="text-xs text-blue-600 font-medium mt-1">{{ materialForm.file.name }}</p>
              <input type="file" class="hidden" @change="materialForm.file = $event.target.files[0]" />
            </label>
          </div>

          <!-- URL input -->
          <div v-if="materialForm.type === 'link'">
            <label class="text-sm font-semibold text-slate-700 mb-1 block">URL</label>
            <input v-model="materialForm.url" type="url" class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:border-blue-400 focus:ring-1 focus:ring-blue-400 outline-none" placeholder="https://..." />
          </div>

          <!-- Text content -->
          <div v-if="materialForm.type === 'text'">
            <label class="text-sm font-semibold text-slate-700 mb-1 block">Konten</label>
            <textarea v-model="materialForm.content" rows="4" class="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm focus:border-blue-400 focus:ring-1 focus:ring-blue-400 outline-none resize-y" placeholder="Tulis catatan materi..."></textarea>
          </div>

          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="closeMaterialModal" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800 transition-colors">Batal</button>
            <Button type="submit" :disabled="materialForm.processing" variant="primary" class="px-6">Simpan</Button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>
