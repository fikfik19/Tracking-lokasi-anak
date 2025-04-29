<x-app-layout>
    <div class="pt-16 pb-16 bg-gray-100 min-h-screen overflow-x-hidden">
        <div class="max-w-sm mx-auto px-2">
            <!-- Display Logged-in User's Name -->
            <div class="bg-white shadow-sm rounded-lg mb-4 relative text-center py-4">
                <p class="text-sm text-gray-600">Welcome, <span class="font-semibold">{{ Auth::user()->name }}</span>!</p>
            </div>

            <!-- Emergency Alert -->
            @if ($lastLocation && $lastLocation->emergency)
                <div
                    class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg shadow-sm mb-4 animate-pulse">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-bold">EMERGENCY ALERT!</p>
                            <p class="text-xs">Emergency signal detected at
                                {{ $lastLocation->created_at->format('H:i:s') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Location Map -->
            <div class="bg-white shadow-sm rounded-lg mb-4 relative">
                <div class="p-3">
                    <h3 class="text-base font-medium text-gray-900 mb-2">Current Location</h3>
                    <div id="map" class="h-64 w-full rounded"></div>
                </div>
            </div>

            <!-- Recent Locations -->
            <div class="bg-white shadow-sm rounded-lg relative">
                <div class="p-3">
                    <h3 class="text-base font-medium text-gray-900 mb-2">Recent Locations</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Time
                                    </th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Lat</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Long
                                    </th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status
                                    </th>
                                    @if ($locations->contains(fn($loc) => $loc->device_id))
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                            Device</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($locations as $location)
                                    <tr>
                                        <td class="px-3 py-2 text-xs text-gray-500">
                                            {{ $location->created_at->format('H:i:s') }}<br>
                                            <span
                                                class="text-xs text-gray-400">{{ $location->created_at->format('Y-m-d') }}</span>
                                        </td>
                                        <td class="px-3 py-2 text-xs text-gray-500">
                                            {{ number_format($location->latitude, 6) }}
                                        </td>
                                        <td class="px-3 py-2 text-xs text-gray-500">
                                            {{ number_format($location->longitude, 6) }}
                                        </td>
                                        <td class="px-3 py-2 text-xs">
                                            @if ($location->emergency)
                                                <span
                                                    class="px-2 py-1 text-xs font-semibold text-red-500 bg-red-100 rounded-full">
                                                    Emergency
                                                </span>
                                            @else
                                                <span
                                                    class="px-2 py-1 text-xs font-semibold text-green-500 bg-green-100 rounded-full">
                                                    Normal
                                                </span>
                                            @endif
                                        </td>
                                        @if ($location->device_id)
                                            <td class="px-3 py-2 text-xs text-gray-500">
                                                {{ $location->device_id }}
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script>
        // Initialize map
        const map = L.map('map', {
            zoomControl: false
        }).setView([-6.2088, 106.8456], 13);

        L.control.zoom({
            position: 'bottomright'
        }).addTo(map);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);

        let marker;

        @if ($lastLocation)
            const customIcon = L.icon({
                iconUrl: @if ($lastLocation->emergency)
                    'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png'
                @else
                    'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png'
                @endif ,
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });

            marker = L.marker(
                [{{ $lastLocation->latitude }}, {{ $lastLocation->longitude }}], {
                    icon: customIcon
                }
            ).addTo(map);

            @if ($lastLocation->emergency)
                marker.bindPopup("<b>EMERGENCY!</b><br>Location reported.").openPopup();
            @endif

            map.setView([{{ $lastLocation->latitude }}, {{ $lastLocation->longitude }}], 15);
        @endif

        let audioAlert;

        function updateLocations() {
            fetch('{{ route('locations.get') }}')
                .then(response => response.json())
                .then(locations => {
                    if (locations.length > 0) {
                        window.location.reload();
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        setInterval(updateLocations, 10000);

        @if ($lastLocation && $lastLocation->emergency)
            audioAlert = new Audio('https://actions.google.com/sounds/v1/alarms/beep_short.ogg');
            audioAlert.loop = true;
            audioAlert.play().catch(e => console.log('Audio playback failed:', e));
        @else
            if (audioAlert) {
                audioAlert.pause();
                audioAlert.currentTime = 0;
            }
        @endif
    </script>
</x-app-layout>
