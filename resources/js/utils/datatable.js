import { ref } from 'vue'

export default function useDatatable() {

    const lazyState = ref({
        // PrimeVue offset (0-based)
        first: 0,
        // API
        page: 1,
        per_page: 10,
        sort_field: 'created_at',
        sort_order: 'desc',
        // { field: { value, matchMode } }
        filters: {}
    })

    const primeSortToApi = (sortOrder) => {
        return sortOrder === 1 ? 'asc' : 'desc'
    }

    const firstToPage = (first, rows) => Math.floor((first || 0) / (rows || 10)) + 1

    const applyPrimeEvent = (e = {}) => {
        // Pagination
        if (typeof e.first === 'number') {
            lazyState.value.first = e.first
        }

        if (typeof e.rows === 'number') {
            lazyState.value.per_page = e.rows
        }

        // PrimeVue pot enviar e.page (0-based) o no; si no, calculem a partir de first/rows
        if (typeof e.page === 'number') {
            lazyState.value.page = e.page + 1
        } else {
            lazyState.value.page = firstToPage(lazyState.value.first, lazyState.value.per_page)
        }

        // Sort
        if (e.sortField) {
            lazyState.value.sort_field = e.sortField
            lazyState.value.sort_order = primeSortToApi(e.sortOrder)
        }

        // Filters
        if (e.filters) {
            lazyState.value.filters = primeFiltersToApi(e.filters)
        }
    }

    // IMPORTANT: ara NO aplanem a string.
    // Guardem value + matchMode (de menu i de row filters).
    const primeFiltersToApi = (filters = {}) => {
        const out = {}

        for (const [field, meta] of Object.entries(filters)) {
            const constraint0 = meta?.constraints?.[0]

            const value = meta?.value ?? constraint0?.value ?? null
            const matchMode = meta?.matchMode ?? constraint0?.matchMode ?? null

            if (value !== null && value !== '') {
                out[field] = { value, matchMode }
            }
        }

        return out
    }

    const extractPagination = (response) => {
        const data = response?.data ?? response;

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

        return {
            page: 1,
            per_page: Array.isArray(data?.data) ? data.data.length : 0,
            total: Array.isArray(data?.data) ? data.data.length : 0,
            last_page: 1,
            from: null,
            to: null
        };
    }

    // Quan canvies sort o filtre, és bona pràctica tornar a la primera pàgina
    const resetToFirstPage = () => {
        lazyState.value.first = 0
        lazyState.value.page = 1
    }

    const dtOnPage = (e) => { applyPrimeEvent(e); }
    const dtOnSort = (e) => { applyPrimeEvent(e); resetToFirstPage(); }
    const dtOnFilter = (e) => { applyPrimeEvent(e); resetToFirstPage(); }

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
