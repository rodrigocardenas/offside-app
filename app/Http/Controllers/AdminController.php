<?php

namespace App\Http\Controllers;

use App\Facades\CloudflareImages;
use App\Models\Group;
use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

class AdminController extends Controller
{
    /**
     * Show Cloudflare Images admin dashboard
     */
    public function cloudflareImagesDashboard(): View|Factory|Application
    {
        // Check if user is admin
        if (!auth()->user()?->hasRole('admin')) {
            abort(403, 'Únicamente administradores pueden acceder a este dashboard');
        }

        // Get stats from Cloudflare
        $stats = CloudflareImages::getAccountStats();
        
        // Get additional local stats
        $totalUsers = User::count();
        $totalGroups = Group::count();
        $totalAvatarsWithCloudflare = User::where('avatar_provider', 'cloudflare')->count();
        $totalCoversWithCloudflare = Group::where('cover_provider', 'cloudflare')->count();
        
        // Calculate percentages
        $avatarCloudflarePercentage = $totalUsers > 0 ? round(($totalAvatarsWithCloudflare / $totalUsers) * 100, 1) : 0;
        $coverCloudflarePercentage = $totalGroups > 0 ? round(($totalCoversWithCloudflare / $totalGroups) * 100, 1) : 0;
        
        // Get recent uploads
        $recentUploads = User::where('avatar_provider', 'cloudflare')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->select(['id', 'name', 'updated_at', 'avatar_cloudflare_id'])
            ->get();

        return view('admin.cloudflare-dashboard', [
            'stats' => $stats,
            'totalUsers' => $totalUsers,
            'totalGroups' => $totalGroups,
            'totalAvatarsWithCloudflare' => $totalAvatarsWithCloudflare,
            'totalCoversWithCloudflare' => $totalCoversWithCloudflare,
            'avatarCloudflarePercentage' => $avatarCloudflarePercentage,
            'coverCloudflarePercentage' => $coverCloudflarePercentage,
            'recentUploads' => $recentUploads,
        ]);
    }
}
