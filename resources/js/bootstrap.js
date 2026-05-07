import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.content;

if (csrfToken) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
}

window.axios.interceptors.request.use((config) => {
    config.headers['X-Terminal-Time'] = new Date().toISOString();
    return config;
});

window.axios.interceptors.response.use(
    (response) => {
        // Phase 4: Hardware Routing for USB Printing
        // Extract base64 receipt payload and forward to local proxy daemon
        if (response.data?.receipt_payload) {
            axios.post('http://localhost:1611/print', {
                payload: response.data.receipt_payload
            }).catch(err => {
                console.error('Local printing failed:', err);
            });
        }
        return response;
    },
    (error) => {
        if (error?.response?.status === 401) {
            window.dispatchEvent(new CustomEvent('pos:unauthenticated'));
        }

        return Promise.reject(error);
    },
);
