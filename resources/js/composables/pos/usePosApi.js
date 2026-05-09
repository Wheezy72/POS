export function usePosApi(http = window.axios) {
    return {
        loginWithPin: (pin) => http.post('/api/login-pin', { pin }),
        logout: () => http.post('/api/logout'),
        fetchMe: () => http.get('/api/auth/me'),
        openShift: (openingCash = 0) => http.post('/api/shifts/open', { opening_cash: openingCash }),
        recordCashDrawer: (payload) => http.post('/api/shifts/cash-drawer-transactions', payload),
        voidSale: (payload) => http.post('/api/pos/void-sale', payload),
        searchProducts: (query) => http.post('/api/pos/search', { query }),
        fetchLivePayments: () => http.get('/api/pos/mpesa/live-feed'),
        startStkPush: (payload) => http.post('/api/pos/mpesa/stk-push', payload),
        checkout: (payload) => http.post('/api/pos/checkout', payload),
        fetchStkStatus: (checkoutRequestId) => http.post('/api/pos/mpesa/stk-status', {
            checkout_request_id: checkoutRequestId,
        }),
        verifyMpesa: (checkoutRequestId) => http.post('/api/pos/mpesa-verify', {
            CheckoutRequestID: checkoutRequestId,
        }),
    };
}
