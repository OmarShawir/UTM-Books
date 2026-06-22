<script setup>
import { computed } from 'vue';
import { RouterLink, RouterView, useRouter } from 'vue-router';
import { useAuth } from './stores/auth';

const auth   = useAuth();
const router = useRouter();

const apiBase = import.meta.env.VITE_API_BASE_URL;

function logout() {
  auth.logout();
  router.push('/login');
}
</script>

<template>
  <header>
    <h1>📚 UTM Books</h1>
    <RouterLink to="/"        :class="{ active: $route.path === '/' }">Books</RouterLink>
    <RouterLink to="/profile" :class="{ active: $route.path === '/profile' }" v-if="auth.isAuthenticated">Profile</RouterLink>
    <RouterLink to="/login"   :class="{ active: $route.path === '/login' }"   v-else>Login</RouterLink>

    <span v-if="auth.isAuthenticated" style="font-size: 13px; opacity: .85;">
      {{ auth.user?.name }}
      <span class="tag" :class="{ admin: auth.isAdmin }">{{ auth.user?.role }}</span>
    </span>
    <button v-if="auth.isAuthenticated" @click="logout">Logout</button>
  </header>

  <main>
    <RouterView />
    <p style="text-align:center; color: var(--muted); font-size: 11px; margin-top: 32px;">
      API: {{ apiBase }}
    </p>
  </main>
</template>
