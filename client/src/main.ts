/**
 * main.ts
 *
 * Application entry point.  This file bootstraps the Vue 3 application by:
 *  1. Creating the root Vue app instance from the `App.vue` component.
 *  2. Installing Pinia (state management) as a plugin so all stores are
 *     available throughout the component tree.
 *  3. Installing Vue Router so `<router-view>` and navigation guards work.
 *  4. Importing the global CSS stylesheet (`main.css`).
 *  5. Mounting the app to the `#app` DOM element defined in `index.html`.
 *
 * Plugin installation order matters:
 *  - Pinia must be installed BEFORE any store is used (including by the
 *    router's navigation guard, which lazy-imports the auth store).
 *  - Router must be installed BEFORE mount so the initial route is resolved.
 */

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import './assets/main.css' // Global styles (Tailwind or custom CSS)

// Create the root Vue application instance
const app = createApp(App)

// Install Pinia for centralised state management (stores/auth, stores/preshift, etc.)
app.use(createPinia())

// Install Vue Router for SPA navigation and route guards
app.use(router)

// Mount the app to the DOM -- this triggers the initial render and route resolution
app.mount('#app')
