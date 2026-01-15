@php
    $themeColors = $themeColors ?? [];
    $bgPrimary = $themeColors['bgPrimary'] ?? '#0a2e2c';
    $bgSecondary = $themeColors['bgSecondary'] ?? '#0f3d3a';
    $bgTertiary = $themeColors['bgTertiary'] ?? '#1a524e';
    $textPrimary = $themeColors['textPrimary'] ?? '#ffffff';
    $textSecondary = $themeColors['textSecondary'] ?? '#b0b0b0';
    $borderColor = $themeColors['borderColor'] ?? '#2a4a47';
    $accentColor = $themeColors['accentColor'] ?? '#00deb0';
    $accentDark = $themeColors['accentDark'] ?? '#17b796';
    $isDark = $themeColors['isDark'] ?? ($textPrimary === '#ffffff');
    $componentsBackground = $themeColors['componentsBackground'] ?? ($isDark ? '#1a524e' : '#ffffff');
    $buttonBgHover = $themeColors['buttonBgHover'] ?? ($isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0, 0, 0, 0.04)');
@endphp

<!-- Chat Section - New Design -->
<div style="display: flex; flex-direction: column; min-height: 100%;">
<div id="chatSection" class="chat-section"
    style="margin: 5px; background: {{ $componentsBackground }}; border-radius: 16px; display: flex; flex-direction: column; max-height: 400px; border: 1px solid {{ $borderColor }}; box-shadow: 0 2px 4px rgba(0,0,0,0.1); color: {{ $textPrimary }};">
    <!-- Chat Title -->
    <div class="chat-title"
        style="display: flex; align-items: center; gap: 8px; padding: 16px; font-size: 16px; font-weight: 600; color: {{ $textPrimary }}; border-bottom: 1px solid {{ $borderColor }}; flex-shrink: 0;">
        <i class="fas fa-comments"></i> {{ __('views.chat.title') }}
    </div>

    <!-- Chat Messages Container (Scrollable) -->
    <div class="chat-messages" id="chatMessages"
        style="display: flex; flex-direction: column; gap: 12px; padding: 16px; overflow-y: auto; flex: 1; background: {{ $bgSecondary }}; border-bottom: 1px solid {{ $borderColor }};">
        @forelse($group->chatMessages->reverse() as $message)
            @php
                $chatUser = $message->user;
                $chatName = $chatUser->name ?? 'Usuario';
                $initials = '';
                if ($chatName) {
                    $parts = explode(' ', $chatName);
                    foreach ($parts as $part) {
                        $initials .= strtoupper(substr($part, 0, 1));
                    }
                }
                $initials = $initials !== '' ? substr($initials, 0, 1) : '?';
                $avatarPalette = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];
                $paletteIndex = $chatUser
                    ? crc32($chatUser->id . $chatName) % count($avatarPalette)
                    : array_rand($avatarPalette);
                $avatarColor = $avatarPalette[$paletteIndex];
            @endphp
            <div class="message" style="display: flex; gap: 8px; align-items: flex-start;">
                <!-- Avatar -->
                @if($chatUser && $chatUser->avatar)
                    <img src="{{ $chatUser->avatar_url }}"
                        alt="{{ $chatName }}"
                        class="message-avatar"
                        style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 1px solid {{ $borderColor }}; flex-shrink: 0;">
                @else
                    <div class="message-avatar"
                        style="width: 32px; height: 32px; border-radius: 50%; background: {{ $avatarColor }}; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 12px; font-weight: bold; flex-shrink: 0;">
                        {{ $initials }}
                    </div>
                @endif

                <!-- Message Content -->
                <div class="message-content" style="flex: 1;">
                    <!-- Message Header (Name + Time) -->
                    <div class="message-header"
                        style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                        <div class="message-name"
                            style="font-size: 12px; font-weight: 600; color: {{ $textPrimary }};">
                            {{ $message->user->name }}
                        </div>
                        <div class="message-time" style="font-size: 11px; color: {{ $textSecondary }};">
                            {{ $message->created_at->diffForHumans() }}
                        </div>
                    </div>

                    <!-- Message Text -->
                    <div class="message-text"
                        style="font-size: 14px; line-height: 1.4; color: {{ $textPrimary }};">
                        {{ $message->message }}
                    </div>
                </div>
            </div>
        @empty
            <div style="text-align: center; padding: 20px; color: {{ $textSecondary }};">
                <p>{{ __('views.chat.no_messages_yet') }}</p>
            </div>
        @endforelse
    </div>

    <!-- Chat Input (Fixed at bottom) -->
    <div class="chat-input"
        style="padding: 12px 16px; border-top: 1px solid {{ $borderColor }}; display: flex; gap: 8px; flex-shrink: 0; background: {{ $componentsBackground }}; border-radius: 0 0 16px 16px;">
        <form action="{{ route('chat.store', $group) }}" method="POST" style="display: flex; gap: 8px; width: 100%;"
            id="chatForm">
            @csrf
            <input type="text" name="message" id="chatMessage"
                style="flex: 1; padding: 10px 12px; border: 1px solid {{ $borderColor }}; border-radius: 20px; font-size: 14px; outline: none; background: {{ $isDark ? '#08201d' : '#fdfdfd' }}; color: {{ $textPrimary }};"
                placeholder="{{ __('views.chat.type_message') }}" required>
            <button type="submit" id="sendMessageBtn" title="{{ __('views.chat.send') }}"
                style="padding: 10px 16px; background: {{ $accentColor }}; color: {{ $isDark ? '#003b2f' : '#003b2f' }}; border: none; border-radius: 20px; cursor: pointer; font-weight: 600; transition: all 0.2s ease; flex-shrink: 0;"
                class="hover:opacity-80"
                onmouseover="this.style.opacity='0.9'"
                onmouseout="this.style.opacity='1'">
                <span class="hidden sm:block">{{ __('views.chat.send') }}</span>
                <i class="fas fa-paper-plane sm:hidden"></i>
            </button>
        </form>
    </div>
