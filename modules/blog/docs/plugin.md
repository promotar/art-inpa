# Blog Plugin

Version: 2.0.1

## Purpose

The Blog plugin provides a WordPress Classic Editor style publishing workflow for Art INPA. It supports real post CRUD, TinyMCE visual editing, raw HTML editing, media upload/selection, SEO metadata, categories, tags, scheduling, previews, autosave, revisions, and frontend article pages.

## Admin Workflow

Admin routes live under:

```text
/admin/plugins/blog
```

Primary screens:

- `/admin/plugins/blog/posts` - all posts.
- `/admin/plugins/blog/posts/create` - add new post.
- `/admin/plugins/blog/categories` - all categories.
- `/admin/plugins/blog/categories/create` - add category.

## Editor

The post editor uses TinyMCE loaded from jsDelivr:

```text
https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js
```

Supported controls include:

- bold, italic, underline, strikethrough
- paragraph and H1-H6 block formats
- font size
- text and background color
- ordered and unordered lists
- blockquote
- links and unlink through TinyMCE
- images and media insert
- tables
- alignment
- code block
- raw HTML Code tab
- undo/redo
- fullscreen
- word count
- paste cleanup through TinyMCE

The Visual and Code tabs synchronize before save, preview, autosave, and publish.

## Media Library

Media endpoints:

- `GET /admin/plugins/blog/media`
- `POST /admin/plugins/blog/media`
- `PATCH /admin/plugins/blog/media/{media}`
- `DELETE /admin/plugins/blog/media/{media}`

Uploads are stored on the Laravel `public` disk under:

```text
blog/media
```

Media metadata includes title, alt text, caption, mime type, size, width, height, uploader, and URL.

## Database Tables

Core tables:

- `blog_posts`
- `blog_categories`
- `blog_tags`
- `blog_post_tag`
- `blog_category_post`
- `blog_media`
- `blog_post_revisions`
- `blog_post_meta`

`blog_posts` supports title, slug, content, excerpt, status, visibility, password, published and scheduled timestamps, author, featured image, layout/template, SEO title/description, focus keyword, canonical URL, robots flags, schema type, and soft deletes.

## Publishing Rules

Public frontend pages only show posts where:

- `status = published`
- `visibility = public`
- `published_at` is null or in the past
- `scheduled_at` is null or in the past

Private and password-protected posts do not appear publicly.

## Frontend Routes

- `/blog`
- `/blog/{slug}`
- `/blog/category/{slug}`
- `/blog/tag/{slug}`
- `/blog/assets/blog.css` (plugin-owned stylesheet; no managed public asset directory required)

## VvvebJs Frontend Menu

Installation registers the database-backed frontend menu `blog.frontend` with a
top-level Blog item and a Categories submenu. It is available directly from the
Frontend Menu element in VvvebJs and can be extended or reordered from the
platform Frontend Menus screen.

Frontend output includes meta title, meta description, canonical link, robots, Open Graph basics, JSON-LD schema, featured image, category, tags, author, date, excerpt, and sanitized HTML content.

## Security

- Admin routes use the platform admin middleware stack.
- CSRF is used for form and Ajax requests.
- Uploads are validated by mime type and size.
- Script tags, inline event handlers, and `javascript:` URLs are stripped from content unless a future explicit super-admin DB setting enables script content.
- The public frontend renders stored HTML only after save-time sanitization.

## Verification

Manual smoke checks performed:

- create draft data path
- publish path
- scheduled post hidden until time
- private post hidden publicly
- category relation saved
- tag relation saved
- revision saved
- frontend article renders HTML
- schema output exists
- script tag stripped
- template/layout saved
- media upload creates `blog_media`
- media can be set as featured image
- featured image renders on frontend
