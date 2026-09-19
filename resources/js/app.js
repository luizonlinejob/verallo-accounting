import './bootstrap';
import { createApp } from 'vue';
import MainApp from './components/MainApp.vue';
import LoginForm from './components/LoginForm.vue';

const loginEl = document.getElementById('login-app');
const appEl = document.getElementById('app');

if (loginEl) {
  createApp(LoginForm).mount('#login-app');
  console.log('🔐 Login form mounted');
} else if (appEl) {
  createApp(MainApp).mount('#app');
  console.log('🎓 Main app mounted');
}