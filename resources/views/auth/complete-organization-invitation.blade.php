<x-guest-layout>
    <div class="mb-6">
        <p class="text-sm font-semibold text-teal-700">
            Organization invitation
        </p>

        <h1 class="mt-2 text-2xl font-bold text-gray-900">
            Complete your account setup
        </h1>

        <p class="mt-3 text-sm leading-6 text-gray-600">
            You were invited to join
            <strong>
                {{ $user->organization?->name }}
            </strong>
            on eConsent.
        </p>
    </div>

    <div class="mb-6 rounded-xl border border-gray-200 bg-gray-50 p-4">
        <dl class="space-y-3 text-sm">
            <div>
                <dt class="font-semibold text-gray-700">
                    Name
                </dt>

                <dd class="mt-1 text-gray-600">
                    {{ $user->name }}
                </dd>
            </div>

            <div>
                <dt class="font-semibold text-gray-700">
                    Role
                </dt>

                <dd class="mt-1 text-gray-600">
                    {{ $roleName }}
                </dd>
            </div>

            <div>
                <dt class="font-semibold text-gray-700">
                    Email
                </dt>

                <dd class="mt-1 text-gray-600">
                    {{ $user->email }}
                </dd>
            </div>
        </dl>
    </div>

    <form
        method="POST"
        action="{{ route(
            'organization-invitations.complete',
            ['token' => $token]
        ) }}"
        class="space-y-5"
    >
        @csrf

        <input
            type="hidden"
            name="email"
            value="{{ $user->email }}"
        >

        <div>
            <label
                for="password"
                class="block text-sm font-semibold text-gray-700"
            >
                Create password
            </label>

            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="new-password"
                class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700"
            >

            @error('password')
                <p class="mt-2 text-sm text-red-600">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div>
            <label
                for="password_confirmation"
                class="block text-sm font-semibold text-gray-700"
            >
                Confirm password
            </label>

            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700"
            >
        </div>

        @error('email')
            <p class="text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror

        <button
            type="submit"
            class="inline-flex w-full items-center justify-center rounded-lg bg-teal-700 px-4 py-3 text-sm font-semibold text-white transition hover:bg-teal-800"
        >
            Complete account setup
        </button>
    </form>
</x-guest-layout>
