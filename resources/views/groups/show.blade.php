<x-app-layout
    :logo-url="asset('images/logo_alone.png')"
    alt-text="Offside Club"
>
    {{-- setear en el navigation el yield navigation-title: --}}
    @section('navigation-title', $group->name)
    @if ($group->id == 69)
        @section('navigation-logo', asset("images/competitions/".$group->competition?->crest_url))
    @endif

    @php
        $themeMode = auth()->user()->theme_mode ?? 'light';
        $isDark = $themeMode === 'dark';

        // Colores dinámicos
        $bgPrimary = $isDark ? '#0a2e2c' : '#f5f5f5';
        $bgSecondary = $isDark ? '#0f3d3a' : '#f5f5f5';
        $bgTertiary = $isDark ? '#1a524e' : '#ffffff';
        $textPrimary = $isDark ? '#ffffff' : '#333333';
        $textSecondary = $isDark ? '#b0b0b0' : '#999999';
        $borderColor = $isDark ? '#2a4a47' : '#e0e0e0';
        $accentColor = '#00deb0';
        $accentDark = '#17b796';
        $componentsBackground = $isDark ? '#1a524e' : '#ffffff';
        $buttonBgHover = $isDark ? 'rgba(0, 222, 176, 0.12)' : 'rgba(0, 222, 176, 0.08)';

        // Theme variables for share modal
        $shareModalBg = $isDark ? '#10302d' : '#ffffff';
        $shareModalText = $isDark ? '#f1fff8' : '#333333';
        $shareModalBorder = $isDark ? '#1d4f4a' : '#e0e0e0';
        $shareTextareaBg = $isDark ? 'rgba(255,255,255,0.05)' : '#f5f5f5';
        $shareModalShadow = $isDark ? '0 14px 40px rgba(0, 0, 0, 0.55)' : '0 10px 40px rgba(0, 0, 0, 0.2)';
        $shareCloseColor = $isDark ? '#d5fdf0' : '#999999';

        $topUsers = $group->users->sortByDesc('total_points')->take(3)->values();
    @endphp

    <div class="min-h-screen p-1 md:p-6 pb-24" style="background: {{ $bgPrimary }}; color: {{ $textPrimary }}; margin-top: 3.75rem;">

        <!-- Share Group Button -->
        <div class="flex justify-end px-1 mb-4" style="margin-top: 12px;">
            <button type="button"
                    onclick="showInviteModal(@js($group->name), @js(route('groups.invite', $group->code)))"
                    style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border: none; border-radius: 999px; background: linear-gradient(135deg, #17b796, #00deb0); color: #003b2f; font-size: 13px; font-weight: 700; cursor: pointer; box-shadow: 0 10px 20px rgba(0, 222, 176, 0.25); transition: transform 0.2s ease;"
                    onmouseover="this.style.transform='translateY(-2px)'"
                    onmouseout="this.style.transform='translateY(0)';">
                <i class="fas fa-paper-plane"></i>
                <span>{{ __('views.groups.share_group') }}</span>
            </button>
        </div>

        <!-- Ranking Section -->
        <div class="ml-1 mr-1" style="background: {{ $bgTertiary }}; padding: 5px; border-radius: 16px; border: 1px solid {{ $borderColor }}; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 16px;">
            <div style="display: flex; align-items: center; justify-content: flex-start; gap: 8px; margin-bottom: 2px; font-size: 16px; font-weight: 600; color: {{ $textPrimary }}; padding: 8px;">
                {{ __('views.rankings.title') }}
                <a href="{{ url('/groups', $group->id) }}/ranking" style="margin-left: auto; font-size: 12px; color: {{ $textSecondary }}; cursor: pointer; padding: 4px 8px; border-radius: 12px; background: {{ $bgSecondary }}; border: 1px solid {{ $borderColor }}; transition: all 0.2s ease;"
                    onmouseover="this.style.background='{{ $isDark ? '#2a4a47' : '#f0f0f0' }}'; this.style.color='{{ $textPrimary }}';"
                    onmouseout="this.style.background='{{ $bgSecondary }}'; this.style.color='{{ $textSecondary }}';">
                    {{ __('messages.more') }}
                </a>
            </div>

            @if($topUsers->count() >= 1)
            <div class="podium-container">
              @if($topUsers->count() >= 2)
              <!-- Second Place -->
              <div class="podium-position second">
                <div class="avatar-section">
                  <div class="avatar-container">
                    @if($topUsers[1]->avatar)
                      <img src="{{ $topUsers[1]->avatar_url }}" alt="{{ $topUsers[1]->name }}" class="podium-avatar">
                    @else
                      <div class="podium-avatar-placeholder">{{ substr($topUsers[1]->name, 0, 1) }}</div>
                    @endif
                  </div>
                  <div class="podium-points">{{ number_format($topUsers[1]->total_points ?? 0, 0, ',', '.') }} pts.</div>
                </div>
                <div class="podium-step">
                </div>
              </div>
              @endif
              <!-- First Place -->
              <div class="podium-position first">
                <div class="avatar-section">
                  <div class="avatar-container">
                    @if($topUsers[0]->avatar)
                      <img src="{{ $topUsers[0]->avatar_url }}" alt="{{ $topUsers[0]->name }}" class="podium-avatar">
                    @else
                      <div class="podium-avatar-placeholder">{{ substr($topUsers[0]->name, 0, 1) }}</div>
                    @endif
                  </div>
                  <div class="podium-points">{{ number_format($topUsers[0]->total_points ?? 0, 0, ',', '.') }} pts.</div>
                </div>
                <div class="podium-step highest">
                </div>
              </div>
              @if($topUsers->count() >= 3)
              <!-- Third Place -->
              <div class="podium-position third">
                <div class="avatar-section">
                  <div class="avatar-container">
                    @if($topUsers[2]->avatar)
                      <img src="{{ $topUsers[2]->avatar_url }}" alt="{{ $topUsers[2]->name }}" class="podium-avatar">
                    @else
                      <div class="podium-avatar-placeholder">{{ substr($topUsers[2]->name, 0, 1) }}</div>
                    @endif
                  </div>
                  <div class="podium-points">{{ number_format($topUsers[2]->total_points ?? 0, 0, ',', '.') }} pts.</div>
                </div>
                <div class="podium-step">
                </div>
              </div>
              @endif
            </div>
            @else
            <div style="color: {{ $textSecondary }}; font-size: 14px; text-align: center; width: 100%; padding: 20px;">
              {{ __('views.rankings.no_players') }}
            </div>
            @endif
        </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div>

                    <!-- Preguntas de Partidos -->
                    <x-groups.group-match-questions :match-questions="$matchQuestions" :user-answers="$userAnswers" :current-matchday="$currentMatchday" :group="$group" :social-question="$socialQuestion ?? null" :theme-colors="compact('isDark', 'bgPrimary', 'bgSecondary', 'bgTertiary', 'textPrimary', 'textSecondary', 'borderColor', 'accentColor', 'accentDark', 'componentsBackground', 'buttonBgHover')" />

                    <!-- Pregunta Social -->
                    {{-- @if($group->users->count() >= 2)
                        @if($socialQuestion)
                            <x-groups.group-social-question :social-question="$socialQuestion" :user-answers="$userAnswers" :theme-colors="compact('isDark', 'bgPrimary', 'bgSecondary', 'bgTertiary', 'textPrimary', 'textSecondary', 'borderColor', 'accentColor', 'accentDark', 'componentsBackground', 'buttonBgHover')" />
                        @endif
                    @else
                        <x-groups.group-social-invite :group="$group" :theme-colors="compact('isDark', 'bgPrimary', 'bgSecondary', 'bgTertiary', 'textPrimary', 'textSecondary', 'borderColor', 'accentColor', 'accentDark', 'componentsBackground', 'buttonBgHover')" />
                    @endif --}}
                </div>

                <!-- Chat del Grupo -->
                <x-groups.group-chat :group="$group" :theme-colors="compact('isDark', 'bgPrimary', 'bgSecondary', 'bgTertiary', 'textPrimary', 'textSecondary', 'borderColor', 'accentColor', 'accentDark', 'componentsBackground', 'buttonBgHover')" />
            </div>
        </div>

        <!-- Menú inferior fijo -->
        <x-layout.bottom-navigation active-item="grupo" />
         <!-- Botón flotante del chat -->
    @if (request()->route()->getName() !== 'groups.predictive-results')
        <button id="chatToggle" style="position: fixed; bottom: 6rem; right: 2rem; background: {{ $accentColor }}; color: #000; border-radius: 50%; padding: 1rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3); transition: all 0.3s; display: flex; align-items: center; justify-content: center; z-index: 50; border: none; cursor: pointer;" class="hover:opacity-90">
            <svg xmlns="http://www.w3.org/2000/svg" style="height: 24px; width: 24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <span id="unreadCount" style="position: absolute; top: -4px; right: -4px; background: #ef4444; color: white; font-size: 0.75rem; border-radius: 50%; height: 20px; width: 20px; display: flex; align-items: center; justify-content: center;">
                {{ $group->chatMessages()->count() }}
            </span>
        </button>
    @endif

    </div>

    <!-- Modal de Feedback -->
    <div id="feedbackModal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); display: none; align-items: center; justify-content: center; z-index: 9999; padding: 20px;">
        <div style="background: {{ $bgTertiary }}; border-radius: 16px; width: 100%; max-width: 480px; padding: 28px 24px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2); border: 1px solid {{ $borderColor }};">

            {{-- Header --}}
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
                <h2 style="font-size: 22px; font-weight: 700; color: {{ $textPrimary }}; margin: 0; display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-comments" style="color: {{ $accentColor }};"></i>
                    Envíanos tu opinión
                </h2>
                <button id="closeFeedbackModal" style="background: none; border: none; font-size: 24px; color: {{ $textSecondary }}; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 6px; transition: all 0.2s ease;"
                    onmouseover="this.style.background='{{ $isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.05)' }}'; this.style.color='{{ $textPrimary }}';"
                    onmouseout="this.style.background='none'; this.style.color='{{ $textSecondary }}';">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Descripción --}}
            <p style="color: {{ $textSecondary }}; font-size: 14px; margin-bottom: 20px; line-height: 1.5;">
                {{ __('views.feedback.description') }}
            </p>

            {{-- Formulario --}}
            <form id="feedbackForm" style="display: flex; flex-direction: column; gap: 16px;">
                @csrf

                {{-- Tipo de comentario --}}
                <div>
                    <label for="type" style="display: block; font-size: 14px; font-weight: 600; color: {{ $textPrimary }}; margin-bottom: 8px;">{{ __('views.feedback.type_label') }}</label>
                    <select id="type" name="type" style="width: 100%; background: {{ $bgSecondary }}; border: 1px solid {{ $borderColor }}; border-radius: 10px; padding: 12px 16px; color: {{ $textPrimary }}; font-size: 14px; cursor: pointer; transition: all 0.3s ease; box-sizing: border-box;"
                        onfocus="this.style.borderColor='{{ $accentColor }}'; this.style.boxShadow='0 0 0 3px {{ $isDark ? 'rgba(0, 222, 176, 0.1)' : 'rgba(0, 222, 176, 0.08)' }}';"
                        onblur="this.style.borderColor='{{ $borderColor }}'; this.style.boxShadow='none';">
                        <option value="suggestion" style="background: {{ $bgSecondary }}; color: {{ $textPrimary }};">{{ __('views.feedback.suggestion') }}</option>
                        <option value="bug" style="background: {{ $bgSecondary }}; color: {{ $textPrimary }};">{{ __('views.feedback.bug') }}</option>
                        <option value="compliment" style="background: {{ $bgSecondary }}; color: {{ $textPrimary }};">{{ __('views.feedback.compliment') }}</option>
                        <option value="other" style="background: {{ $bgSecondary }}; color: {{ $textPrimary }};">{{ __('views.feedback.other') }}</option>
                    </select>
                </div>

                {{-- Mensaje --}}
                <div>
                    <label for="message" style="display: block; font-size: 14px; font-weight: 600; color: {{ $textPrimary }}; margin-bottom: 8px;">{{ __('views.feedback.message_label') }}</label>
                    <textarea id="message" name="message" rows="4" required placeholder="{{ __('views.feedback.message_placeholder') }}"
                        style="width: 100%; background: {{ $bgSecondary }}; border: 1px solid {{ $borderColor }}; border-radius: 10px; padding: 12px 16px; color: {{ $textPrimary }}; font-size: 14px; font-family: inherit; resize: vertical; box-sizing: border-box; transition: all 0.3s ease;"
                        onfocus="this.style.borderColor='{{ $accentColor }}'; this.style.boxShadow='0 0 0 3px {{ $isDark ? 'rgba(0, 222, 176, 0.1)' : 'rgba(0, 222, 176, 0.08)' }}';"
                        onblur="this.style.borderColor='{{ $borderColor }}'; this.style.boxShadow='none';"></textarea>
                </div>

                {{-- Opción anónima --}}
                <div style="display: flex; align-items: center; gap: 10px; padding: 12px; background: {{ $bgSecondary }}; border-radius: 8px; border: 1px solid {{ $borderColor }};">
                    <input type="checkbox" id="is_anonymous" name="is_anonymous" style="width: 18px; height: 18px; cursor: pointer; accent-color: {{ $accentColor }};">
                    <label for="is_anonymous" style="font-size: 14px; color: {{ $textPrimary }}; cursor: pointer; margin: 0; flex: 1;">
                        <i class="fas fa-mask" style="margin-right: 6px; color: {{ $accentColor }};"></i> {{ __('views.feedback.anonymous') }}
                    </label>
                </div>

                {{-- Botones --}}
                <div style="display: flex; gap: 12px; margin-top: 8px;">
                    <button type="button" id="cancelFeedback" style="flex: 1; padding: 12px 16px; background: {{ $bgSecondary }}; border: 1px solid {{ $borderColor }}; border-radius: 10px; color: {{ $textPrimary }}; font-weight: 600; cursor: pointer; transition: all 0.2s ease; font-size: 15px;"
                        onmouseover="this.style.background='{{ $isDark ? '#1a524e' : '#f0f0f0' }}';"
                        onmouseout="this.style.background='{{ $bgSecondary }}';">
                        {{ __('views.settings.cancel') }}
                    </button>
                    <button type="submit" style="flex: 1; padding: 12px 16px; background: linear-gradient(135deg, {{ $accentDark }}, {{ $accentColor }}); border: none; border-radius: 10px; color: #000; font-weight: 600; cursor: pointer; transition: all 0.2s ease; font-size: 15px; display: flex; align-items: center; justify-content: center; gap: 8px;"
                        onmouseover="this.style.opacity='0.9'; this.style.transform='translateY(-1px)';"
                        onmouseout="this.style.opacity='1'; this.style.transform='translateY(0)';">
                        <i class="fas fa-paper-plane"></i> {{ __('views.feedback.submit') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Invite Modal -->
    <div id="inviteModal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); display: none; align-items: center; justify-content: center; z-index: 9999; padding: 20px;">
        <div style="background: {{ $shareModalBg }}; border: 1px solid {{ $shareModalBorder }}; border-radius: 16px; width: 100%; max-width: 420px; padding: 28px 24px; box-shadow: {{ $shareModalShadow }}; color: {{ $shareModalText }};">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
                <h2 style="font-size: 24px; font-weight: 700; color: {{ $shareModalText }}; margin: 0;">{{ __('views.groups.share_group') }}</h2>
                <button onclick="document.getElementById('inviteModal').style.display = 'none'" style="background: none; border: none; font-size: 24px; color: {{ $shareCloseColor }}; cursor: pointer; padding: 0; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <label for="inviteMessage" style="display: block; font-size: 14px; font-weight: 600; color: {{ $shareModalText }}; margin-bottom: 8px;">{{ __('views.groups.invitation_message') }}</label>
                    <textarea id="inviteMessage" rows="4" readonly
                              style="width: 100%; background: {{ $shareTextareaBg }}; border: 1px solid {{ $shareModalBorder }}; border-radius: 8px; padding: 12px 16px; color: {{ $shareModalText }}; font-size: 14px; font-family: 'Courier New', monospace; resize: none; box-sizing: border-box;"></textarea>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 8px;">
                    <button type="button" onclick="copyInviteText(this)"
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
        (function() {
            if (window.__groupShareModalFromShowInit) {
                return;
            }
            window.__groupShareModalFromShowInit = true;

            function getInviteModal() {
                return document.getElementById('inviteModal');
            }

            function getInviteMessageField() {
                return document.getElementById('inviteMessage');
            }

            document.addEventListener('DOMContentLoaded', function() {
                const modal = getInviteModal();
                if (!modal) {
                    return;
                }
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        modal.style.display = 'none';
                    }
                });
            });

            window.showInviteModal = function(groupName, inviteUrl) {
                const modal = getInviteModal();
                const messageArea = getInviteMessageField();
                if (!modal || !messageArea) {
                    return;
                }
                
                // Extraer el código del grupo de la URL
                const code = inviteUrl.split('/').pop();
                
                // Crear URL clickeable: inviteUrl corta
                // WhatsApp la reconocerá como URL válida y la hará clickeable
                const inviteUrlShort = window.location.origin + '/invite/' + code;
                const inviteUrlFull = window.location.origin + '/groups/invite/' + code;
                
                // Mensaje con URL HTTPS clickeable en WhatsApp
                const message = `¡Únete al grupo "${groupName}" en Offside Club!\n\n${inviteUrlShort}\n\n¡Ven a competir con nosotros!`;
                messageArea.value = message;
                
                // Guardar URLs para luego usarlas
                messageArea.dataset.inviteUrl = inviteUrlShort;
                messageArea.dataset.inviteUrlFull = inviteUrlFull;
                messageArea.dataset.code = code;
                
                modal.style.display = 'flex';
            };

            window.copyInviteText = function(button) {
                const messageArea = getInviteMessageField();
                if (!messageArea) {
                    return;
                }
                const text = messageArea.value;

                const onSuccess = () => showCopyFeedback(button);

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(onSuccess).catch(() => {
                        if (copyToClipboardFallback(text)) {
                            onSuccess();
                        }
                    });
                } else {
                    if (copyToClipboardFallback(text)) {
                        onSuccess();
                    }
                }
            };

            window.shareOnWhatsApp = function() {
                const messageArea = getInviteMessageField();
                if (!messageArea) {
                    return;
                }
                
                const text = messageArea.value;
                
                // Enviar mensaje con URL HTTPS clickeable
                // WhatsApp reconocerá la URL y la hará clickeable automáticamente
                const encodedMessage = encodeURIComponent(text);
                const whatsappUrl = `https://wa.me/?text=${encodedMessage}`;
                window.open(whatsappUrl, '_blank', 'noopener,noreferrer');
            };

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
                    return true;
                } catch (err) {
                    console.error('Error al copiar:', err);
                    return false;
                }
            }

            function showCopyFeedback(button) {
                if (!button) {
                    return;
                }
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i><span>Copiado!</span>';
                button.style.background = '#28a745';
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.background = '#17b796';
                }, 2000);
            }
        })();
    </script>




