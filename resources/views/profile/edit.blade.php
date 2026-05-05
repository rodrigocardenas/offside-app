@php
    $themeMode = auth()->user()->theme_mode ?? 'light';
    $isDark = $themeMode === 'dark';
    $layout = $isDark ? 'mobile-dark-layout' : 'mobile-light-layout';
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
                $msgBgProfile = $isDark ? 'background: rgba(40, 167, 69, 0.15); color: #5cdd6f;' : 'background: #d4edda; color: #155724;';
            @endphp
            <div style="{{ $msgBgProfile }} border-left: 4px solid #28a745; padding: 12px 16px; margin: 16px; border-radius: 8px; font-size: 14px;">
                <i class="fas fa-check-circle" style="margin-right: 8px;"></i>
                {{ session('success') }}
            </div>
        @endif

        {{-- PROFILE FORM --}}
        <div class="profile-section">
            {{-- <div class="section-title mt-8 mb-4" style="display: flex; align-items: center; gap: 8px; justify-content: center; margin-top: 24px; margin-bottom: 16px;">
                <i class="fas fa-user"></i>
                Editar Perfil
            </div> --}}
            {{-- <p style="color: #666; font-size: 14px; margin: 0 16px 20px 16px;">Actualiza tu información personal</p> --}}

            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" style="margin: 16px; padding-bottom: 80px;">
                @csrf
                @method('PUT')

                {{-- AVATAR SECTION --}}
                @php
                    $cardBg = $isDark ? '#1a3d3a' : '#fff';
                    $cardBorder = $isDark ? '#2a4a47' : '#e0e0e0';
                    $textColor = $isDark ? '#b0b0b0' : '#666';
                    $labelColor = $isDark ? '#ffffff' : '#333';
                    $inputBg = $isDark ? '#0f3d3a' : 'white';
                @endphp
                <div style="background: {{ $cardBg }}; border-radius: 12px; padding: 20px; margin-bottom: 12px; border: 1px solid {{ $cardBorder }}; text-align: center;">
                    <div style="position: relative; display: inline-block; margin-bottom: 16px;">
                        @if($user->avatar || $user->avatar_cloudflare_id)
                            <img src="{{ $user->getAvatarUrl('small') }}"
                                 alt="{{ $user->name }}"
                                 class="avatar-preview"
                                 style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #00deb0; display: block;"
                                 loading="lazy">
                        @else
                            <div class="avatar-placeholder" style="width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #17b796, #00deb0); display: flex; align-items: center; justify-content: center; color: white; font-size: 40px; font-weight: bold; border: 3px solid #00deb0; margin: 0 auto;">
                                {{ substr($user->name, 0, 1) }}
                            </div>
                        @endif

                        {{-- Cloudflare indicator --}}
                        @if($user->avatar_provider === 'cloudflare' && config('cloudflare.enabled'))
                            <div title="Optimized by Cloudflare Images" style="position: absolute; top: -8px; left: -8px; background: #faad14; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid white; box-shadow: 0 2px 8px rgba(250, 173, 20, 0.3);">
                                <svg style="width: 16px; height: 16px; color: white;" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        @endif

                        <label for="avatar" style="position: absolute; bottom: -8px; right: -8px; background: #00deb0; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 8px rgba(0, 222, 176, 0.3); transition: all 0.3s ease; border: 3px solid white;">
                            <i class="fas fa-camera" style="color: white; font-size: 16px;"></i>
                        </label>
                        <input type="file" id="avatar" name="avatar" accept="image/*" style="display: none;">
                    </div>
                    <p style="color: {{ $textColor }}; font-size: 13px; margin: 8px 0 0 0; padding: 0 16px; word-wrap: break-word; overflow-wrap: break-word;">
                        {{ __('views.profile_section.change_photo') }}
                        @if(config('cloudflare.enabled'))
                            <span style="font-size: 12px; color: #00deb0; margin-left: 6px;">
                                ({{ __('views.profile_section.cloudflare_optimized') }})
                            </span>
                        @endif
                    </p>
                    @error('avatar')
                        <p style="color: #dc3545; font-size: 12px; margin-top: 8px;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- NOMBRE --}}
                <div style="background: {{ $cardBg }}; border-radius: 12px; padding: 14px; margin-bottom: 12px; border: 1px solid {{ $cardBorder }};">
                    <label style="display: block; font-weight: 600; color: {{ $labelColor }}; font-size: 14px; margin-bottom: 8px;">
                        <i class="fas fa-user" style="color: #00deb0; margin-right: 6px;"></i>
                        {{ __('views.profile_section.name_label') }}
                    </label>
                    <input type="text" id="name" name="name"
                           value="{{ old('name', $user->name) }}"
                           style="width: 100%; border: 1px solid {{ $cardBorder }}; border-radius: 8px; padding: 10px; font-size: 14px; color: {{ $labelColor }}; background: {{ $inputBg }}; box-sizing: border-box;">
                    @error('name')
                        <p style="color: #dc3545; font-size: 12px; margin-top: 6px;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- EMAIL --}}
                <div style="background: {{ $cardBg }}; border-radius: 12px; padding: 14px; margin-bottom: 12px; border: 1px solid {{ $cardBorder }};">
                    <label style="display: block; font-weight: 600; color: {{ $labelColor }}; font-size: 14px; margin-bottom: 8px;">
                        <i class="fas fa-envelope" style="color: #00deb0; margin-right: 6px;"></i>
                        {{ __('views.profile_section.email_label') }}
                    </label>
                    <input type="email" id="email" name="email"
                           value="{{ old('email', $user->email) }}"
                           style="width: 100%; border: 1px solid {{ $cardBorder }}; border-radius: 8px; padding: 10px; font-size: 14px; color: {{ $labelColor }}; background: {{ $inputBg }}; box-sizing: border-box;">
                    @error('email')
                        <p style="color: #dc3545; font-size: 12px; margin-top: 6px;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- COMPETENCIA FAVORITA --}}
                <div style="background: {{ $cardBg }}; border-radius: 12px; padding: 14px; margin-bottom: 12px; border: 1px solid {{ $cardBorder }};">
                    <label style="display: block; font-weight: 600; color: {{ $labelColor }}; font-size: 14px; margin-bottom: 8px;">
                        <i class="fas fa-trophy" style="color: #00deb0; margin-right: 6px;"></i>
                        {{ __('views.profile_section.favorite_competition') }}
                    </label>
                    <select id="favorite_competition_id" name="favorite_competition_id"
                            style="width: 100%; border: 1px solid {{ $cardBorder }}; border-radius: 8px; padding: 10px; font-size: 14px; color: {{ $labelColor }}; box-sizing: border-box; background: {{ $inputBg }};">
                        <option value="">{{ __('views.profile_section.select_competition') }}</option>
                        @foreach($competitions as $competition)
                            <option value="{{ $competition->id }}" {{ old('favorite_competition_id', $user->favorite_competition_id) == $competition->id ? 'selected' : '' }}>
                                {{ $competition->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('favorite_competition_id')
                        <p style="color: #dc3545; font-size: 12px; margin-top: 6px;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- CLUB FAVORITO --}}
                <div style="background: {{ $cardBg }}; border-radius: 12px; padding: 14px; margin-bottom: 12px; border: 1px solid {{ $cardBorder }};">
                    <label style="display: block; font-weight: 600; color: {{ $labelColor }}; font-size: 14px; margin-bottom: 8px;">
                        <i class="fas fa-shield-alt" style="color: #00deb0; margin-right: 6px;"></i>
                        {{ __('views.profile_section.favorite_club') }}
                    </label>
                    <select id="favorite_club_id" name="favorite_club_id"
                            style="width: 100%; border: 1px solid {{ $cardBorder }}; border-radius: 8px; padding: 10px; font-size: 14px; color: {{ $labelColor }}; box-sizing: border-box; background: {{ $inputBg }};">
                        <option value="">{{ __('views.profile_section.select_club') }}</option>
                        @foreach($clubs as $club)
                            <option value="{{ $club->id }}" {{ old('favorite_club_id', $user->favorite_club_id) == $club->id ? 'selected' : '' }}>
                                {{ $club->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('favorite_club_id')
                        <p style="color: #dc3545; font-size: 12px; margin-top: 6px;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- SELECCIÓN NACIONAL FAVORITA --}}
                <div style="background: {{ $cardBg }}; border-radius: 12px; padding: 14px; margin-bottom: 12px; border: 1px solid {{ $cardBorder }};">
                    <label style="display: block; font-weight: 600; color: {{ $labelColor }}; font-size: 14px; margin-bottom: 8px;">
                        <i class="fas fa-flag" style="color: #00deb0; margin-right: 6px;"></i>
                        {{ __('views.profile_section.favorite_national_team') }}
                    </label>
                    <select id="favorite_national_team_id" name="favorite_national_team_id"
                            style="width: 100%; border: 1px solid {{ $cardBorder }}; border-radius: 8px; padding: 10px; font-size: 14px; color: {{ $labelColor }}; box-sizing: border-box; background: {{ $inputBg }};">
                        <option value=""></option>
                        @foreach($nationalTeams as $team)
                            <option value="{{ $team->id }}" {{ old('favorite_national_team_id', $user->favorite_national_team_id) == $team->id ? 'selected' : '' }}>
                                {{ $team->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('favorite_national_team_id')
                        <p style="color: #dc3545; font-size: 12px; margin-top: 6px;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- BOTÓN GUARDAR --}}
                <button type="submit" style="width: 100%; padding: 12px 16px; background: linear-gradient(135deg, #17b796, #00deb0); color: white; border: none; border-radius: 10px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 16px;">
                    <i class="fas fa-save"></i>
                    {{ __('views.profile_section.save_changes') }}
                </button>
            </form>

            {{-- ZONA DE PELIGRO: ELIMINAR CUENTA --}}
            <div style="margin: 24px 16px 0 16px; background: {{ $isDark ? '#2d1b1b' : '#fff5f5' }}; border: 1px solid {{ $isDark ? '#6b2020' : '#fecaca' }}; border-radius: 12px; padding: 16px;">
                <p style="color: {{ $isDark ? '#f87171' : '#dc2626' }}; font-weight: 600; font-size: 14px; margin: 0 0 8px 0;">
                    <i class="fas fa-exclamation-triangle" style="margin-right: 6px;"></i>
                    {{ __('views.profile_section.delete_account') }}
                </p>
                <p style="color: {{ $isDark ? '#fca5a5' : '#991b1b' }}; font-size: 13px; margin: 0 0 12px 0; line-height: 1.5;">
                    {{ __('views.profile_section.delete_account_warning') }}
                </p>
                <button type="button" onclick="openDeleteModal()"
                    style="width: 100%; padding: 10px 16px; background: transparent; color: {{ $isDark ? '#f87171' : '#dc2626' }}; border: 1.5px solid {{ $isDark ? '#f87171' : '#dc2626' }}; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s;">
                    <i class="fas fa-trash-alt"></i>
                    {{ __('views.profile_section.delete_account') }}
                </button>
            </div>
        </div>
        {{-- BOTTOM NAVIGATION --}}
        <x-layout.bottom-navigation active-item="profile" />
    </div>

    {{-- MODAL PASO 1: CONFIRMACIÓN CON "DELETE" --}}
    <div id="delete-modal-step1" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center; padding:16px;">
        <div style="background: {{ $isDark ? '#1a2a2a' : '#ffffff' }}; border-radius: 16px; padding: 24px; width:100%; max-width:400px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
            <div style="text-align:center; margin-bottom:16px;">
                <div style="width:56px; height:56px; background:{{ $isDark ? '#6b2020' : '#fee2e2' }}; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 12px;">
                    <i class="fas fa-trash-alt" style="color:{{ $isDark ? '#f87171' : '#dc2626' }}; font-size:22px;"></i>
                </div>
                <h3 style="color:{{ $isDark ? '#f1f1f1' : '#111' }}; font-size:18px; font-weight:700; margin:0 0 8px;">
                    {{ __('views.profile_section.delete_account_title') }}
                </h3>
                <p style="color:{{ $isDark ? '#aaa' : '#666' }}; font-size:13px; line-height:1.5; margin:0;">
                    {{ __('views.profile_section.delete_account_warning') }}
                </p>
            </div>

            <p style="color:{{ $isDark ? '#ccc' : '#444' }}; font-size:13px; margin:0 0 8px;">{!! __('views.profile_section.delete_account_confirm_label') !!}</p>
            <input type="text" id="delete-confirm-input"
                placeholder="{{ __('views.profile_section.delete_account_confirm_placeholder') }}"
                autocomplete="off"
                oninput="checkDeleteInput()"
                style="width:100%; border:1.5px solid {{ $isDark ? '#555' : '#d1d5db' }}; border-radius:8px; padding:10px 12px; font-size:14px; color:{{ $isDark ? '#f1f1f1' : '#111' }}; background:{{ $isDark ? '#0f2b2a' : '#f9fafb' }}; box-sizing:border-box; margin-bottom:16px; letter-spacing:1px;">

            <div style="display:flex; gap:10px;">
                <button type="button" onclick="closeDeleteModal()"
                    style="flex:1; padding:10px; background:{{ $isDark ? '#2a3a3a' : '#f3f4f6' }}; color:{{ $isDark ? '#ccc' : '#374151' }}; border:none; border-radius:8px; font-weight:600; font-size:14px; cursor:pointer;">
                    {{ __('views.profile_section.delete_account_cancel') }}
                </button>
                <button type="button" id="delete-step1-btn" onclick="openDeleteModalStep2()" disabled
                    style="flex:1; padding:10px; background:#dc2626; color:white; border:none; border-radius:8px; font-weight:600; font-size:14px; cursor:not-allowed; opacity:0.4; transition:all 0.2s;">
                    {{ __('views.profile_section.delete_account_button') }}
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL PASO 2: ÚLTIMA CONFIRMACIÓN --}}
    <div id="delete-modal-step2" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:10000; align-items:center; justify-content:center; padding:16px;">
        <div style="background: {{ $isDark ? '#1a2a2a' : '#ffffff' }}; border-radius: 16px; padding: 24px; width:100%; max-width:400px; box-shadow: 0 20px 60px rgba(0,0,0,0.4);">
            <div style="text-align:center; margin-bottom:20px;">
                <div style="width:56px; height:56px; background:#dc2626; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 12px;">
                    <i class="fas fa-exclamation" style="color:white; font-size:24px;"></i>
                </div>
                <h3 style="color:{{ $isDark ? '#f1f1f1' : '#111' }}; font-size:18px; font-weight:700; margin:0 0 8px;">
                    {{ __('views.profile_section.delete_account_step2_title') }}
                </h3>
                <p style="color:{{ $isDark ? '#fca5a5' : '#991b1b' }}; font-size:13px; line-height:1.5; margin:0;">
                    {{ __('views.profile_section.delete_account_step2_warning') }}
                </p>
            </div>

            <form method="POST" action="{{ route('profile.destroy') }}">
                @csrf
                @method('DELETE')
                <input type="hidden" name="confirm_delete" value="DELETE">
                <div style="display:flex; gap:10px;">
                    <button type="button" onclick="closeDeleteModalStep2()"
                        style="flex:1; padding:10px; background:{{ $isDark ? '#2a3a3a' : '#f3f4f6' }}; color:{{ $isDark ? '#ccc' : '#374151' }}; border:none; border-radius:8px; font-weight:600; font-size:14px; cursor:pointer;">
                        {{ __('views.profile_section.delete_account_cancel') }}
                    </button>
                    <button type="submit"
                        style="flex:1; padding:10px; background:#dc2626; color:white; border:none; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer;">
                        {{ __('views.profile_section.delete_account_confirm_button') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODALES --}}
    @if(View::exists('components.feedback-modal'))
        <x-feedback-modal />
    @endif


    <style>
        .profile-section {
            margin-bottom: 80px;
        }

        /* Avatar Label - Hover Effect */
        label[for="avatar"] {
            transition: all 0.3s ease;
        }

        label[for="avatar"]:hover {
            background: #0eb88a !important;
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 222, 176, 0.4) !important;
        }

        label[for="avatar"]:active {
            transform: scale(0.95);
        }

        /* Avatar Preview */
        .avatar-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #00deb0;
            display: block;
        }

        .avatar-placeholder {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #17b796, #00deb0);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 40px;
            font-weight: bold;
            border: 3px solid #00deb0;
            margin: 0 auto;
        }

        /* Estilos para input y select */
        input[type="text"],
        input[type="email"],
        select {
            transition: all 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        select:focus {
            outline: none;
            border-color: #00deb0;
            box-shadow: 0 0 0 3px rgba(0, 222, 176, 0.1);
        }

        button[type="submit"] {
            transition: all 0.3s ease;
        }

        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 222, 176, 0.3);
        }

        button[type="submit"]:active {
            transform: translateY(0);
        }

        /* Responsivo */
        @media (max-width: 480px) {
            .avatar-placeholder,
            .avatar-preview {
                width: 80px;
                height: 80px;
                font-size: 32px;
            }

            label[for="avatar"] {
                width: 36px;
                height: 36px;
                bottom: -6px;
                right: -6px;
            }

            label[for="avatar"] i {
                font-size: 14px;
            }
        }
    </style>

    <script>
        // Variables globales desde Blade
        const initialCompetitionId = "{{ $user->favorite_competition_id ?? NULL }}";
        const initialClubId = "{{ $user->favorite_club_id ?? NULL }}";
        const competitionSelectEl = document.getElementById('favorite_competition_id');
        const clubSelectEl = document.getElementById('favorite_club_id');

        // Mostrar vista previa de la imagen seleccionada
        const avatarInput = document.getElementById('avatar');
        if (avatarInput) {
            avatarInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Validar que sea una imagen
                    if (!file.type.startsWith('image/')) {
                        window.showErrorToast('Por favor selecciona un archivo de imagen');
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.classList.add('avatar-preview');
                        img.style.display = 'block';

                        const avatarContainer = document.querySelector('div[style*="position: relative"]');
                        const existingElement = avatarContainer.querySelector('img, .avatar-placeholder');

                        if (existingElement) {
                            avatarContainer.replaceChild(img, existingElement);
                        }
                    }
                    reader.readAsDataURL(file);
                }
            });
        }

        // Función para cargar clubs de una competencia
        function loadClubsForCompetition(competitionId, selectedClubId = null) {
            if (!clubSelectEl) return;

            // Limpiar el selector de clubes
            clubSelectEl.innerHTML = '<option value="">Selecciona un club</option>';

            if (competitionId) {
                // Hacer la petición AJAX para obtener los clubes de la competencia
                fetch(`/api/competitions/${competitionId}/teams`)
                    .then(response => {
                        if (!response.ok) throw new Error('Network response error');
                        return response.json();
                    })
                    .then(data => {
                        if (Array.isArray(data)) {
                            data.forEach(team => {
                                const option = document.createElement('option');
                                option.value = team.id;
                                option.textContent = team.name;

                                // Si hay un equipo seleccionado, marcarlo como selected
                                if (selectedClubId && String(team.id) === String(selectedClubId)) {
                                    option.selected = true;
                                }

                                clubSelectEl.appendChild(option);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error loading clubs:', error);
                        clubSelectEl.innerHTML = '<option value="">Error al cargar clubes</option>';
                    });
            }
        }

        // Actualizar clubes cuando cambie la competencia
        if (competitionSelectEl) {
            competitionSelectEl.addEventListener('change', function(e) {
                const competitionId = e.target.value;
                loadClubsForCompetition(competitionId);
            });
        }

        // Cargar clubes al iniciar la página si hay competencia seleccionada
        document.addEventListener('DOMContentLoaded', function() {
            if (competitionSelectEl && initialCompetitionId) {
                loadClubsForCompetition(initialCompetitionId, initialClubId);
            }
        });

        // --- Delete account modal logic ---
        function openDeleteModal() {
            const modal = document.getElementById('delete-modal-step1');
            modal.style.display = 'flex';
            document.getElementById('delete-confirm-input').value = '';
            document.getElementById('delete-step1-btn').disabled = true;
            document.getElementById('delete-step1-btn').style.opacity = '0.4';
            document.getElementById('delete-step1-btn').style.cursor = 'not-allowed';
        }

        function closeDeleteModal() {
            document.getElementById('delete-modal-step1').style.display = 'none';
        }

        function checkDeleteInput() {
            const val = document.getElementById('delete-confirm-input').value.trim();
            const btn = document.getElementById('delete-step1-btn');
            if (val === 'DELETE') {
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.style.cursor = 'pointer';
            } else {
                btn.disabled = true;
                btn.style.opacity = '0.4';
                btn.style.cursor = 'not-allowed';
            }
        }

        function openDeleteModalStep2() {
            document.getElementById('delete-modal-step1').style.display = 'none';
            document.getElementById('delete-modal-step2').style.display = 'flex';
        }

        function closeDeleteModalStep2() {
            document.getElementById('delete-modal-step2').style.display = 'none';
        }

        // Close step1 modal on backdrop click
        document.getElementById('delete-modal-step1').addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });

        // Close step2 modal on backdrop click
        document.getElementById('delete-modal-step2').addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModalStep2();
        });
    </script>

    @if(session('status') === 'profile-updated')
        <script>
            // Recarga la página después de 1 segundo para aplicar el nuevo tema
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        </script>
    @endif
</x-dynamic-layout>
