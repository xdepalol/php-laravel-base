<template>
    <MainLayout :menuItems="items" />
</template>

<script setup>
import { ref } from 'vue';
import MainLayout from './MainLayout.vue';

const items = ref([
    {
        label: 'Docencia',
        items: [
            {
                label: 'Mis asignaturas',
                icon: 'pi pi-book',
                route: '/app/mis-asignaturas',
                permission: 'subjectgroup-list',
                roles: ['teacher'],
            },
        ],
    },
    {
        label: 'Contenido',
        items: [
            {
                label: 'Posts',
                icon: 'pi pi-th-large',
                route: '/app/posts'
            },
        ]
    },
    {
        label: 'Cuenta',
        items: [
            {
                label: 'Perfil',
                icon: 'pi pi-user',
                route: '/app/profile'
            }
        ]
    }
]);
</script>
