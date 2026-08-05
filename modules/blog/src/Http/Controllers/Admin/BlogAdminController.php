<?php

namespace Modules\Blog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

class BlogAdminController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.plugins.blog.posts.index');
    }

    public function settings(): RedirectResponse
    {
        return redirect()->route('admin.plugins.blog.posts.index');
    }
}
