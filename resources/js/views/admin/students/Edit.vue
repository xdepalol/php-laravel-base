<template>
    <div class="show-d"></div>
    <div class="grid grid-flow-col auto-rows-min gap-5">

        <Panel class="col-span-12" pt:content:class="flex flex-col gap-10 justify-between">
            <template #header>
                <h5 class="student-name text-2xl font-bold mb-1">{{ student.name }}</h5>
            </template>
            <div>
                <h6 class="mb-4 text-lg font-bold">Datos personales</h6>
                <div class="mb-4">
                    <div class="flex items-center gap-3">
                        <label for="name">Nombre:</label>
                        <InputText 
                            v-model="student.name" 
                            type="text" 
                            size="small" 
                            id="name" 
                            :class="{ 'p-invalid': hasError('name') }"
                        />
                    </div>
                    <small v-if="hasError('name')" class="p-error">
                        {{ getError('name') }}
                    </small>
                </div>

                <div class="mb-4">
                    <div class="flex items-center gap-3">
                        <label for="surname1">Primer apellido:</label>
                        <InputText 
                            v-model="student.surname1" 
                            size="small" 
                            type="text" 
                            id="surname1" 
                            :class="{ 'p-invalid': hasError('surname1') }"
                        />
                    </div>
                    <small v-if="hasError('surname1')" class="p-error">
                        {{ getError('surname1') }}
                    </small>
                </div>

                <div class="mb-4">
                    <div class="flex items-center gap-3">
                        <label for="surname2">Segundo apellido:</label>
                        <InputText 
                            v-model="student.surname2" 
                            type="text" 
                            size="small" 
                            id="surname2" 
                            :class="{ 'p-invalid': hasError('surname2') }"
                        />
                    </div>
                    <small v-if="hasError('surname2')" class="p-error">
                        {{ getError('surname2') }}
                    </small>
                </div>

                <div class="mb-4">
                    <div class="flex items-center gap-3">
                        <label for="email">Email:</label>
                        <InputText 
                            v-model="student.email" 
                            type="email" 
                            size="small" 
                            id="email" 
                            :class="{ 'p-invalid': hasError('email') }"
                        />
                    </div>
                    <small v-if="hasError('email')" class="p-error">
                        {{ getError('email') }}
                    </small>
                </div>

                <div class="mb-4">
                    <div class="flex items-center gap-3">
                        <label for="student-birthday">Fecha nacimiento:</label>
                        <InputText
                            v-model="student.birthday_date"
                            id="student-birthday"
                            type="date"
                            size="small"
                            :class="{ 'p-invalid': hasError('birthday_date') }"
                        />
                    </div>
                    <small v-if="hasError('birthday_date')" class="p-error">
                        {{ getError('birthday_date') }}
                    </small>
                </div>
                
            </div>
            <div class="flex text-right self-end gap-4">
                <Button
                    label="Volver"
                    icon="pi pi-chevron-left"
                    severity="secondary"
                    class="btn-back"
                    @click="router.push({ name: 'students.index' })"
                />
                <Button :disabled="isLoading" @click="submitForm" :loading="isLoading">
                    <span v-if="!isLoading">Guardar</span>
                    <span v-else>Guardando...</span>
                </Button>
            </div>

        </Panel>
    </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { usePrimeVue } from 'primevue/config';
import useStudents from "@/composables/students";

const $primevue = usePrimeVue();
const route = useRoute();
const router = useRouter();

const {student, getStudent, updateStudent, isLoading, hasError, getError, setStudent } = useStudents();

const submitForm = async () => {
    try {
        await updateStudent();
    } catch (e) {
        // Errors handled by composable (toast)
    }
}

onMounted(async () => {
    console.log(route.params.id);
    const studentData = await getStudent(route.params.id);
})

</script>

