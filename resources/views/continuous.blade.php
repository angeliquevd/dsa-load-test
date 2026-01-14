<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Continuous Execution - DSA Load Test</title>
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
                    <a href="{{ url('/') }}" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-200 hover:text-gray-900">Batch Statements</a>
                    <a href="{{ route('single') }}" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-200 hover:text-gray-900">Single Statement</a>
                    <a href="{{ route('continuous') }}" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-200 hover:text-gray-900 bg-gray-200 text-gray-900">Continuous</a>
                    <a href="{{ route('metrics') }}" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-200 hover:text-gray-900">View Metrics</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow container mx-auto px-6 py-12">
        <div class="bg-white p-8 rounded-lg shadow-xl max-w-2xl mx-auto">
            <h1 class="text-3xl font-bold mb-2 text-center text-gray-700">Continuous Execution</h1>
            <p class="text-center text-gray-500 mb-8">Multi: 300 SORs/cycle (3 x 100 batch) | Single: 1000 SORs/cycle</p>

            @if(Session::has('status'))
                <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6 rounded-md" role="alert">
                    <p class="font-bold">Status</p>
                    <p>{{ Session::get('status') }}</p>
                </div>
            @endif

            <div class="mb-8">
                @if($isRunning)
                    <div class="flex items-center justify-center mb-4">
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                            Running
                        </span>
                    </div>
                    <form action="{{ route('continuous.stop') }}" method="post">
                        @csrf
                        <button type="submit"
                                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-150 ease-in-out">
                            Stop Continuous Execution
                        </button>
                    </form>
                @else
                    <div class="flex items-center justify-center mb-4">
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                            <span class="w-2 h-2 bg-gray-400 rounded-full mr-2"></span>
                            Stopped
                        </span>
                    </div>
                    <form action="{{ route('continuous.start') }}" method="post">
                        @csrf
                        <button type="submit"
                                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150 ease-in-out">
                            Start Continuous Execution
                        </button>
                    </form>
                @endif
            </div>

            <div id="stats-container">
                @php
                    $displayRun = $currentRun ?? $lastRun;
                @endphp

                @if($displayRun)
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">
                        {{ $currentRun ? 'Current Run' : 'Last Run' }} Statistics
                    </h2>

                    <!-- Common Stats -->
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-yellow-50 p-4 rounded-lg">
                            <p class="text-sm text-yellow-600 font-medium">Duration</p>
                            <p class="text-2xl font-bold text-yellow-700" id="stat-duration">{{ $displayRun->getDurationInSeconds() ?? 0 }}s</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm text-gray-600 font-medium">Started</p>
                            <p class="text-sm font-bold text-gray-700" id="stat-started">{{ $displayRun->started_at?->format('H:i:s') ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <!-- Multi Stats -->
                    <h3 class="text-lg font-semibold text-blue-700 mb-3">Multi (Batch) Statements</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <p class="text-sm text-blue-600 font-medium">Cycles</p>
                            <p class="text-2xl font-bold text-blue-700" id="stat-cycles">{{ $displayRun->total_cycles }}</p>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg">
                            <p class="text-sm text-green-600 font-medium">Total SORs</p>
                            <p class="text-2xl font-bold text-green-700" id="stat-statements">{{ number_format($displayRun->total_statements) }}</p>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg">
                            <p class="text-sm text-purple-600 font-medium">SORs/second</p>
                            <p class="text-2xl font-bold text-purple-700" id="stat-rate">{{ $displayRun->getStatementsPerSecond() }}</p>
                        </div>
                        <div class="bg-red-50 p-4 rounded-lg">
                            <p class="text-sm text-red-600 font-medium">Errors</p>
                            <p class="text-2xl font-bold text-red-700" id="stat-errors">{{ $displayRun->total_errors }}</p>
                        </div>
                    </div>

                    <!-- Single Stats -->
                    <h3 class="text-lg font-semibold text-indigo-700 mb-3">Single Statements</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-indigo-50 p-4 rounded-lg">
                            <p class="text-sm text-indigo-600 font-medium">Cycles</p>
                            <p class="text-2xl font-bold text-indigo-700" id="stat-single-cycles">{{ $displayRun->total_single_cycles ?? 0 }}</p>
                        </div>
                        <div class="bg-teal-50 p-4 rounded-lg">
                            <p class="text-sm text-teal-600 font-medium">Total SORs</p>
                            <p class="text-2xl font-bold text-teal-700" id="stat-single-statements">{{ number_format($displayRun->total_single_statements ?? 0) }}</p>
                        </div>
                        <div class="bg-pink-50 p-4 rounded-lg">
                            <p class="text-sm text-pink-600 font-medium">SORs/second</p>
                            <p class="text-2xl font-bold text-pink-700" id="stat-single-rate">{{ $displayRun->getSingleStatementsPerSecond() ?? 0 }}</p>
                        </div>
                        <div class="bg-orange-50 p-4 rounded-lg">
                            <p class="text-sm text-orange-600 font-medium">Errors</p>
                            <p class="text-2xl font-bold text-orange-700" id="stat-single-errors">{{ $displayRun->total_single_errors ?? 0 }}</p>
                        </div>
                    </div>
                @else
                    <div class="bg-gray-50 p-6 rounded-lg text-center">
                        <p class="text-gray-500">No execution history yet. Click "Start" to begin.</p>
                    </div>
                @endif
            </div>
        </div>
    </main>

    <footer class="bg-white mt-auto">
        <div class="container mx-auto px-6 py-4 text-center text-gray-500 text-sm">
            &copy; {{ date('Y') }} DSA Load Tester. All rights reserved.
        </div>
    </footer>

    @if($isRunning)
    <script>
        function updateStats() {
            fetch('{{ route('continuous.stats') }}')
                .then(response => response.json())
                .then(data => {
                    if (data.run) {
                        // Multi stats
                        document.getElementById('stat-cycles').textContent = data.run.total_cycles;
                        document.getElementById('stat-statements').textContent = data.run.total_statements.toLocaleString();
                        document.getElementById('stat-duration').textContent = (data.run.duration_seconds || 0) + 's';
                        document.getElementById('stat-rate').textContent = data.run.statements_per_second;
                        document.getElementById('stat-errors').textContent = data.run.total_errors;

                        // Single stats
                        document.getElementById('stat-single-cycles').textContent = data.run.total_single_cycles || 0;
                        document.getElementById('stat-single-statements').textContent = (data.run.total_single_statements || 0).toLocaleString();
                        document.getElementById('stat-single-rate').textContent = data.run.single_statements_per_second || 0;
                        document.getElementById('stat-single-errors').textContent = data.run.total_single_errors || 0;
                    }

                    if (!data.isRunning) {
                        // Reload page if execution stopped
                        window.location.reload();
                    }
                })
                .catch(error => console.error('Error fetching stats:', error));
        }

        // Update stats every 2 seconds while running
        setInterval(updateStats, 2000);
    </script>
    @endif

</body>
</html>
