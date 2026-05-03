import { router } from '@inertiajs/vue3'
import axios from 'axios'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ref } from 'vue'

const modal = ref(null)
const pageComponents = import.meta.glob('../Pages/**/*.vue')

function setModal(data) {
    if (!data || !data.component) {
        console.warn('Ignoring modal payload without a component.', data)
        return
    }

    if (modal.value?.show) {
        return
    }

    resolvePageComponent(
        `../Pages/${data.component}.vue`,
        pageComponents,
    )
        .then((component) => {
            modal.value = {
                ...data,
                resolvedComponent: component,
                show: true,
            }
        })
        .catch((error) => {
            console.error(`Failed to resolve modal component [${data.component}].`, error)
        })
}

function open(href) {
    axios
        .get(href, {
            headers: {
                'X-Inertia': true,
                'X-Modal': true,
            },
        })
        .then((response) => setModal(response.data))
        .catch((error) => {
            console.error(`Failed to open modal for [${href}].`, error)
        })
}

function close() {
    if (modal.value) {
        modal.value.show = false
    }
}

function reset() {
    // redirect back to base url
    if (modal.value?.baseUrl && modal.value.baseUrl !== window.location.href) {
        router.visit(modal.value.baseUrl)
    }

    modal.value = null
}

export { close, modal, open, reset, setModal }
