<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class LogController extends Controller
{
    public function index()
    {
        return Inertia::render('Platform/Logs/Index', [
            'queryLoggingEnabled' => Cache::get('query_logging_enabled', false),
            'systemLogs' => $this->getSystemLogs(),
            'queryLogs' => $this->getQueryLogs(),
        ]);
    }

    public function toggleQueryLogging(Request $request)
    {
        $enabled = $request->boolean('enabled');
        if ($enabled) {
            Cache::put('query_logging_enabled', true, now()->addHours(24)); // Auto-disable after 24h
        } else {
            Cache::forget('query_logging_enabled');
        }

        return back()->with('success', 'Query logging ' . ($enabled ? 'enabled' : 'disabled') . '.');
    }

    public function clearSystemLogs()
    {
        $logFile = storage_path('logs/laravel.log');
        if (File::exists($logFile)) {
            File::put($logFile, '');
        }

        return back()->with('success', 'System logs cleared.');
    }

    public function clearQueryLogs()
    {
        $date = now()->format('Y-m-d');
        $logFile = storage_path("logs/query-{$date}.log");
        if (File::exists($logFile)) {
            File::put($logFile, '');
        }

        return back()->with('success', 'Query logs cleared.');
    }

    private function getSystemLogs()
    {
        $logFile = storage_path('logs/laravel.log');
        if (!File::exists($logFile)) {
            return [];
        }

        $logs = [];
        $file = File::get($logFile);
        $lines = explode("\n", $file);
        $lines = array_reverse($lines); // Show newest first

        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            // Simple parsing logic, can be improved with regex
            // Example line: [2024-01-01 12:00:00] local.ERROR: Message ...
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.*)/', $line, $matches)) {
                $logs[] = [
                    'timestamp' => $matches[1],
                    'environment' => $matches[2],
                    'level' => $matches[3],
                    'message' => $matches[4],
                ];
            } else {
                // If it's a stack trace line or doesn't match standard format, append to previous log if possible
                // For simplicity in this UI, we might skip or handle differently.
                // Here we'll just add it as a "raw" line attached to previous if we want, or ignore for cleaner view.
                // Let's keep it simple: only show main log lines.
            }

            if (count($logs) >= 200) break; // Limit to 200 lines
        }

        return $logs;
    }

    private function getQueryLogs()
    {
        $date = now()->format('Y-m-d');
        $logFile = storage_path("logs/query-{$date}.log");

        if (!File::exists($logFile)) {
            return [];
        }

        $logs = [];
        $file = File::get($logFile);
        $lines = explode("\n", $file);
        $lines = array_reverse($lines);

        foreach ($lines as $line) {
            if (empty(trim($line))) continue;

            // Example format: [2024-01-01 12:00:00] [12ms] select * from users where id = ? [1]
            if (preg_match('/^\[(.*?)\] \[(.*?)\] (.*)/', $line, $matches)) {
                $logs[] = [
                    'timestamp' => $matches[1],
                    'duration' => $matches[2],
                    'query' => $matches[3],
                ];
            } else {
                 $logs[] = [
                    'timestamp' => '',
                    'duration' => '',
                    'query' => $line,
                ];
            }

            if (count($logs) >= 200) break;
        }

        return $logs;
    }
}
