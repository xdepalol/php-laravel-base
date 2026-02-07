import { ref } from 'vue'

export default function useDatatable() {

    const lazyState = ref({
        page: 1,
        per_page: 10,
        sort_field: 'created_at',
        sort_order: 'desc',
        filters: {}
    })

    const primeSortToApi = (sortOrder) => {
        return sortOrder === 1 ? 'asc' : 'desc'
    }

    const applyPrimeEvent = (e = {}) => {
        // Page (PrimeVue 0-based)
        if (typeof e.page === 'number') {
            lazyState.value.page = e.page + 1
            lazyState.value.per_page = e.rows
        }

        // Sort
        if (e.sortField) {
            lazyState.value.sort_field = e.sortField
            lazyState.value.sort_order = e.sortOrder === 1 ? 'asc' : 'desc'
        }

        // Filters (aquí tu decideixes el mapping)
        if (e.filters) {
            lazyState.value.filters = primeFiltersToApi(e.filters)
        }
    }

    const primeFiltersToApi = (filters) => {
        const out = {}

        for (const [field, meta] of Object.entries(filters || {})) {
            const value =
            meta?.value ??
            meta?.constraints?.[0]?.value

            if (value !== null && value !== '') {
            out[field] = value
            }
        }

        return out
    }

    const extractPagination = (response) => {
        const data = response?.data ?? response;

        // Resource: { data, meta }
        if (data?.meta) {
            return {
                page: data.meta.current_page,
                per_page: data.meta.per_page,
                total: data.meta.total,
                last_page: data.meta.last_page,
                from: data.meta.from,
                to: data.meta.to
            };
        }

        // paginator "pla" (integrat)
        if (data?.current_page) {
            return {
                page: data.current_page,
                per_page: data.per_page,
                total: data.total,
                last_page: data.last_page,
                from: data.from,
                to: data.to
            };
        }

        // Fallback (sense paginació)
        return {
            page: 1,
            per_page: Array.isArray(data?.data) ? data.data.length : 0,
            total: Array.isArray(data?.data) ? data.data.length : 0,
            last_page: 1,
            from: null,
            to: null
        };
    }

    const dtOnPage = async (e) => { applyPrimeEvent(e); }
    const dtOnSort = async (e) => { applyPrimeEvent(e); lazyState.value.page = 1; }
    const dtOnFilter = async (e) => { applyPrimeEvent(e); lazyState.value.page = 1; }

    return {
        lazyState,
        primeSortToApi,
        primeFiltersToApi,
        applyPrimeEvent,
        extractPagination,
        dtOnPage,
        dtOnSort,
        dtOnFilter
    }
}
