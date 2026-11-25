@extends('layout.main')
@section('title', 'Team Members — ' . config('app.name'))

@section('content')
<div class="main-inner">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 lg:mb-8">
        <div>
            <h2 class="h2">Team Management</h2>
            <p class="text-sm text-n100 mt-1">{{ $team ? "Team: {$team->team_name}" : 'Invite colleagues to collaborate on your workspace.' }}</p>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-6">
        <div class="box col-span-12 lg:col-span-4 border border-n30">
            <h4 class="h4 bb-dashed pb-4 mb-4">Invite Team Member</h4>
            <form method="POST" action="{{ route('team.invite') }}" class="space-y-4">
                @csrf
                @if (! $team)
                    <div>
                        <label for="team_name" class="block font-medium mb-2">Team Name</label>
                        <input type="text" name="team_name" id="team_name" value="{{ $client->name }} Team"
                            class="w-full rounded-3xl border border-n30 bg-primary/5 px-4 py-3 text-sm" />
                    </div>
                @endif
                <div>
                    <label for="name" class="block font-medium mb-2">Full Name</label>
                    <input type="text" name="name" id="name" required
                        class="w-full rounded-3xl border border-n30 bg-primary/5 px-4 py-3 text-sm"
                        placeholder="Jane Doe" />
                </div>
                <div>
                    <label for="email" class="block font-medium mb-2">Email Address</label>
                    <input type="email" name="email" id="email" required
                        class="w-full rounded-3xl border border-n30 bg-primary/5 px-4 py-3 text-sm"
                        placeholder="colleague@company.com" />
                </div>
                <div>
                    <label for="role_id" class="block font-medium mb-2">Role</label>
                    <select name="role_id" id="role_id" class="w-full rounded-3xl border border-n30 bg-primary/5 px-4 py-3 text-sm">
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->role_name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn-primary w-full justify-center">Send Invitation</button>
            </form>
        </div>

        <div class="box col-span-12 lg:col-span-8 border border-n30">
            <h4 class="h4 bb-dashed pb-4 mb-4">Members</h4>
            <div class="overflow-x-auto">
                <table class="w-full whitespace-nowrap text-sm">
                    <thead>
                        <tr class="bg-secondary/5">
                            <th class="px-4 py-3 text-start">Name</th>
                            <th class="px-4 py-3 text-start">Email</th>
                            <th class="px-4 py-3 text-start">Role</th>
                            <th class="px-4 py-3 text-start">Status</th>
                            <th class="px-4 py-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($members as $member)
                            <tr class="even:bg-secondary/5">
                                <td class="px-4 py-2.5 font-medium">{{ $member->client?->name }}</td>
                                <td class="px-4 py-2.5">{{ $member->client?->email }}</td>
                                <td class="px-4 py-2.5">{{ $member->role?->role_name }}</td>
                                <td class="px-4 py-2.5"><span class="rounded-full bg-primary/10 px-3 py-1 text-xs text-primary">{{ ucfirst($member->status) }}</span></td>
                                <td class="px-4 py-2.5 text-end">
                                    <form method="POST" action="{{ route('team.remove', $member) }}" onsubmit="return confirm('Remove this team member?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-error py-1 px-3 text-xs">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-n100">No team members added yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
