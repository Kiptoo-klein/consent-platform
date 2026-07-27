<x-guest-layout>
    <div class="mb-6 text-sm leading-6 text-gray-600">
        Thanks for signing up. Before continuing, verify your email
        address using the link sent to your inbox.
    </div>

    @if (session('status') === 'verification-link-sent')
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
            A new verification link has been sent to your email address.
        </div>
    @endif

    <div class="flex items-center justify-between gap-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <button
                type="submit"
                class="inline-flex items-center rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800"
            >
                Resend Verification Email
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button
                type="submit"
                class="text-sm font-semibold text-gray-600 underline hover:text-gray-900"
            >
                Log Out
            </button>
        </form>
    </div>
</x-guest-layout>
