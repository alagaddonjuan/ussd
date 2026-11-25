<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Mail\TeamInviteMail;
use App\Models\Client;
use App\Models\Role;
use App\Models\Team;
use App\Models\TeamMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(Request $request): View
    {
        $client = $request->user();

        $team = Team::query()->where('client_id', $client->id)->first();
        $members = $team
            ? TeamMember::query()->with(['client', 'role'])->where('team_id', $team->id)->get()
            : collect();

        $roles = Role::query()->orderBy('id')->get();

        return view('client.team', compact('client', 'team', 'members', 'roles'));
    }

    public function invite(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'team_name' => ['nullable', 'string', 'max:255'],
        ]);

        $owner = $request->user();

        if (strtolower($data['email']) === strtolower($owner->email)) {
            return back()->withErrors(['email' => 'You cannot invite your own account.']);
        }

        $tempPassword = null;

        $team = DB::transaction(function () use ($owner, $data, &$tempPassword): Team {
            $team = Team::firstOrCreate(
                ['client_id' => $owner->id],
                ['team_name' => $data['team_name'] ?: "{$owner->name} Team", 'created_at' => now()]
            );

            $invitedClient = Client::query()->where('email', $data['email'])->first();

            if (! $invitedClient) {
                $tempPassword = Str::random(12);
                $invitedClient = Client::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($tempPassword),
                    'token_balance' => 0,
                    'is_admin' => false,
                    'status' => 'active',
                    'created_at' => now(),
                ]);
            }

            $exists = TeamMember::query()
                ->where('team_id', $team->id)
                ->where('client_id', $invitedClient->id)
                ->exists();

            if (! $exists) {
                TeamMember::create([
                    'team_id' => $team->id,
                    'client_id' => $invitedClient->id,
                    'role_id' => $data['role_id'],
                    'status' => 'active',
                    'invitation_token' => Str::random(32),
                    'created_at' => now(),
                ]);
            }

            return $team;
        });

        Mail::to($data['email'])->send(
            new TeamInviteMail($team->team_name, $data['email'], $tempPassword, route('login'))
        );

        return back()->with('status', "Team invitation sent to {$data['email']}.");
    }

    public function remove(Request $request, TeamMember $member): RedirectResponse
    {
        $team = Team::query()->where('client_id', $request->user()->id)->first();
        if (! $team || (int) $member->team_id !== (int) $team->id) {
            abort(403);
        }

        $member->delete();

        return back()->with('status', 'Team member removed.');
    }
}
