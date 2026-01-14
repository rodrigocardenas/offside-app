@php
    $themeColors = $themeColors ?? [];
    $isDark = $themeColors['isDark'] ?? ($isDark ?? true);
    $bgPrimary = $themeColors['bgPrimary'] ?? ($isDark ? '#0a2e2c' : '#f5f5f5');
    $bgSecondary = $themeColors['bgSecondary'] ?? ($isDark ? '#0f3d3a' : '#f5f5f5');
    $bgTertiary = $themeColors['bgTertiary'] ?? ($isDark ? '#1a524e' : '#ffffff');
    $componentsBackground = $themeColors['componentsBackground'] ?? ($isDark ? '#1a524e' : '#ffffff');
    $textPrimary = $themeColors['textPrimary'] ?? ($isDark ? '#ffffff' : '#333333');
    $textSecondary = $themeColors['textSecondary'] ?? ($isDark ? '#b0b0b0' : '#999999');
    $borderColor = $themeColors['borderColor'] ?? ($isDark ? '#2a4a47' : '#e0e0e0');
    $accentColor = $themeColors['accentColor'] ?? '#00deb0';
    $accentDark = $themeColors['accentDark'] ?? '#17b796';
@endphp

    // Generar URL de invitación
    $inviteUrl = route('groups.invite', $group->code);
    $joinGroupText = __('views.groups.join_group_invite', ['group' => $group->name]);
    $competeText = __('views.groups.compete_with_us');
    $inviteMessage = "{$joinGroupText}\n\n{$inviteUrl}\n\n{$competeText}";
@endphp

<!-- Invitación Social Section -->
<div style="background: {{ $componentsBackground }}; border-radius: 16px; padding: 24px; margin-top: 16px; border: 1px solid {{ $borderColor }}; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
    <!-- Header con icono -->
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
        <div style="width: 40px; height: 40px; background: {{ $accentColor }}; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <i class="fas fa-users" style="color: {{ $isDark ? '#000' : '#003b2f' }}; font-size: 20px;"></i>
        </div>
        <div>
            <h2 style="font-size: 18px; font-weight: 600; color: {{ $textPrimary }}; margin: 0;">Preguntas Sociales</h2>
            <p style="font-size: 12px; color: {{ $textSecondary }}; margin: 4px 0 0 0;">Requiere mínimo 2 miembros</p>
        </div>
    </div>

    <!-- Contenido principal -->
    <div style="background: {{ $bgSecondary }}; border-radius: 12px; padding: 20px; border: 1px solid {{ $borderColor }}; margin-bottom: 16px;">
        <p style="color: {{ $textSecondary }}; font-size: 14px; line-height: 1.6; margin: 0 0 16px 0;">
            {{ __('views.groups.invite_more_members') }}
        </p>

        <!-- Indicador de miembros -->
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 4px; background: {{ $bgPrimary }}; padding: 8px 12px; border-radius: 8px; border: 1px solid {{ $borderColor }};">
                <i class="fas fa-users" style="color: {{ $accentColor }}; font-size: 14px;"></i>
                <span style="color: {{ $textPrimary }}; font-size: 13px; font-weight: 600;">
                    {{ $group->users->count() }}
                    <span style="color: {{ $textSecondary }};">de 4+ miembros</span>
                </span>
            </div>
        </div>

        <!-- Link de invitación -->
        <div style="background: {{ $bgPrimary }}; border-radius: 8px; padding: 12px; border: 1px solid {{ $borderColor }}; margin-bottom: 16px;">
            <p style="font-size: 11px; color: {{ $textSecondary }}; text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 8px 0; font-weight: 600;">
                <i class="fas fa-link" style="margin-right: 4px;"></i> Link de invitación
            </p>
            <div style="display: flex; align-items: center; gap: 8px; overflow: hidden;">
                <span class="invite-url" style="background: {{ $bgSecondary }}; color: {{ $accentColor }}; font-family: 'Courier New', monospace; padding: 10px 12px; border-radius: 6px; font-weight: bold; font-size: 12px; flex: 1; text-align: left; border: 1px solid {{ $borderColor }}; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    {{ $inviteUrl }}
                </span>
                <button class="copy-url-btn" style="background: {{ $accentColor }}; color: {{ $isDark ? '#000' : '#003b2f' }}; border: none; border-radius: 6px; padding: 10px 12px; cursor: pointer; font-weight: 600; transition: all 0.2s ease; font-size: 12px; flex-shrink: 0;"
                    onmouseover="this.style.opacity='0.8'"
                    onmouseout="this.style.opacity='1'"
                    title="Copiar link">
                    <i class="fas fa-copy"></i> Copiar
                </button>
            </div>
        </div>

        <!-- Mensaje de instrucciones -->
        <div style="background: {{ $bgPrimary }}; border-left: 3px solid {{ $accentColor }}; border-radius: 6px; padding: 12px; border-top-left-radius: 0; border-bottom-left-radius: 0;">
            <p style="color: {{ $textSecondary }}; font-size: 12px; margin: 0; line-height: 1.5;">
                <i class="fas fa-info-circle" style="color: {{ $accentColor }}; margin-right: 8px;"></i>
                Comparte este link con tus amigos para que se unan al grupo.
            </p>
        </div>
    </div>

    <!-- Botones de compartir -->
    <div style="display: flex; gap: 8px;">
        <button class="copy-invite-btn" style="flex: 1; background: {{ $accentColor }}; color: {{ $isDark ? '#000' : '#003b2f' }}; border: none; border-radius: 12px; padding: 12px 16px; font-weight: 600; cursor: pointer; font-size: 14px; transition: all 0.2s ease; display: flex; align-items: center; justify-content: center; gap: 6px;"
            onmouseover="this.style.opacity='0.85'"
            onmouseout="this.style.opacity='1'"
            title="Copiar mensaje de invitación">
            <i class="fas fa-copy"></i> Copiar
        </button>
        <button class="whatsapp-invite-btn" style="flex: 1; background: #25D366; color: #fff; border: none; border-radius: 12px; padding: 12px 16px; font-weight: 600; cursor: pointer; font-size: 14px; transition: all 0.2s ease; display: flex; align-items: center; justify-content: center; gap: 6px;"
            onmouseover="this.style.opacity='0.9'"
            onmouseout="this.style.opacity='1'"
            title="Compartir en WhatsApp">
            <i class="fab fa-whatsapp"></i> WhatsApp
        </button>
    </div>
