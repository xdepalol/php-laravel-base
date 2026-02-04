<template>

    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-lg font-semibold">Gestión de Posts</h2>
        <p class="text-sm text-gray-600">Lista simple de posts</p>
      </div>
      <div class="flex gap-2">
        <Button
            label="Actualizar"
            icon="pi pi-refresh"
            size="small"
            outlined
            severity="secondary"
            :loading="isLoading"
            @click="getPosts"
        />
        <Button
            v-if="can('post-create')"
            label="Nuevo Post"
            icon="pi pi-plus"
            size="small"
            severity="primary"
            @click="router.push('/admin/posts/create')"
        />

      </div>
    </div>

    <DataTable :value="posts" tableStyle="min-width: 50rem">
        <Column field="id" header="ID" sortable>
        </Column>
        <Column field="title" header="Titulo" sortable></Column>
        <Column field="content" header="Contenido">
            <template #body="{data}">
                <div class="post-content" v-html="data.content"></div>
            </template>
        </Column>
        <Column field="categories" header="Categoría">
            <template #body="{data}">
                <div class="categories-list">
                    <Badge v-for="cat in data.categories" :key="cat.id" severity="info">
                        {{cat.name}}
                    </Badge>
                </div>
            </template>
        </Column>
        <Column field="user" header="Creado por">
            <template #body="{ data }">
                <UserName :user="data.user" />
            </template>
        </Column>
        <Column field="created_at" header="Creado">
             <template #body="{data}">
                {{ formatDate(data.created_at)}}
            </template>
        </Column>
    </DataTable>

</template>

<script setup>
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import useUtils from "@/composables/utils";
import usePosts from "@/composables/posts";
import UserName from '@/components/UserName.vue'
import { useAbility } from '@casl/vue';

const router = useRouter();
const {posts, getPosts, isLoading} = usePosts();
const {formatDate} = useUtils();
const { can } = useAbility();

onMounted(()=> {
    getPosts()
    .then(response => {
        console.log('Estoy en la vista')
    });
    // axios.get('/api/posts')
    //     .then(response => {
    //         posts.value = response.data;
    //         console.log(response.data);
    //     })
});

</script>