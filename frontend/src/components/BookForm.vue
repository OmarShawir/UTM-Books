<script setup>
import { ref, watchEffect } from 'vue';
import api from '../api/client';

const props = defineProps({ book: { type: Object, default: null } });
const emit  = defineEmits(['saved', 'cancel']);

const form = ref({ title: '', author: '', year: new Date().getFullYear(), genre: '' });
const error = ref('');
const busy  = ref(false);

watchEffect(() => {
  if (props.book) {
    form.value = { ...props.book };
  } else {
    form.value = { title: '', author: '', year: new Date().getFullYear(), genre: '' };
  }
});

async function submit() {
  error.value = '';
  busy.value  = true;
  try {
    const payload = {
      title:  form.value.title,
      author: form.value.author,
      year:   Number(form.value.year),
      genre:  form.value.genre || undefined,
    };
    if (props.book?.id) {
      await api.put(`/api/books/${props.book.id}`, payload);
      emit('saved', 'Updated');
    } else {
      await api.post('/api/books', payload);
      emit('saved', 'Created');
    }
  } catch (e) {
    const data = e.response?.data;
    error.value = data?.errors
      ? Object.values(data.errors).join(' • ')
      : (data?.error || e.message);
  } finally {
    busy.value = false;
  }
}
</script>

<template>
  <div class="card">
    <h3 style="margin-top: 0;">{{ props.book?.id ? 'Edit book' : 'New book' }}</h3>
    <p v-if="error" class="alert error">{{ error }}</p>
    <div class="row">
      <div>
        <label>Title</label>
        <input v-model="form.title" />
      </div>
      <div>
        <label>Author</label>
        <input v-model="form.author" />
      </div>
    </div>
    <div class="row">
      <div>
        <label>Year</label>
        <input v-model.number="form.year" type="number" />
      </div>
      <div>
        <label>Genre</label>
        <input v-model="form.genre" />
      </div>
    </div>
    <p style="margin-top: 18px; display: flex; gap: 12px;">
      <button class="primary" :disabled="busy" @click="submit">
        {{ busy ? 'Saving…' : 'Save' }}
      </button>
      <button @click="$emit('cancel')">Cancel</button>
    </p>
  </div>
</template>
