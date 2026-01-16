@php
    // Las variables de tema ya están compartidas globalmente por el middleware
    // Solo validar que existan en caso de que se use el componente sin middleware
    $themeColors = $themeColors ?? [];
    $isDark = $themeColors['isDark'] ?? ($isDark ?? true);
    $bgPrimary = $themeColors['bgPrimary'] ?? ($bgPrimary ?? ($isDark ? '#0a2e2c' : '#f5f5f5'));
    $bgSecondary = $themeColors['bgSecondary'] ?? ($bgSecondary ?? ($isDark ? '#0f3d3a' : '#f5f5f5'));
    $bgTertiary = $themeColors['bgTertiary'] ?? ($bgTertiary ?? ($isDark ? '#1a524e' : '#ffffff'));
    $textPrimary = $themeColors['textPrimary'] ?? ($textPrimary ?? ($isDark ? '#ffffff' : '#333333'));
    $textSecondary = $themeColors['textSecondary'] ?? ($textSecondary ?? ($isDark ? '#b0b0b0' : '#999999'));
    $borderColor = $themeColors['borderColor'] ?? ($borderColor ?? ($isDark ? '#2a4a47' : '#e0e0e0'));
    $componentsBackground = $themeColors['componentsBackground'] ?? ($componentsBackground ?? ($isDark ? '#1a524e' : '#ffffff'));
    $buttonBgHover = $themeColors['buttonBgHover'] ?? ($buttonBgHover ?? ($isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0, 0, 0, 0.04)'));
    $accentColor = $themeColors['accentColor'] ?? ($accentColor ?? '#00deb0');
    $accentDark = $themeColors['accentDark'] ?? ($accentDark ?? '#003b2f');

    $shareModalBg = $isDark ? '#10302d' : '#ffffff';
    $shareModalText = $isDark ? '#f1fff8' : '#333333';
    $shareModalBorder = $isDark ? '#1d4f4a' : '#e0e0e0';
    $shareTextareaBg = $isDark ? 'rgba(255,255,255,0.05)' : '#f5f5f5';
    $shareModalShadow = $isDark ? '0 14px 40px rgba(0, 0, 0, 0.55)' : '0 10px 40px rgba(0, 0, 0, 0.2)';
    $shareCloseColor = $isDark ? '#d5fdf0' : '#999999';
@endphp

<div class="mt-1">
    <!-- Título de Preguntas -->
    {{-- <div class="flex items-center gap-2 mb-6 px-4">
        <i class="fas fa-star" style="color: {{ $accentColor }};"></i>
        <h2 class="text-base font-semibold" style="color: {{ $textPrimary }};">{{ __('views.groups.available_questions') }}</h2>
    </div> --}}

    <!-- Acciones del grupo -->
    <div class="flex justify-end px-1 mb-4">
        <button type="button"
                onclick="showInviteModal(@js($group->name), @js(route('groups.invite', $group->code)))"
                style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border: none; border-radius: 999px; background: linear-gradient(135deg, #17b796, #00deb0); color: #003b2f; font-size: 13px; font-weight: 700; cursor: pointer; box-shadow: 0 10px 20px rgba(0, 222, 176, 0.25); transition: transform 0.2s ease;"
                onmouseover="this.style.transform='translateY(-2px)'"
                onmouseout="this.style.transform='translateY(0)';">
            <i class="fas fa-paper-plane"></i>
            <span>{{ __('views.groups.share_group') }}</span>
        </button>
    </div>

    <!-- Carrusel de preguntas -->
    <div class="relative flex items-center">
        <!-- Carrusel -->
        <div class="overflow-x-auto hide-scrollbar snap-x snap-mandatory flex space-x-4 flex-1 px-1 pb-4" id="predictiveQuestionsCarousel">
            @forelse($matchQuestions->where('type', 'predictive') as $question)
                <!-- Prediction Section (Similar to HTML design) -->
                <div class="snap-center flex-none w-full rounded-2xl p-5 border shadow-sm" id="question{{ $question->id }}" style="background: {{ $componentsBackground }}; border-color: {{ $borderColor }}; min-width: 300px; {{ $question->is_disabled || $question->available_until->addHours(4) < now() ? 'opacity-60;' : '' }}">

                <!-- Prediction Header -->
                <div class="text-center mb-5">
                    <div class="inline-block px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider mb-3" style="background: {{ $accentColor }}; color: #000;">
                        {{ __('views.groups.prediction_of_the_day') }}
                    </div>
                    <div class="text-xs mb-4" style="color: {{ $textSecondary }};">
                        <i class="fas fa-circle" style="color: {{ $accentColor }}; font-size: 3px;"></i>
                        {{ $group->competition?->name }} • {{ __('views.groups.matchday') }} {{ $question->football_match->matchday ?? 'TBD' }}

                    </div>
                </div>

                <!-- Match Info -->
                @if($question->football_match)
                    @if($question->templateQuestion->homeTeam)
                        <div class="flex items-center justify-center gap-4 mb-5">
                            <div class="flex flex-col items-center gap-1">
                                <img src="{{ $question->templateQuestion->homeTeam->crest_url ?? asset('images/default-crest.png') }}" alt="{{ $question->templateQuestion->homeTeam->name }}" class="w-16 h-16 object-contain" title="{{ $question->templateQuestion->homeTeam?->name }}">
                                <span class="text-xs font-medium" style="color: {{ $textPrimary }};">{{ Str::limit($question->templateQuestion->home_team, 20, '') }}</span>
                            </div>
                                <span class="text-sm font-bold" style="color: {{ $accentDark }};">@userTime($question->football_match->date, 'H:i')</span>
                            <div class="flex flex-col items-center gap-1">
                                <img src="{{ $question->templateQuestion->awayTeam?->crest_url }}" alt="{{ $question->templateQuestion->awayTeam->name }}" class="w-16 h-16 object-contain" title="{{ $question->templateQuestion->awayTeam?->name }}">
                                <span class="text-xs font-medium" style="color: {{ $textPrimary }};">{{ Str::limit($question->templateQuestion->away_team, 20, '') }}</span>
                            </div>
                        </div>
                    @else
                        <div class="flex items-center justify-center gap-4 mb-5">
                            <div class="flex flex-col items-center gap-1">
                                <img src="{{ $question->football_match->homeTeam->crest_url ?? asset('images/default-crest.png') }}" alt="{{ $question->football_match->homeTeam?->name }}" class="w-16 h-16 object-contain" title="{{ $question->football_match->homeTeam?->name }}">
                                <span class="text-xs font-medium" style="color: {{ $textPrimary }};">{{ Str::limit($question->football_match->home_team, 20, '') }}</span>
                            </div>
                            <span class="text-sm font-bold" style="color: {{ $accentDark }};">@userTime($question->football_match->date, 'H:i')</span>
                            <div class="flex flex-col items-center gap-1">
                                <img src="{{ $question->football_match->awayTeam?->crest_url }}" alt="{{ $question->football_match->awayTeam?->name }}" class="w-16 h-16 object-contain" title="{{ $question->football_match->awayTeam?->name }}">
                                <span class="text-xs font-medium" style="color: {{ $textPrimary }};">{{ Str::limit($question->football_match->away_team, 20, '') }}</span>
                            </div>
                        </div>
                    @endif
                @endif

                <!-- Question Title -->
                <div class="text-base font-bold text-center mb-5" style="color: {{ $accentColor }};">
                    {{ $question->title }}
                </div>

                <!-- Options (Answers) -->
                @if((!isset($userHasAnswered) && $question->available_until->addHours(4) > now() && !$question->is_disabled) || (isset($userHasAnswered) && $userHasAnswered->updated_at->diffInMinutes(now()) <= 5 && $question->can_modify))
                    <form action="{{ route('questions.answer', $question) }}" method="POST" class="group-question-form">
                        @csrf
                        <div class="grid grid-cols-2 gap-3 mb-5">
                            @foreach($question->options->sortBy('text') as $option)
                                @php
                                    $answers = $question->answers->where('question_option_id', $option->id);
                                    $isStacked = $answers->count() > 2;
                                    $allNames = $answers->pluck('user.name')->implode(', ');
                                @endphp
                                <label class="option-label w-full py-3 px-2 border rounded-xl text-xs transition-all cursor-pointer" style="background: {{ $bgSecondary }}; color: {{ $textPrimary }}; border-color: {{ $borderColor }}; display: flex; justify-content: space-between; align-items: center; gap: 0.5rem;" onmouseover="this.style.borderColor='{{ $accentColor }}'; this.style.backgroundColor='{{ $buttonBgHover }}'" onmouseout="this.style.borderColor='{{ $borderColor }}'; this.style.backgroundColor='{{ $bgSecondary }}'">
                                    <input type="radio" name="question_option_id" value="{{ $option->id }}" style="display: none;" onchange="this.closest('form').submit();">
                                    <span style="pointer-events: none; flex: 1; text-align: center;">{{ $option->text }}</span>
                                    <div style="display: flex; align-items: center;">
                                        @if($answers->count() > 0)
                                            <div class="flex items-center {{ $isStacked ? '-space-x-4' : 'space-x-1' }}" @if($isStacked) title="Votaron: {{ $allNames }}" @endif>
                                                @foreach($answers->take(3) as $answer)
                                                    @if($answer->user->avatar)
                                                        <img src="{{ $answer->user->avatar_url }}"
                                                             alt="{{ $answer->user->name }}"
                                                             class="w-5 h-5 rounded-full border border-white shadow-sm object-cover {{ $isStacked ? 'ring-1 ring-white' : '' }}"
                                                             title="{{ $answer->user->name }}"
                                                             style="pointer-events: none;">
                                                    @else
                                                        @php
                                                            $initials = '';
                                                            $nameParts = explode(' ', $answer->user->name);
                                                            foreach($nameParts as $part) {
                                                                $initials .= strtoupper(substr($part, 0, 1));
                                                            }
                                                            $colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];
                                                            $color = $colors[array_rand($colors)];
                                                        @endphp
                                                        <div class="w-5 h-5 rounded-full text-white flex items-center justify-center text-xs font-bold border border-white shadow-sm {{ $isStacked ? 'ring-1 ring-white' : '' }}"
                                                             style="background: {{ $color }}; pointer-events: none;"
                                                             title="{{ $answer->user->name }}">
                                                            {{ substr($initials, 0, 1) }}
                                                        </div>
                                                    @endif
                                                @endforeach
                                                @if($isStacked && $answers->count() > 3)
                                                    <span class="text-xs font-bold ml-1" style="color: {{ $textSecondary }}; pointer-events: none;">+{{ $answers->count() - 3 }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </form>

                    <!-- Timer -->
                    <div class="text-center text-sm font-semibold" style="color: {{ $accentColor }};">
                        <i class="fas fa-clock"></i>
                        @if($question->is_disabled)
                            {{ __('views.groups.question_disabled') }}
                        @elseif($question->available_until->addHours(4) > now())
                            <span class="countdown" data-time="{{ $question->available_until->addHours(4)->timezone('Europe/Madrid')->format('Y-m-d H:i') }}"></span>
                        @else
                            {{ __('views.groups.match_finished') }}
                        @endif
                    </div>
                @else
                    <!-- Display Results -->
                    <div class="grid grid-cols-2 gap-3 mb-5">
                        @foreach($question->options->sortBy('text') as $option)
                            @php
                                $answers = $question->answers->where('question_option_id', $option->id);
                                $isStacked = $answers->count() > 2;
                                $allNames = $answers->pluck('user.name')->implode(', ');
                                $optionBg = $bgSecondary;
                                $optionColor = $textPrimary;
                                if ($question->available_until->addHours(4) > now() && !$question->is_disabled) {
                                    if ($userHasAnswered && $userHasAnswered->id == $option->id) {
                                        $optionBg = $accentDark;
                                        $optionColor = '#ffffff';
                                    }
                                } else {
                                    if ($option->is_correct) {
                                        $optionBg = '#28a745';
                                        $optionColor = '#ffffff';
                                    } elseif ($userHasAnswered && $userHasAnswered->id == $option->id) {
                                        $optionBg = '#dc3545';
                                        $optionColor = '#ffffff';
                                    }
                                }
                            @endphp
                            <div class="w-full py-3 px-2 border rounded-xl text-xs font-medium" style="background: {{ $optionBg }}; color: {{ $optionColor }}; border-color: {{ $borderColor }}; display: flex; justify-content: space-between; align-items: center;">
                                <span>{{ $option->text }}</span>
                                <div style="display: flex; align-items: center;">
                                    @if($answers->count() > 0)
                                        <div class="flex items-center {{ $isStacked ? '-space-x-4' : 'space-x-1' }}" @if($isStacked) title="Votaron: {{ $allNames }}" @endif>
                                            @foreach($answers->take(3) as $answer)
                                                @if($answer->user->avatar)
                                                    <img src="{{ $answer->user->avatar_url }}"
                                                         alt="{{ $answer->user->name }}"
                                                         class="w-5 h-5 rounded-full border border-white shadow-sm object-cover {{ $isStacked ? 'ring-1 ring-white' : '' }}"
                                                         title="{{ $answer->user->name }}"
                                                         style="pointer-events: none;">
                                                @else
                                                    @php
                                                        $initials = '';
                                                        $nameParts = explode(' ', $answer->user->name);
                                                        foreach($nameParts as $part) {
                                                            $initials .= strtoupper(substr($part, 0, 1));
                                                        }
                                                        $colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];
                                                        $color = $colors[array_rand($colors)];
                                                    @endphp
                                                    <div class="w-5 h-5 rounded-full text-white flex items-center justify-center text-xs font-bold border border-white shadow-sm {{ $isStacked ? 'ring-1 ring-white' : '' }}"
                                                         style="background: {{ $color }}; pointer-events: none;"
                                                         title="{{ $answer->user->name }}">
                                                        {{ substr($initials, 0, 1) }}
                                                    </div>
                                                @endif
                                            @endforeach
                                            @if($isStacked && $answers->count() > 3)
                                                <span class="text-xs font-bold ml-1" style="color: {{ $textSecondary }}; pointer-events: none;">+{{ $answers->count() - 3 }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Timer -->
                    <div class="text-center text-sm font-semibold" style="color: {{ $accentColor }};">
                        <i class="fas fa-clock"></i>
                        @if($question->is_disabled)
                            {{ __('views.groups.question_disabled') }}
                        @elseif($question->available_until->addHours(4) > now())
                            <span class="countdown" data-time="{{ $question->available_until->addHours(4)->timezone('Europe/Madrid')->format('Y-m-d H:i:s') }}"></span>
                        @else
                            {{ __('views.groups.match_finished') }}
                        @endif
                    </div>
                @endif

                <!-- Like/Dislike Buttons -->
                <div class="flex justify-end gap-3 mt-4">
                    <button type="button" class="like-btn text-sm transition-colors" style="color: {{ $textSecondary }};" data-question-id="{{ $question->id }}" data-template-question-id="{{ $question->template_question_id }}">
                        <i class="fas fa-thumbs-up"></i>
                    </button>
                    <button type="button" class="dislike-btn text-sm transition-colors" style="color: {{ $textSecondary }};" data-question-id="{{ $question->id }}" data-template-question-id="{{ $question->template_question_id }}">
                        <i class="fas fa-thumbs-down"></i>
                    </button>
                </div>
            </div>
        @empty
            <div class="text-center py-12 px-4" style="color: {{ $textSecondary }};">
                <i class="fas fa-inbox text-4xl mb-3" style="color: {{ $borderColor }};"></i>
                <p class="text-sm">{{ __('views.groups.no_available_questions') }}</p>
            </div>
        @endforelse

            <!-- Pregunta Social o Invitación de Miembros -->
            @if($group->users->count() >= 2)
                @if($socialQuestion ?? false)
                    <div class="snap-center flex-none w-full" style="min-width: 300px;">
                        <x-groups.group-social-question :social-question="$socialQuestion" :user-answers="$userAnswers" :theme-colors="compact('isDark', 'bgPrimary', 'bgSecondary', 'bgTertiary', 'textPrimary', 'textSecondary', 'borderColor', 'accentColor', 'accentDark', 'componentsBackground', 'buttonBgHover')" />
                    </div>
                @endif
            @else
                <!-- Slide de Invitación para Agregar Miembros -->
                <div class="snap-center flex-none w-full rounded-2xl p-5 border shadow-sm" style="background: {{ $componentsBackground }}; border-color: {{ $borderColor }}; min-width: 300px;">
                    <div class="text-center mb-5">
                        <div class="inline-block px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider mb-3" style="background: {{ $accentColor }}; color: #000;">
                            {{ __('views.groups.social_questions') }}
                        </div>
                        <div class="text-xs mb-4" style="color: {{ $textSecondary }};">
                            <i class="fas fa-circle" style="color: {{ $accentColor }}; font-size: 3px;"></i>
                            {{ __('views.groups.coming_soon') }}
                        </div>
                    </div>

                    <div class="flex flex-col items-center justify-center gap-4 py-8">
                        <div class="text-5xl" style="color: {{ $accentColor }};">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-center mb-2" style="color: {{ $textPrimary }};">
                                {{ __('views.groups.add_more_members') }}
                            </h3>

                            <p class="text-xs text-center" style="color: {{ $textSecondary }};">
                                {{ __('views.groups.social_questions_description') }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 p-3 rounded-lg text-xs font-medium text-center" style="background: {{ $bgSecondary }}; color: {{ $textSecondary }}; border: 1px solid {{ $borderColor }};">
                        {{ __('views.groups.current_members') }}: <span style="color: {{ $accentColor }}; font-bold;">{{ $group->users->count() }}/2</span>
                    </div>
                </div>
            @endif
        </div>

        <!-- Flecha Izquierda -->
        <button class="absolute left-0 z-10 rounded-full p-2 shadow-md hover:shadow-lg transition-all" style="background: {{ $bgTertiary }}; color: {{ $accentColor }}; top: 50%; transform: translateY(-50%);" onclick="document.getElementById('predictiveQuestionsCarousel').scrollBy({left: -300, behavior: 'smooth'})">
            <i class="fas fa-chevron-left text-lg"></i>
        </button>

        <!-- Flecha Derecha -->
        <button class="absolute right-0 z-10 rounded-full p-2 shadow-md hover:shadow-lg transition-all" style="background: {{ $bgTertiary }}; color: {{ $accentColor }}; top: 50%; transform: translateY(-50%);" onclick="document.getElementById('predictiveQuestionsCarousel').scrollBy({left: 300, behavior: 'smooth'})">
            <i class="fas fa-chevron-right text-lg"></i>
        </button>
    </div>

        <!-- Indicadores de navegación -->
        <div class="flex justify-center mt-4 gap-1">
            @foreach($matchQuestions as $index => $question)
                <button class="w-2 h-2 rounded-full question-indicator transition-all" style="background: {{ $borderColor }};" data-index="{{ $index }}"></button>
            @endforeach
            <!-- Indicador para la pregunta social -->
            <button class="w-2 h-2 rounded-full question-indicator transition-all" style="background: {{ $borderColor }};" data-index="{{ $matchQuestions->count() }}"></button>
        </div>
    </div>
</div>

@once
<!-- Invite Modal (shared with index) -->
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
        if (window.__groupShareModalFromQuestionsInit) {
            return;
        }
        window.__groupShareModalFromQuestionsInit = true;

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
            const message = `¡Únete al grupo "${groupName}" en Offside Club!\n\n${inviteUrl}\n\n¡Ven a competir con nosotros!`;
            messageArea.value = message;
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
            const originalHtml = button.innerHTML;
            const originalBg = button.style.background;
            button.innerHTML = '<i class="fas fa-check"></i><span> ¡Copiado!</span>';
            button.style.background = '#00c800';
            button.disabled = true;

            setTimeout(() => {
                button.innerHTML = originalHtml;
                button.style.background = originalBg || '#17b796';
                button.disabled = false;
            }, 2000);
        }
    })();
</script>
@endonce
