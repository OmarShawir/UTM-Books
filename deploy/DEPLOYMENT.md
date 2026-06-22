# Deployment Guide — Books Full-Stack App

This document walks through deploying the Chapter 13 stack:
**Vue 3 frontend** + **PHP Slim backend** + **MySQL database**, plus
the **Capacitor Android wrap**.

> Replace example URLs with your actual hostnames as you go.

## 1. Pre-flight checklist

- [ ] Backend code (Ch12_BooksAPI_Solution) committed to its own git repo
- [ ] Frontend code committed to a separate git repo
- [ ] `.env` files NOT committed (only `.env.example` is)
- [ ] Production-strength `JWT_SECRET` generated:
  ```
  php -r "echo bin2hex(random_bytes(32));"
  ```

## 2. Database

Pick one:
- **PlanetScale** (free hobby plan, MySQL-compatible)
- **AWS RDS** / **Cloud SQL** (paid but production-grade)
- **Shared hosting MySQL** (cPanel)

Steps:
1. Create the database (UTF-8 / utf8mb4).
2. Apply schema:
   ```
   mysql -h <host> -u <user> -p < sql/schema.sql
   ```
3. Save credentials to your password manager.

## 3. Backend (Render Web Service example)

1. Push the backend repo to GitHub.
2. On Render dashboard → **New → Web Service** → connect repo.
3. **Runtime**: Docker (or PHP via custom Dockerfile). For a quick start, use a `Dockerfile` like:
   ```dockerfile
   FROM php:8.2-apache
   RUN docker-php-ext-install pdo_mysql
   COPY . /var/www/html
   RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf
   ```
4. Set the **environment variables** from `.env.example`:
   - `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
   - `JWT_SECRET`, `JWT_TTL`, `JWT_ISSUER`
   - `CORS_ALLOWED_ORIGINS` ← include the frontend URL
   - `APP_DEBUG=false`
5. Deploy. Note the public URL, e.g. `https://books-api-XXXX.onrender.com`.
6. Smoke-test:
   ```
   curl https://books-api-XXXX.onrender.com/
   ```

## 4. Frontend (Vercel example)

1. Push the frontend repo to GitHub.
2. On Vercel → **Add New → Project** → import the repo.
3. Framework: **Vite**. Build command: `npm run build`. Output: `dist`.
4. **Environment variable**:
   - `VITE_API_BASE_URL` = `https://books-api-XXXX.onrender.com`
5. Deploy. Note the URL, e.g. `https://books.vercel.app`.
6. Update the BACKEND `CORS_ALLOWED_ORIGINS` to include `https://books.vercel.app`, then redeploy.
7. Open the frontend, log in with one of the seeded users, verify create/edit/delete.

### Netlify alternative

```
npm i -g netlify-cli
npm run build
netlify deploy --prod --dir=dist
```

Add `VITE_API_BASE_URL` in **Site settings → Build & deploy → Environment**.

## 5. Capacitor — Android wrap

After the frontend is deployed (or with localhost during development):

```
cd frontend
npm install
npm install @capacitor/core @capacitor/cli @capacitor/android
npx cap init books-app com.utm.books --web-dir=dist
npm run build
npx cap add android
npx cap sync android
npx cap open android       # opens Android Studio
```

In Android Studio:
1. Wait for Gradle sync to finish.
2. Pick an emulator or connect an Android device with USB debugging.
3. Click **▶ Run**.
4. Log in with the seeded credentials.

### Release APK (optional)

1. Generate a keystore once:
   ```
   keytool -genkey -v -keystore release.keystore -alias books -keyalg RSA -keysize 2048 -validity 10000
   ```
2. Add to `android/gradle.properties`:
   ```
   MYAPP_RELEASE_STORE_FILE=release.keystore
   MYAPP_RELEASE_KEY_ALIAS=books
   MYAPP_RELEASE_STORE_PASSWORD=...
   MYAPP_RELEASE_KEY_PASSWORD=...
   ```
3. Edit `android/app/build.gradle` to use the signing config in `release`.
4. Android Studio → **Build → Generate Signed Bundle / APK**.

## 6. Post-launch

- Add an uptime check on `/` (UptimeRobot, Better Stack).
- Schedule daily DB backups.
- Add a `/api/health` endpoint for green/red dashboards.
- Document a rollback procedure.
- Rotate `JWT_SECRET` periodically (and on suspected leak — invalidates all sessions).