</x-app-layout>
<style>
    .hide-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .hide-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
    .question-indicator.active {
        background-color: theme('colors.offside-secondary');
    }

    .podium-container {
      display: flex;
      justify-content: center;
      align-items: end;
      gap: 16px;
      padding: 20px 16px;
      min-height: 200px;
    }

    .podium-position {
      display: flex;
      flex-direction: column;
      align-items: center;
      position: relative;
    }

    .podium-step {
      background: {{ $bgSecondary }};
      border: 2px solid {{ $accentColor }};
      border-radius: 16px 16px 0 0;
      padding: 16px 12px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 8px;
      min-width: 80px;
      position: relative;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      transition: all 0.3s ease;
    }

    .podium-step:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    }

    .podium-step.highest {
      height: 160px;
      background: linear-gradient(135deg, {{ $accentColor }}, {{ $accentDark }});
      color: white;
    }

    .second .podium-step {
      height: 120px;
      background: {{ $bgSecondary }};
    }

    .third .podium-step {
      height: 120px;
      background: {{ $bgSecondary }};
    }

    .podium-avatar, .podium-avatar-placeholder {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      border: 3px solid {{ $accentColor }};
      object-fit: cover;
    }

    .podium-avatar-placeholder {
      background: linear-gradient(135deg, {{ $accentDark }}, {{ $accentColor }});
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: bold;
      font-size: 18px;
    }

    .podium-name {
      font-weight: 600;
      font-size: 12px;
      color: {{ $textPrimary }};
      text-align: center;
      max-width: 80px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .podium-points {
      font-weight: bold;
      font-size: 14px;
      color: {{ $accentColor }};
    }

    .position-badge {
      position: absolute;
      top: -10px;
      background: {{ $accentColor }};
      color: white;
      border-radius: 50%;
      width: 24px;
      height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 12px;
      border: 2px solid {{ $bgTertiary }};
    }

    .position-badge.gold {
      background: #FFD700;
      color: #333;
    }

    .star-icon {
      position: absolute;
      top: -20px;
      color: #FFD700;
      font-size: 20px;
      animation: sparkle 2s infinite;
    }

    @keyframes sparkle {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.2); }
    }

    /* Mobile responsive */
    @media (max-width: 600px) {
      .podium-container {
        gap: 8px;
        padding: 16px 8px;
        min-height: 120px;
      }
      .podium-position {
        flex: 1;
      }
      .podium-step {
        padding: 10px 52px;
        min-width: unset;
      }
      .podium-step.highest {
        height: 35px;
      }
      .second .podium-step, .third .podium-step {
        height: 20px;
      }
      .podium-avatar, .podium-avatar-placeholder {
        width: 40px;
        height: 40px;
      }
      .podium-name {
        font-size: 10px;
      }
      .podium-points {
        font-size: 12px;
      }
      .position-badge {
        width: 20px;
        height: 20px;
        font-size: 10px;
      }
      .star-icon {
        font-size: 16px;
      }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.querySelector('.overflow-x-auto');
        const indicators = document.querySelectorAll('.question-indicator');
        let currentIndex = 0;

        // Actualizar indicadores al hacer scroll
        container.addEventListener('scroll', () => {
            const scrollPosition = container.scrollLeft;
            const itemWidth = container.offsetWidth;
            currentIndex = Math.round(scrollPosition / itemWidth);
            updateIndicators();
        });

        // Click en los indicadores
        indicators.forEach((indicator, index) => {
            indicator.addEventListener('click', () => {
                const itemWidth = container.offsetWidth;
                container.scrollTo({
                    left: itemWidth * index,
                    behavior: 'smooth'
                });
                currentIndex = index;
                updateIndicators();
            });
        });

        function updateIndicators() {
            indicators.forEach((indicator, index) => {
                indicator.classList.toggle('active', index === currentIndex);
            });
        }

        // Inicializar indicadores
        updateIndicators();
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Función para actualizar el contador de mensajes no leídos
        function updateUnreadCount() {
            fetch(`{{ route('chat.unread-count', $group) }}`)
                .then(response => response.json())
                .then(data => {
                    const unreadCount = document.getElementById('unreadCount');
                    if (data.unread_count > 0) {
                        unreadCount.textContent = data.unread_count;
                        unreadCount.classList.remove('hidden');
                    } else {
                        unreadCount.classList.add('hidden');
                    }
                });
        }

        // Actualizar el contador cada 30 segundos
        setInterval(updateUnreadCount, 30000);

        // Marcar mensajes como leídos cuando se hace clic en el botón del chat
        const chatToggle = document.getElementById('chatToggle');
        if (chatToggle) {
            chatToggle.addEventListener('click', function() {
                fetch(`{{ route('chat.mark-as-read', $group) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('unreadCount').classList.add('hidden');
                    }
                });
            });
        }

        // Marcar mensajes como leídos cuando se hace scroll al chat
        const chatSection = document.getElementById('chatSection');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    fetch(`{{ route('chat.mark-as-read', $group) }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('unreadCount').classList.add('hidden');
                        }
                    });
                }
            });
        });

        if (chatSection) {
            observer.observe(chatSection);
        }

        // Actualizar el contador inicialmente
        updateUnreadCount();
    });
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        var hash = window.location.hash;
        if(hash && /^#question\d+$/.test(hash)) {
            var $target = $(hash);
            if($target.length) {
                // Sube dos niveles: el padre del padre del div con el id
                var $scrollTo = $target.parent().parent().parent();
                if($scrollTo.length) {
                    $('html, body').animate({
                        scrollTop: $scrollTo.offset().top - 40 // Ajusta el margen si lo necesitas
                    }, 600);
                } else {
                    $('html, body').animate({
                        scrollTop: $target.offset().top - 40
                    }, 600);
                }
            }
        }
    });
