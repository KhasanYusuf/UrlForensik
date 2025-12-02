<?php

namespace App\Http\Controllers;

use App\Models\MonitoredSite;
use App\Models\Kasus;
use App\Models\BuktiDigital;
use App\Models\TindakanForensik;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display dashboard.
     */
    public function index()
    {
        $total_monitored = MonitoredSite::count();
        $monitored_up = MonitoredSite::where('status', 'UP')->count();
        $monitored_defaced = MonitoredSite::where('status', 'DEFACED')->count();

        $data = [
            'total_monitored' => $total_monitored,
            'monitored_up' => $monitored_up,
            'monitored_defaced' => $monitored_defaced,
            'total_kasus' => Kasus::count(),
            'total_bukti' => BuktiDigital::count(),
            'total_users' => User::count(),
            'kasus_open' => Kasus::where('status_kasus', 'Open')->count(),
            'kasus_closed' => Kasus::where('status_kasus', 'Closed')->count(),
            'tindakan_planned' => TindakanForensik::where('status_tindakan', 'Planned')->count(),
            'tindakan_progress' => TindakanForensik::where('status_tindakan', 'In Progress')->count(),
            'tindakan_completed' => TindakanForensik::where('status_tindakan', 'Completed')->count(),
            'recent_incidents' => Kasus::with('korban')->orderBy('tanggal_kejadian', 'desc')->take(5)->get(),
        ];

        return view('dashboard', $data);
    }
}
