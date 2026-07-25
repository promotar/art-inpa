# Blog Visual Editor And Sidebar Submenu Fix

Date: 2026-06-28

## Objective

Fix the Blog admin navigation so the Blog item shows direct submenu links, and restyle the Blog post editor to match the requested WordPress classic post editor layout more closely.

## Execution Plan

1. Preserve the current live files with a server-side backup.
2. Update the admin navigation renderer so stored menu children are not discarded.
3. Replace the Blog post form view with a WordPress-style classic editor layout using explicit CSS.
4. Clear Laravel caches and verify the rendered page.
5. Document the work and rollback path.

## Files Changed

- `/var/www/store.z4rank.com/laravel/resources/views/layouts/navigation.blade.php`
- `/var/www/store.z4rank.com/laravel/modules/Blog/resources/views/admin/posts/form.blade.php`

## Backup

Server backup:

```text
/root/codex-backups/blog-visual-menu-fix-20260628-045856
```

Backed up files:

- `navigation.blade.php`
- `posts-form.blade.php`

## Implementation Details

- Added recursive stored admin menu mapping in `navigation.blade.php` so child menu items from the platform registry/menu system are preserved.
- Added desktop and mobile submenu rendering under each parent item.
- Added active-state propagation so a parent item is active when a child route is active.
- Added submenu styling compatible with the existing dark admin sidebar.
- Rebuilt the Blog post editor form with explicit WordPress-like styling:
  - title field
  - builder button
  - Add Media and Add Form buttons
  - Visual/Code tabs
  - quicktag toolbar
  - large editor area
  - excerpt panel
  - Rank Math SEO-style panel
  - Publish, Slug, Categories, Tags, Post Attributes, and Featured Image sidebar panels
  - Post Layout Options panel
- Kept existing form field names and JavaScript IDs used by the Blog controller and SEO scoring logic.

## Verification

- `php artisan view:clear` completed successfully.
- `php artisan optimize:clear` completed successfully.
- `php artisan route:list` includes Blog admin routes:
  - `admin/plugins/blog`
  - `admin/plugins/blog/posts`
  - `admin/plugins/blog/posts/create`
  - `admin/plugins/blog/categories`
  - `admin/plugins/blog/categories/create`
  - `admin/plugins/blog/settings`
- Rendered the actual Blog create page through Laravel using the admin controller.
- Verified the rendered HTML contains:
  - `Add New Post`
  - `All Posts`
  - `Categories`
  - `Add Category`
  - `/admin/plugins/blog/posts/create`
  - `/admin/plugins/blog/posts`
  - `/admin/plugins/blog/categories/create`
  - `Rank Math SEO`
  - `Post Layout Options`
- Captured a local headless Chrome screenshot from the rendered Laravel HTML:

```text
D:\codex_progects\inpa-server-proxmox\remote-edit\blog-visual-menu-fix\rendered-post-create.png
```

## Rollback

To rollback:

```bash
cp -a /root/codex-backups/blog-visual-menu-fix-20260628-045856/navigation.blade.php /var/www/store.z4rank.com/laravel/resources/views/layouts/navigation.blade.php
cp -a /root/codex-backups/blog-visual-menu-fix-20260628-045856/posts-form.blade.php /var/www/store.z4rank.com/laravel/modules/Blog/resources/views/admin/posts/form.blade.php
cd /var/www/store.z4rank.com/laravel
php artisan view:clear
php artisan optimize:clear
```

## Credential Handling

Server access used existing project credentials from `passwords.txt`. No plaintext secret was copied into project documentation, reports, logs, commits, or public output.
