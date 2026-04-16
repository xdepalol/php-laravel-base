import { authStore } from '../store/auth'

const AuthenticatedLayout = () => import('../layouts/AdminLayout.vue');
const AuthenticatedUserLayout = () => import('../layouts/UserLayout.vue');
const GuestLayout = () => import('../layouts/GuestLayout.vue');

async function requireLogin(to, from, next) {
    const auth = authStore();
    const isLogin = !!auth.authenticated;

    if (isLogin) {
        next()
    } else {
        next('/login')
    }
}

const hasAdmin = (roles = []) =>
    roles.some((role) => role?.name?.toLowerCase().includes('admin'));

/** Alumnos sin rol docente: entrada al grupo en Actividades; docentes: Resumen. */
function subjectGroupDefaultRedirect(to) {
    const roles = authStore().user?.roles?.map((r) => r.name) ?? []
    const studentOnly = roles.includes('student') && !roles.includes('teacher')
    return {
        name: studentOnly ? 'app.subject-group.activities' : 'app.subject-group.overview',
        params: to.params,
    }
}

async function guest(to, from, next) {
    const auth = authStore()
    let isLogin = !!auth.authenticated;

    if (isLogin) {
        next('/')
    } else {
        next()
    }
}

async function requireAdmin(to, from, next) {
    const auth = authStore();
    let isLogin = !!auth.authenticated;
    let user = auth.user;

    if (isLogin) {
        if (hasAdmin(user.roles)) {
            next()
        } else {
            next('/app')
        }
    } else {
        next('/login')
    }
}

