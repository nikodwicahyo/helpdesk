import { router } from "@inertiajs/vue3";

const extractAuth = (page) => {
    if (page?.props?.auth?.user?.id) {
        return page.props.auth;
    }

    return null;
};

export const getAuthFromRouter = () => {
    try {
        return extractAuth(router?.page);
    } catch (error) {
        console.warn("Failed to read auth data from Inertia router", error);
        return null;
    }
};

export const getAuthFromDomSnapshot = () => {
    if (typeof document === "undefined") {
        return null;
    }

    const pageElement = document.querySelector("[data-page]");
    if (!pageElement) {
        return null;
    }

    try {
        const page = JSON.parse(pageElement.getAttribute("data-page"));
        return extractAuth(page);
    } catch (error) {
        console.warn("Failed to parse Inertia page snapshot", error);
        return null;
    }
};

export const getInertiaAuthProps = () => {
    return getAuthFromRouter() ?? getAuthFromDomSnapshot();
};
