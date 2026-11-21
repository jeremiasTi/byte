<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function update(Request $r)
    {
        $u = $r->user();
        $data = $r->validate([
            'name' => 'required|string|max:120',
            'weekly_goal_minutes' => 'required|integer|min:30|max:6000',
            'reminders_enabled' => 'required|boolean',
            'current_password' => 'nullable|string',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if (! empty($data['password'])) {
            if (! Hash::check($data['current_password'] ?? '', $u->password)) {
                throw ValidationException::withMessages(['current_password' => 'Senha atual incorreta.']);
            }
            $u->password = $data['password'];
        }

        $u->forceFill([
            'name' => $data['name'],
            'weekly_goal_minutes' => $data['weekly_goal_minutes'],
            'reminders_enabled' => $data['reminders_enabled'],
        ])->save();

        return $u->fresh();
    }

    /** Exclusão da conta e de todos os dados (direito do titular — LGPD). */
    public function destroy(Request $r)
    {
        $r->validate(['password' => 'required|string']);
        abort_unless(Hash::check($r->password, $r->user()->password), 422, 'Senha incorreta.');

        $r->user()->tokens()->delete();
        $r->user()->delete();

        return response()->noContent();
    }
}
