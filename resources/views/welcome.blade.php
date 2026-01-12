<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DSA Load Test</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen flex flex-col">

    <nav class="bg-white shadow-md">
        <div class="container mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <a href="{{ url('/') }}" class="text-xl font-bold text-blue-600 hover:text-blue-700">DSA Load Tester</a>
                </div>
                <div class="flex space-x-4">
                    <a href="{{ url('/') }}" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-200 hover:text-gray-900 {{ request()->is('/') ? 'bg-gray-200 text-gray-900' : '' }}">Batch Statements</a>
                    <a href="{{ route('single') }}" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-200 hover:text-gray-900 {{ request()->routeIs('single') ? 'bg-gray-200 text-gray-900' : '' }}">Single Statement</a>
                    <a href="{{ route('continuous') }}" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-200 hover:text-gray-900 {{ request()->routeIs('continuous') ? 'bg-gray-200 text-gray-900' : '' }}">Continuous</a>
                    <a href="{{ route('metrics') }}" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-200 hover:text-gray-900 {{ request()->routeIs('metrics') ? 'bg-gray-200 text-gray-900' : '' }}">View Metrics</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow container mx-auto px-6 py-12">
        <div class="bg-white p-8 rounded-lg shadow-xl max-w-xl mx-auto">
            <h1 class="text-3xl font-bold mb-8 text-center text-gray-700">Send Batch Statements</h1>

            @if(Session::has('status'))
                <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6 rounded-md" role="alert">
                    <p class="font-bold">Status</p>
                    <p>{{ Session::get('status') }}</p>
                </div>
            @endif

            <form action="{{ route('fire') }}" method="post" class="space-y-6">
                @csrf
                <div>
                    <label for="limit" class="block text-sm font-medium text-gray-700 mb-1">Number of Batches to Send</label>
                    <p class="text-xs text-gray-500 mb-2">Each batch contains 100 statements.</p>
                    <input type="number" name="limit" id="limit" min="1" required 
                           class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           placeholder="e.g., 10">
                </div>

                <div>
                    <button type="submit" 
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                        Fire Batch Statements
                    </button>
                </div>
            </form>
        </div>
    </main>

    <footer class="bg-white mt-auto">
        <div class="container mx-auto px-6 py-4 text-center text-gray-500 text-sm">
            &copy; {{ date('Y') }} DSA Load Tester. All rights reserved.
        </div>
    </footer>

</body>
</html>
