<template>
    <Panel class="flex flex-col justify-center my-10">
        <form @submit.prevent="submitForm">
            <div class="mb-3">
                <div class="flex items-center gap-3">
                    <label for="student-name">Nombre:</label>
                    <InputText v-model="student.name" id="student-name" type="text" size="small" :invalid="!!errors.name" />
                </div>
                <div class="text-red-400 mt-1">
                    {{ errors.name }}
                </div>
                <div class="mt-1">
                    <div v-for="message in validationErrors?.name" class="text-red-400">
                        {{ message }}
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <div class="flex items-center gap-3">
                    <label for="student-surname1">Primer apellido:</label>
                    <InputText v-model="student.surname1" id="student-surname1" type="text" size="small" :invalid="!!errors.surname1" />
                </div>
                <div class="text-red-400 mt-1">
                    {{ errors.surname1 }}
                </div>
                <div class="mt-1">
                    <div v-for="message in validationErrors?.surname1" class="text-red-400">
                        {{ message }}
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <div class="flex items-center gap-3">
                    <label for="student-surname2">Segundo apellido:</label>
                    <InputText v-model="student.surname2" id="student-surname2" type="text" size="small" :invalid="!!errors.surname2" />
                </div>
                <div class="text-red-400 mt-1">
                    {{ errors.surname2 }}
                </div>
                <div class="mt-1">
                    <div v-for="message in validationErrors?.surname2" class="text-red-400">
                        {{ message }}
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <div class="flex items-center gap-3">
                    <label for="student-email">Email:</label>
                    <InputText v-model="student.email" id="student-email" type="email" size="small" :invalid="!!errors.email" />
                </div>
                <div class="text-red-400 mt-1">
                    {{ errors.email }}
                </div>
                <div class="mt-1">
                    <div v-for="message in validationErrors?.email" class="text-red-400">
                        {{ message }}
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <div class="flex items-center gap-3">
                    <label for="student-birthday">Email:</label>
                    <InputText v-model="student.birthday_date" id="student-birthday" type="date" size="small" :invalid="!!errors.birthday_date" />
                </div>
                <div class="text-red-400 mt-1">
                    {{ errors.birthday_date }}
                </div>
                <div class="mt-1">
                    <div v-for="message in validationErrors?.birthday_date" class="text-red-400">
                        {{ message }}
                    </div>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex text-right self-end gap-4">
                <Button
                    label="Volver"
                    icon="pi pi-chevron-left"
                    severity="secondary"
                    class="btn-back"
                    @click="router.push({ name: 'students.index' })"
                />
                <Button :disabled="isLoading" type="submit">
                    <div v-show="isLoading" class=""></div>
                    <span v-if="isLoading">Procesando...</span>
                    <span v-else>Save</span>
                </Button>
            </div>
        </form>
    </Panel>
</template>
<script setup>
    import { useRouter } from "vue-router";
    import { onMounted } from "vue";
    import useStudents from "@/composables/students";

    const router = useRouter();
    const { student, createStudent, validationErrors, isLoading, errors } = useStudents();

    const submitForm = async () => {
        try {
            const studentData = await createStudent(student.value);

            router.replace({
                name: 'students.edit',
                params: { id: studentData.id }
            });
        } catch (e) {
            // Errors handled by composable (toast)
        }
    };

    onMounted(() => {
    })
</script>
