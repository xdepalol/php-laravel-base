<template>
    <Panel class="flex flex-col justify-center my-10">
        <form @submit.prevent="submitForm">
            <div class="mb-3">
                <div class="flex items-center gap-3">
                    <label for="user-name">Name:</label>
                    <InputText v-model="user.name" id="user-name" type="text" size="small" :invalid="!!errors.name" />
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
                    <label for="user-surname1">Surname 1:</label>
                    <InputText v-model="user.surname1" id="user-surname1" type="text" size="small" :invalid="!!errors.surname1" />
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
                    <label for="user-surname2">Surname 2:</label>
                    <InputText v-model="user.surname2" id="user-surname2" type="text" size="small" :invalid="!!errors.surname2" />
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
                    <label for="user-email">Email:</label>
                    <InputText v-model="user.email" id="user-email" type="email" size="small" :invalid="!!errors.email" />
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
                    <label for="user-password">Password:</label>
                    <InputText v-model="user.password" id="user-password" type="password" size="small" :invalid="!!errors.password" />
                </div>
                <div class="text-red-400 mt-1">
                    {{ errors.password }}
                </div>
                <div class="mt-1">
                    <div v-for="message in validationErrors?.password" class="text-red-400">
                        {{ message }}
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <div class="flex items-center gap-3">
                    <label for="user-role">Role:</label>
                    <MultiSelect
                        v-model="user.role_id"
                        :options="roles"
                        size="small"
                        display="chip"
                        optionLabel="name"
                        optionValue="id"
                        filter
                        :invalid="!!errors.role_id"
                    />
                </div>
                <div class="text-red-400 mt-1">
                    {{ errors.role_id }}
                </div>
                <div class="mt-1">
                    <div v-for="message in validationErrors?.role" class="text-red-400">
                        {{ message }}
                    </div>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex mt-4 text-right gap-4">
                <Button
                    label="Volver"
                    icon="pi pi-chevron-left"
                    severity="secondary"
                    class="btn-back"
                    @click="router.push({ name: 'students.index' })"
                />
                <Button :disabled="isLoading" type="submit">
                    <div v-show="isLoading" class=""></div>
                    <span v-if="isLoading">Processing...</span>
                    <span v-else>Save</span>
                </Button>
            </div>
        </form>
    </Panel>
</template>
<script setup>
    import { onMounted } from "vue";
    import { useRouter } from "vue-router";
    import useRoles from "@/composables/roles";
    import useUsers from "@/composables/users";

    const router = useRouter();
    const { roles, getRoles } = useRoles();
    const { user, createUser, validationErrors, isLoading, errors } = useUsers();

    function submitForm() {
        createUser(user.value)
    }

    onMounted(() => {
        getRoles()
    })
</script>
