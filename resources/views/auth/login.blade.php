<!DOCTYPE html>
<html>
<head>

    <title>Admin Login</title>

    <script src="https://cdn.tailwindcss.com"></script>

</head>

<body class="bg-gray-100">

    <div class="flex items-center justify-center h-screen">

        <div class="bg-white p-8 rounded shadow w-96">

            <h2 class="text-2xl font-bold mb-6 text-center">
                Admin Login
            </h2>

            <!-- LOGIN FORM -->
            <form method="POST" action="{{ route('login') }}">

                @csrf

                <!-- USERNAME -->
                <div class="mb-4">

                    <label class="block mb-2">
                        Username
                    </label>

                    <input
                        type="text"
                        name="user_username"
                        value="{{ old('user_username') }}"
                        class="w-full border p-2 rounded"
                        required
                    >

                    @error('user_username')

                        <p class="text-red-500 text-sm mt-1">
                            {{ $message }}
                        </p>

                    @enderror

                </div>

                <!-- PASSWORD -->
                <div class="mb-4">

                    <label class="block mb-2">
                        Password
                    </label>

                    <input
                        type="password"
                        name="password"
                        class="w-full border p-2 rounded"
                        required
                    >

                    @error('password')

                        <p class="text-red-500 text-sm mt-1">
                            {{ $message }}
                        </p>

                    @enderror

                </div>

                <!-- REMEMBER ME -->
                <div class="mb-4 flex items-center">

                    <input
                        type="checkbox"
                        name="remember"
                        class="mr-2"
                    >

                    <label>
                        Remember Me
                    </label>

                </div>

                <!-- LOGIN BUTTON -->
                <button
                    type="submit"
                    class="bg-blue-600 text-white w-full py-2 rounded hover:bg-blue-700"
                >

                    Login

                </button>

            </form>

        </div>

    </div>

</body>
</html>