</div>
<!-- Botón de Premio/Penitencia (solo para creador) -->
{{-- @if ($group->created_by === auth()->id())
    <div style="margin-top: 32px; display: flex; justify-content: center;">
        <button id="openRewardPenaltyBtn" class="btn btn-primary" style="background-color: {{ $accentColor }};"
            onclick="document.getElementById('rewardPenaltyModal').classList.remove('hidden')">
            <i class="fas fa-plus"></i>
            <span>Agregar recompensa/penitencia</span>
        </button>
    </div>
@endif --}}
</div>
@if ($group->created_by === auth()->id())
    <div class="flex justify-center mt-8">
        @if ($group->reward_or_penalty)
            <div class="flex justify-center mt-2 mb-2">
                <div class="px-4 py-1 text-white rounded-lg text-center accentColor" style="background-color: {{ $accentColor }};">
                    <span class="font-bold">{{ __('views.groups.reward_punishment_label') }}:</span><br>
                    <span class="reward-or-penalty-text" style="font-style: italic">{{ $group->reward_or_penalty }}</span> <button
                        id="openRewardPenaltyModal"
                        class=" text-white rounded-lg hover:bg-offside-secondary transition-colors focus:outline-none">
                        <i class="fa-solid fa-edit ml-2"></i>
                    </button>
                    </span>
                </div>
            </div>
        @else
            <button id="openRewardPenaltyModal"
                class="flex items-center px-4 py-2  rounded-lg  mt-4" style="background-color: {{ $accentColor }};">
                <i class="fa-solid fa-plus"></i>
                {{ __('views.groups.add_reward_punishment') }}
            </button>
        @endif
    </div>
@elseif($group->reward_or_penalty)
    <div style="margin-top: 32px; margin-bottom: 20px; display: flex; justify-content: center;">
        <button id="openRewardPenaltyBtn" class="btn btn-primary" style="background-color: {{ $accentColor }};"
            onclick="document.getElementById('rewardPenaltyModal').classList.remove('hidden')">
            <i class="fas fa-plus"></i>
            <span>{{ __('views.groups.add_reward_punishment') }}</span>
        </button>
    </div>
@endif

