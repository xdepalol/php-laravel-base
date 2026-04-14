<template>
  <Card>
    <template #title>Entregas del equipo</template>
    <template #content>
      <p class="text-sm text-slate-600 mb-4">
        Entregables de la actividad y entregas registradas para este equipo. Como miembro del equipo puedes
        registrar una entrega con enlace y comentarios; mientras el entregable no esté cerrado puedes editar la
        entrega del equipo o la tuya; la calificación y la retroalimentación las publica el docente.
      </p>
      <DataTable :value="rows" :loading="loading" data-key="deliverable_id" striped-rows class="text-sm">
        <template #empty>
          <span class="text-slate-500">No hay entregables en esta actividad.</span>
        </template>
        <Column field="title" header="Entregable" />
        <Column field="due_date" header="Fecha límite" class="whitespace-nowrap w-32">
          <template #body="{ data }">
            <UtcFormatted :value="data.due_date" variant="datetime" />
          </template>
        </Column>
        <Column header="Entregas (equipo)" class="w-36">
          <template #body="{ data }">
            {{ data.delivered_count }}/{{ data.team_submission_count }}
          </template>
        </Column>
        <Column header="Estado" class="w-40">
          <template #body="{ data }">
            <Tag v-if="data.last_status_label" :value="data.last_status_label" severity="secondary" />
            <span v-else class="text-slate-500">—</span>
          </template>
        </Column>
        <Column v-if="isStudentOnlyView" header="" class="w-44">
          <template #body="{ data }">
            <div class="flex flex-wrap gap-1">
              <Button
                v-if="showEditButton(data)"
                label="Editar"
                icon="pi pi-pencil"
                size="small"
                outlined
                severity="secondary"
                @click="openSubmitDialog(data, 'edit')"
              />
              <Button
                v-if="showSubmitButton(data)"
                label="Entregar"
                icon="pi pi-upload"
                size="small"
                outlined
                @click="openSubmitDialog(data, 'create')"
              />
              <span v-if="!showEditButton(data) && !showSubmitButton(data)" class="text-xs text-slate-400">—</span>
            </div>
          </template>
        </Column>
      </DataTable>
    </template>
  </Card>

  <Dialog
    v-model:visible="submitDialogOpen"
    modal
    :header="submitMode === 'edit' ? 'Editar entrega' : 'Registrar entrega'"
    :style="{ width: 'min(480px, 96vw)' }"
    :closable="!submitSaving"
    @hide="onSubmitDialogHide"
  >
    <div v-if="submitTarget" class="flex flex-col gap-4">
      <div>
        <p class="text-sm font-medium text-slate-800">{{ submitTarget.title }}</p>
        <p class="text-xs text-slate-500 mt-1">
          {{
            submitTarget.is_group_deliverable
              ? 'Entregable de grupo: la entrega cuenta para todo el equipo.'
              : 'Entregable individual: la entrega es personal (como miembro del equipo en esta actividad).'
          }}
        </p>
      </div>
      <div class="flex flex-col gap-1.5">
        <label for="submit-content-url" class="text-sm font-medium text-slate-700">Enlace del trabajo (opcional)</label>
        <InputText
          id="submit-content-url"
          v-model="submitForm.content_url"
          class="w-full"
          placeholder="https://docs.google.com/…, Figma, repositorio…"
          :disabled="submitSaving"
          autocomplete="url"
        />
        <span class="text-xs text-slate-500">Documento, diseño, código u otro recurso enlazable.</span>
      </div>
      <div class="flex flex-col gap-1.5">
        <label for="submit-content-text" class="text-sm font-medium text-slate-700">Comentarios (opcional)</label>
        <Textarea
          id="submit-content-text"
          v-model="submitForm.content_text"
          class="w-full"
          rows="4"
          auto-resize
          placeholder="Notas para el docente, alcance, incidencias…"
          :disabled="submitSaving"
        />
      </div>
    </div>
    <template #footer>
      <Button label="Cancelar" severity="secondary" text :disabled="submitSaving" @click="submitDialogOpen = false" />
      <Button
        :label="submitMode === 'edit' ? 'Guardar cambios' : 'Enviar entrega'"
        :icon="submitMode === 'edit' ? 'pi pi-save' : 'pi pi-check'"
        :loading="submitSaving"
        :disabled="submitSaving"
        @click="confirmSubmit"
      />
    </template>
  </Dialog>
</template>

<script setup>
import { inject, ref, watch } from 'vue'
import axios from 'axios'
import { sortDeliverablesByDueDateThenId } from '@/utils/deliverablesSort'
import { useActivityViewerRole } from '@/composables/useActivityViewerRole'
import { useToast } from '@/composables/useToast'

const activityId = inject('activityId')
const teamId = inject('teamId')

const { isStudentOnlyView } = useActivityViewerRole()
const toast = useToast()

const rows = ref([])
const loading = ref(false)

const submitDialogOpen = ref(false)
const submitSaving = ref(false)
const submitMode = ref('create')
const submitTarget = ref(null)
const submitForm = ref({
  content_url: '',
  content_text: '',
})

