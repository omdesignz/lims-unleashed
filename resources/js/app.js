import "./bootstrap";
import "../css/app.css";
import { createApp, defineAsyncComponent, h } from "vue";
import { createPinia } from 'pinia'
import shortkey from 'vue-shortkey'
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { route, ZiggyVue } from 'ziggy-js'
import Layout from "./Shared/Layouts/Layout.vue";
import { createInertiaApp, Link, Head } from "@inertiajs/vue3";
import { usePopstate } from "./Composables/usePopstate";
import Vue3ColorPicker from "vue3-colorpicker";
import "vue3-colorpicker/style.css";
import { MotionPlugin } from "@vueuse/motion";
import { i18nVue } from "laravel-vue-i18n";
import { register } from "swiper/element/bundle";

const appName =
  window.document.getElementsByTagName("title")[0]?.innerText || "Gestlab V3";

const ApexChart = defineAsyncComponent(async () => (await import("vue3-apexcharts")).default);

register();

import.meta.glob(["../images/**", "../fonts/**"]);

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  progress: {
    // The delay after which the progress bar will appear, in milliseconds...
    delay: 250,

    // The color of the progress bar...
    color: '#29d',

    // Whether to include the default NProgress styles...
    includeCSS: true,

    // Whether the NProgress spinner will be shown...
    showSpinner: true,
  },
  resolve: (name) => {
    if (!name || typeof name !== 'string') {
      console.error('Inertia tried to resolve an invalid page component.', name);

      return resolvePageComponent(
        './Pages/Error.vue',
        import.meta.glob('./Pages/**/*.vue'),
      );
    }

    return resolvePageComponent(
      `./Pages/${name}.vue`,
      import.meta.glob("./Pages/**/*.vue"),
    )
      .then((module) => {
        if (!Object.prototype.hasOwnProperty.call(module.default, 'layout')) {
          module.default.layout = Layout;
        }

        return module;
      })
      .catch((error) => {
        console.error(`Failed to resolve Inertia page [${name}].`, error);

        return resolvePageComponent(
          './Pages/Error.vue',
          import.meta.glob('./Pages/**/*.vue'),
        );
      });
  },
  setup({ el, App, props, plugin }) {
    const ziggyConfig = props.initialPage.props?.ziggy
      ? {
          ...props.initialPage.props.ziggy,
          location: new URL(props.initialPage.props.ziggy.location),
        }
      : undefined;

    const routeWithConfig = (name, params, absolute, config) => route(
      name,
      params,
      absolute,
      config ?? ziggyConfig,
    );

    if (typeof window !== 'undefined') {
      window.route = routeWithConfig;
      window.Ziggy = ziggyConfig;
    }

    createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(ZiggyVue, ziggyConfig)
      .use(i18nVue, {
        lang: props.initialPage.props?.language ?? "pt",
        fallbackLang: "en",
        resolve: async (lang) => {
          const langs = import.meta.glob("../../lang/*.json");
          return await langs[`../../lang/${lang}.json`]();
        },
      })
      .use(MotionPlugin)
      .use(Vue3ColorPicker)
      .use(shortkey)
      .use(Toast, {
        position: 'top-right',
        timeout: 3000,
        closeOnClick: true,
        pauseOnFocusLoss: true,
        pauseOnHover: true,
        draggable: true,
        draggablePercent: 0.6,
        showCloseButtonOnHover: false,
        hideProgressBar: true,
        closeButton: 'button',
        icon: true,
        rtl: false
      })
      .use(createPinia())
      .component("Link", Link)
      .component("Head", Head)
      .component('apexchart', ApexChart)
      .mixin({
        methods: {
          route(name, params, absolute, config) {
            return routeWithConfig(name, params, absolute, config ?? this.$page.props.ziggy);
          },
        },
      })
      .use(usePopstate)
      .mount(el);
  },
});
