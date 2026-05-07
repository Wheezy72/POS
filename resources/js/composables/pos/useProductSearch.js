import { ref } from 'vue';

export function useProductSearch(posApi, {
    addProduct,
    focusScannerInput,
    toast,
    showSearchModal,
    barcode,
    searchQuery,
} = {}) {
    const searchResults = ref([]);
    const searchBusy = ref(false);

    let searchDebounceTimer = null;
    let searchRequestSequence = 0;

    function queueProductSearch(query, options = {}) {
        if (searchDebounceTimer) {
            window.clearTimeout(searchDebounceTimer);
        }

        const term = String(query ?? '').trim();

        if (term.length < 2) {
            searchRequestSequence += 1;
            searchBusy.value = false;
            searchResults.value = [];
            return;
        }

        searchDebounceTimer = window.setTimeout(() => {
            searchProducts(term, options);
        }, 220);
    }

    async function searchProducts(query = barcode?.value, options = {}) {
        const term = String(query ?? '').trim();
        const {
            autoSelectExact = true,
            notifyOnEmpty = true,
            openModalOnResults = true,
        } = options;

        if (!term) {
            searchResults.value = [];
            focusScannerInput?.(true);
            return;
        }

        const requestId = ++searchRequestSequence;
        searchBusy.value = true;

        try {
            const response = await posApi.searchProducts(term);

            if (requestId !== searchRequestSequence) {
                return;
            }

            searchResults.value = response.data;

            if (searchQuery) {
                searchQuery.value = term;
            }

            const exactMatch = response.data.find((product) => product.barcode === term || product.sku === term);

            if (autoSelectExact && exactMatch) {
                addProduct?.(exactMatch);

                if (barcode) {
                    barcode.value = '';
                }

                searchResults.value = [];

                if (showSearchModal) {
                    showSearchModal.value = false;
                }

                focusScannerInput?.(true);
                return;
            }

            if (openModalOnResults && response.data.length > 0 && showSearchModal) {
                showSearchModal.value = true;
            } else if (notifyOnEmpty && response.data.length === 0) {
                toast?.('No match', `No product matched "${term}".`, 'info');
            }
        } catch (error) {
            if (requestId === searchRequestSequence) {
                toast?.('Search failed', error?.response?.data?.message ?? 'Unable to search products.', 'error');
            }
        } finally {
            if (requestId === searchRequestSequence) {
                searchBusy.value = false;
            }
        }
    }

    function stopProductSearch() {
        if (searchDebounceTimer) {
            window.clearTimeout(searchDebounceTimer);
            searchDebounceTimer = null;
        }
    }

    function resetProductSearch() {
        stopProductSearch();
        searchRequestSequence += 1;
        searchResults.value = [];
        searchBusy.value = false;
    }

    return {
        searchResults,
        searchBusy,
        queueProductSearch,
        searchProducts,
        stopProductSearch,
        resetProductSearch,
    };
}
