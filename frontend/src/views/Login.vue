<script setup>
import { ref } from 'vue';
import { useRouter, useRoute, RouterLink } from 'vue-router';
import { useAuth } from '../stores/auth';

const auth = useAuth();
const router = useRouter();
const route  = useRoute();

const email    = ref('member@books.test');
const password = ref('password');
const error    = ref('');
const busy     = ref(false);

async function submit() {
  error.value = '';
  busy.value  = true;
  try {
    await auth.login(email.value, password.value);
    router.push(route.query.redirect ?? '/');
  } catch (e) {
    if (e.response?.status === 429) {
      error.value = 'Too many attempts. Please wait and try again.';
    } else if (e.response?.status === 401) {
      error.value = 'Invalid email or password.';
    } else {
      error.value = 'Login failed: ' + (e.message || 'unknown error');
    }
  } finally {
    busy.value = false;
  }
}
</script>

<template>
  <div class="card" style="max-width: 420px; margin: 32px auto;">
    <h2 style="margin-top: 0;">Sign in</h2>
    <p v-if="error" class="alert error">{{ error }}</p>

    <label for="email">Email</label>
    <input id="email" v-model="email" type="email" autocomplete="email" />

    <label for="pw">Password</label>
    <input id="pw" v-model="password" type="password" autocomplete="current-password" />

    <p style="margin-top: 18px;">
      <button class="primary" :disabled="busy" @click="submit">
        {{ busy ? 'Signing in…' : 'Sign in' }}
      </button>
    </p>
    <p style="font-size: 13px; color: var(--muted);">
      No account? <RouterLink to="/register">Register</RouterLink>
    </p>
    <p style="font-size: 12px; color: var(--muted); margin-top: 24px; line-height: 1.4;">
      Demo logins:<br>
      member@books.test / password<br>
      admin@books.test / password
    </p>
  </div>
</template>
