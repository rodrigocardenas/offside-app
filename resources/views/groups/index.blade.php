@php
    $themeMode = auth()->user()->theme_mode ?? 'light';
    $isDark = $themeMode === 'dark';
    $layout = $isDark ? 'mobile-dark-layout' : 'mobile-light-layout';
@endphp

<x-dynamic-layout :layout="$layout">
    @push('scripts')
        <script src="{{ asset('js/common/navigation.js') }}"></script>
        <script src="{{ asset('js/common/hover-effects.js') }}"></script>
        <script src="{{ asset('js/common/modal-handler.js') }}"></script>
        <script src="{{ asset('js/groups/group-selection.js') }}"></script>
        <script src="{{ asset('js/groups/notification-checker.js') }}"></script>
    @endpush

    @section('navigation-title', 'Offside Club')

    <div class="main-container">
        {{-- 1. HEADER CON LOGO --}}
        <x-layout.header-profile
            :logo-url="asset('images/logo_alone.png')"
            alt-text="Offside Club"
        />

        {{-- 1.5 MENSAJES DE SESIÓN --}}
        @php
            $themeMode = auth()->user()->theme_mode ?? 'light';
            $isDarkMsg = $themeMode === 'dark';
            $msgBg = $isDarkMsg ? '#1a524e' : '#d4edda';
            $msgBgError = $isDarkMsg ? '#522a2a' : '#f8d7da';
            $msgText = $isDarkMsg ? '#00deb0' : '#155724';
            $msgTextError = $isDarkMsg ? '#f8d7da' : '#721c24';
            $msgBorder = $isDarkMsg ? '#00deb0' : '#c3e6cb';
            $msgBorderError = $isDarkMsg ? '#f8d7da' : '#f5c6cb';
        @endphp

        @if ($message = session('success'))
            <div style="margin: 16px 16px 0 16px; padding: 16px; background: {{ $msgBg }}; border: 1px solid {{ $msgBorder }}; border-radius: 8px; color: {{ $msgText }}; font-size: 14px; display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-check-circle" style="font-size: 18px; flex-shrink: 0;"></i>
                <span>{{ $message }}</span>
                <button type="button" onclick="this.parentElement.style.display='none'" style="margin-left: auto; background: none; border: none; color: inherit; cursor: pointer; font-size: 16px; padding: 0;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif

        @if ($message = session('error'))
            <div style="margin: 16px 16px 0 16px; padding: 16px; background: {{ $msgBgError }}; border: 1px solid {{ $msgBorderError }}; border-radius: 8px; color: {{ $msgTextError }}; font-size: 14px; display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-exclamation-circle" style="font-size: 18px; flex-shrink: 0;"></i>
                <span>{{ $message }}</span>
                <button type="button" onclick="this.parentElement.style.display='none'" style="margin-left: auto; background: none; border: none; color: inherit; cursor: pointer; font-size: 16px; padding: 0;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif

        {{-- 2. BARRA DE ESTADÍSTICAS --}}
        <x-groups.stats-bar
            :streak="$userStreak"
            :accuracy="$userAccuracy"
            :groups-count="$totalGroups"
        />

        {{-- 3. BANNER DE NOTIFICACIONES --}}
        <x-common.notification-banner
            :show="$hasPendingPredictions"
            message="{{ __('views.groups.pending_predictions') }}"
            type="warning"
        />

        {{-- 4. PARTIDO DESTACADO --}}
        @if($featuredMatch)
            <x-matches.featured-match
                :match="$featuredMatch"
                title="{{ __('views.groups.featured_match') }}"
            />
        @endif

        {{-- 5. SECCIÓN DE GRUPOS --}}
        <div class="groups-section">
            <div class="section-title">
                <i class="fas fa-users"></i> {{ __('views.groups.my_groups') }}
            </div>

            {{-- Official Groups --}}
            @foreach($officialGroups as $group)
                <x-groups.group-card
                    :group="$group"
                    :user-rank="$group->userRank"
                    :has-pending="$group->pending"
                    :show-members="true"
                />
            @endforeach

            {{-- Amateur Groups --}}
            @foreach($amateurGroups as $group)
                <x-groups.group-card
                    :group="$group"
                    :user-rank="$group->userRank"
                    :has-pending="$group->pending"
                    :show-members="true"
                />
            @endforeach

            {{-- Empty State --}}
            @if($officialGroups->isEmpty() && $amateurGroups->isEmpty())
                <div style="text-align: center; padding: 40px 20px; color: #999;">
                    <i class="fas fa-users" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                    <p style="font-size: 16px; margin-bottom: 8px;">{{ __('views.groups.no_groups') }}</p>
                    <p style="font-size: 14px;">{{ __('messages.search') }} o crea uno nuevo</p>
                </div>
            @endif
        </div>

        {{-- 5.5 BOTONES DE ACCIÓN --}}
        @php
            $themeMode = auth()->user()->theme_mode ?? 'light';
            $isDarkMode = $themeMode === 'dark';
            $bgColor = $isDarkMode ? '#1a524e' : '#f5f5f5';
            $textColor = $isDarkMode ? '#ffffff' : '#333333';
            $secondaryTextColor = $isDarkMode ? '#b0b0b0' : '#999999';
            $accentColor = '#00deb0';
            $accentDark = '#17b796';
        @endphp

        <div style="margin-top: 32px; margin-left:15px; margin-right:15px; margin-bottom: 15px; padding: 20px; background: {{ $bgColor }}; border-radius: 12px; display: flex; flex-direction: column; gap: 12px;">
            {{-- Botón Crear Grupo --}}
            <a href="{{ route('groups.create') }}"
               style="display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 14px 20px; background: linear-gradient(135deg, {{ $accentDark }}, {{ $accentColor }}); border: none; border-radius: 8px; color: white; font-weight: 600; text-decoration: none; cursor: pointer; transition: all 0.3s ease; font-size: 15px;"
               onmouseover="this.style.opacity='0.9'; this.style.transform='scale(1.02)'"
               onmouseout="this.style.opacity='1'; this.style.transform='scale(1)'">
                <i class="fas fa-plus"></i>
                {{ __('views.groups.create') }}
            </a>

            {{-- Divider --}}
            <div style="display: flex; align-items: center; gap: 10px; margin: 4px 0;">
                <div style="flex: 1; height: 1px; background: {{ $isDarkMode ? 'rgba(255,255,255,0.1)' : '#e0e0e0' }};"></div>
                <span style="color: {{ $secondaryTextColor }}; font-size: 12px; font-weight: 500;">O</span>
                <div style="flex: 1; height: 1px; background: {{ $isDarkMode ? 'rgba(255,255,255,0.1)' : '#e0e0e0' }};"></div>
            </div>

            {{-- Botón Unirse a Grupo --}}
            <button onclick="document.getElementById('joinGroupModal').style.display = 'flex'"
                    style="display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 14px 20px; background: transparent; border: 2px solid {{ $accentColor }}; border-radius: 8px; color: {{ $accentColor }}; font-weight: 600; cursor: pointer; transition: all 0.3s ease; font-size: 15px;"
                    onmouseover="this.style.background='{{ $isDarkMode ? 'rgba(0, 222, 176, 0.1)' : 'rgba(0, 222, 176, 0.05)' }}';"
                    onmouseout="this.style.background='transparent';">
                <i class="fas fa-sign-in-alt"></i>
                {{ __('views.groups.join_group') }}
            </button>
        </div>

        {{-- 6. NAVEGACIÓN INFERIOR --}}
        <x-layout.bottom-navigation active-item="grupo" />
    </div>

    {{-- MODALES --}}
    @if(View::exists('components.feedback-modal'))
        <x-feedback-modal />
    @endif

    {{-- WELCOME WIZARD --}}
    <x-wizard-modal />

    {{-- JOIN GROUP MODAL --}}
    @php
        $themeMode = auth()->user()->theme_mode ?? 'light';
        $isDarkModal = $themeMode === 'dark';
        $modalBg = $isDarkModal ? '#10302d' : '#ffffff';
        $modalText = $isDarkModal ? '#f1fff8' : '#333333';
        $modalBorder = $isDarkModal ? '#1d4f4a' : '#e0e0e0';
        $modalLabel = $isDarkModal ? '#8de5d5' : '#333333';
        $modalInputBg = $isDarkModal ? '#08201d' : '#ffffff';
        $modalSecondaryBg = $isDarkModal ? 'rgba(255,255,255,0.08)' : '#f5f5f5';
        $modalSecondaryHover = $isDarkModal ? 'rgba(255,255,255,0.18)' : '#e8e8e8';
        $modalHint = $isDarkModal ? '#9bcfcc' : '#999999';
        $modalCloseColor = $isDarkModal ? '#d5fdf0' : '#999999';
        $modalFocusGlow = $isDarkModal ? '0 0 0 3px rgba(0, 222, 176, 0.25)' : '0 0 0 3px rgba(0, 222, 176, 0.1)';
        $modalSurfaceShadow = $isDarkModal ? '0 14px 40px rgba(0, 0, 0, 0.55)' : '0 10px 40px rgba(0, 0, 0, 0.2)';
    @endphp

    <div id="joinGroupModal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); display: none; align-items: center; justify-content: center; z-index: 9999; padding: 20px;">
        <div style="background: {{ $modalBg }}; border: 1px solid {{ $modalBorder }}; border-radius: 16px; width: 100%; max-width: 420px; padding: 28px 24px; box-shadow: {{ $modalSurfaceShadow }}; color: {{ $modalText }};">

            {{-- Header --}}
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
                <h2 style="font-size: 24px; font-weight: 700; color: {{ $modalText }}; margin: 0;">{{ __('views.settings.join_group_title') }}</h2>
                <button onclick="document.getElementById('joinGroupModal').style.display = 'none'" style="background: none; border: none; font-size: 24px; color: {{ $modalCloseColor }}; cursor: pointer; padding: 0; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Form --}}
            <form action="{{ route('groups.join') }}" method="POST" style="display: flex; flex-direction: column; gap: 20px;">
                @csrf

                <div>
                    <label for="joinCode" style="display: block; font-size: 14px; font-weight: 600; color: {{ $modalLabel }}; margin-bottom: 8px;">{{ __('views.settings.group_code') }}</label>
                    <input id="joinCode" type="text" name="code" required
                        style="width: 100%; background: {{ $modalInputBg }}; border: 1px solid {{ $modalBorder }}; border-radius: 8px; padding: 12px 16px; color: {{ $modalText }}; font-size: 15px; transition: all 0.3s ease; box-sizing: border-box;"
                        onfocus="this.style.borderColor='#00deb0'; this.style.boxShadow='{{ $modalFocusGlow }}'"
                        onblur="this.style.borderColor='{{ $modalBorder }}'; this.style.boxShadow='none'"
                        placeholder="{{ __('views.settings.group_code_placeholder') }}" />
                </div>

                {{-- Botones --}}
                <div style="display: flex; gap: 12px; margin-top: 8px;">
                        <button type="button" onclick="document.getElementById('joinGroupModal').style.display = 'none'"
                            style="flex: 1; padding: 12px 16px; background: {{ $modalSecondaryBg }}; border: none; border-radius: 8px; color: {{ $modalText }}; font-weight: 600; cursor: pointer; transition: all 0.3s ease;"
                            onmouseover="this.style.background='{{ $modalSecondaryHover }}'"
                            onmouseout="this.style.background='{{ $modalSecondaryBg }}'">
                        {{ __('views.settings.cancel') }}
                    </button>
                    <button type="submit"
                            style="flex: 1; padding: 12px 16px; background: linear-gradient(135deg, #17b796, #00deb0); border: none; border-radius: 8px; color: white; font-weight: 600; cursor: pointer; transition: all 0.3s ease;"
                            onmouseover="this.style.opacity='0.9'"
                            onmouseout="this.style.opacity='1'">
                        {{ __('views.settings.join') }}
                    </button>
                </div>
            </form>

            {{-- Info text --}}
            <p style="font-size: 12px; color: {{ $modalHint }}; margin-top: 16px; text-align: center; margin-bottom: 0;">
                Pídele el código a alguien del grupo
            </p>
        </div>
    </div>

    {{-- INVITE MODAL --}}
    @php
        $inviteModalBg = $modalBg;
        $inviteModalText = $modalText;
        $inviteModalBorder = $modalBorder;
        $inviteTextarea = $isDarkModal ? 'rgba(255,255,255,0.05)' : '#f5f5f5';
        $inviteModalShadow = $modalSurfaceShadow;
        $inviteCloseColor = $modalCloseColor;
    @endphp

    <div id="inviteModal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); display: none; align-items: center; justify-content: center; z-index: 9999; padding: 20px;">
        <div style="background: {{ $inviteModalBg }}; border: 1px solid {{ $inviteModalBorder }}; border-radius: 16px; width: 100%; max-width: 420px; padding: 28px 24px; box-shadow: {{ $inviteModalShadow }}; color: {{ $inviteModalText }};">

            {{-- Header --}}
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
                <h2 style="font-size: 24px; font-weight: 700; color: {{ $inviteModalText }}; margin: 0;">{{ __('views.groups.share_group') }}</h2>
                <button onclick="document.getElementById('inviteModal').style.display = 'none'" style="background: none; border: none; font-size: 24px; color: {{ $inviteCloseColor }}; cursor: pointer; padding: 0; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Contenido --}}
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <label for="inviteMessage" style="display: block; font-size: 14px; font-weight: 600; color: {{ $inviteModalText }}; margin-bottom: 8px;">{{ __('views.groups.invitation_message') }}</label>
                    <textarea id="inviteMessage" rows="4" readonly
                        style="width: 100%; background: {{ $inviteTextarea }}; border: 1px solid {{ $inviteModalBorder }}; border-radius: 8px; padding: 12px 16px; color: {{ $inviteModalText }}; font-size: 14px; font-family: 'Courier New', monospace; resize: none; box-sizing: border-box;">
                    </textarea>
                </div>

                {{-- Botones --}}
                <div style="display: flex; gap: 12px; margin-top: 8px;">
                    <button type="button" onclick="copyInviteText()"
                            style="flex: 1; padding: 12px 16px; background: #17b796; border: none; border-radius: 8px; color: white; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px;"
                            onmouseover="this.style.background='#00deb0'"
                            onmouseout="this.style.background='#17b796'">
                        <i class="fas fa-copy"></i>
                        <span>{{ __('views.groups.copy') }}</span>
                    </button>
                    <button type="button" onclick="shareOnWhatsApp()"
                            style="flex: 1; padding: 12px 16px; background: #25D366; border: none; border-radius: 8px; color: white; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px;"
                            onmouseover="this.style.background='#20ba5a'"
                            onmouseout="this.style.background='#25D366'">
                        <i class="fab fa-whatsapp"></i>
                        <span>{{ __('views.groups.whatsapp') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Cerrar modal al hacer click fuera
        document.getElementById('joinGroupModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });

        document.getElementById('inviteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });

        // Función para mostrar modal de invitación
        window.showInviteModal = function(groupName, inviteUrl) {
            const modal = document.getElementById('inviteModal');
            const messageArea = document.getElementById('inviteMessage');
            const message = `¡Únete al grupo "${groupName}" en Offside Club!\n\n${inviteUrl}\n\n¡Ven a competir con nosotros!`;
            messageArea.value = message;
            modal.style.display = 'flex';
        };

        // Función para copiar mensaje
        window.copyInviteText = function() {
            const messageArea = document.getElementById('inviteMessage');
            const text = messageArea.value;

            // Usar Clipboard API si está disponible
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(() => {
                    showCopyFeedback();
                }).catch(() => {
                    // Fallback
                    copyToClipboardFallback(text);
                });
            } else {
                copyToClipboardFallback(text);
            }
        };

        // Fallback para copiar
        function copyToClipboardFallback(text) {
            try {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                showCopyFeedback();
            } catch (err) {
                console.error('Error al copiar:', err);
            }
        }

        // Feedback visual
        function showCopyFeedback() {
            const button = event.target.closest('button') || event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i> <span>¡Copiado!</span>';
            button.style.background = '#00c800';
            button.disabled = true;

            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.background = '';
                button.disabled = false;
            }, 2000);
        }

        // Función para compartir en WhatsApp
        window.shareOnWhatsApp = function() {
            const messageArea = document.getElementById('inviteMessage');
            const text = messageArea.value;
            const encodedMessage = encodeURIComponent(text);
            const whatsappUrl = `https://wa.me/?text=${encodedMessage}`;
            window.open(whatsappUrl, '_blank', 'noopener,noreferrer');
        };
    </script>

</x-dynamic-layout>