<!-- Modal Premio/Penitencia -->
@if ($group->created_by === auth()->id())
    <div id="rewardPenaltyModal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div
            style="background: {{ $bgSecondary }}; border-radius: 16px; width: 100%; max-width: 480px; padding: 28px 24px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2); border: 1px solid {{ $borderColor }};">

            {{-- Header --}}
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
                <h2
                    style="font-size: 22px; font-weight: 700; color: {{ $textPrimary }}; margin: 0; display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-trophy" style="color: {{ $accentColor }};"></i>
                    {{ __('views.groups.reward_punishment') }}
                </h2>
                <button id="closeRewardPenaltyModal"
                    style="background: none; border: none; font-size: 24px; color: {{ $textSecondary }}; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 6px; transition: all 0.2s ease;"
                    onmouseover="this.style.background='{{ $isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.05)' }}'; this.style.color='{{ $textPrimary }}';"
                    onmouseout="this.style.background='none'; this.style.color='{{ $textSecondary }}';">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Descripción --}}
            <p style="color: {{ $textSecondary }}; font-size: 14px; margin-bottom: 20px; line-height: 1.5;">
                {{ __('views.groups.reward_punishment_description') }}
            </p>

            {{-- Formulario --}}
            <form id="rewardPenaltyForm" style="display: flex; flex-direction: column; gap: 16px;">
                @csrf

                <div>
                    <label for="reward_or_penalty"
                        style="display: block; font-size: 14px; font-weight: 600; color: {{ $textPrimary }}; margin-bottom: 8px;">{{ __('views.groups.reward_punishment_label') }}</label>
                    <textarea id="reward_or_penalty" name="reward_or_penalty" rows="4" required
                        style="width: 100%; background: {{ $bgSecondary }}; border: 1px solid {{ $borderColor }}; border-radius: 10px; padding: 12px 16px; color: {{ $textPrimary }}; font-size: 14px; font-family: inherit; resize: vertical; box-sizing: border-box; transition: all 0.3s ease;"
                        onfocus="this.style.borderColor='{{ $accentColor }}'; this.style.boxShadow='0 0 0 3px {{ $isDark ? 'rgba(0, 222, 176, 0.1)' : 'rgba(0, 222, 176, 0.08)' }}';"
                        onblur="this.style.borderColor='{{ $borderColor }}'; this.style.boxShadow='none';">{{ $group->reward_or_penalty }}</textarea>
                </div>

                {{-- Botones --}}
                <div style="display: flex; gap: 12px; margin-top: 8px;">
                    <button type="button" id="cancelRewardPenalty"
                        style="flex: 1; padding: 12px 16px; background: {{ $bgSecondary }}; border: 1px solid {{ $borderColor }}; border-radius: 10px; color: {{ $textPrimary }}; font-weight: 600; cursor: pointer; transition: all 0.2s ease; font-size: 15px;"
                        onmouseover="this.style.background='{{ $isDark ? '#1a524e' : '#f0f0f0' }}';"
                        onmouseout="this.style.background='{{ $bgSecondary }}';">
                        {{ __('views.settings.cancel') }}
                    </button>
                    <button type="submit"
                        style="flex: 1; padding: 12px 16px; background: linear-gradient(135deg, {{ $accentDark }}, {{ $accentColor }}); border: none; border-radius: 10px; color: #000; font-weight: 600; cursor: pointer; transition: all 0.2s ease; font-size: 15px; display: flex; align-items: center; justify-content: center; gap: 8px;"
                        onmouseover="this.style.opacity='0.9'; this.style.transform='translateY(-1px)';"
                        onmouseout="this.style.opacity='1'; this.style.transform='translateY(0)';">
                        <i class="fas fa-check"></i> {{ __('views.settings.save') }}
                    </button>
                </div>

                {{-- Mensaje de éxito --}}
                <div id="rewardPenaltySuccess"
                    style="display: none; margin-top: 12px; padding: 12px 16px; background: rgba(0, 200, 0, 0.1); border: 1px solid #00c800; border-radius: 8px; color: #00c800; font-weight: 600; text-align: center; font-size: 14px;">
                    <i class="fas fa-check-circle" style="margin-right: 8px;"></i> {{ __('views.groups.saved_successfully') }}
                </div>
            </form>
        </div>
    </div>
@endif

