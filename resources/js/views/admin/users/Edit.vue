<template>
    <div class="show-d"></div>
    <div class="grid grid-flow-col auto-rows-min gap-5">
        <Panel class="col-span-1">
            <div class="user-profile">
                <div class="user-avatar">
                    <FileUpload
                        name="picture"
                        url="/api/users/updateimg"
                        @before-upload="onBeforeUpload"
                        @upload="onTemplatedUpload($event)"
                        accept="image/*"
                        :maxFileSize="1500000"
                        @select="onSelectedFiles"
                        pt:content:class="fu-content"
                        pt:buttonbar:class="fu-header"
                        pt:root:class="fu"
                        class="fu"
                    >
                        <template #header="{ chooseCallback, uploadCallback, clearCallback, files, uploadedFiles }">
                            <div class="flex flex-wrap justify-content-between align-items-center flex-1 gap-2">
                                <div class="flex gap-2">
                                    <Button @click="chooseCallback()" icon="pi pi-images" rounded outlined></Button>
                                    <Button @click="uploadEvent(uploadCallback, uploadedFiles)" icon="pi pi-cloud-upload" rounded outlined severity="success" :disabled="!files || files.length === 0"></Button>
                                    <Button @click="clearCallback()" icon="pi pi-times" rounded outlined severity="danger" :disabled="!files || files.length === 0"></Button>
                                </div>
                                <p class="mt-4 mb-0">Drag and drop files to here to upload.</p>
                            </div>
                        </template>

                        <template #content="{ files, uploadedFiles, removeUploadedFileCallback, removeFileCallback }">
                            <img v-if=" files.length > 0" v-for="(file, index) of files" :key="file.name + file.type + file.size" role="presentation" :alt="file.name" :src="file.objectURL" class="object-cover w-full aspect-square rounded-tl-2 rounded-tr-2" />
                            <div v-else>
                                <img v-if="uploadedFiles.length > 0" :key="uploadedFiles[uploadedFiles.length-1].name + uploadedFiles[uploadedFiles.length-1].type + uploadedFiles[uploadedFiles.length-1].size" role="presentation" :alt="uploadedFiles[uploadedFiles.length-1].name" :src="uploadedFiles[uploadedFiles.length-1].objectURL" class="object-cover w-full aspect-square rounded-tl-2 rounded-tr-2" />
                            </div>
                        </template>

                        <template #empty>
                            <img v-if="user.avatar" :src=user.avatar alt="Avatar" class="object-cover w-full aspect-square rounded-tl-2 rounded-tr-2">
                            <img v-if="!user.avatar" src="https://bootdey.com/img/Content/avatar/avatar7.png" alt="Avatar Default" class="object-cover w-full aspect-square rounded-tl-2 rounded-tr-2">
                        </template>
                    </FileUpload>
                </div>

            </div>
        </Panel>

        <Panel class="col-span-12" pt:content:class="flex flex-col gap-10 justify-between">
            <template #header>
                <h5 class="user-name text-2xl font-bold mb-1">{{ user.name }}</h5>
            </template>
            <div>
                <h6 class="mb-4 text-lg font-bold">Datos personales</h6>
                <div class="mb-4">
                    <div class="flex items-center gap-3">
                        <label for="name">Nombre:</label>
                        <InputText 
                            v-model="user.name" 
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
                            v-model="user.surname1" 
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
                            v-model="user.surname2" 
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
                            v-model="user.email" 
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
                        <label for="password">Password:</label>
                        <InputText 
                            v-model="user.password" 
                            type="password" 
                            size="small" 
                            id="password" 
                            :class="{ 'p-invalid': hasError('password') }"
                        />
                    </div>
                    <small v-if="hasError('password')" class="p-error">
                        {{ getError('password') }}
                    </small>
                </div>

                <div class="mb-4">
                    <div class="flex items-center gap-3">
                         <label for="roles">Roles:</label>
                        <MultiSelect 
                            id="roles" 
                            v-model="user.role_id" 
                            display="chip" 
                            :options="roles" 
                            optionLabel="name" 
                            optionValue="id"
                            placeholder="Seleciona los roles" 
                            appendTo=".show-d"
                            class="w-100"
                            :class="{ 'p-invalid': hasError('role_id') }"
                        />
                    </div>
                    <small v-if="hasError('role_id')" class="p-error">
                        {{ getError('role_id') }}
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
import useRoles from "@/composables/roles";
import useUsers from "@/composables/users";

const $primevue = usePrimeVue();
const route = useRoute();
const router = useRouter();

const { roles, getRoles } = useRoles();
const {user, getUser, updateUser, isLoading, hasError, getError,setUser } = useUsers();

const submitForm = async () => {
    try {
        await updateUser();
    } catch (e) {
        // Errors handled by composable (toast)
    }
}

onMounted(async () => {
    await getRoles();
    const userData = await getUser(route.params.id);
    // Ensure roles are mapped correctly for MultiSelect (array of IDs)
    if (userData.roles) {
        user.value.role_id = userData.roles.map(r => r.id);
    }
})

// File Upload Logic
const totalSize = ref(0);
const totalSizePercent = ref(0);
const files = ref([]);

const onBeforeUpload = (event) => {
    event.formData.append('id', user.value.id)
};

const onSelectedFiles = (event) => {
    files.value = event.files;
    if (event.files.length > 1) {
        event.files = event.files.splice(0, event.files.length - 1);
    }
    files.value.forEach((file) => {
        totalSize.value += parseInt(formatSize(file.size));
    });
};

const uploadEvent = async (callback, uploadedFiles) => {
    totalSizePercent.value = totalSize.value / 10;
    await callback();
};

const onTemplatedUpload = (event) => {
    // Reload user to get new avatar
    getUser(user.value.id);
};

const formatSize = (bytes) => {
    const k = 1024;
    const dm = 3;
    const sizes = $primevue.config.locale.fileSizeTypes;

    if (bytes === 0) {
        return `0 ${sizes[0]}`;
    }

    const i = Math.floor(Math.log(bytes) / Math.log(k));
    const formattedSize = parseFloat((bytes / Math.pow(k, i)).toFixed(dm));

    return `${formattedSize} ${sizes[i]}`;
};

</script>

<style>
.fu-content {
    padding: 0px !important;
    border: 0px !important;
    border-radius: 6px;
}

.fu-header {
    border: 0px !important;
    border-radius: 6px;
}

.fu {
    display: flex;
    flex-direction: column-reverse;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
}
</style>
