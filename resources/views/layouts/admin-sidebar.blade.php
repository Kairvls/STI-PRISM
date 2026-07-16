<div class="w-64 min-h-screen bg-blue-900 text-white p-5">

    <h1 class="text-2xl font-bold mb-8">
        PRISM
    </h1>

    <ul class="space-y-4">

        <li>
            <a href="/admin/dashboard">
                Dashboard
            </a>
        </li>

        <li>
            <a href="/admin/users">
                Users
            </a>
        </li>

        <li>
            <a href="/admin/users/create">
                Create Account
            </a>
        </li>

        <li>
            <a href="/admin/settings/campus-setup-pin">
                System Settings
            </a>
        </li>

        <form method="POST" action="{{ route('logout') }}">

            @csrf

            <button type="submit">
                Logout
            </button>

        </form>
        {{-- ===================================================== --}}
        {{-- ADDED RIS ADMIN APPROVAL: SIDEBAR LINK --}}
        {{-- ===================================================== --}}

        <li>
            <a href="{{ route('admin.procurement-review.index') }}">
                RIS Approval
            </a>
        </li>


    </ul>

</div>
