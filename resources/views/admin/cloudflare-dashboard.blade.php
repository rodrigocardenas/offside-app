<x-dynamic-layout>
    @php
        $themeMode = auth()->user()->theme_mode ?? 'light';
        $isDark = $themeMode === 'dark';

        // Define color variables
        $bgPrimary = $isDark ? '#0a2e2c' : '#f9f9f9';
        $bgSecondary = $isDark ? '#0f3d3a' : '#f5f5f5';
        $bgTertiary = $isDark ? '#1a524e' : '#ffffff';
        $borderColor = $isDark ? '#2a4a47' : '#e0e0e0';
        $textPrimary = $isDark ? '#ffffff' : '#333333';
        $textSecondary = $isDark ? '#b0b0b0' : '#999999';
        $accentColor = '#00deb0';
        $accentDark = '#17b796';
    @endphp

    <div style="min-height: 100vh; background: {{ $bgPrimary }}; color: {{ $textPrimary }}; padding: 16px 20px; padding-top: 80px;">
        <div style="max-width: 1200px; margin: 0 auto;">
            <!-- Header -->
            <div style="margin-bottom: 32px;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <h1 style="font-size: 32px; font-weight: 700; color: {{ $textPrimary }}; margin: 0;">🌐 Cloudflare Images</h1>
                        <p style="font-size: 14px; color: {{ $textSecondary }}; margin: 4px 0 0 0;">Dashboard de administración</p>
                    </div>
                    <div style="padding: 12px 16px; border-radius: 8px; background: {{ $stats['available'] ? '#d1fae5' : '#fee2e2' }}; color: {{ $stats['available'] ? '#065f46' : '#991b1b' }}; font-weight: 600;">
                        {{ $stats['status'] }}
                    </div>
                </div>
            </div>

            <!-- Stats Cards Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; margin-bottom: 32px;">
                <!-- Total Images Card -->
                <div style="background: {{ $bgTertiary }}; border: 1px solid {{ $borderColor }}; border-radius: 12px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                        <h3 style="font-size: 14px; color: {{ $textSecondary }}; margin: 0; font-weight: 600;">Total en Cloudflare</h3>
                        <i class="fas fa-cloud" style="font-size: 20px; color: {{ $accentColor }};"></i>
                    </div>
                    <div style="font-size: 28px; font-weight: 700; color: {{ $textPrimary }};">{{ number_format($stats['total_images']) }}</div>
                    <p style="font-size: 12px; color: {{ $textSecondary }}; margin: 8px 0 0 0;">imágenes almacenadas</p>
                </div>

                <!-- Cloudflare Avatars Card -->
                <div style="background: {{ $bgTertiary }}; border: 1px solid {{ $borderColor }}; border-radius: 12px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                        <h3 style="font-size: 14px; color: {{ $textSecondary }}; margin: 0; font-weight: 600;">Avatares Cloudflare</h3>
                        <i class="fas fa-user-circle" style="font-size: 20px; color: {{ $accentColor }};"></i>
                    </div>
                    <div style="font-size: 28px; font-weight: 700; color: {{ $textPrimary }};">{{ $totalAvatarsWithCloudflare }}</div>
                    <div style="display: flex; align-items: center; margin-top: 8px; gap: 8px;">
                        <div style="flex: 1; height: 4px; background: {{ $bgSecondary }}; border-radius: 2px; overflow: hidden;">
                            <div style="height: 100%; background: {{ $accentColor }}; width: {{ $avatarCloudflarePercentage }}%;"></div>
                        </div>
                        <span style="font-size: 12px; color: {{ $textSecondary }}; white-space: nowrap;">{{ $avatarCloudflarePercentage }}%</span>
                    </div>
                </div>

                <!-- Cloudflare Group Covers Card -->
                <div style="background: {{ $bgTertiary }}; border: 1px solid {{ $borderColor }}; border-radius: 12px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                        <h3 style="font-size: 14px; color: {{ $textSecondary }}; margin: 0; font-weight: 600;">Portadas Cloudflare</h3>
                        <i class="fas fa-image" style="font-size: 20px; color: {{ $accentColor }};"></i>
                    </div>
                    <div style="font-size: 28px; font-weight: 700; color: {{ $textPrimary }};">{{ $totalCoversWithCloudflare }}</div>
                    <div style="display: flex; align-items: center; margin-top: 8px; gap: 8px;">
                        <div style="flex: 1; height: 4px; background: {{ $bgSecondary }}; border-radius: 2px; overflow: hidden;">
                            <div style="height: 100%; background: {{ $accentColor }}; width: {{ $coverCloudflarePercentage }}%;"></div>
                        </div>
                        <span style="font-size: 12px; color: {{ $textSecondary }}; white-space: nowrap;">{{ $coverCloudflarePercentage }}%</span>
                    </div>
                </div>

                <!-- Today Uploads Card -->
                <div style="background: {{ $bgTertiary }}; border: 1px solid {{ $borderColor }}; border-radius: 12px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                        <h3 style="font-size: 14px; color: {{ $textSecondary }}; margin: 0; font-weight: 600;">Uploads Hoy</h3>
                        <i class="fas fa-arrow-up" style="font-size: 20px; color: {{ $accentColor }};"></i>
                    </div>
                    <div style="font-size: 28px; font-weight: 700; color: {{ $textPrimary }};">{{ $stats['today_uploads'] }}</div>
                    <p style="font-size: 12px; color: {{ $textSecondary }}; margin: 8px 0 0 0;">imágenes subidas hoy</p>
                </div>
            </div>

            <!-- Summary Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 32px;">
                <!-- Total Users Card -->
                <div style="background: {{ $bgTertiary }}; border: 1px solid {{ $borderColor }}; border-radius: 12px; padding: 16px;">
                    <h4 style="font-size: 12px; color: {{ $textSecondary }}; margin: 0 0 8px 0; font-weight: 600;">Total Usuarios</h4>
                    <div style="font-size: 24px; font-weight: 700; color: {{ $accentColor }};">{{ $totalUsers }}</div>
                </div>

                <!-- Total Groups Card -->
                <div style="background: {{ $bgTertiary }}; border: 1px solid {{ $borderColor }}; border-radius: 12px; padding: 16px;">
                    <h4 style="font-size: 12px; color: {{ $textSecondary }}; margin: 0 0 8px 0; font-weight: 600;">Total Grupos</h4>
                    <div style="font-size: 24px; font-weight: 700; color: {{ $accentColor }};">{{ $totalGroups }}</div>
                </div>

                <!-- Local Avatars Card -->
                <div style="background: {{ $bgTertiary }}; border: 1px solid {{ $borderColor }}; border-radius: 12px; padding: 16px;">
                    <h4 style="font-size: 12px; color: {{ $textSecondary }}; margin: 0 0 8px 0; font-weight: 600;">Avatares Locales</h4>
                    <div style="font-size: 24px; font-weight: 700; color: {{ $textPrimary }};">{{ \App\Models\User::where('avatar_provider', 'local')->count() }}</div>
                </div>

                <!-- Features Enabled Card -->
                <div style="background: {{ $bgTertiary }}; border: 1px solid {{ $borderColor }}; border-radius: 12px; padding: 16px;">
                    <h4 style="font-size: 12px; color: {{ $textSecondary }}; margin: 0 0 8px 0; font-weight: 600;">Estado</h4>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: {{ config('cloudflare.enabled') ? '#10b981' : '#ef4444' }};"></span>
                        <span style="color: {{ $textPrimary }}; font-weight: 500;">{{ config('cloudflare.images.enabled') ? 'Activo' : 'Inactivo' }}</span>
                    </div>
                </div>
            </div>

            <!-- Recent Uploads Table -->
            @if($recentUploads->isNotEmpty())
            <div style="background: {{ $bgTertiary }}; border: 1px solid {{ $borderColor }}; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 32px;">
                <div style="padding: 20px; border-bottom: 1px solid {{ $borderColor }};">
                    <h3 style="font-size: 16px; font-weight: 700; color: {{ $textPrimary }}; margin: 0;">📋 Últimos Uploads</h3>
                </div>

                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: {{ $bgSecondary }}; border-bottom: 1px solid {{ $borderColor }};">
                                <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: {{ $textSecondary }}; font-size: 12px;">Usuario</th>
                                <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: {{ $textSecondary }}; font-size: 12px;">Cloudflare ID</th>
                                <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: {{ $textSecondary }}; font-size: 12px;">Actualizado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentUploads as $upload)
                            <tr style="border-bottom: 1px solid {{ $borderColor }}; transition: all 0.2s ease;"
                                onmouseover="this.style.background='{{ $bgSecondary }}'"
                                onmouseout="this.style.background='transparent'">
                                <td style="padding: 12px 16px; color: {{ $textPrimary }};">
                                    <a href="{{ route('profile.show', $upload->id) }}" style="color: {{ $accentColor }}; text-decoration: none; font-weight: 500;">
                                        {{ $upload->name }}
                                    </a>
                                </td>
                                <td style="padding: 12px 16px; color: {{ $textSecondary }}; font-family: monospace; font-size: 11px;">
                                    {{ Str::limit($upload->avatar_cloudflare_id, 20) }}...
                                </td>
                                <td style="padding: 12px 16px; color: {{ $textSecondary }}; font-size: 12px;">
                                    {{ $upload->updated_at->diffForHumans() }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <!-- Configuration Info -->
            <div style="background: {{ $bgTertiary }}; border: 1px solid {{ $borderColor }}; border-radius: 12px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h3 style="font-size: 16px; font-weight: 700; color: {{ $textPrimary }}; margin: 0 0 16px 0;">⚙️ Configuración</h3>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px;">
                    <!-- Cloudflare Config -->
                    <div style="padding: 12px; background: {{ $bgSecondary }}; border-radius: 8px;">
                        <h4 style="font-size: 12px; color: {{ $textSecondary }}; margin: 0 0 8px 0; font-weight: 600;">Cloudflare Images</h4>
                        <ul style="margin: 0; padding: 0; list-style: none; font-size: 12px;">
                            <li style="color: {{ $textPrimary }}; margin-bottom: 4px;">
                                <strong>Enabled:</strong> 
                                <span style="color: {{ config('cloudflare.images.enabled') ? '#10b981' : '#ef4444' }};">
                                    {{ config('cloudflare.images.enabled') ? '✓ Yes' : '✗ No' }}
                                </span>
                            </li>
                            <li style="color: {{ $textPrimary }}; margin-bottom: 4px;">
                                <strong>Domain:</strong> <code style="color: {{ $accentColor }};">{{ config('cloudflare.images.domain') }}</code>
                            </li>
                            <li style="color: {{ $textPrimary }};">
                                <strong>Transforms:</strong> {{ count(config('cloudflare.transforms', [])) }} configurados
                            </li>
                        </ul>
                    </div>

                    <!-- Storage Config -->
                    <div style="padding: 12px; background: {{ $bgSecondary }}; border-radius: 8px;">
                        <h4 style="font-size: 12px; color: {{ $textSecondary }}; margin: 0 0 8px 0; font-weight: 600;">Almacenamiento Local</h4>
                        <ul style="margin: 0; padding: 0; list-style: none; font-size: 12px;">
                            <li style="color: {{ $textPrimary }}; margin-bottom: 4px;">
                                <strong>Disk:</strong> <code style="color: {{ $accentColor }};">{{ config('cloudflare.fallback_disk', 'public') }}</code>
                            </li>
                            <li style="color: {{ $textPrimary }}; margin-bottom: 4px;">
                                <strong>Fallback:</strong> ✓ Habilitado
                            </li>
                            <li style="color: {{ $textPrimary }};">
                                <strong>Lo local se usa cuando</strong> Cloudflare falla
                            </li>
                        </ul>
                    </div>

                    <!-- Upload Config -->
                    <div style="padding: 12px; background: {{ $bgSecondary }}; border-radius: 8px;">
                        <h4 style="font-size: 12px; color: {{ $textSecondary }}; margin: 0 0 8px 0; font-weight: 600;">Configuración de Upload</h4>
                        <ul style="margin: 0; padding: 0; list-style: none; font-size: 12px;">
                            <li style="color: {{ $textPrimary }}; margin-bottom: 4px;">
                                <strong>Max Size:</strong> {{ config('cloudflare.upload.max_size', 5242880) / 1024 / 1024 }}MB
                            </li>
                            <li style="color: {{ $textPrimary }}; margin-bottom: 4px;">
                                <strong>Retries:</strong> {{ config('cloudflare.upload.retries', 3) }}
                            </li>
                            <li style="color: {{ $textPrimary }};">
                                <strong>Timeout:</strong> {{ config('cloudflare.upload.timeout', 30) }}s
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-layout>
