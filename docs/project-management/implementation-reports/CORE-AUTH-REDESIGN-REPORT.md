# Core Auth Redesign Report

Date: 2026-07-13

## Objective

Redesign the core authentication pages to match the provided INPA visual reference and verify that login, registration, email verification prompt, resend verification email, and email verification logic continue working.

This change is in Laravel core only. No plugin source files were changed.

## Files Changed

```text
app/Models/User.php
app/Http/Controllers/Auth/AuthenticatedSessionController.php
app/Http/Controllers/Auth/RegisteredUserController.php
app/Http/Controllers/Auth/EmailVerificationNotificationController.php
app/Http/Controllers/Auth/VerifyEmailController.php
routes/web.php
resources/views/layouts/guest.blade.php
resources/views/auth/login.blade.php
resources/views/auth/register.blade.php
resources/views/auth/verify-email.blade.php
resources/css/app.css
resources/css/auth.css
resources/js/app.js
public/build
/var/www/store.z4rank.com/public_html/build
```

## Implementation

- Rebuilt the guest/auth shell as a centered INPA-style white card on a soft background.
- Updated login, registration, and email verification pages to follow the reference: large logo area, clean typography, red primary action, soft borders, clear spacing, and verification help panel.
- Added `resources/css/auth.css` as the dedicated auth design system CSS file.
- Added password show/hide controls in `resources/js/app.js`.
- Registration now displays first name and last name fields, then stores them as the existing `users.name` value.
- Added terms acceptance validation to registration.
- Enabled `Illuminate\Contracts\Auth\MustVerifyEmail` on the core `User` model.
- Registration now redirects to `verification.notice` after login.
- `/account` now uses `auth` and `verified` middleware.
- Verification redirects normal users to `front.account` and staff/admin users to `dashboard`.
- Hardened login redirect by falling back to `Auth::user()` after authentication.

## Backup

```text
/var/backups/art-inpa/core-auth-redesign-20260713-230900
```

## Verification

```text
php -l app/Models/User.php: passed
php -l app/Http/Controllers/Auth/AuthenticatedSessionController.php: passed
php -l app/Http/Controllers/Auth/RegisteredUserController.php: passed
php -l app/Http/Controllers/Auth/EmailVerificationNotificationController.php: passed
php -l app/Http/Controllers/Auth/VerifyEmailController.php: passed
php -l routes/web.php: passed
php -l auth Blade views: passed
php artisan optimize:clear --no-ansi: passed
php artisan route:cache --no-ansi: passed
php artisan view:cache --no-ansi: passed
php artisan config:cache --no-ansi: passed
npm run build: passed
public/build copied to public_html/build: passed
GET /login: 200
GET /register: 200
GET /verify-email as guest: 302
Functional smoke: core-auth-flow-ok register_redirect=verify-email verification_notification=sent resend=sent login=ok
Temporary verification script removed: passed
```

Browser visual QA was not run because the in-app Browser tool was not available in this Codex session.

## Rollback

```bash
cd /var/www/store.z4rank.com/laravel

cp /var/backups/art-inpa/core-auth-redesign-20260713-230900/app/Models/User.php app/Models/User.php
cp -a /var/backups/art-inpa/core-auth-redesign-20260713-230900/app/Http/Controllers/Auth app/Http/Controllers/
cp -a /var/backups/art-inpa/core-auth-redesign-20260713-230900/resources/views/auth resources/views/
cp /var/backups/art-inpa/core-auth-redesign-20260713-230900/resources/views/layouts/guest.blade.php resources/views/layouts/guest.blade.php
cp /var/backups/art-inpa/core-auth-redesign-20260713-230900/resources/css/app.css resources/css/app.css
cp /var/backups/art-inpa/core-auth-redesign-20260713-230900/routes/web.php routes/web.php
rm -f resources/css/auth.css
rm -rf public/build
cp -a /var/backups/art-inpa/core-auth-redesign-20260713-230900/public-build/build public/build
rm -rf /var/www/store.z4rank.com/public_html/build
cp -a /var/backups/art-inpa/core-auth-redesign-20260713-230900/public-html-build/build /var/www/store.z4rank.com/public_html/build
php artisan optimize:clear --no-ansi
php artisan route:cache --no-ansi
php artisan view:cache --no-ansi
php artisan config:cache --no-ansi
```
