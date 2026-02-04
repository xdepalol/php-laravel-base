import { ref, inject } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import * as yup from 'yup'
import { useValidation } from './useValidation'
import { useToast } from './useToast'

export default function usePosts() {
    const { errors, validate, clearErrors, hasError, getError } = useValidation()
    const router = useRouter()
    const toast = useToast()

    const isLoading = ref(false)
    const posts = ref([])
    const initialPost = {
        title: '',
        content: '',
        categories: [],
        thumbnail: null
    }
    const post = ref({ ...initialPost })
    const validationErrors = errors


    const postSchema = yup.object({
        title: yup.string().trim().required('El título es obligatorio'),
        content: yup.string().trim().required('El contenido es obligatorio'),
        categories: yup.array().nullable(),
        thumbnail: yup.mixed().nullable(),
    })

    const getPosts = async () => {
        return axios.get('/api/posts')
            .then(response => {
                posts.value = response.data;
                return response;
            })
    }

    const getPost = async (id) => {
        return axios.get('/api/posts/' + id)
            .then(response => {
                post.value = response.data.data ?? response.data;
                return response;
            })
    }

    const storePost = async (post) => {
        if (isLoading.value) return;

        isLoading.value = true
        clearErrors()

        const { isValid } = validate(postSchema, post)
        if (!isValid) {
            isLoading.value = false
            return
        }

        const serializedPost = serializePost(post)

        axios.post('/api/posts', serializedPost, {
            headers: {
                "content-type": "multipart/form-data"
            }
        })
            .then(response => {
                //router.push({ name: 'posts.index' })
                toast.crud.created('Post')
            })
            .catch(error => {
                if (error.response?.data) {
                    validationErrors.value = error.response.data.errors
                }
            })
            .finally(() => isLoading.value = false)
    }

    const resetPost = () => {
        post.value = { ...initialPost }
        clearErrors()
    }

    const updatePost = async (post) => {
        if (isLoading.value) return;

        isLoading.value = true
        clearErrors()

        const { isValid } = validate(postSchema, post)
        if (!isValid) {
            isLoading.value = false
            return
        }

        axios.put('/api/posts/' + post.id, post)
            .then(response => {
                router.push({ name: 'posts.index' })
                toast.crud.updated('Post')
            })
            .catch(error => {
                if (error.response?.data) {
                    validationErrors.value = error.response.data.errors
                }
            })
            .finally(() => isLoading.value = false)
    }

    const deletePost = async (id) => {
        axios.delete('/api/posts/' + id)
            .then(response => {
                getPosts()
                toast.crud.deleted('Post')
            })
            .catch(error => {
                toast.error('Error', 'No se pudo eliminar el post')
            })
    }

    const serializePost = (data) => {
        const form = new FormData()
        Object.entries(data).forEach(([key, value]) => {
            if (value === undefined || value === null) return
            if (Array.isArray(value)) {
                value.forEach(item => form.append(`${key}[]`, item))
            } else {
                form.append(key, value)
            }
        })
        return form
    }
    return {
        posts,
        post,
        getPosts,
        getPost,
        storePost,
        updatePost,
        deletePost,
        resetPost,
        errors,
        hasError,
        getError,
        validationErrors,
        isLoading
    }
}
