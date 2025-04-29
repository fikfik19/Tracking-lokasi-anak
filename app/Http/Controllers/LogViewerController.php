<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;

class LogViewerController extends Controller
{
    public function index()
    {
        // Path ke file log
        $logPath = storage_path('logs/laravel.log');

        // Cek apakah file exists
        if (!File::exists($logPath)) {
            return response()->view('errors.404', [], 404);
        }

        // Baca konten file
        $logContent = File::get($logPath);

        // Parse log menjadi array entries
        $pattern = '/^\[(?<date>.*)\]\s(?<env>\w+)\.(?<type>\w+):(?<message>.*)/m';
        preg_match_all($pattern, $logContent, $matches, PREG_SET_ORDER);

        $logs = collect($matches)->map(function ($match) {
            return [
                'date' => $match['date'],
                'env' => $match['env'],
                'type' => $match['type'],
                'message' => trim($match['message'])
            ];
        })->reverse();

        return view('logs.index', compact('logs'));
    }

    public function download()
    {
        $logPath = storage_path('logs/laravel.log');

        if (!File::exists($logPath)) {
            abort(404);
        }

        $headers = [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="laravel.log"',
        ];

        return response()->download($logPath, 'laravel.log', $headers);
    }

    public function clear()
    {
        $logPath = storage_path('logs/laravel.log');

        if (File::exists($logPath)) {
            File::put($logPath, '');
            return redirect()->back()->with('success', 'Log file has been cleared');
        }

        return redirect()->back()->with('error', 'Log file not found');
    }
}
