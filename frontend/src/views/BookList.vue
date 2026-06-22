<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '../api/client';
import { useAuth } from '../stores/auth';
import BookForm from '../components/BookForm.vue';

const auth   = useAuth();
const books  = ref([]);
const q      = ref('');
const error  = ref('');
const ok     = ref('');
const editing = ref(null); // null | 'new' | book object

const filtered = computed(() => books.value);

async function load() {
  error.value = '';
  try {
    const { data } = await api.get('/api/books', { params: { q: q.value || undefined } });
    books.value = data.data;
  } catch (e) {
    error.value = e.message;
  }
}

async function remove(book) {
  if (!confirm(`Delete "${book.title}"?`)) return;
  try {
    await api.delete(`/api/books/${book.id}`);
    ok.value = 'Deleted';
    await load();
  } catch (e) {
    error.value = e.response?.data?.error || e.message;
  }
}

function startCreate()       { editing.value = 'new'; }
function startEdit(book)     { editing.value = { ...book }; }
function cancelEdit()        { editing.value = null; }
async function onSaved(msg)  { ok.value = msg; editing.value = null; await load(); }

function canModify(book) {
  if (!auth.isAuthenticated) return false;
  if (auth.isAdmin) return true;
  return book.created_by === auth.user.id;
}

onMounted(load);
</script>

<template>
  <div class="card">
    <div class="row" style="align-items: end;">
      <div style="flex: 2;">
        <label>Search by title or author</label>
        <input v-model="q" placeholder="e.g. clean" @keyup.enter="load" />
      </div>
      <div>
        <button class="primary" @click="load">Search</button>
      </div>
      <div v-if="auth.isAuthenticated">
        <button class="primary" @click="startCreate">+ New book</button>
      </div>
    </div>
  </div>

  <BookForm
    v-if="editing !== null"
    :book="editing === 'new' ? null : editing"
    @saved="onSaved"
    @cancel="cancelEdit"
  />

  <p v-if="error" class="alert error">{{ error }}</p>
  <p v-if="ok"    class="alert ok">{{ ok }}</p>

  <div class="card" v-if="filtered.length">
    <div class="book" v-for="b in filtered" :key="b.id">
      <div>
        <strong>{{ b.title }}</strong>
        <span class="tag">{{ b.year }}</span>
        <div class="meta">{{ b.author }} • {{ b.genre }}</div>
      </div>
      <div class="actions" v-if="canModify(b)">
        <button @click="startEdit(b)">Edit</button>
        <button class="danger" v-if="auth.isAdmin" @click="remove(b)">Delete</button>
      </div>
    </div>
  </div>
  <p v-else class="card" style="text-align: center; color: var(--muted);">
    No books found.
  </p>
</template>
