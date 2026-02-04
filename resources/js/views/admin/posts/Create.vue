<template>
    <Panel class="flex flex-col justify-center my-10">
        <form @submit.prevent="submitForm">
            <div class="mb-3">
                <div class="flex items-center gap-3">
                    <label for="post-title">Título:</label>
                    <InputText v-model="post.title" id="post-title" type="text" size="small" :invalid="!!errors.title" />
                </div>
                <div class="text-red-400 mt-1">
                    {{ errors.title }}
                </div>
                <div class="mt-1">
                    <div v-for="message in validationErrors?.title" class="text-red-400">
                        {{ message }}
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <div class="flex items-center gap-3">
                    <label for="post-content">Contenido:</label>
                    <!-- <Textarea v-model="post.content" id="post-content" rows="5" :invalid="!!errors.content" class="w-full" /> -->
                    <Editor
                        v-model="post.content"
                        editorStyle="height: 200px"
                        :invalid="!!errors.content"
                    />
                </div>
                <div class="text-red-400 mt-1">
                    {{ errors.content }}
                </div>
                <div class="mt-1">
                    <div v-for="message in validationErrors?.content" class="text-red-400">
                        {{ message }}
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <div class="flex items-center gap-3">
                    <label for="post-category">Categorías:</label>
                    <MultiSelect
                        v-model="post.categories"
                        :options="categories"
                        size="small"
                        display="chip"
                        optionLabel="name"
                        optionValue="id"
                        filter
                        :invalid="!!errors.categories"
                    />
                </div>
                <div class="text-red-400 mt-1">
                    {{ errors.categories }}
                </div>
                <div class="mt-1">
                    <div v-for="message in validationErrors?.category" class="text-red-400">
                        {{ message }}
                    </div>
                </div>
            </div>

            <!-- Buttons -->
            <div class="mt-4 text-right">
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
    import usePosts from "@/composables/posts";
    import useCategories from "@/composables/categories";

    const { categories, getCategories } = useCategories();
    const { post, storePost, validationErrors, isLoading, errors } = usePosts();

    function submitForm() {
        storePost(post.value)
    }

    onMounted(() => {
        getCategories()
    })
</script>