</script>
<script>
    $(document).ready(function() {
        // Abrir modal
        $('#openFeedbackModal').on('click', function(e) {
            console.log('Open feedback modal clicked');

            e.preventDefault();
            $('#feedbackModal').removeClass('hidden');
        });

        // Cerrar modal
        $('#closeFeedbackModal, #cancelFeedback').on('click', function() {
            $('#feedbackModal').addClass('hidden');
        });

        // Enviar formulario
        $('#feedbackForm').on('submit', function(e) {
            e.preventDefault();

            $.ajax({
                url: '{{ route("feedback.store") }}',
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    alert(response.message);
                    $('#feedbackModal').addClass('hidden');
                    $('#feedbackForm')[0].reset();
                },
                error: function(xhr) {
                    const errors = xhr.responseJSON.errors;
                    let errorMessage = 'Por favor, corrige los siguientes errores:\n';

                    for (const field in errors) {
                        errorMessage += `- ${errors[field][0]}\n`;
                    }

                    alert(errorMessage);
                }
            });
        });
        $('#chatToggle').on('click', function() {
            $('html, body').animate({
                scrollTop: $('#chatSection').offset().top - 20
            }, 500);
        });
        console.log('Document ready');

        // Handle like button click
        $(document).on('click', '.like-btn', function(e) {
            e.preventDefault();
            const templateQuestionId = $(this).data('template-question-id');
            handleReaction(templateQuestionId, 'like');
        });

        // Handle dislike button click
        $(document).on('click', '.dislike-btn', function(e) {
            e.preventDefault();
            const templateQuestionId = $(this).data('template-question-id');
            handleReaction(templateQuestionId, 'dislike');
        });

        // Function to handle reaction (like/dislike)
        function handleReaction(templateQuestionId, type) {
            const url = '/questions/' + templateQuestionId + '/react';
            const token = $('meta[name="csrf-token"]').attr('content');

            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: token,
                    reaction: type
                },
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        updateReactionColors(templateQuestionId, data.user_reaction);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                }
            });
        }

        function updateReactionColors(templateQuestionId, reaction) {
            const likeButtons = $('.like-btn[data-template-question-id="' + templateQuestionId + '"]');
            const dislikeButtons = $('.dislike-btn[data-template-question-id="' + templateQuestionId + '"]');

            likeButtons.each(function() {
                const defaultColor = this.dataset.defaultColor || '';
                this.style.color = defaultColor;
            });

            dislikeButtons.each(function() {
                const defaultColor = this.dataset.defaultColor || '';
                this.style.color = defaultColor;
            });

            if (reaction === 'like') {
                likeButtons.each(function() {
                    const activeColor = this.dataset.activeColor || '#00deb0';
                    this.style.color = activeColor;
                });
            } else if (reaction === 'dislike') {
                dislikeButtons.each(function() {
                    const activeColor = this.dataset.activeColor || '#ef4444';
                    this.style.color = activeColor;
                });
            }
        }
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const countdownElements = document.querySelectorAll('.countdown');

        countdownElements.forEach(element => {
            const endTime = new Date(element.dataset.time).getTime();

            function updateCountdown() {
                const now = new Date().getTime();
                const timeLeft = endTime - now;

                if (timeLeft <= 0) {
                    element.textContent = 'Tiempo agotado';
                    return;
                }

                const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
                const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));

                element.textContent = `${days > 0 ? days + 'd ' : ''}${hours}h ${minutes}m `;
            }

            // Actualizar cada segundo
            updateCountdown();
            setInterval(updateCountdown, 1000);
        });

        const chatContainer = document.querySelector('.overflow-y-auto');

        if (chatContainer) {
            // Desplazar el contenedor al final
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Variable global temporal para guardar el último botón clickeado
        let lastClickedOptionButton = null;

        // Capturar clicks en los botones de opciones
        document.querySelectorAll('button[name="question_option_id"]').forEach(button => {
            button.addEventListener('click', function(e) {
                lastClickedOptionButton = this;
                // Permitir el submit normal
            });
        });

        // Capturar el envío de formularios de preguntas
        document.querySelectorAll('form[action*="questions.answer"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                // Si hay un botón clickeado, agregar su valor
                if (lastClickedOptionButton && lastClickedOptionButton.form === this) {
                    formData.set('question_option_id', lastClickedOptionButton.value);
                }

                fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => {
                    if (response.redirected) {
                        window.location.href = response.url;
                    } else {
                        return response.text();
                    }
                })
                .then(data => {
                    if (data) {
                        window.location.reload();
                    }
                })
                .catch(error => {
                    alert('Error al enviar la respuesta. Por favor, intenta nuevamente.');
                });
            });
        });
    });
</script>
<script>
    // Forzar actualización del Service Worker para solucionar problemas de caché
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.getRegistrations().then(function(registrations) {
            for(let registration of registrations) {
                registration.update();
                console.log('Service Worker actualizado');
            }
        });

        // Limpiar cache del service worker si es necesario
        if ('caches' in window) {
            caches.keys().then(function(cacheNames) {
                return Promise.all(
                    cacheNames.map(function(cacheName) {
                        if (cacheName.includes('offside-club')) {
                            console.log('Limpiando cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            });
        }
    }
</script>

