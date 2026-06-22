<script setup>
import { ref } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import { useAuth } from '../stores/auth';

const auth   = useAuth();
const router = useRouter();

const name     = ref('');
const email    = ref('');
const password = ref('');
const error    = ref('');
const busy     = ref(false);

async function submit() {
  error.value = '';
  busy.value  = true;
  try {
    await auth.register(name.value, email.value, password.value);
    router.push('/');
  } catch (e) {
    error.value = e.response?.data?.errors
      ? Object.values(e.response.data.errors).join(' • ')
      : (e.response?.data?.error || e.message);
  } finally {
    busy.value = false;
  }
}
</script>

<template>
  <div class="card" style="max-width: 420px; margin: 32px auto;">
    <h2 style="margin-top: 0;">Register</h2>
    <p v-if="error" class="alert error">{{ error }}</p>

    <label>Full name</label>
    <input v-model="name" />

    <label>Email</label>
    <input v-model="email" type="email" autocomplete="email" />

    <label>Password (min 8 chars)</label>
    <input v-model="password" type="password" autocomplete="new-password" />

    <p style="margin-top: 18px;">
      <button class="primary" :disabled="busy" @click="submit">
        {{ busy ? 'Creating…' : 'Create account' }}
      </button>
    </p>
    <p style="font-size: 13px; color: var(--muted);">
      Already have one? <RouterLink to="/login">Sign in</RouterLink>
    </p>
  </div>
</template>
