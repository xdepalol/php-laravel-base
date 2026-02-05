import { ref, inject } from 'vue'
import * as yup from 'yup'
import { useToast } from './useToast'
import { useValidation } from './useValidation'
import axios from 'axios'

export default function useUsers() {
    const users = ref([])
    const isLoading = ref(false)
    const toast = useToast()
    const swal = inject('$swal')

    const initialUser = {
        id: null,
        name: '',
        email: '',
        password: '',
        surname1: '',
        surname2: '',
        role_id: [],
        avatar: null
    }

    const user = ref({ ...initialUser })

    const {
        errors,
        validate,
        clearErrors,
        hasError,
        getError
    } = useValidation()

    const userSchema = yup.object({
        name: yup.string().required('El nombre es obligatorio'),
        email: yup.string().email('Email inválido').required('El email es obligatorio'),
        password: yup.string().min(8, 'La contraseña debe tener al menos 8 caracteres').nullable(),
        surname1: yup.string().required('El primer apellido es obligatorio'),
        surname2: yup.string().nullable(),
        role_id: yup.array().min(1, 'Debe seleccionar al menos un rol').nullable()
    })


    const withLoading = async (fn) => {
        if (isLoading.value) throw new Error('Operación en curso')
        isLoading.value = true
        try {
            return await fn()
        } finally {
            isLoading.value = false
        }
    }

    const resetUser = () => { user.value = { ...initialUser }; clearErrors() }

    const setUser = (data = {}) => {
        user.value = {
            id: data.id ?? null,
            name: data.name ?? '',
            email: data.email ?? '',
            password: '',
            surname1: data.surname1 ?? '',
            surname2: data.surname2 ?? '',
            role_id: data.roles ?? [],
            avatar: data.avatar ?? null
        }
        clearErrors()
    }

    const upsertUserRecord = (userRecord) => {
        if (!userRecord?.id) return

        if (users.value.data) {
            const index = users.value.data.findIndex(u => u.id === userRecord.id)
            if (index !== -1) {
                users.value.data[index] = userRecord
            } else {
                users.value.data.unshift(userRecord)
            }
        }
    }

    const getUsers = async (
        page = 1,
        search_id = '',
        search_title = '',
        search_global = '',
        sort_field = 'created_at',
        sort_order = 'desc'
    ) => {
        const params = {
            page,
            search_id,
            search_title,
            search_global,
            sort_field,
            sort_order
        }
        const query = new URLSearchParams(params).toString()
        return axios.get(`/api/users?${query}`)
            .then(response => {
                users.value = response.data;
                return response;
            })
    }

    const getUser = async (id) => {
        return withLoading(async () => {
            const response = await axios.get('/api/users/' + id)
            user.value = response.data.data
            return response.data.data
        })
    }

    const createUser = async (userData) => {
        const { isValid } = validate(userSchema, userData || user.value)
        if (!isValid) {
            toast.error('Error de validación', 'Revisa los campos resaltados.')
            throw new Error('Validación')
        }

        try {
            const payload = { ...user.value }

            if (Array.isArray(payload.role_id)) {
                payload.role_id = payload.role_id.map(r => r.id || r)
            }

            const response = await withLoading(() => axios.post('/api/users', payload))
            const data = response.data?.data ?? response.data
            toast.crud.created('Usuario')
            return data
        } catch (error) {
            if (error.response?.data?.errors) {
                console.log(error.response.data.errors)
            }
            toast.error('Error', 'No se pudo crear el usuario')
            throw error
        }
    }

    const updateUser = async (userData) => {
        const dataToValidate = userData || user.value

        const schema = userSchema.shape({
            password: yup.string().min(8, 'La contraseña debe tener al menos 8 caracteres').nullable().notRequired()
        })

        const { isValid } = validate(schema, dataToValidate)
        if (!isValid) {
            toast.error('Error de validación', 'Revisa los campos resaltados.')
            throw new Error('Validación')
        }

        try {
            const payload = { ...user.value }
            if (Array.isArray(payload.role_id)) {
                payload.role_id = payload.role_id.map(r => r.id || r)
            }

            const response = await withLoading(() => axios.put(`/api/users/${user.value.id}`, payload))
            const data = response.data?.data ?? response.data
            toast.crud.updated('Usuario')
            return data
        } catch (error) {
            toast.error('Error', 'No se pudo actualizar el usuario')
            throw error
        }
    }

    const deleteUser = async (id, index) => {
        if (!swal) {
            // Fallback if swal not injected
            if (!confirm('¿Estás seguro?')) return
            await axios.delete('/api/users/' + id)
            users.value.data.splice(index, 1)
            toast.crud.deleted('Usuario')
            return
        }

        swal({
            title: '¿Estás seguro?',
            text: '¡No podrás revertir esto!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#ef4444',
            reverseButtons: true
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    await withLoading(() => axios.delete('/api/users/' + id))
                    if (users.value.data) {
                        users.value.data.splice(index, 1);
                    }
                    toast.crud.deleted('Usuario')
                } catch (error) {
                    toast.error('Error', 'No se pudo eliminar el usuario')
                }
            }
        })
    }

    return {
        users,
        user,
        isLoading,
        errors,
        hasError,
        getError,
        resetUser,
        setUser,
        getUsers,
        getUser,
        createUser,
        updateUser,
        deleteUser,
        upsertUserRecord
    }
}
