<x-guest-layout>
    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <input
            type="hidden"
            name="token"
            value="{{ $request->route('token') }}"
        >

        <div>
            <label
                for="email"
                class="block text-sm font-semibold text-gray-700"
            >
                Email Address
            </label>

            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email', $request->email) }}"
                required
                autofocus
                autocomplete="username"
                class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700"
            >

            @error('email')
                <p class="mt-2 text-sm text-red-700">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="mt-5">
            <label
                for="password"
                class="block text-sm font-semibold text-gray-700"
            >
                New Password
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
                <p class="mt-2 text-sm text-red-700">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="mt-5">
            <label
                for="password_confirmation"
                class="block text-sm font-semibold text-gray-700"
            >
                Confirm New Password
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

        <div class="mt-6 flex justify-end">
            <button
                type="submit"
                class="inline-flex items-center rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800"
            >
                Reset Password
            </button>
        </div>
    </form>
</x-guest-layout>
