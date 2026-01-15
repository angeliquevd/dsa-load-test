<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Statement Metrics</title>
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
    <script>
        function confirmTruncate() {
            return confirm('Are you sure you want to delete all run statistics and API errors? This action cannot be undone.');
        }
    </script>
    <div class="container mx-auto mt-10 p-6 bg-white shadow-md rounded-lg">
        <h1 class="text-3xl font-bold mb-6 text-center text-blue-600">Load Test Metrics</h1>

        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        @if ($latestRun)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-blue-50 p-6 rounded-lg shadow">
                    <h2 class="text-xl font-semibold text-blue-700 mb-2">Batch Statements</h2>
                    <p class="text-3xl font-bold text-blue-500">{{ number_format($totalStatements) }}</p>
                    <p class="text-sm text-blue-600">{{ $statementsPerSecond }} / sec</p>
                </div>
                <div class="bg-indigo-50 p-6 rounded-lg shadow">
                    <h2 class="text-xl font-semibold text-indigo-700 mb-2">Single Statements</h2>
                    <p class="text-3xl font-bold text-indigo-500">{{ number_format($totalSingleStatements) }}</p>
                    <p class="text-sm text-indigo-600">{{ $singleStatementsPerSecond }} / sec</p>
                </div>
                <div class="bg-green-50 p-6 rounded-lg shadow">
                    <h2 class="text-xl font-semibold text-green-700 mb-2">Duration (Latest Run)</h2>
                    <p class="text-3xl font-bold text-green-500">{{ $duration ?? 'N/A' }}</p>
                    @if($durationInSeconds !== null)
                        <p class="text-sm text-green-600">({{ number_format($durationInSeconds) }} seconds)</p>
                    @endif
                </div>
                <div class="bg-red-50 p-6 rounded-lg shadow">
                    <h2 class="text-xl font-semibold text-red-700 mb-2">API Errors</h2>
                    <p class="text-3xl font-bold text-red-500">{{ $apiErrorCount }}</p>
                    <p class="text-sm text-red-600">Batch: {{ $totalErrors }} | Single: {{ $totalSingleErrors }}</p>
                </div>
            </div>

            <div class="bg-gray-50 p-6 rounded-lg shadow mb-6">
                <h2 class="text-xl font-semibold text-gray-700 mb-4">Latest Run Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-gray-600 font-medium">Started At:</p>
                        <p class="text-lg text-gray-800">{{ $latestRun->started_at?->toDateTimeString() ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 font-medium">Stopped At:</p>
                        <p class="text-lg text-gray-800">{{ $latestRun->stopped_at?->toDateTimeString() ?? 'Still running...' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 font-medium">Status:</p>
                        <p class="text-lg {{ $latestRun->status === 'running' ? 'text-green-600' : 'text-gray-800' }}">
                            {{ ucfirst($latestRun->status) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-600 font-medium">Total Cycles:</p>
                        <p class="text-lg text-gray-800">Batch: {{ $latestRun->total_cycles }} | Single: {{ $latestRun->total_single_cycles }}</p>
                    </div>
                </div>
            </div>
        @else
            <div class="bg-yellow-50 p-6 rounded-lg shadow text-center">
                <p class="text-xl text-yellow-700">No load test runs recorded yet.</p>
            </div>
        @endif

        @if ($apiErrorCount > 0)
            <div class="bg-gray-50 p-6 rounded-lg shadow mb-6">
                <h2 class="text-xl font-semibold text-gray-700 mb-4">API Errors by Status Code</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($apiErrorsByStatus as $error)
                        <div class="p-4 bg-white rounded-lg shadow">
                            <p class="text-gray-600 font-medium">Status {{ $error->status_code ?? 'Connection Error' }}</p>
                            <p class="text-lg text-gray-800">{{ $error->total }} errors</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @elseif (!$latestRun && $apiErrorCount == 0)
            <div class="bg-yellow-50 p-6 rounded-lg shadow text-center mt-6">
                 <p class="text-xl text-yellow-700">No API errors found yet.</p>
            </div>
        @endif

        <div class="mt-8 text-center">
            <form action="{{ route('metrics.truncate') }}" method="POST" onsubmit="return confirmTruncate();" class="inline-block mr-4">
                @csrf
                <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Clear All Statistics
                </button>
            </form>
            <a href="{{ url('/') }}" class="text-blue-500 hover:text-blue-700 underline inline-block">Back to Home</a>
        </div>
    </div>
    </main>

    <footer class="bg-white mt-auto">
        <div class="container mx-auto px-6 py-4 text-center text-gray-500 text-sm">
            &copy; {{ date('Y') }} DSA Load Tester. All rights reserved.
        </div>
    </footer>

</body>
</html>
