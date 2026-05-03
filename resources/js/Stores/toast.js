import {reactive} from "vue";

export default reactive({
    items: [],
    add(toast) {
        this.items.unshift({
            key: Symbol(),
            variant: toast?.variant || toast?.type || 'success',
            duration: toast?.duration || 5000,
            ...toast
        });
    },
    remove(index) {
        this.items.splice(index, 1);
    },
});
