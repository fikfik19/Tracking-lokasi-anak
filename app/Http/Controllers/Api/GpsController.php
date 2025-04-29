<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GpsLocation;
use Illuminate\Http\Request;

class GpsController extends Controller
{
    public function store(Request $request)
    {
        try {
            // Log raw data untuk debugging
            \Log::info('Received GPS data: ' . $request->getContent());

            // Validate request
            $validated = $request->validate([
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                'emergency' => 'boolean', // Optional emergency state
                'device_id' => 'nullable|string'
            ]);

            // Simpan ke database
            $location = GpsLocation::create($validated);

            // Return simple OK response
            return response('OK', 200);

        } catch (\Exception $e) {
            // Log error tapi tetap return OK
            \Log::error('GPS data error: ' . $e->getMessage());
            return response('OK', 200); // Tetap return OK untuk mencegah retry
        }
    }
}