export default [
    {
        path: '/',
        component: GuestLayout,
        children: [
            {
                path: '/',
                name: 'home',
                component: () => import('../views/public/home/index.vue'),
            },

            {
                path: 'login',
                name: 'auth.login',
                component: () => import('../views/auth/login/Login.vue'),
                beforeEnter: guest,
            },
            {
                path: 'register',
                name: 'auth.register',
                component: () => import('../views/auth/register/index.vue'),
                beforeEnter: guest,
            },
            {
                path: 'forgot-password',
                name: 'auth.forgot-password',
                component: () => import('../views/auth/passwords/Email.vue'),
                beforeEnter: guest,
            },
            {
                path: 'reset-password/:token',
                name: 'auth.reset-password',
                component: () => import('../views/auth/passwords/Reset.vue'),
                beforeEnter: guest,
            },
        ]
    },

    {
        path: '/app',
        component: AuthenticatedUserLayout,
        name: 'app',
        beforeEnter: requireLogin,
        meta: { breadCrumb: '.' },
        children: [
            {
                name: 'app.profile',
                path: 'profile',
                component: () => import('../views/user/profile.vue'),
                meta: {
                    breadCrumb: 'Perfil',
                },
            },
            {
                name: 'app.my-subject-groups',
                path: 'mis-asignaturas',
                component: () => import('../views/app/teaching/MySubjectGroups.vue'),
                meta: {
                    breadCrumb: 'Mis asignaturas',
                    pageTitle: 'Mis asignaturas',
                },
            },
            {
                path: 'asignaturas/:id',
                component: () => import('../views/app/teaching/subject-group/SubjectGroupLayout.vue'),
                meta: {
                    breadCrumb: 'Grupo de asignatura',
                    pageTitle: 'Grupo de asignatura',
                },
                children: [
                    {
                        path: '',
                        redirect: subjectGroupDefaultRedirect,
                    },
                    {
                        name: 'app.subject-group.overview',
                        path: 'resumen',
                        component: () =>
                            import('../views/app/teaching/subject-group/SubjectGroupOverviewTab.vue'),
                        meta: {
                            subjectGroupTab: 'overview',
                            breadCrumb: 'Resumen',
                            pageTitle: 'Resumen',
                        },
                    },
                    {
                        name: 'app.subject-group.students',
                        path: 'estudiantes',
                        component: () =>
                            import('../views/app/teaching/subject-group/SubjectGroupStudentsTab.vue'),
                        meta: {
                            subjectGroupTab: 'students',
                            breadCrumb: 'Estudiantes',
                            pageTitle: 'Estudiantes',
                        },
                    },
                    {
                        name: 'app.subject-group.activity.create',
                        path: 'actividades/nueva',
                        component: () =>
                            import(
                                '../views/app/teaching/subject-group/SubjectGroupActivityCreatePage.vue'
                            ),
                        meta: {
                            subjectGroupTab: 'activities',
                            breadCrumb: 'Nueva actividad',
                            pageTitle: 'Nueva actividad',
                        },
                    },
                    {
                        name: 'app.subject-group.activities',
                        path: 'actividades',
                        component: () =>
                            import('../views/app/teaching/subject-group/SubjectGroupActivitiesTab.vue'),
                        meta: {
                            subjectGroupTab: 'activities',
                            breadCrumb: 'Actividades',
                            pageTitle: 'Actividades',
                        },
                    },
                ],
            },
            {
                name: 'app.activity.deliverable.create',
                path: 'actividades/:activityId/entregables/nuevo',
                component: () =>
                    import('../views/app/activity/ActivityDeliverableFormPage.vue'),
                meta: {
                    breadCrumb: 'Nuevo entregable',
                    pageTitle: 'Nuevo entregable',
                },
            },
            {
                name: 'app.activity.deliverable.edit',
                path: 'actividades/:activityId/entregables/:deliverableId/editar',
                component: () =>
                    import('../views/app/activity/ActivityDeliverableFormPage.vue'),
                meta: {
                    breadCrumb: 'Editar entregable',
                    pageTitle: 'Editar entregable',
                },
            },
            {
                name: 'app.activity.deliverable.submissions',
                path: 'actividades/:activityId/entregables/:deliverableId/entregas',
                component: () =>
                    import('../views/app/activity/ActivityDeliverableSubmissionsPage.vue'),
                meta: {
                    breadCrumb: 'Entregas del entregable',
                    pageTitle: 'Entregas',
                },
            },
            {
                name: 'app.activity.submission.detail',
                path: 'actividades/:activityId/entregables/:deliverableId/entregas/:submissionId',
                component: () =>
                    import('../views/app/activity/ActivitySubmissionDetailPage.vue'),
                meta: {
                    breadCrumb: 'Entrega',
                    pageTitle: 'Entrega',
                },
            },
            {
                path: 'actividades/:activityId',
                component: () => import('../views/app/activity/ActivityLayout.vue'),
                meta: {
                    breadCrumb: 'Actividad',
                    pageTitle: 'Actividad',
                },
                children: [
                    {
                        path: '',
                        redirect: (to) => ({
                            name: 'app.activity.overview',
                            params: to.params,
                            query: to.query,
                        }),
                    },
                    {
                        name: 'app.activity.overview',
                        path: 'resumen',
                        component: () => import('../views/app/activity/tabs/ActivityOverviewTab.vue'),
                        meta: {
                            activityTab: 'overview',
                            breadCrumb: 'Resumen',
                            pageTitle: 'Resumen',
                        },
                    },
                    {
                        name: 'app.activity.teams',
                        path: 'equipos',
                        component: () => import('../views/app/activity/tabs/ActivityTeamsTab.vue'),
                        meta: {
                            activityTab: 'teams',
                            breadCrumb: 'Equipos',
                            pageTitle: 'Equipos',
                        },
                    },
                    {
                        name: 'app.activity.phase.create',
                        path: 'fases/nueva',
                        component: () => import('../views/app/activity/ActivityPhaseFormPage.vue'),
                        meta: {
                            activityTab: 'phases',
                            breadCrumb: 'Nueva fase',
                            pageTitle: 'Nueva fase',
                        },
                    },
                    {
                        name: 'app.activity.phase.edit',
                        path: 'fases/:phaseId/editar',
                        component: () => import('../views/app/activity/ActivityPhaseFormPage.vue'),
                        meta: {
                            activityTab: 'phases',
                            breadCrumb: 'Editar fase',
                            pageTitle: 'Editar fase',
                        },
                    },
                    {
                        name: 'app.activity.phase.show',
                        path: 'fases/:phaseId',
                        component: () => import('../views/app/activity/ActivityPhaseDetailPage.vue'),
                        meta: {
                            activityTab: 'phases',
                            breadCrumb: 'Fase',
                            pageTitle: 'Fase',
                        },
                    },
                    {
                        name: 'app.activity.phases',
                        path: 'fases',
                        component: () => import('../views/app/activity/tabs/ActivityPhasesTab.vue'),
                        meta: {
                            activityTab: 'phases',
                            breadCrumb: 'Fases',
                            pageTitle: 'Fases',
                        },
                    },
                    {
                        name: 'app.activity.deliverables',
                        path: 'entregables',
                        component: () => import('../views/app/activity/tabs/ActivityDeliverablesTab.vue'),
                        meta: {
                            activityTab: 'deliverables',
                            breadCrumb: 'Entregables',
                            pageTitle: 'Entregables',
                        },
                    },
                    {
                        name: 'app.activity.submissions',
                        path: 'entregas',
                        component: () =>
                            import('../views/app/activity/tabs/ActivitySubmissionsTab.vue'),
                        meta: {
                            activityTab: 'submissions',
                            breadCrumb: 'Entregas',
                            pageTitle: 'Entregas',
                        },
                    },
                    {
                        path: 'roles',
                        redirect: (to) => ({
                            name: 'app.activity.overview',
                            params: to.params,
                            query: to.query,
                        }),
                    },
                    {
                        path: 'backlog',
                        redirect: (to) => ({
                            name: 'app.activity.teams',
                            params: to.params,
                            query: to.query,
                        }),
                    },
                    {
                        path: 'tareas',
                        redirect: (to) => ({
                            name: 'app.activity.teams',
                            params: to.params,
                            query: to.query,
                        }),
                    },
                ],
            },
            {
                path: 'actividades/:activityId/equipos/:teamId',
                component: () => import('../views/app/activity/team/TeamWorkspaceLayout.vue'),
                meta: {
                    breadCrumb: 'Equipo',
                    pageTitle: 'Equipo',
                },
                children: [
                    {
                        path: '',
                        redirect: (to) => ({
                            name: 'app.activity.team.overview',
                            params: to.params,
                            query: to.query,
                        }),
                    },
                    {
                        name: 'app.activity.team.overview',
                        path: 'resumen',
                        component: () => import('../views/app/activity/team/TeamOverviewTab.vue'),
                        meta: {
                            teamTab: 'overview',
                            breadCrumb: 'Resumen',
                            pageTitle: 'Resumen',
                        },
                    },
                    {
                        name: 'app.activity.team.phase.show',
                        path: 'fases/:phaseId',
                        component: () => import('../views/app/activity/ActivityPhaseDetailPage.vue'),
                        meta: {
                            teamTab: 'phases',
                            requiresActivitySprints: true,
                            breadCrumb: 'Fase',
                            pageTitle: 'Fase',
                        },
                    },
                    {
                        name: 'app.activity.team.phases',
                        path: 'fases',
                        component: () => import('../views/app/activity/tabs/ActivityPhasesTab.vue'),
                        meta: {
                            teamTab: 'phases',
                            requiresActivitySprints: true,
                            breadCrumb: 'Fases',
                            pageTitle: 'Fases',
                        },
                    },
                    {
                        name: 'app.activity.team.backlog',
                        path: 'backlog',
                        component: () => import('../views/app/activity/team/TeamBacklogTab.vue'),
                        meta: {
                            teamTab: 'backlog',
                            requiresActivityBacklog: true,
                            breadCrumb: 'Backlog',
                            pageTitle: 'Backlog',
                        },
                    },
                    {
                        name: 'app.activity.team.sprint-kanban',
                        path: 'sprint',
                        component: () => import('../views/app/activity/team/TeamSprintKanbanTab.vue'),
                        meta: {
                            teamTab: 'sprint-kanban',
                            requiresActivitySprints: true,
                            breadCrumb: 'Sprint',
                            pageTitle: 'Sprint',
                        },
                    },
                    {
                        path: 'tareas',
                        redirect: (to) => ({
                            name: 'app.activity.team.backlog',
                            params: to.params,
                            query: to.query,
                        }),
                    },
                    {
                        name: 'app.activity.team.deliverables',
                        path: 'entregas',
                        component: () => import('../views/app/activity/team/TeamDeliverablesTab.vue'),
                        meta: {
                            teamTab: 'deliverables',
                            breadCrumb: 'Entregas',
                            pageTitle: 'Entregas',
                        },
                    },
                ],
            },

        ]
    },


    {
        path: '/admin',
        component: AuthenticatedLayout,
        beforeEnter: requireAdmin,
        meta: { breadCrumb: 'Dashboard' },
        children: [
            {
                name: 'admin.index',
                path: '',
                component: () => import('../views/admin/index.vue'),
                meta: {
                    breadCrumb: 'Admin',
                    hideBreadcrumb: true
                }
            },
            {
                name: 'profile.index',
                path: 'profile',
                component: () => import('../views/admin/profile/index.vue'),
                meta: { breadCrumb: 'Profile' }
            },

            {
                name: 'categories',
                path: 'categories',
                meta: { breadCrumb: 'Categories' },
                children: [
                    {
                        name: 'categories.index',
                        path: '',
                        component: () => import('../views/admin/categories/Index.vue'),
                        meta: {
                            breadCrumb: 'View category',
                            hideBreadcrumb: true
                        }
                    },
                ]
            },

            {
                name: 'posts',
                path: 'posts',
                meta: { breadCrumb: 'Posts' },
                children: [
                    {
                        name: 'posts.index',
                        path: '',
                        component: () => import('../views/admin/posts/Index.vue'),
                        meta: {
                            breadCrumb: 'View Posts',
                            hideBreadcrumb: true
                        }
                    },
                    {
                        name: 'posts.create',
                        path: 'create',
                        component: () => import('../views/admin/posts/Create.vue'),
                        meta: {
                            breadCrumb: 'Nuevo Post',
                            linked: false
                        }
                    },
                ]
            },

            {
                name: 'students',
                path: 'students',
                meta: { breadCrumb: 'Estudiantes' },
                children: [
                    {
                        name: 'students.index',
                        path: '',
                        component: () => import('../views/admin/students/Index.vue'),
                        meta: {
                            breadCrumb: 'Estudiantes',
                            hideBreadcrumb: true
                        }
                    },
                    {
                        name: 'students.create',
                        path: 'create',
                        component: () => import('../views/admin/students/Create.vue'),
                        meta: {
                            breadCrumb: 'Nuevo estudiante',
                            linked: false
                        }
                    },
                    {
                        name: 'students.edit',
                        path: 'edit/:id',
                        component: () => import('../views/admin/students/Edit.vue'),
                        meta: {
                            breadCrumb: 'Editar estudiante',
                            linked: false
                        }
                    },                ]
            },

            {
                name: 'permissions',
                path: 'permissions',
                meta: { breadCrumb: 'Permisos' },
                children: [
                    {
                        name: 'permissions.index',
                        path: '',
                        component: () => import('../views/admin/permissions/Index.vue'),
                        meta: {
                            breadCrumb: 'Permissions',
                            hideBreadcrumb: true
                        }
                    },
                ]
            },
            {
                name: 'users',
                path: 'users',
                meta: { breadCrumb: 'Usuarios' },
                children: [
                    {
                        name: 'users.index',
                        path: '',
                        component: () => import('../views/admin/users/Index.vue'),
                        meta: {
                            breadCrumb: 'Usuarios',
                            hideBreadcrumb: true // Ocultar breadcrumb del layout porque la Card tiene su propio header
                        }
                    },
                    {
                        name: 'users.create',
                        path: 'create',
                        component: () => import('../views/admin/users/Create.vue'),
                        meta: {
                            breadCrumb: 'Crear Usuario',
                            linked: false
                        }
                    },
                    {
                        name: 'users.edit',
                        path: 'edit/:id',
                        component: () => import('../views/admin/users/Edit.vue'),
                        meta: {
                            breadCrumb: 'Editar Usuario',
                            linked: false
                        }
                    }
                ]
            },

            {
                name: 'roles',
                path: 'roles',
                meta: { breadCrumb: 'Roles' },
                children: [
                    {
                        name: 'roles.index',
                        path: '',
                        component: () => import('../views/admin/roles/Index.vue'),
                        meta: {
                            breadCrumb: 'Roles',
                            hideBreadcrumb: true
                        }
                    },
                    {
                        name: 'admin.roles.edit',
                        path: 'edit/:id',
                        component: () => import('../views/admin/roles/Edit.vue'),
                        meta: {
                            breadCrumb: 'Editar Rol',
                            linked: false
                        }
                    }
                ]
            },
        ]
    },
    {
        path: "/:pathMatch(.*)*",
        name: 'NotFound',
        component: () => import("../views/errors/404.vue"),
    },
];
