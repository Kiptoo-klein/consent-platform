<x-guest-layout>
    <div class="mb-6 text-sm leading-6 text-gray-600">
        Enter your email address and we will send you a password-reset
        link.
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

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
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="email"
                class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-700 focus:ring-teal-700"
            >

            @error('email')
                <p class="mt-2 text-sm text-red-700">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="mt-6 flex items-center justify-between gap-4">
            <a
                href="{{ route('login') }}"
                class="text-sm font-semibold text-gray-600 underline hover:text-gray-900"
            >
                Back to Login
            </a>

            <button
                type="submit"
                class="inline-flex items-center rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800"
            >
                Send Reset Link
            </button>
        </div>
    </form>
</x-guest-layout>
