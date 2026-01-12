<x-dynamic-layout>
    @php
        $themeMode = auth()->user()->theme_mode ?? 'auto';
        $isDark = $themeMode === 'dark';

        // Define color variables
        $bgPrimary = $isDark ? '#0a2e2c' : '#ffffff';
        $bgSecondary = $isDark ? '#0f3d3a' : '#f5f5f5';
        $borderColor = $isDark ? '#2a4a47' : '#e0e0e0';
        $textPrimary = $isDark ? '#ffffff' : '#333333';
        $textSecondary = $isDark ? '#b0b0b0' : '#999999';
        $labelColor = $isDark ? '#ffffff' : '#333333';
        $inputBg = $isDark ? '#1a524e' : '#ffffff';
        $accentColor = '#00deb0';
        $accentDark = '#17b796';
    @endphp

    <div style="min-height: 100vh; background: {{ $isDark ? 'linear-gradient(135deg, #0a2e2c 0%, #0f3d3a 100%)' : '#f9f9f9' }}; color: {{ $textPrimary }}; padding: 16px 20px; padding-top: 80px;">
        <div style="max-width: 600px; margin: 0 auto;">
            <div style="background: {{ $bgPrimary }}; border: 1px solid {{ $borderColor }}; border-radius: 16px; padding: 32px 24px; box-shadow: 0 4px 12px rgba(0, 0, 0, {{ $isDark ? '0.3' : '0.1' }});">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px;">
                    <h2 style="font-size: 28px; font-weight: 700; color: {{ $textPrimary }}; margin: 0;">{{ __('views.groups.create_new_group') }}</h2>
                    <i class="fas fa-users" style="font-size: 32px; color: {{ $accentColor }};"></i>
                </div>

                <form method="POST" action="{{ route('groups.store') }}" id="createGroupForm" style="display: flex; flex-direction: column; gap: 20px;">
                    @csrf
                    <input type="hidden" name="form_submitted" value="1">

                    <div>
                        <label for="name" style="display: block; font-size: 14px; font-weight: 600; color: {{ $labelColor }}; margin-bottom: 8px;">{{ __('views.groups.group_name_label') }}</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                            style="width: 100%; background: {{ $inputBg }}; border: 1px solid {{ $borderColor }}; border-radius: 8px; padding: 12px 16px; color: {{ $textPrimary }}; font-size: 14px; transition: all 0.3s ease;"
                            onfocus="this.style.borderColor='{{ $accentColor }}'; this.style.boxShadow='0 0 0 3px rgba(0, 222, 176, 0.1)'"
                            onblur="this.style.borderColor='{{ $borderColor }}'; this.style.boxShadow='none'"
                            placeholder="{{ __('views.groups.group_name_placeholder') }}" />
                        @error('name')
                            <p style="margin-top: 6px; font-size: 13px; color: #ef4444;">{{ $message }}</p>
                        @enderror
                    </div>
                    {{-- category --}}
                    <div>
                        <label for="category" style="display: block; font-size: 14px; font-weight: 600; color: {{ $labelColor }}; margin-bottom: 8px;">{{ __('views.groups.category_label') }}</label>
                        <select id="category" name="category" required readonly
                            style="width: 100%; background: {{ $inputBg }}; border: 1px solid {{ $borderColor }}; border-radius: 8px; padding: 12px 16px; color: {{ $textPrimary }}; font-size: 14px; transition: all 0.3s ease; cursor: pointer;">
                            <option value="official" selected>{{ __('views.groups.category_official') }}</option>
                            <option value="aficionado">{{ __('views.groups.category_amateur') }}</option>
                        </select>
                    </div>

                    <div>
                        <label for="competition_id" style="display: block; font-size: 14px; font-weight: 600; color: {{ $labelColor }}; margin-bottom: 8px;">{{ __('views.groups.competition_label') }}</label>
                        <select id="competition_id" name="competition_id" required
                            style="width: 100%; background: {{ $inputBg }}; border: 1px solid {{ $borderColor }}; border-radius: 8px; padding: 12px 16px; color: {{ $textPrimary }}; font-size: 14px; transition: all 0.3s ease; cursor: pointer;"
                            onfocus="this.style.borderColor='{{ $accentColor }}'; this.style.boxShadow='0 0 0 3px rgba(0, 222, 176, 0.1)'"
                            onblur="this.style.borderColor='{{ $borderColor }}'; this.style.boxShadow='none'">
                            <option value="">{{ __('views.groups.select_competition') }}</option>
                            @foreach($competitions as $competition)
                                <option value="{{ $competition->id }}" {{ old('competition_id') == $competition->id ? 'selected' : '' }}>
                                    {{ $competition->name }} ({{ $competition->type }})
                                </option>
                            @endforeach
                        </select>
                        @error('competition_id')
                            <p style="margin-top: 6px; font-size: 13px; color: #ef4444;">{{ $message }}</p>
                        @enderror
                    </div>
                    {{-- recompensa o penalización --}}
                    <div>
                        <label for="reward_or_penalty" style="display: block; font-size: 14px; font-weight: 600; color: {{ $labelColor }}; margin-bottom: 8px;">{{ __('views.groups.reward_penalty_label') }}</label>
                        <textarea id="reward_or_penalty" name="reward_or_penalty" rows="4"
                            style="width: 100%; background: {{ $inputBg }}; border: 1px solid {{ $borderColor }}; border-radius: 8px; padding: 12px 16px; color: {{ $textPrimary }}; font-size: 14px; transition: all 0.3s ease; resize: vertical; font-family: inherit;"
                            onfocus="this.style.borderColor='{{ $accentColor }}'; this.style.boxShadow='0 0 0 3px rgba(0, 222, 176, 0.1)'"
                            onblur="this.style.borderColor='{{ $borderColor }}'; this.style.boxShadow='none'"
                            placeholder="{{ __('views.groups.reward_penalty_placeholder') }}">{{ old('reward_or_penalty') }}</textarea>
                    </div>

                    <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 12px; margin-top: 12px; border-top: 1px solid {{ $borderColor }};">
                        <a href="{{ route('groups.index') }}"
                           style="font-size: 14px; color: {{ $textSecondary }}; text-decoration: none; transition: color 0.3s ease;"
                           onmouseover="this.style.color='{{ $textPrimary }}'"
                           onmouseout="this.style.color='{{ $textSecondary }}'">
                            {{ __('views.groups.cancel') }}
                        </a>
                        <button type="submit"
                                style="background: linear-gradient(135deg, {{ $accentDark }}, {{ $accentColor }}); color: white; padding: 12px 24px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px;"
                                onmouseover="this.style.opacity='0.9'"
                                onmouseout="this.style.opacity='1'"
                                id="submitButton">
                            <span id="buttonText">{{ __('views.groups.create_group_button') }}</span>
                            <span id="loadingSpinner" style="display: none;">
                                <i class="fas fa-spinner" style="animation: spin 1s linear infinite;"></i>
                            </span>
                        </button>
                    </div>
                </form>

                <style>
                    @keyframes spin {
                        from { transform: rotate(0deg); }
                        to { transform: rotate(360deg); }
                    }

                    html body input:focus,
                    html body select:focus,
                    html body textarea:focus {
                        outline: none;
                    }
                </style>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const form = document.getElementById('createGroupForm');
                        const submitButton = document.getElementById('submitButton');
                        const buttonText = document.getElementById('buttonText');
                        const loadingSpinner = document.getElementById('loadingSpinner');
                        let isSubmitting = false;

                        // Generar un token único para este formulario
                        const formToken = Math.random().toString(36).substring(2);
                        const tokenInput = document.createElement('input');
                        tokenInput.type = 'hidden';
                        tokenInput.name = 'form_token';
                        tokenInput.value = formToken;
                        form.appendChild(tokenInput);

                        form.addEventListener('submit', function(e) {
                            if (isSubmitting) {
                                e.preventDefault();
                                return false;
                            }

                            isSubmitting = true;
                            submitButton.disabled = true;
                            buttonText.textContent = 'Creando...';
                            loadingSpinner.style.display = 'inline';

                            // Deshabilitar el botón después de 5 segundos si no hay respuesta
                            setTimeout(() => {
                                if (isSubmitting) {
                                    isSubmitting = false;
                                    submitButton.disabled = false;
                                    buttonText.textContent = 'Crear grupo';
                                    loadingSpinner.style.display = 'none';
                                }
                            }, 5000);
                        });
                    });
                </script>
            </div>
        </div>
    </div>
</x-dynamic-layout>
