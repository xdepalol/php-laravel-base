import { ref, inject } from 'vue'
import { useRouter } from 'vue-router'
import { formatUtcIso } from '@/utils/datetime'

export default function useUtils() {

    const formatDate = (data) => {
        return formatUtcIso(data, 'datetime') ?? '—';
    };

    const isClose = (date) => {
        let now = new Date();
        let exerciseCloseD = new Date(date);
        return exerciseCloseD < now;
    };


    return {
        formatDate,
        isClose
    }
}
