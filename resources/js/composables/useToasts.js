import { ref } from 'vue';

let toastRef = null;

export function useToasts() {
    const toasts = ref([]);

    const setToastRef = (ref) => {
        toastRef = ref;
    };

    const success = (options) => {
        if (toastRef) {
            return toastRef.success(options);
        }
        console.log('Success:', options.title || options.message);
    };

    const error = (options) => {
        if (toastRef) {
            return toastRef.error(options);
        }
        console.error('Error:', options.title || options.message);
    };

    const warning = (options) => {
        if (toastRef) {
            return toastRef.warning(options);
        }
        console.warn('Warning:', options.title || options.message);
    };

    const info = (options) => {
        if (toastRef) {
            return toastRef.info(options);
        }
        console.info('Info:', options.title || options.message);
    };

    const show = (options) => {
        if (toastRef) {
            return toastRef.show(options);
        }
        console.log('Toast:', options.title || options.message);
    };

    return {
        toasts,
        setToastRef,
        success,
        error,
        warning,
        info,
        show
    };
}