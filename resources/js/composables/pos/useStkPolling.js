import { ref } from 'vue';

export function useStkPolling(posApi, toast) {
    const stkCheckoutRequestId = ref('');
    const stkStatusMessage = ref('Idle');

    let stkPollTimer = null;

    function startStkPolling(receiptNumber, checkoutRequestId) {
        stopStkPolling();

        let attempts = 0;
        stkStatusMessage.value = 'Polling STK status…';

        stkPollTimer = window.setInterval(async () => {
            attempts += 1;

            try {
                const statusResponse = await posApi.fetchStkStatus(checkoutRequestId);

                const status = statusResponse.data.status ?? {};
                const isSuccess = status.ResultCode === 0
                    || status.ResultCode === '0'
                    || status.ResponseCode === '0'
                    || String(status.ResultDesc ?? '').toLowerCase().includes('success');

                if (isSuccess) {
                    await posApi.verifyMpesa(checkoutRequestId);

                    stkStatusMessage.value = `STK confirmed for ${receiptNumber}.`;
                    toast('M-PESA confirmed', `Sale ${receiptNumber} has been marked paid.`, 'success');
                    stopStkPolling();
                    return;
                }

                stkStatusMessage.value = status.ResultDesc || status.CustomerMessage || 'Awaiting customer confirmation on handset…';

                if (attempts >= 24) {
                    stkStatusMessage.value = 'Polling timed out. Use the verify endpoint later if payment completes.';
                    stopStkPolling();
                }
            } catch (error) {
                stkStatusMessage.value = error?.response?.data?.message ?? 'Unable to poll STK status.';

                if (attempts >= 24) {
                    stopStkPolling();
                }
            }
        }, 5000);
    }

    function stopStkPolling() {
        if (stkPollTimer) {
            window.clearInterval(stkPollTimer);
            stkPollTimer = null;
        }
    }

    function resetStkStatus() {
        stkCheckoutRequestId.value = '';
        stkStatusMessage.value = 'Idle';
    }

    return {
        stkCheckoutRequestId,
        stkStatusMessage,
        startStkPolling,
        stopStkPolling,
        resetStkStatus,
    };
}