<script>
    (function() {
        'use strict';

        let isSubmitting = false;

        document.addEventListener('DOMContentLoaded', function() {
            const chatForm = document.getElementById('chatForm');
            const sendButton = document.getElementById('sendMessageBtn');
            const messageInput = document.getElementById('chatMessage');
            const chatContainer = document.getElementById('chatMessages');

            if (!chatForm || !sendButton || !messageInput || !chatContainer) {
                console.warn('Chat elements not found');
                return;
            }

            // Scroll al final al cargar
            setTimeout(() => {
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }, 100);

            chatForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                if (isSubmitting) return;

                isSubmitting = true;
                sendButton.disabled = true;
                sendButton.style.opacity = '0.6';

                try {
                    const formData = new FormData(chatForm);
                    const csrfToken = document.querySelector('meta[name="csrf-token"]');

                    const response = await fetch(chatForm.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken ? csrfToken.content : ''
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        messageInput.value = '';

                        // Limpiar mensaje vacío
                        const emptyMsg = chatContainer.querySelector('[style*="text-align"]');
                        if (emptyMsg) {
                            emptyMsg.remove();
                        }

                        const firstLetter = (data.message.user.name || '?').charAt(0)
                            .toUpperCase();
                        const newMessageHTML = `
                        <div class="message" style="display: flex; gap: 8px; align-items: flex-start;">
                            <div class="message-avatar" style="width: 32px; height: 32px; border-radius: 50%; background: {{ $accentColor }}; display: flex; align-items: center; justify-content: center; color: {{ $isDark ? '#003b2f' : '#003b2f' }}; font-size: 12px; font-weight: bold; flex-shrink: 0;">
                                ${firstLetter}
                            </div>
                            <div class="message-content" style="flex: 1;">
                                <div class="message-header" style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                    <div class="message-name" style="font-size: 12px; font-weight: 600; color: {{ $textPrimary }};">
                                        ${(data.message.user.name || 'Usuario').replace(/</g, '&lt;').replace(/>/g, '&gt;')}
                                    </div>
                                    <div class="message-time" style="font-size: 11px; color: {{ $textSecondary }};">
                                        hace unos segundos
                                    </div>
                                </div>
                                <div class="message-text" style="font-size: 14px; line-height: 1.4; color: {{ $textPrimary }};">
                                    ${(data.message.message || '').replace(/</g, '&lt;').replace(/>/g, '&gt;')}
                                </div>
                            </div>
                        </div>
                    `;

                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = newMessageHTML;
                        const messageEl = tempDiv.firstElementChild;

                        if (messageEl && chatContainer) {
                            chatContainer.appendChild(messageEl);
                            setTimeout(() => {
                                chatContainer.scrollTop = chatContainer.scrollHeight;
                            }, 50);
                        }
                    }
                } catch (error) {
                    console.error('Error al enviar mensaje:', error);
                } finally {
                    isSubmitting = false;
                    sendButton.disabled = false;
                    sendButton.style.opacity = '1';
                }
            });

            messageInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey && !isSubmitting) {
                    e.preventDefault();
                    chatForm.dispatchEvent(new Event('submit'));
                }
            });
        });
    })();
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const openModalBtn = document.getElementById('openRewardPenaltyModal');
        const closeModalBtn = document.getElementById('closeRewardPenaltyModal');
        const cancelBtn = document.getElementById('cancelRewardPenalty');
        const modal = document.getElementById('rewardPenaltyModal');
        const rewardPenaltyForm = document.getElementById('rewardPenaltyForm');

        if (!modal) return;

        if (openModalBtn) {
            openModalBtn.addEventListener('click', () => modal.classList.remove('hidden'));
        }
        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', () => modal.classList.add('hidden'));
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => modal.classList.add('hidden'));
        }

        if (rewardPenaltyForm) {
            rewardPenaltyForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                const groupId = {{ $group->id }};
                const textarea = document.getElementById('reward_or_penalty');
                const csrfToken = document.querySelector('meta[name="csrf-token"]');

                try {
                    const response = await fetch(`/groups/${groupId}/reward-or-penalty`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken ? csrfToken.content : '',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            reward_or_penalty: textarea.value
                        })
                    });

                    if (response.ok) {
                        const result = await response.json();
                        const successMsg = document.getElementById('rewardPenaltySuccess');
                        if (successMsg) {
                            successMsg.classList.remove('hidden');
                            setTimeout(() => {
                                successMsg.classList.add('hidden');
                                modal.classList.add('hidden');
                                document.querySelectorAll('.reward-or-penalty-text')
                                    .forEach(el => {
                                        el.textContent = result.reward_or_penalty ||
                                            textarea.value;
                                    });
                            }, 1200);
                        }
                    } else {
                        alert('{{ __('views.groups.error_save') }}');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('{{ __('views.groups.error_save') }}');
                }
            });
        }
    });
</script>
