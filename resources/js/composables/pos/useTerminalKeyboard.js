import { onBeforeUnmount, onMounted } from 'vue';

export function useTerminalKeyboard({
    closeModals,
    newSale,
    openSearchModal,
    openPayModal,
    logout,
    focusScannerInput,
    creditSalesEnabled,
    paymentTab,
}) {
    function handleGlobalKeydown(event) {
        if (event.key === 'Escape') {
            event.preventDefault();
            closeModals();
            return;
        }

        switch (event.key) {
            case 'F1':
                event.preventDefault();
                newSale();
                break;
            case 'F2':
                event.preventDefault();
                openSearchModal();
                break;
            case 'F4':
                event.preventDefault();
                openPayModal();
                break;
            case 'F7':
                if (!creditSalesEnabled.value) {
                    break;
                }

                event.preventDefault();
                openPayModal();
                paymentTab.value = 'credit';
                break;
            case 'F8':
                event.preventDefault();
                focusScannerInput(true);
                break;
            case 'F10':
                event.preventDefault();
                logout();
                break;
            default:
                break;
        }
    }

    onMounted(() => {
        window.addEventListener('keydown', handleGlobalKeydown);
    });

    onBeforeUnmount(() => {
        window.removeEventListener('keydown', handleGlobalKeydown);
    });

    return {
        handleGlobalKeydown,
    };
}
