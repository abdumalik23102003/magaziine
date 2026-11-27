<?php

namespace App\Http\Controllers;

use App\Services\AvatarService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Sozlamalar sahifasi hamma uchun (mehmon, mijoz, hamkor, xodim).
 * Mavzu/shrift sozlamalari brauzerda (public/js/prefs.js); profil rasmi kirgan foydalanuvchi uchun serverda,
 * mehmon uchun faqat brauzerda saqlanadi.
 */
class SettingsController extends Controller
{
    public function show(Request $request): View
    {
        return view('pages.settings', ['user' => $request->user()]);
    }

    public function updateAvatar(Request $request, AvatarService $avatars): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.AvatarService::MAX_KB, 'dimensions:max_width=8000,max_height=8000'],
        ]);

        $avatars->store($request->user(), $request->file('avatar'));

        return redirect()->route('settings')->with('status', 'Profile photo updated.');
    }

    public function destroyAvatar(Request $request, AvatarService $avatars): RedirectResponse
    {
        $avatars->remove($request->user());

        return redirect()->route('settings')->with('status', 'Profile photo removed.');
    }
}
