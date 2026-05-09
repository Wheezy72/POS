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
    quickPay,
    openDiscount,
    holdSale,
    voidLastItem,
    cancelAll,
    promptAssignCustomer,
    promptPriceOverride,
    promptQuantity,
    addMiscItem,
    recordDrawerOpen,
    recordCashDrop,
    recordPayout,
    reprintReceipt,
    voidLastSalePrompt,
    openSettings,
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
            case 'F3':
                event.preventDefault();

                if (typeof openDiscount === 'function') {
                    openDiscount();
                }
                break;
            case 'F4':
                event.preventDefault();

                if (typeof quickPay === 'function') {
                    quickPay('mpesa');
                } else {
                    openPayModal();
                }
                break;
            case 'F5':
                event.preventDefault();

                if (typeof quickPay === 'function') {
                    quickPay('cash');
                } else {
                    openPayModal();
                    paymentTab.value = 'cash';
                }
                break;
            case 'F6':
                event.preventDefault();

                if (typeof quickPay === 'function') {
                    quickPay('card');
                } else {
                    openPayModal();
                }
                break;
            case 'F7':
                event.preventDefault();
                holdSale?.();
                break;
            case 'F8':
                event.preventDefault();
                openPayModal();
                break;
            case 'F9':
                event.preventDefault();
                promptAssignCustomer?.();
                break;
            case 'F10':
                event.preventDefault();
                logout();
                break;
            case 'F12':
                event.preventDefault();
                openSettings?.();
                break;
            case 'Delete':
                event.preventDefault();
                voidLastItem?.();
                break;
            default:
                if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'x') {
                    event.preventDefault();
                    cancelAll?.();
                    return;
                }

                if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'p') {
                    event.preventDefault();
                    promptPriceOverride?.();
                    return;
                }

                if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'q') {
                    event.preventDefault();
                    promptQuantity?.();
                    return;
                }

                if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'm') {
                    event.preventDefault();
                    addMiscItem?.();
                    return;
                }

                if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'd') {
                    event.preventDefault();
                    recordDrawerOpen?.();
                    return;
                }

                if (event.altKey && event.key.toLowerCase() === 'd') {
                    event.preventDefault();
                    recordCashDrop?.();
                    return;
                }

                if (event.altKey && event.key.toLowerCase() === 'p') {
                    event.preventDefault();
                    recordPayout?.();
                    return;
                }

                if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'r') {
                    event.preventDefault();
                    reprintReceipt?.();
                    return;
                }

                if (event.altKey && event.key.toLowerCase() === 'v') {
                    event.preventDefault();
                    voidLastSalePrompt?.();
                    return;
                }

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
