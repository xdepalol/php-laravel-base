<template>
    <div class="permissions-page">
        <Card>
            <template #title>
                <div class="flex items-center justify-between w-full">
                    <span>Gestión de Permisos</span>
                    <div class="flex items-center gap-2">
                        <Button
                            label="Actualizar"
                            icon="pi pi-refresh"
                            size="small"
                            outlined
                            severity="secondary"
                            :loading="isLoading"
                            @click="getPermissions"
                        />
                        <Button
                            v-if="can('permission-create')"
                            label="Nuevo Permiso"
                            icon="pi pi-plus"
                            size="small"
                            severity="primary"
                            @click="openCreateDialog"
                        />
                    </div>
                </div>
            </template>

            <template #subtitle>
                Administra y gestiona los permisos del sistema. Crea, edita y elimina permisos según tus permisos.
            </template>

            <template #content>
                <div v-if="isLoading" class="table-loading-skeleton space-y-3">
                    <div
                        v-for="row in skeletonRows"
                        :key="row"
                        class="flex gap-3 items-center"
                    >
                        <Skeleton width="60px" height="1.25rem" />
                        <Skeleton width="200px" height="1.25rem" />
                        <Skeleton width="140px" height="1.25rem" />
                        <div class="flex gap-2 ml-auto">
                            <Skeleton width="2.5rem" height="2.5rem" shape="circle" />
                            <Skeleton width="2.5rem" height="2.5rem" shape="circle" />
                        </div>
                    </div>
                </div>
                <DataTable
                    v-else
                    v-model:filters="filters"
                    :value="permissions || []"
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
                            <p class="empty-state-text">No se encontraron permisos</p>
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
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" placeholder="Nombre" class="w-full" />
                        </template>
                    </Column>

                    <Column header="Acciones" class="w-[150px]">
                        <template #body="slotProps">
                            <Skeleton v-if="isLoading" width="4rem" height="2rem" />
                            <div v-else class="flex gap-2">
                                <Button
                                    v-if="can('permission-edit')"
                                    v-tooltip.top="'Editar permiso'"
                                    icon="pi pi-pencil"
                                    rounded
                                    text
                                    severity="secondary"
                                    size="small"
                                    @click="openEditDialog(slotProps.data)"
                                />
                                <Button
                                    v-if="can('permission-delete')"
                                    v-tooltip.top="'Eliminar permiso'"
                                    icon="pi pi-trash"
                                    rounded
                                    text
                                    severity="danger"
                                    size="small"
                                    @click="confirmDeletePermission(slotProps.data)"
                                />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </template>
        </Card>

        <Dialog
            v-model:visible="permissionDialog.open"
            modal
            :header="permissionDialog.type === 'create' ? 'Crear Permiso' : 'Editar Permiso'"
            :style="{ width: '400px' }"
            class="permission-dialog"
        >
            <div class="flex flex-col gap-4">
                <div>
                    <label for="permission-name" class="dialog-label">Nombre del permiso</label>
                    <InputText
                        id="permission-name"
                        v-model="permission.name"
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
                    v-if="permissionDialog.type === 'create'"
                    label="Crear"
                    @click="submitCreate"
                    :loading="isSubmitting"
                    :disabled="isSubmitting"
                />
                <Button
                    v-else
                    label="Guardar"
                    @click="submitUpdate"
                    :loading="isSubmitting"
                    :disabled="isSubmitting"
                />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, inject, watch } from "vue";
import usePermissions from "@/composables/permissions";
import { useAbility } from '@casl/vue';
import {FilterMatchMode, FilterOperator} from "@primevue/core/api";
import { formatUtcIso } from '@/utils/datetime';

const FILTERS_STORAGE_KEY = 'admin_permissions_table_filters';
const {permissions, permission, getPermissions, createPermission, updatePermission, deletePermission, resetPermission, setPermission, hasError, getError, upsertPermissionRecord, isLoading} = usePermissions();
const { can } = useAbility();

const swal = inject('$swal');
const canUseBrowserStorage = typeof window !== 'undefined';

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    id: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    name: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    created_at: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
});

const permissionDialog = reactive({
    open: false,
    type: 'create'
});

const isSubmitting = computed(() => isLoading.value);
const skeletonRows = Array.from({ length: 5 }, (_, index) => index);

const saveFiltersToStorage = (currentFilters) => {
    if (!canUseBrowserStorage) return;
    try {
        window.localStorage.setItem(FILTERS_STORAGE_KEY, JSON.stringify(currentFilters));
    } catch (error) {
        console.warn('No se pudieron guardar los filtros de permisos', error);
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
        console.warn('No se pudieron restaurar los filtros de permisos', error);
    }
};

watch(filters, (newFilters) => {
    saveFiltersToStorage(newFilters);
}, { deep: true });

const openCreateDialog = () => {
    resetPermission();
    permissionDialog.type = 'create';
    permissionDialog.open = true;
};

const openEditDialog = (currentPermission) => {
    setPermission(currentPermission);
    permissionDialog.type = 'edit';
    permissionDialog.open = true;
};

const closeDialog = () => {
    permissionDialog.open = false;
    resetPermission();
};

const submitCreate = () => {
    if (isSubmitting.value) return;

    createPermission()
        .then(createdPermission => {
            if (createdPermission) {
                upsertPermissionRecord(createdPermission);
                closeDialog();
            }
        });
};

const submitUpdate = () => {
    if (isSubmitting.value) return;

    updatePermission()
        .then(updatedPermission => {
            if (updatedPermission) {
                upsertPermissionRecord(updatedPermission);
                closeDialog();
            }
        });
};

const performDelete = (id) => {
    deletePermission(id);
};

const confirmDeletePermission = (currentPermission) => {
    if (!swal) {
        performDelete(currentPermission.id);
        return;
    }

    swal({
        icon: 'warning',
        title: '¿Eliminar permiso?',
        text: `El permiso "${currentPermission.name}" se eliminará de forma permanente.`,
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444'
    }).then((result) => {
        if (result.isConfirmed) {
            performDelete(currentPermission.id);
        }
    });
};

const formatDate = (dateString) => formatUtcIso(dateString, 'datetime') ?? '-';

onMounted(() => {
    restoreFiltersFromStorage();
    getPermissions();
});
</script>