const SUBMISSION_STATUS = {
  0: 'Pendiente',
  1: 'Entregada',
  2: 'Calificada',
}

/** DeliverableStatus.CLOSED */
const DELIVERABLE_CLOSED = 2

function unwrap(response) {
  return response.data?.data ?? response.data
}

function deliverableStatusValue(d) {
  const s = d?.status
  if (s == null) return null
  if (typeof s === 'object' && s !== null && 'value' in s) return s.value
  return s
}

function showEditButton(data) {
  if (data.deliverable_status === DELIVERABLE_CLOSED) return false
  return !!data.last_submission_id
}

function showSubmitButton(data) {
  if (data.deliverable_status !== 1) return false
  return !data.last_submission_id
}

function openSubmitDialog(row, mode) {
  submitMode.value = mode === 'edit' ? 'edit' : 'create'
  submitTarget.value = row
  if (mode === 'edit') {
    submitForm.value = {
      content_url: row.last_content_url ? String(row.last_content_url) : '',
      content_text: row.last_content_text ? String(row.last_content_text) : '',
    }
  } else {
    submitForm.value = { content_url: '', content_text: '' }
  }
  submitDialogOpen.value = true
}

function onSubmitDialogHide() {
  if (!submitSaving.value) {
    submitTarget.value = null
    submitMode.value = 'create'
  }
}

async function confirmSubmit() {
  const row = submitTarget.value
  const tid = teamId?.value
  const did = row?.deliverable_id
  if (!row || !tid || !did) return

  const payload = {
    team_id: Number(tid),
    content_url: submitForm.value.content_url?.trim() || null,
    content_text: submitForm.value.content_text?.trim() || null,
  }

  submitSaving.value = true
  try {
    if (submitMode.value === 'edit' && row.last_submission_id) {
      const { team_id: _t, ...putBody } = payload
      await axios.put(`/api/deliverables/${did}/submissions/${row.last_submission_id}`, putBody)
      toast.success('Entrega actualizada')
    } else {
      await axios.post(`/api/deliverables/${did}/submissions`, payload)
      toast.success('Entrega registrada', 'El docente podrá revisarla y calificarla.')
    }
    submitDialogOpen.value = false
    submitTarget.value = null
    submitMode.value = 'create'
    await load()
  } catch (e) {
    const msg = e.response?.data?.message || e.response?.data?.errors
    if (typeof msg === 'object') {
      toast.error('No se pudo guardar', 'Revisa los datos o tu permiso en el equipo.')
    } else {
      toast.error('No se pudo guardar la entrega', typeof msg === 'string' ? msg : null)
    }
  } finally {
    submitSaving.value = false
  }
}

async function load() {
  const aid = activityId?.value
  const tid = teamId?.value
  if (!aid || !tid) return
  loading.value = true
  rows.value = []
  try {
    const dRes = await axios.get(`/api/activities/${aid}/deliverables`)
    const dList = unwrap(dRes)
    const deliverables = sortDeliverablesByDueDateThenId(Array.isArray(dList) ? dList : [])
    const enriched = await Promise.all(
      deliverables.map(async (d) => {
        const id = d.id
        const deliverable_status = deliverableStatusValue(d)
        try {
          const sRes = await axios.get(`/api/deliverables/${id}/submissions`)
          const subs = unwrap(sRes)
          const list = Array.isArray(subs) ? subs : []
          const forTeam = list.filter((s) => Number(s.team_id) === Number(tid))
          const delivered = forTeam.filter((s) => {
            const st = s.status?.value ?? s.status
            return st >= 1
          })
          let lastStatusLabel = null
          let last = null
          if (forTeam.length) {
            last = forTeam.reduce((a, b) => {
              const da = a.submitted_at ? new Date(a.submitted_at).getTime() : 0
              const db = b.submitted_at ? new Date(b.submitted_at).getTime() : 0
              return db >= da ? b : a
            })
            const sv = last.status?.value ?? last.status
            lastStatusLabel = SUBMISSION_STATUS[sv] ?? String(sv)
          }
          return {
            deliverable_id: id,
            title: d.title,
            due_date: d.due_date,
            is_group_deliverable: !!d.is_group_deliverable,
            deliverable_status: deliverable_status ?? null,
            team_submission_count: forTeam.length,
            delivered_count: delivered.length,
            last_status_label: lastStatusLabel,
            last_submission_id: last?.id ?? null,
            last_content_url: last?.content_url ?? '',
            last_content_text: last?.content_text ?? '',
          }
        } catch {
          return {
            deliverable_id: id,
            title: d.title,
            due_date: d.due_date,
            is_group_deliverable: !!d.is_group_deliverable,
            deliverable_status: deliverable_status ?? null,
            team_submission_count: 0,
            delivered_count: 0,
            last_status_label: null,
            last_submission_id: null,
            last_content_url: '',
            last_content_text: '',
          }
        }
      })
    )
    rows.value = enriched
  } finally {
    loading.value = false
  }
}

watch([() => activityId?.value, () => teamId?.value], load, { immediate: true })
</script>
