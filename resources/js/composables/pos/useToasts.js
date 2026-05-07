import { ref } from 'vue';

export function useToasts({ onError } = {}) {
    const toasts = ref([]);

    function toast(title, message, variant = 'info') {
        const id = `${Date.now()}-${Math.random()}`;
        toasts.value.push({ id, title, message, variant });

        if (variant === 'error') {
            onError?.();
        }

        window.setTimeout(() => {
            toasts.value = toasts.value.filter((toastItem) => toastItem.id !== id);
        }, 4200);
    }

    return {
        toasts,
        toast,
    };
}
