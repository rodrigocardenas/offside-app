@php
    $themeMode = auth()->user()->theme_mode ?? 'light';
    $isDark = $themeMode === 'dark';
    $layout = $isDark ? 'mobile-dark-layout' : 'mobile-light-layout';
    $labelColor = $isDark ? '#ffffff' : '#333';
    $descColor = $isDark ? '#b0b0b0' : '#666';
@endphp

<x-dynamic-layout :layout="$layout">
    @push('scripts')
        <script src="{{ asset('js/common/navigation.js') }}"></script>
        <script src="{{ asset('js/common/modal-handler.js') }}"></script>
    @endpush

    <div class="main-container">
        {{-- HEADER --}}
        <x-layout.header-profile
            :logo-url="asset('images/logo_alone.png')"
            alt-text="Offside Club"
        />

        {{-- SUCCESS MESSAGE --}}
        @if(session('success'))
            @php
                $msgBgDark = $isDark ? 'background: rgba(40, 167, 69, 0.15); color: #5cdd6f;' : 'background: #d4edda; color: #155724;';
            @endphp
            <div style="{{ $msgBgDark }} border-left: 4px solid #28a745; padding: 12px 16px; margin: 16px; border-radius: 8px; font-size: 14px;">
                <i class="fas fa-check-circle" style="margin-right: 8px;"></i>
                {{ session('success') }}
            </div>
        @endif

        {{-- SETTINGS CONTENT --}}
        <div class="settings-content">
            {{-- SETTINGS TABS --}}
            <div class="settings-tabs">
                <button class="settings-tab-btn active" data-tab="appearance">
                    <i class="fas fa-palette"></i>
                    <span>{{ __('views.settings.appearance') }}</span>
                </button>
                <button class="settings-tab-btn" data-tab="notifications">
                    <i class="fas fa-bell"></i>
                    <span>{{ __('views.settings.notifications') }}</span>
                </button>
                <button class="settings-tab-btn" data-tab="privacy">
                    <i class="fas fa-lock"></i>
                    <span>{{ __('views.settings.privacy') }}</span>
                </button>
            </div>

            {{-- APPEARANCE PANEL --}}
            <div id="appearance" class="settings-panel active">
                <div class="settings-section">
                    <div class="section-title" style="color: {{ $labelColor }};">
                        <i class="fas fa-palette"></i>
                        {{ __('views.settings.appearance') }}
                    </div>
                    <p style="color: {{ $descColor }}; font-size: 14px; margin-bottom: 20px;">{{ __('views.settings.personalize') }}</p>

                    <form action="{{ route('settings.update') }}" method="POST" id="appearanceForm">
                        @csrf
                        @method('PUT')

                        <div class="theme-section">
                            <label style="display: block; font-weight: 600; color: {{ $labelColor }}; margin-bottom: 8px;">
                                {{ __('views.settings.theme_mode') }}
                            </label>
                            <p style="color: {{ $descColor }}; font-size: 13px; margin-bottom: 16px;">
                                {{ __('views.settings.theme_mode_desc') }}
                            </p>

                            <div class="theme-selector">
                                <label class="theme-option">
                                    <input
                                        type="radio"
                                        name="theme_mode"
                                        value="light"
                                        {{ (auth()->user()->theme_mode ?? 'light') === 'light' ? 'checked' : '' }}
                                        class="theme-radio"
                                    >
                                    <div class="theme-card light-card">
                                        <i class="fas fa-sun"></i>
                                        <span>{{ __('views.settings.light') }}</span>
                                    </div>
                                </label>

                                <label class="theme-option">
                                    <input
                                        type="radio"
                                        name="theme_mode"
                                        value="dark"
                                        {{ (auth()->user()->theme_mode ?? 'light') === 'dark' ? 'checked' : '' }}
                                        class="theme-radio"
                                    >
                                    <div class="theme-card dark-card">
                                        <i class="fas fa-moon"></i>
                                        <span>{{ __('views.settings.dark') }}</span>
                                    </div>
                                </label>

                                
                            </div>

                            @error('theme_mode')
                                <span style="color: #dc3545; font-size: 13px;">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="language-section" style="margin-top: 32px; padding-top: 24px; border-top: 1px solid {{ $isDark ? '#404040' : '#e0e0e0' }};">
                            <label style="display: block; font-weight: 600; color: {{ $labelColor }}; margin-bottom: 8px;">
                                <i class="fas fa-globe"></i>
                                {{ __('views.settings.language') }}
                            </label>
                            <p style="color: {{ $descColor }}; font-size: 13px; margin-bottom: 16px;">
                                {{ __('views.settings.language_desc') }}
                            </p>

                            <select name="language" class="language-select" style="padding: 10px 12px; border-radius: 8px; border: 1px solid {{ $isDark ? '#505050' : '#ddd' }}; background: {{ $isDark ? '#2a2a2a' : '#fff' }}; color: {{ $labelColor }}; font-size: 14px; width: 100%;">
                                <option value="es" {{ auth()->user()->language === 'es' || is_null(auth()->user()->language) ? 'selected' : '' }}>
                                    {{ __('messages.spanish') }}
                                </option>
                                <option value="en" {{ auth()->user()->language === 'en' ? 'selected' : '' }}>
                                    {{ __('messages.english') }}
                                </option>
                            </select>

                            @error('language')
                                <span style="color: #dc3545; font-size: 13px;">{{ $message }}</span>
                            @enderror
                        </div>

                        <button type="submit" class="btn-submit" style="margin-top: 20px;">
                            <i class="fas fa-save"></i>
                            {{ __('messages.save') }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- NOTIFICATIONS PANEL --}}
            <div id="notifications" class="settings-panel">
                <div class="settings-section">
                    <div class="section-title">
                        <i class="fas fa-bell"></i>
                        {{ __('views.settings.notifications') }}
                    </div>
                    <div class="coming-soon">
                        <i class="fas fa-rocket" style="font-size: 48px; color: #ccc;"></i>
                        <p style="color: #999; margin: 0;">{{ __('views.settings.coming_soon') }}</p>
                    </div>
                </div>
            </div>

            {{-- PRIVACY PANEL --}}
            <div id="privacy" class="settings-panel">
                <div class="settings-section">
                    <div class="section-title">
                        <i class="fas fa-lock"></i>
                        {{ __('views.settings.privacy') }}
                    </div>
                    <div class="coming-soon">
                        <i class="fas fa-rocket" style="font-size: 48px; color: #ccc;"></i>
                        <p style="color: #999; margin: 0;">{{ __('views.settings.coming_soon') }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- BOTTOM NAVIGATION --}}
        <x-layout.bottom-navigation active-item="settings" />
    </div>

    {{-- MODALES --}}
    @if(View::exists('components.feedback-modal'))
        <x-feedback-modal />
    @endif

    <style>
        /* Settings Content Layout */
        .settings-content {
            margin: 0;
            padding-bottom: 80px;
        }

        /* Section Title */
        .section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
            margin-top: 24px;
            margin-bottom: 16px;
            font-weight: 600;
            font-size: 16px;
        }

        /* Dark Mode - Section Title */
        .main-container .section-title {
            color: var(--dark-text-primary, #333);
        }

        /* Settings Tabs */
        .settings-tabs {
            display: flex;
            gap: 8px;
            padding: 16px;
            background: #fff;
            border-bottom: 1px solid #e0e0e0;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* Dark Mode - Settings Tabs */
        .main-container .settings-tabs {
            background: var(--dark-bg-secondary, #fff);
            border-bottom-color: var(--dark-border, #e0e0e0);
        }

        .settings-tab-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border: 1px solid #e0e0e0;
            background: #f5f5f5;
            border-radius: 20px;
            color: #666;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .settings-tab-btn:hover {
            border-color: #00deb0;
            background: #fff;
        }

        .settings-tab-btn.active {
            border-color: #00deb0;
            background: #e8f9f6;
            color: #003b2f;
        }

        /* Dark Mode - Settings Tab Buttons */
        .main-container .settings-tab-btn {
            border-color: var(--dark-border, #e0e0e0);
            background: var(--dark-bg-tertiary, #f5f5f5);
            color: var(--dark-text-secondary, #666);
        }

        .main-container .settings-tab-btn:hover {
            border-color: #00deb0;
            background: var(--dark-bg-secondary, #fff);
        }

        .main-container .settings-tab-btn.active {
            border-color: #00deb0;
            background: rgba(0, 222, 176, 0.15);
            color: var(--dark-accent, #003b2f);
        }

        .settings-tab-btn i {
            font-size: 14px;
        }

        /* Settings Section */
        .settings-section {
            background: #fff;
            margin: 16px;
            padding: 16px;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Dark Mode - Settings Section */
        .main-container .settings-section {
            background: var(--dark-bg-secondary, #fff);
            border-color: var(--dark-border, #e0e0e0);
            color: var(--dark-text-primary, #333);
        }

        .settings-panel {
            display: none;
        }

        .settings-panel.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Theme Section */
        .theme-section {
            margin-bottom: 16px;
        }

        /* Theme Selector */
        .theme-selector {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 16px;
        }

        .theme-option {
            cursor: pointer;
        }

        .theme-radio {
            display: none;
        }

        .theme-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 16px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            background: #fff;
            transition: all 0.3s ease;
            min-height: 110px;
        }

        .theme-card i {
            font-size: 24px;
            color: #666;
        }

        .theme-card span {
            font-weight: 500;
            font-size: 12px;
            color: #333;
            text-align: center;
        }

        /* Dark Mode - Theme Card */
        .main-container .theme-card {
            border-color: var(--dark-border, #e0e0e0);
            background: var(--dark-bg-tertiary, #fff);
            color: var(--dark-text-secondary, #333);
        }

        .main-container .theme-card i {
            color: var(--dark-text-secondary, #666);
        }

        .main-container .theme-card span {
            color: var(--dark-text-secondary, #333);
        }

        /* Light Theme Card */
        .light-card {
            background-color: #f9fafb;
            color: #1f2937;
        }

        .light-card i {
            color: #fbbf24;
        }

        /* Dark Theme Card */
        .dark-card {
            background-color: #1f2937;
            color: #f9fafb;
        }

        .dark-card i {
            color: #fbbf24;
        }

        /* Auto theme card removed */

        /* Selected State */
        .theme-radio:checked + .theme-card {
            border-color: #00deb0;
            border-width: 2px;
            box-shadow: 0 0 0 2px rgba(0, 222, 176, 0.2);
            background-color: rgba(0, 222, 176, 0.05);
        }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            padding: 12px 16px;
            background: linear-gradient(135deg, #17b796, #00deb0);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 222, 176, 0.3);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        /* Coming Soon */
        .coming-soon {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 16px;
            padding: 40px 20px;
            background: #f5f5f5;
            border-radius: 10px;
            text-align: center;
        }

        /* Dark Mode - Coming Soon */
        .main-container .coming-soon {
            background: var(--dark-bg-tertiary, #f5f5f5);
            color: var(--dark-text-secondary, #999);
        }

        .main-container .coming-soon i {
            color: var(--dark-border, #ccc);
        }

        /* Responsive */
        @media (max-width: 600px) {
            .settings-tabs {
                padding: 12px;
                gap: 6px;
            }

            .settings-tab-btn {
                padding: 6px 12px;
                font-size: 12px;
            }

            .settings-section {
                margin: 12px;
                padding: 14px;
            }

            .theme-selector {
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
            }

            .theme-card {
                padding: 12px 8px;
                min-height: 100px;
            }

            .theme-card i {
                font-size: 20px;
            }

            .theme-card span {
                font-size: 11px;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.settings-tab-btn');
            const panels = document.querySelectorAll('.settings-panel');

            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const tabName = this.getAttribute('data-tab');

                    // Remove active class from all buttons and panels
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    panels.forEach(panel => panel.classList.remove('active'));

                    // Add active class to clicked button and corresponding panel
                    this.classList.add('active');
                    document.getElementById(tabName).classList.add('active');
                });
            });
        });
    </script>
</x-dynamic-layout>
