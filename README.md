# Books Full-Stack — Chapter 13 Solution

SCSM2223 — Cross-Platform Application Development
Faculty of Computing, Universiti Teknologi Malaysia

This is the **complete Chapter 13 reference solution**: a Vue 3 + Vite frontend that consumes the hardened Chapter 12 PHP Slim API, with Capacitor configured for Android.

## What's inside

```
Ch13_FullStack_Solution/
├── frontend/                       ← THIS folder is the deliverable
│   ├── package.json                ← Vite + Vue 3 + Pinia + Axios + Capacitor
│   ├── vite.config.js
│   ├── index.html
│   ├── capacitor.config.json       ← Android wrapper config
│   ├── .env.development            ← VITE_API_BASE_URL=http://localhost:8000
│   ├── .env.production             ← VITE_API_BASE_URL=https://api.books.utm.my
│   ├── .gitignore
│   └── src/
│       ├── main.js
│       ├── App.vue                 ← top nav + RouterView
│       ├── style.css
│       ├── api/
│       │   └── client.js           ← Axios + JWT interceptor + 401 auto-logout
│       ├── stores/
│       │   └── auth.js             ← Pinia store with login/register/logout
│       ├── router/
│       │   └── index.js            ← public + requiresAuth routes
│       ├── views/
│       │   ├── BookList.vue        ← search, list, ownership-aware actions
│       │   ├── Login.vue
│       │   ├── Register.vue
│       │   └── Profile.vue
│       └── components/
│           └── BookForm.vue        ← create + edit form, partial-update aware
├── deploy/
│   └── DEPLOYMENT.md               ← step-by-step backend + frontend + Capacitor
└── README.md  (this file)
```

The backend used by this frontend is the previous chapter's solution:
[`../Ch12_BooksAPI_Solution/`](../Ch12_BooksAPI_Solution/).

## Run locally (dev)

### 1. Start the backend

```
cd Ch12_BooksAPI_Solution
composer install
mysql -u root < sql/schema.sql           # only the first time
copy .env.example .env                    # set a real JWT_SECRET in .env
php -S localhost:8000 -t public
```

### 2. Start the frontend

```
cd Ch13_FullStack_Solution/frontend
npm install
npm run dev                               # http://localhost:5173
```

The frontend reads `VITE_API_BASE_URL` from `.env.development`, which already points at `http://localhost:8000`.

### 3. Seeded logins

| Email              | Password   | Role   |
|--------------------|------------|--------|
| admin@books.test   | password   | admin  |
| member@books.test  | password   | member |

## Build for production

```
cd frontend
npm run build         # outputs ./dist
npm run preview       # preview the production bundle locally on :4173
```

The `dist/` folder is what you upload to any static host (Vercel, Netlify, Render Static, GitHub Pages, S3+CloudFront, or `public_html` of a shared host).

For deployment instructions, see [`deploy/DEPLOYMENT.md`](deploy/DEPLOYMENT.md).

## Wrap with Capacitor for Android

```
cd frontend
npm run build
npm run android:add        # one-time, creates android/ folder
npm run android:sync       # copy dist/ + plugins to android/
npm run android:open       # opens Android Studio
```

Then click ▶ Run in Android Studio on an emulator or USB-debugging device. The same Vue UI now runs in a native shell. To install on a phone, generate a signed APK from Android Studio → Build menu.

## Highlights

- **One Axios client** for the whole app (`src/api/client.js`) — request interceptor attaches the JWT, response interceptor logs the user out on 401.
- **Pinia auth store** (`src/stores/auth.js`) persists `token` and `user` in `localStorage` and exposes `isAuthenticated` / `isAdmin` getters used everywhere in the UI.
- **Router guards** redirect unauthenticated users to `/login` for `meta.requiresAuth` routes (e.g. `/profile`).
- **Ownership-aware UI** — `Edit` shows only when the current user is the book's `created_by`; `Delete` is admin-only, matching the backend's enforcement.
- **Env-driven API URL** — switch between local dev and production simply by changing `VITE_API_BASE_URL`. No code changes.
- **Mobile-ready** — a single `capacitor.config.json` plus four npm scripts is all that's needed to package the app as Android.
