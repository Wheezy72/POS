import { nextTick, onBeforeUnmount, onMounted } from 'vue';

export function useScannerFocus({
    scannerInput,
    pinInput,
    searchInput,
    showPinOverlay,
    showSearchModal,
    showPayModal,
}) {
    let focusTimer = null;

    function focusPriorityInput() {
        if (showPinOverlay.value) {
            focusPinInput();
            return;
        }

        if (showSearchModal.value) {
            nextTick(() => searchInput.value?.focus());
            return;
        }

        focusScannerInput(true);
    }

    function focusPinInput() {
        nextTick(() => pinInput.value?.focus());
    }

    function focusScannerInput(force = true) {
        if (showPinOverlay.value || showSearchModal.value || showPayModal.value) {
            return;
        }

        const element = scannerInput.value;

        if (!element) {
            return;
        }

        if (!force && document.activeElement === element) {
            return;
        }

        element.focus();
        element.select();
    }

    function handleVisibilityChange() {
        if (!document.hidden) {
            focusPriorityInput();
        }
    }

    onMounted(() => {
        focusTimer = window.setInterval(() => focusScannerInput(false), 800);
        window.addEventListener('focus', focusPriorityInput);
        window.addEventListener('visibilitychange', handleVisibilityChange);
    });

    onBeforeUnmount(() => {
        if (focusTimer) {
            window.clearInterval(focusTimer);
        }

        window.removeEventListener('focus', focusPriorityInput);
        window.removeEventListener('visibilitychange', handleVisibilityChange);
    });

    return {
        focusPriorityInput,
        focusPinInput,
        focusScannerInput,
        handleVisibilityChange,
    };
}
