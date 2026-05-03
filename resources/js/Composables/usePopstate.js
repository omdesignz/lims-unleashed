import { usePage, router } from "@inertiajs/vue3"

export const usePopstate = () => {
    // router.on('finish', () => {
        window.addEventListener('popstate', () => {
            usePage().props.popstate = true;
        //   });
    })
  }