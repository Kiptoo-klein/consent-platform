<x-guest-layout>
    <div class="mb-6 text-sm leading-6 text-gray-600">
        This is a secure area. Confirm your password before continuing.
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div>
            <label
                for="password"
                class="block text-sm font-semibold text-gray-700"
            >
                Password
            </label>

            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700"
            >

            @error('password')
                <p class="mt-2 text-sm text-red-700">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="mt-6 flex justify-end">
            <button
                type="submit"
                class="inline-flex items-center rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800"
            >
                Confirm Password
            </button>
        </div>
    </form>
</x-guest-layout>