</div>

<script>
(function() {
    'use strict';

    // Función auxiliar para copiar al portapapeles
    function copyToClipboard(text) {
        return new Promise((resolve, reject) => {
            // Método 1: Usar Clipboard API si está disponible
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text)
                    .then(() => resolve(true))
                    .catch(() => {
                        // Si falla Clipboard API, intentar método alternativo
                        copyToClipboardFallback(text) ? resolve(true) : reject(false);
                    });
            } else {
                // Fallback para navegadores antiguos
                copyToClipboardFallback(text) ? resolve(true) : reject(false);
            }
        });
    }

    // Método alternativo para copiar (sin usar Clipboard API)
    function copyToClipboardFallback(text) {
        try {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            const success = document.execCommand('copy');
            document.body.removeChild(textarea);
            return success;
        } catch (err) {
            console.error('Error al copiar (fallback):', err);
            return false;
        }
    }

    // Event listeners para copiar URL e invitar
    document.addEventListener('DOMContentLoaded', function() {
        const copyUrlBtn = document.querySelector('.copy-url-btn');
        const urlSpan = document.querySelector('.invite-url');
        const copyInviteBtn = document.querySelector('.copy-invite-btn');
        const whatsappBtn = document.querySelector('.whatsapp-invite-btn');

        // Copiar URL de invitación
        if (copyUrlBtn && urlSpan) {
            copyUrlBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const url = urlSpan.textContent.trim();

                copyToClipboard(url).then(() => {
                    const originalText = copyUrlBtn.innerHTML;
                    const originalBg = copyUrlBtn.style.background;

                    copyUrlBtn.innerHTML = '<i class="fas fa-check"></i> ¡Copiado!';
                    copyUrlBtn.style.background = '#00c800';
                    copyUrlBtn.disabled = true;

                    setTimeout(() => {
                        copyUrlBtn.innerHTML = originalText;
                        copyUrlBtn.style.background = originalBg;
                        copyUrlBtn.disabled = false;
                    }, 2000);
                }).catch(err => {
                    console.error('Error al copiar:', err);
                    alert('{{ __('views.groups.copy_link_failed') }}');
                });
            });
        }

        // Copiar mensaje de invitación completo con link
        if (copyInviteBtn && urlSpan) {
            copyInviteBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const groupName = document.querySelector('h2').textContent.trim();
                const url = urlSpan.textContent.trim();
                const inviteText = `¡Únete al grupo "${groupName}" en Offside Club!\n\n${url}\n\n¡Ven a competir con nosotros!`;

                copyToClipboard(inviteText).then(() => {
                    const originalText = copyInviteBtn.innerHTML;
                    const originalBg = copyInviteBtn.style.background;

                    copyInviteBtn.innerHTML = '<i class="fas fa-check"></i> ¡Copiado!';
                    copyInviteBtn.style.background = '#00c800';
                    copyInviteBtn.disabled = true;

                    setTimeout(() => {
                        copyInviteBtn.innerHTML = originalText;
                        copyInviteBtn.style.background = originalBg;
                        copyInviteBtn.disabled = false;
                    }, 2000);
                }).catch(err => {
                    console.error('Error al copiar:', err);
                    alert('{{ __('views.groups.copy_message_failed') }}');
                });
            });
        }

        // Compartir en WhatsApp
        if (whatsappBtn && urlSpan) {
            whatsappBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const groupName = document.querySelector('h2').textContent.trim();
                const url = urlSpan.textContent.trim();
                const message = `¡Únete al grupo "${groupName}" en Offside Club!\n\n${url}\n\n¡Ven a competir con nosotros!`;
                const encodedMessage = encodeURIComponent(message);
                const whatsappUrl = `https://wa.me/?text=${encodedMessage}`;

                window.open(whatsappUrl, '_blank', 'noopener,noreferrer');
            });
        }
    });
})();
</script>
