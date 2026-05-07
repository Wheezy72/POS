export function roundCurrency(value) {
    return Math.round(Number(value || 0) * 100) / 100;
}

export function formatCurrency(value) {
    return new Intl.NumberFormat('en-KE', {
        style: 'currency',
        currency: 'KES',
        minimumFractionDigits: 2,
    }).format(Number(value || 0));
}

export function shortTimestamp(value) {
    if (!value) {
        return '--';
    }

    return new Intl.DateTimeFormat('en-KE', {
        hour: '2-digit',
        minute: '2-digit',
        day: '2-digit',
        month: 'short',
    }).format(new Date(value));
}
