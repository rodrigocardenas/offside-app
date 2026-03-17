<x-dynamic-layout>
    @php
        $themeMode = auth()->user()->theme_mode ?? 'light';
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
                <!-- Header -->
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px;">
                    <div style="flex: 1;">
                        <h2 style="font-size: 28px; font-weight: 700; color: {{ $textPrimary }}; margin: 0 0 4px 0;">{{ __('Editar Grupo') }}</h2>
                        <p style="font-size: 13px; color: {{ $textSecondary }}; margin: 0;">{{ $group->name }}</p>
                    </div>
                    <i class="fas fa-cog" style="font-size: 32px; color: {{ $accentColor }};"></i>
                </div>

                <!-- Form -->
                <form method="POST" action="{{ route('groups.update', $group) }}" id="editGroupForm" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 20px;">
                    @csrf
                    @method('PUT')

                    <!-- Group Name -->
                    <div>
                        <label for="name" style="display: block; font-size: 14px; font-weight: 600; color: {{ $labelColor }}; margin-bottom: 8px;">{{ __('Nombre del Grupo') }}</label>
                        <input id="name" type="text" name="name" value="{{ old('name', $group->name) }}" 
                            style="width: 100%; background: {{ $inputBg }}; border: 1px solid {{ $borderColor }}; border-radius: 8px; padding: 12px 16px; color: {{ $textPrimary }}; font-size: 14px; transition: all 0.3s ease; box-sizing: border-box;"
                            onfocus="this.style.borderColor='{{ $accentColor }}'; this.style.boxShadow='0 0 0 3px rgba(0, 222, 176, 0.1)'"
                            onblur="this.style.borderColor='{{ $borderColor }}'; this.style.boxShadow='none'"
                            placeholder="Nombre del grupo" />
                        @error('name')
                            <p style="margin-top: 6px; font-size: 13px; color: #ef4444;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Cover Image Upload -->
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 600; color: {{ $labelColor }}; margin-bottom: 12px;">{{ __('Imagen de Portada') }}</label>
                        
                        <!-- Current Cover Image Display -->
                        @if ($group->getCoverImageUrl())
                            <div style="margin-bottom: 12px;">
                                <div style="position: relative; width: 100%; height: 200px; border-radius: 8px; overflow: hidden; border: 1px solid {{ $borderColor }};">
                                    <img src="{{ $group->getCoverImageUrl('medium') }}" 
                                         alt="{{ $group->name }}"
                                         style="width: 100%; height: 100%; object-fit: cover;" 
                                         loading="lazy" />
                                    @if ($group->cover_provider === 'cloudflare')
                                        <div style="position: absolute; top: 8px; right: 8px; background: rgba(0, 0, 0, 0.6); color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">
                                            {{ 'Cloudflare' }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- File Input -->
                        <div style="position: relative; border: 2px dashed {{ $borderColor }}; border-radius: 8px; padding: 24px; text-align: center; cursor: pointer; transition: all 0.3s ease; background: {{ $bgSecondary }};"
                             id="coverDropZone"
                             onmouseover="this.style.borderColor='{{ $accentColor }}'; this.style.background='{{ $isDark ? '#1a524e' : '#f0f8f6' }}'"
                             onmouseout="this.style.borderColor='{{ $borderColor }}'; this.style.background='{{ $bgSecondary }}'">
                            <i class="fas fa-image" style="font-size: 32px; color: {{ $accentColor }}; margin-bottom: 8px; display: block;"></i>
                            <p style="margin: 0 0 4px 0; color: {{ $textPrimary }}; font-weight: 500;">{{ __('Arrastra o haz clic para subir') }}</p>
                            <p style="margin: 0; font-size: 12px; color: {{ $textSecondary }};">PNG, JPG, WebP (máx. 5MB)</p>
                            <input id="cover_image" type="file" name="cover_image" accept=".png,.jpg,.jpeg,.webp,.gif" style="display: none;">
                        </div>
                        
                        <!-- File input label replacement -->
                        <script>
                            const coverDropZone = document.getElementById('coverDropZone');
                            const coverInput = document.getElementById('cover_image');
                            
                            coverDropZone.addEventListener('click', () => coverInput.click());
                            
                            coverDropZone.addEventListener('dragover', (e) => {
                                e.preventDefault();
                                coverDropZone.style.borderColor = '{{ $accentColor }}';
                                coverDropZone.style.background = '{{ $isDark ? '#1a524e' : '#f0f8f6' }}';
                            });
                            
                            coverDropZone.addEventListener('dragleave', () => {
                                coverDropZone.style.borderColor = '{{ $borderColor }}';
                                coverDropZone.style.background = '{{ $bgSecondary }}';
                            });
                            
                            coverDropZone.addEventListener('drop', (e) => {
                                e.preventDefault();
                                coverDropZone.style.borderColor = '{{ $borderColor }}';
                                coverDropZone.style.background = '{{ $bgSecondary }}';
                                coverInput.files = e.dataTransfer.files;
                                updateFileName();
                            });
                            
                            coverInput.addEventListener('change', updateFileName);
                            
                            function updateFileName() {
                                if (coverInput.files.length > 0) {
                                    const file = coverInput.files[0];
                                    const sizeInMB = (file.size / 1024 / 1024).toFixed(2);
                                    coverDropZone.innerHTML = `
                                        <i class="fas fa-check-circle" style="font-size: 32px; color: {{ $accentColor }}; margin-bottom: 8px; display: block;"></i>
                                        <p style="margin: 0 0 4px 0; color: {{ $textPrimary }}; font-weight: 500;">${file.name}</p>
                                        <p style="margin: 0; font-size: 12px; color: {{ $textSecondary }};">${sizeInMB}MB</p>
                                    `;
                                }
                            }
                        </script>
                        
                        @error('cover_image')
                            <p style="margin-top: 6px; font-size: 13px; color: #ef4444;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Actions -->
                    <div style="display: flex; gap: 12px; margin-top: 20px;">
                        <a href="{{ route('groups.show', $group) }}" 
                           style="flex: 1; padding: 12px 16px; border: 1px solid {{ $borderColor }}; border-radius: 8px; background: {{ $bgSecondary }}; color: {{ $textPrimary }}; text-align: center; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.3s ease; cursor: pointer;"
                           onmouseover="this.style.background='{{ $isDark ? '#0f3d3a' : '#e5e5e5' }}'"
                           onmouseout="this.style.background='{{ $bgSecondary }}'">
                            {{ __('Cancelar') }}
                        </a>
                        <button type="submit" 
                                style="flex: 1; padding: 12px 16px; border: none; border-radius: 8px; background: {{ $accentColor }}; color: #000; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s ease;"
                                onmouseover="this.style.background='{{ $accentDark }}'"
                                onmouseout="this.style.background='{{ $accentColor }}'">
                            {{ __('Guardar Cambios') }}
                        </button>
                    </div>

                    <!-- Status Messages -->
                    @if (session('status') === 'group-updated')
                        <div style="margin-top: 16px; padding: 12px 16px; border-radius: 8px; background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; font-size: 14px;">
                            <i class="fas fa-check-circle" style="margin-right: 8px;"></i>
                            {{ __('Grupo actualizado exitosamente.') }}
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</x-dynamic-layout>
