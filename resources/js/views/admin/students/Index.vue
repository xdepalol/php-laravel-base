<template>
    <Card>
        <template #title>
            <div class="flex items-center justify-between w-full">
                <span>Gestión de Estudiantes</span>
                <div class="flex items-center gap-2">
                    <Button
                        label="Actualizar"
                        icon="pi pi-refresh"
                        size="small"
                        outlined
                        severity="secondary"
                        :loading="isLoading"
                        @click="getStudents(lazyState)"
                    />
                    <Button
                        v-if="can('student-create')"
                        label="Nuevo Estudiante"
                        icon="pi pi-plus"
                        size="small"
                        severity="primary"
                        @click="router.push('/admin/students/create')"
                    />
                </div>
            </div>
        </template>

        <template #subtitle>Administra y gestiona los estudiantes del sistema. Crea, edita y elimina estudiantes.</template>

        <template #content>
            <DataTable
                :value="students"
                lazy
                paginator
                :totalRecords="pagination?.total ?? 0"
                :rows="lazyState.per_page"
                :first="(lazyState.page - 1) * lazyState.per_page"
                :rows-per-page-options="[10, 25, 50]"
                v-model:filters="studentsFilters"
                dataKey="id"
                striped-rows
                :loading="isLoading"
                @page="onPage"
                @sort="onSort"
                @filter="onFilter"
                filter-display="menu"
                :filter-delay="300"
                :globalFilterFields="['name','surname1', 'surname2', 'email']">

                <Column field="id" header="ID" sortable class="w-[60px]">
                    <template #body="slotProps">
                        <Skeleton v-if="isLoading" width="3rem" height="1rem" />
                        <span v-else class="table-cell-id">{{ slotProps.data.id }}</span>
                    </template>
                </Column>

                <Column field="name" header="Nombre" sortable filter :filter-placeholder="'Nombre'" class="min-w-[200px]">
                    <template #body="slotProps">
                        <Skeleton v-if="isLoading" width="10rem" height="1rem" />
                        <div v-else class="flex items-center space-x-2">
                            <i class="pi pi-user text-blue-600" />
                            <span class="font-medium table-cell-name">{{ slotProps.data.name || '-' }}</span>
                        </div>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText v-model="filterModel.value" placeholder="Nombre" class="w-full" />
                    </template>
                </Column>

                <Column field="surname1" header="Apellido" sortable filter :filter-placeholder="'Apellido'" class="min-w-[200px]">
                    <template #body="slotProps">
                        <Skeleton v-if="isLoading" width="10rem" height="1rem" />
                        <div v-else class="flex items-center space-x-2">
                            <span class="font-medium table-cell-name">{{ slotProps.data.surname1 || '-' }}</span>
                        </div>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText v-model="filterModel.value" placeholder="Apellido" class="w-full" />
                    </template>
                </Column>

                <Column field="surname2" header="2º Apellido" sortable filter :filter-placeholder="'2º Apellido'" class="min-w-[200px]">
                    <template #body="slotProps">
                        <Skeleton v-if="isLoading" width="10rem" height="1rem" />
                        <div v-else class="flex items-center space-x-2">
                            <span class="font-medium table-cell-name">{{ slotProps.data.surname2 || '-' }}</span>
                        </div>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText v-model="filterModel.value" placeholder="2º Apellido" class="w-full" />
                    </template>
                </Column>        

                <Column field="email" header="Email" sortable filter :filter-placeholder="'Email'" class="min-w-[200px]">
                    <template #body="slotProps">
                        <Skeleton v-if="isLoading" width="12rem" height="1rem" />
                        <span v-else class="text-sm table-cell-email">{{ slotProps.data.email || '-' }}</span>
                    </template>
                    <template #filter="{ filterModel }">
                        <InputText v-model="filterModel.value" placeholder="Email" class="w-full" />
                    </template>
                </Column>
            </DataTable>
        </template>
    </Card>
</template>
<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import useStudents from "../../../composables/students";
import useDatatable from "../../../utils/datatable";
import { useAbility } from '@casl/vue';
import { FilterMatchMode, FilterOperator } from "@primevue/core/api";

const router = useRouter();
const { students, pagination, isLoading, getStudents, deleteStudent } = useStudents();
const { can } = useAbility();
const { lazyState, dtOnPage, dtOnSort, dtOnFilter } = useDatatable();

const onPage = async (e) => { dtOnPage(e); await getStudents(lazyState.value); }
const onSort = async (e) => { dtOnSort(e); ; await getStudents(lazyState.value); }
const onFilter = async (e) => { dtOnFilter(e); await getStudents(lazyState.value); }

const studentsFilters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    surname1: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    surname2: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] },
    email: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.STARTS_WITH }] }
});

onMounted(async () => {
    try {
         getStudents(lazyState.value)
    } finally {
    }
});
</script>