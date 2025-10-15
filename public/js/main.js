/**
 * main.js
 *
 * Bootstraps Vuetify and other plugins then mounts the App`
 */

// Plugins
import { registerPlugins } from '@/plugins'

// Components
import App from './App.vue'

// Composables
import { createApp } from 'vue'

// Styles
import 'unfonts.css'
import '@/styles/mokhuba.webflow.css'
import '@/styles/normalize.css'
import '@/styles/webflow.css'
const app = createApp(App)

registerPlugins(app)

app.mount('#app')
