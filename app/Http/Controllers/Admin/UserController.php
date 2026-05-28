<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $users = User::withCount('watchProgress')
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"))
            ->latest()
            ->get();

        return view('admin.users.index', compact('users', 'search'));
    }

    public function toggleAdmin(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Não podes alterar o teu próprio estado de admin.');
        }

        $user->update(['is_admin' => !$user->is_admin]);
        $action = $user->is_admin ? 'promovido a admin' : 'removido do admin';

        return back()->with('success', "{$user->name} foi {$action}.");
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Não podes eliminar a tua própria conta.');
        }

        $name = $user->name;
        $user->delete();

        return back()->with('success', "Utilizador {$name} eliminado.");
    }
}
