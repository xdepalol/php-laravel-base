<template>

    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-lg font-semibold">Gestión de Posts</h2>
        <p class="text-sm text-gray-600">Lista simple de posts</p>
      </div>
      <div class="flex gap-2">
        <button class="px-3 py-2 text-sm border rounded hover:bg-gray-50 bg-white">
          Actualizar'
        </button>
      </div>
    </div>

    <DataTable :value="posts" tableStyle="min-width: 50rem">
        <Column field="id" header="@" sortable></Column>
        <Column field="title" header="Titulo" sortable></Column>
        <Column field="content" header="Contenido"></Column>
        <Column field="categories" header="Cat">
            <template #body="{data}">
                <Badge v-for="cat in data.categories" severity="info">{{cat.name}}</Badge>
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
import useUtils from "@/composables/utils";
import usePosts from "@/composables/posts";

const {posts, getPosts} = usePosts();
const {formatDate} = useUtils();

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