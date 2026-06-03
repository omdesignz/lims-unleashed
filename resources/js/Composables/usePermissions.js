import { usePage } from "@inertiajs/vue3";

export function usePermission()
{
    const normalizeNames = (items = []) => {
        if (!Array.isArray(items)) {
            return [];
        }

        return items
            .map((item) => {
                if (typeof item === 'string') {
                    return item;
                }

                return item?.name ?? item?.value ?? null;
            })
            .filter(Boolean);
    };

    const hasRole = (name) => normalizeNames(usePage().props?.auth?.user?.roles).includes(name);
    const hasPermission = (name) => normalizeNames(usePage().props?.auth?.user?.permissions).includes(name);

    return {hasRole, hasPermission};
}
