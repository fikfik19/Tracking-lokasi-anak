<!-- resources/views/logs/index.blade.php -->
<x-app-layout>
    <div class="pt-16 pb-20"> <!-- Padding for fixed header and footer -->
        <div class="min-w-full mx-auto px-4">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <!-- Header with actions -->
                <div class="p-4 border-b border-gray-200">
                    <div class="flex flex-col space-y-3">
                        <h2 class="text-lg font-semibold text-gray-800">System Logs</h2>
                        <div class="flex space-x-2">
                            <a href="{{ route('logs.download') }}"
                                class="flex-1 bg-blue-500 text-white px-3 py-2 rounded-md text-sm font-medium text-center hover:bg-blue-600 transition">
                                Download Log
                            </a>
                            <form action="{{ route('logs.clear') }}" method="POST" class="flex-1">
                                @csrf
                                <button type="submit"
                                    class="w-full bg-red-500 text-white px-3 py-2 rounded-md text-sm font-medium hover:bg-red-600 transition"
                                    onclick="return confirm('Are you sure you want to clear the log file?')">
                                    Clear Log
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Log entries -->
                <div class="overflow-x-auto">
                    <div class="min-w-full divide-y divide-gray-200">
                        @forelse($logs as $log)
                            <div class="p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm text-gray-600">{{ $log['date'] }}</span>
                                    <span
                                        class="px-2 py-1 text-xs rounded-full
                                        @if ($log['type'] === 'ERROR') bg-red-100 text-red-800
                                        @elseif($log['type'] === 'INFO') bg-blue-100 text-blue-800
                                        @elseif($log['type'] === 'WARNING') bg-yellow-100 text-yellow-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        {{ $log['type'] }}
                                    </span>
                                </div>
                                <div class="text-sm text-gray-800">
                                    <div class="text-xs text-gray-500 mb-1">{{ $log['env'] }}</div>
                                    {{ $log['message'] }}
                                </div>
                            </div>
                        @empty
                            <div class="p-4 text-center text-gray-500">
                                No log entries found.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
