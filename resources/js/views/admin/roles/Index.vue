<template>
    <div class="roles-page">
        <Card>
            <template #title>
                <div class="flex items-center justify-between w-full">
                    <span>Gestión de Roles</span>
                    <div class="flex items-center gap-2">
                        <Button
                            label="Actualizar"
                            icon="pi pi-refresh"
                            size="small"
                            outlined
                            severity="secondary"
                            :loading="isLoading"
                            @click="getRoles"
                        />
                        <Button
                            v-if="can('role-create')"
                            label="Nuevo Rol"
                            icon="pi pi-plus"
                            size="small"
                            severity="primary"
                            @click="openCreateDialog"
                        />
                    </div>
                </div>
            </template>

            <template #subtitle>
                Administra y gestiona los roles del sistema. Crea, edita y elimina roles según tus permisos.
            </template>

            <template #content>
                <DataTable
                    v-model:filters="filters"
                    :value="roles || []"
                    :paginator="true"
                    :rows="10"
                    :rows-per-page-options="[10, 25, 50]"
                    data-key="id"
                    striped-rows
                    size="small"
                    :loading="isLoading"
                    filter-display="menu"
                    :filter-delay="300"
                    :global-filter-fields="['id', 'name', 'created_at']"
                >
                    <template #empty>
                        <div class="table-empty-state">
                            <i class="pi pi-inbox empty-state-icon"></i>
                            <p class="empty-state-text">No se encontraron roles</p>
                            <p class="empty-state-subtext">Intenta ajustar los filtros de búsqueda</p>
                        </div>
                    </template>

                    <Column field="id" header="ID" sortable filter class="w-[80px]">
                        <template #body="slotProps">
                            <Skeleton v-if="isLoading" width="3rem" height="1rem" />
                            <span v-else class="table-cell-id">#{{ slotProps.data.id }}</span>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" placeholder="ID" class="w-full" />
                        </template>
                    </Column>

                    <Column field="name" header="Nombre" sortable filter class="min-w-[200px]">
                        <template #body="slotProps">
                            <Skeleton v-if="isLoading" width="10rem" height="1rem" />
                            <span v-else class="table-cell-name">{{ slotProps.data.name || '-' }}</span>
                        </template>
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" placeholder="Nombre" class="w-full" />
                        </template>
                    </Column>

                    <Column field="created_at" header="Fecha de Creación" sortable class="min-w-[170px]">
                        <template #body="slotProps">
                            <Skeleton v-if="isLoading" width="8rem" height="1rem" />
                            <span v-else class="text-sm table-cell-date">
                                <i class="pi pi-calendar mr-2 text-xs opacity-70"></i>
                                {{ formatDate(slotProps.data.created_at) }}
                            </span>
                        </template>
                    </Column>

                    <Column header="Acciones" class="w-[150px]">
                        <template #body="slotProps">
                            <Skeleton v-if="isLoading" width="4rem" height="2rem" />
                            <div v-else class="flex gap-2">
                        <Button
                            v-if="can('role-edit')"
                            v-tooltip.top="'Editar rol'"
                            icon="pi pi-pencil"
                            rounded
                            text
                            severity="secondary"
                            size="small"
                            @click="goToEdit(slotProps.data)"
                        />
                                <Button
                                    v-if="can('role-delete')"
                                    v-tooltip.top="'Eliminar rol'"
                                    icon="pi pi-trash"
                                    rounded
                                    text
                                    severity="danger"
                                    size="small"
                                    @click="confirmDeleteRole(slotProps.data)"
                                />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </template>
        </Card>

        <Dialog
            v-model:visible="roleDialog.open"
            modal
            :header="roleDialog.type === 'create' ? 'Crear Rol' : 'Editar Rol'"
            :style="{ width: '400px' }"
            class="role-dialog"
        >
            <div class="flex flex-col gap-4">
                <div>
                    <label for="role-name" class="dialog-label">Nombre del rol</label>
                    <InputText
                        id="role-name"
                        v-model="role.name"
                        placeholder="Nombre"
                        class="w-full"
                        :class="{ 'p-invalid': hasError('name') }"
                    />
                    <small v-if="hasError('name')" class="dialog-error">
                        {{ getError('name') }}
                    </small>
                </div>
            </div>
            <template #footer>
                <Button
                    label="Cancelar"
                    severity="secondary"
                    @click="closeDialog"
                    :disabled="isSubmitting"
                />
                <Button
                    label="Crear"
                    @click="submitCreate"
                    :loading="isSubmitting"
                    :disabled="isSubmitting"
                />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, inject, watch } from "vue";
import useRoles from "@/composables/roles";
import { useAbility } from '@casl/vue';
import { FilterMatchMode, FilterOperator  } from "@primevue/core/api";
import { useRouter } from "vue-router";
import { formatUtcIso } from '@/utils/datetime';

const FILTERS_STORAGE_KEY = 'admin_roles_table_filters';
const {roles, role, getRoles, createRole, deleteRole, resetRole, hasError, getError, upsertRoleRecord, isLoading} = useRoles();
const { can } = useAbility();
const router = useRouter();

const swal = inject('$swal');
const canUseBrowserStorage = typeof window !== 'undefined';

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    id: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    name: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    created_at: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
});

const roleDialog = reactive({
    open: false
});

const isSubmitting = computed(() => isLoading.value);

const saveFiltersToStorage = (currentFilters) => {
    if (!canUseBrowserStorage) return;
    try {
        window.localStorage.setItem(FILTERS_STORAGE_KEY, JSON.stringify(currentFilters));
    } catch (error) {
        console.warn('No se pudieron guardar los filtros de roles', error);
    }
};

const restoreFiltersFromStorage = () => {
    if (!canUseBrowserStorage) return;
    try {
        const stored = window.localStorage.getItem(FILTERS_STORAGE_KEY);
        if (!stored) return;
        const parsed = JSON.parse(stored);
        filters.value = {
            global: { ...filters.value.global, ...parsed.global },
            id: { ...filters.value.id, ...parsed.id },
            name: { ...filters.value.name, ...parsed.name },
            created_at: { ...filters.value.created_at, ...parsed.created_at }
        };
    } catch (error) {
        console.warn('No se pudieron restaurar los filtros de roles', error);
    }
};

watch(filters, (newFilters) => {
    saveFiltersToStorage(newFilters);
}, { deep: true });

const openCreateDialog = () => {
    resetRole();
    roleDialog.open = true;
};

const goToEdit = (currentRole) => {
    router.push({ name: 'admin.roles.edit', params: { id: currentRole.id } })
};

const closeDialog = () => {
    roleDialog.open = false;
    resetRole();
};

const submitCreate = () => {
  if (isSubmitting.value) return;
  createRole()
    .then(createdRole => {
      if (createdRole) {
        upsertRoleRecord(createdRole);
        closeDialog();
      }
    })
};

const performDelete = (id) => {
    deleteRole(id);
};

const confirmDeleteRole = (currentRole) => {
    if (!swal) {
        performDelete(currentRole.id);
        return;
    }

    swal({
        icon: 'warning',
        title: '¿Eliminar rol?',
        text: `El rol "${currentRole.name}" se eliminará de forma permanente.`,
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444'
    }).then((result) => {
        if (result.isConfirmed) {
            performDelete(currentRole.id);
        }
    });
};

const formatDate = (dateString) => formatUtcIso(dateString, 'datetime') ?? '-';

onMounted(() => {
    restoreFiltersFromStorage();
    getRoles();
});
</script>
