import { ref, inject } from 'vue'
import * as yup from 'yup'
import { useToast } from './useToast'
import { useValidation } from './useValidation'
import axios from 'axios'
import useDatatable from "../utils/datatable";

const { extractPagination } = useDatatable();

export default function useStudents() {
    const students = ref([])
    const isLoading = ref(false)
    const toast = useToast()
    const swal = inject('$swal')
    const pagination = ref(null)

    const initialStudent = {
        id: null,
        name: '',
        email: '',
        surname1: '',
        surname2: null,
        birthday_date: null
    }

    const student = ref({ ...initialStudent })
    
    const {
        errors,
        validate,
        clearErrors,
        hasError,
        getError
    } = useValidation()

    const studentSchema = yup.object({
        name: yup.string().required('El nombre es obligatorio'),
        email: yup.string().email('Email inválido').required('El email es obligatorio'),
        surname1: yup.string().required('El primer apellido es obligatorio'),
        surname2: yup.string().nullable(),
        birthday_date: yup.date().nullable().transform((v, o) => (o === '' ? null : v)).typeError('Fecha invalida')
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

    const resetStudent = () => { student.value = { ...initialStudent }; clearErrors() }

    const setStudent = (data = {}) => {
        student.value = {
            id: data.id ?? null,
            name: data.name ?? '',
            email: data.email ?? '',
            surname1: data.surname1 ?? '',
            surname2: data.surname2 ?? '',
            birthday_date: data.birthday_date ?? null,
            avatar: data.avatar ?? null
        }
        clearErrors()
    }

    const upsertStudentRecord = (studentRecord) => {
        if (!studentRecord?.id) return

        const index = students.value.findIndex(s => s.id === studentRecord.id)
        if (index !== -1)
            students.value[index] = studentRecord
        else
            students.value.unshift(studentRecord)
    }

    const getStudents = async ({
        page = 1,
        per_page = 10,
        sort_field = 'created_at',
        sort_order = 'desc',
        filters = {}
    }) => {
        const params = {
            page,
            per_page,
            sort_field,
            sort_order
        }
        Object.entries(filters).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== '') {
                params[`filter[${key}]`] = value
            }
        })
        const query = new URLSearchParams(params).toString()
        return axios.get(`/api/students?${query}`)
            .then(response => {
                students.value = response.data.data ?? response.data;
                pagination.value = extractPagination(response);
                return response;
            })
    }

    const getStudent = async (id) => {
        return withLoading(async () => {
            const response = await axios.get('/api/students/' + id)
            student.value = response.data.data ?? response.data
            return response.data.data ?? response.data
        })
    }

    const createStudent = async (studentData) => {
        const { isValid } = validate(studentSchema, studentData || student.value)
        if (!isValid) {
            toast.error('Error de validación', 'Revisa los campos resaltados.')
            throw new Error('Validación')
        }

        try {
            const payload = { ...(studentData || student.value) }

            const response = await withLoading(() => axios.post('/api/students', payload))
            const data = response.data?.data ?? response.data
            toast.crud.created('Estudiante')
            return data
        } catch (error) {
            if (error.response?.data?.errors) {
                console.log(error.response.data.errors)
            }
            toast.error('Error', 'No se pudo crear el estudiante')
            throw error
        }
    }

    const updateStudent = async (studentData) => {
        const { isValid } = validate(studentSchema, studentData || student.value)
        if (!isValid) {
            toast.error('Error de validación', 'Revisa los campos resaltados.')
            throw new Error('Validación')
        }

        try {
            const payload = { ...(studentData || student.value) }

            const response = await withLoading(() => axios.put(`/api/students/${student.value.id}`, payload))
            const data = response.data?.data ?? response.data
            toast.crud.updated('Estudiante')
            return data
        } catch (error) {
            toast.error('Error', 'No se pudo actualizar el estudiante')
            throw error
        }
    }

    const deleteStudent = async (id, index) => {
        if (!swal) {
            // Fallback if swal not injected
            if (!confirm('¿Estás seguro?')) return
            await axios.delete('/api/students/' + id)
            students.value.splice(index, 1)
            toast.crud.deleted('Estudiante')
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
                    await withLoading(() => axios.delete('/api/students/' + id))
                    students.value.splice(index, 1);
                    toast.crud.deleted('Estudiante')
                } catch (error) {
                    toast.error('Error', 'No se pudo eliminar el estudiante')
                }
            }
        })
    }

    return {
        students,
        student,
        pagination,
        isLoading,
        errors,
        hasError,
        getError,
        resetStudent,
        setStudent,
        getStudents,
        getStudent,
        createStudent,
        updateStudent,
        deleteStudent,
        upsertStudentRecord
    }
}