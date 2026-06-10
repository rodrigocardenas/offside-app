<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>⚽ Mi predicción · {{ $match->home_team }} vs {{ $match->away_team }}</title>
    <meta property="og:title" content="Predije {{ $votedOption }} en {{ $match->home_team }} vs {{ $match->away_team }}">
    <meta property="og:description" content="Juega en Offside Club y predice los partidos del Mundial 2026.">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/estadio.avif') }}">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        :root{--gold:#e8c11a;--gold-dk:#c5a215;--navy:#0b1e3a;--navy-mid:#102545;--navy-light:#162e52;--white:#fff;--muted:#9ab0cc;--border:rgba(232,193,26,.22)}
        html,body{min-height:100%;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--navy);color:var(--white);overflow-x:hidden}
        .bg-stadium{position:fixed;inset:0;background:linear-gradient(to bottom,rgba(11,30,58,.78) 0%,rgba(11,30,58,.12) 60%,rgba(11,30,58,.99) 100%),url('{{ asset("images/estadio.avif") }}') center/cover no-repeat;z-index:0}
        .page{position:relative;z-index:1;min-height:100vh;display:flex;flex-direction:column;align-items:center;padding:20px 16px 64px}
        .corner{position:fixed;z-index:20;opacity:.90}
        .corner.tl{top:8px;left:14px;animation:slideDown .6s ease-out both}
        .corner.tr{top:8px;right:14px;animation:slideDown .6s .15s ease-out both}
        .corner img{height:38px;width:auto;filter:drop-shadow(0 2px 8px rgba(0,0,0,.5))}
        @keyframes slideDown{from{transform:translateY(-18px);opacity:0}to{transform:translateY(0);opacity:1}}
        /* share card */
        .share-card{width:100%;max-width:420px;background:rgba(16,37,69,.90);border:1.5px solid var(--border);border-radius:22px;overflow:hidden;margin:50px 0 24px;backdrop-filter:blur(14px);box-shadow:0 8px 40px rgba(0,0,0,.45)}
        .card-head{background:linear-gradient(135deg,rgba(11,30,58,1) 0%,rgba(22,46,82,1) 100%);padding:22px 22px 18px;text-align:center;border-bottom:1px solid rgba(232,193,26,.18)}
        .card-wc{margin-bottom:12px;display:flex;justify-content:center}
        .card-wc-logo{height:34px;width:auto;object-fit:contain;filter:drop-shadow(0 2px 8px rgba(0,0,0,.35))}
        /* teams in card */
        .card-teams-row{display:flex;align-items:center;justify-content:center;gap:10px;margin-bottom:8px}
        .card-team{display:flex;flex-direction:column;align-items:center;gap:6px;flex:1}
        .card-crest{width:52px;height:52px;object-fit:contain;background:rgba(255,255,255,.06);border-radius:50%;padding:6px;border:1.5px solid rgba(232,193,26,.2)}
        .card-tname{font-size:12px;font-weight:700;color:var(--white);text-transform:uppercase;letter-spacing:.4px;text-align:center}
        .card-vs{font-size:11px;font-weight:900;color:var(--muted);letter-spacing:2px;flex-shrink:0;padding:0 4px}
        .card-date{font-size:12px;color:var(--muted)}
        /* body */
        .card-body{padding:24px 22px 28px;text-align:center}
        .pick-label{font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);margin-bottom:12px}
        .pick-value{font-size:24px;font-weight:900;color:var(--gold);background:rgba(232,193,26,.1);border:1.5px solid rgba(232,193,26,.38);border-radius:13px;padding:14px 20px;display:block;margin-bottom:18px;animation:pop .4s cubic-bezier(.34,1.56,.64,1)}
        @keyframes pop{from{transform:scale(.85);opacity:0}to{transform:scale(1);opacity:1}}
        .branding{display:flex;justify-content:center}
        .branding-logo{height:132px;width:auto;max-width:100%;object-fit:contain;opacity:.98;filter:drop-shadow(0 3px 12px rgba(0,0,0,.38))}
        /* actions */
        .actions{width:100%;max-width:420px;display:flex;flex-direction:column;gap:10px}
        .btn-share{display:flex;align-items:center;justify-content:center;gap:9px;padding:15px;background:linear-gradient(135deg,var(--gold),var(--gold-dk));color:var(--navy);font-size:15px;font-weight:800;border-radius:13px;border:none;cursor:pointer;transition:all .2s;box-shadow:0 4px 18px rgba(232,193,26,.32)}
        .btn-share:hover{transform:translateY(-2px);box-shadow:0 7px 24px rgba(232,193,26,.42)}
        .btn-share[disabled]{opacity:.75;cursor:wait}
        .btn-outline{display:flex;align-items:center;justify-content:center;gap:8px;padding:14px;background:transparent;border:1.5px solid rgba(232,193,26,.32);color:var(--white);font-size:14px;font-weight:600;border-radius:13px;cursor:pointer;text-decoration:none;transition:all .2s}
        .btn-outline:hover{border-color:var(--gold);color:var(--gold);background:rgba(232,193,26,.06)}
        .btn-ghost{display:flex;align-items:center;justify-content:center;gap:7px;padding:12px;background:transparent;color:var(--muted);font-size:13px;font-weight:600;border-radius:13px;border:none;cursor:pointer;text-decoration:none;transition:color .2s}
        .btn-ghost:hover{color:var(--white)}
        .divider{border:none;border-top:1px solid rgba(255,255,255,.07);margin:2px 0}
        /* toast */
        .toast{position:fixed;bottom:30px;left:50%;transform:translateX(-50%) translateY(80px);background:var(--navy-light);border:1px solid var(--border);color:var(--white);padding:11px 22px;border-radius:50px;font-size:13px;font-weight:600;transition:transform .3s;z-index:100;white-space:nowrap;box-shadow:0 4px 16px rgba(0,0,0,.3)}
        .toast.show{transform:translateX(-50%) translateY(0)}
        .preview-modal{position:fixed;inset:0;background:rgba(0,0,0,.78);z-index:120;display:none;align-items:center;justify-content:center;padding:18px}
        .preview-card{width:min(92vw,460px);background:rgba(16,37,69,.98);border:1px solid var(--border);border-radius:18px;padding:14px;box-shadow:0 12px 36px rgba(0,0,0,.5)}
        .preview-title{font-size:13px;color:var(--muted);text-align:center;margin:0 0 10px}
        .preview-image-wrap{border-radius:14px;overflow:hidden;border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.04)}
        .preview-image{display:block;width:100%;height:auto;max-height:68vh;object-fit:contain}
        .preview-actions{display:flex;gap:10px;margin-top:12px}
        .preview-btn{flex:1;display:flex;align-items:center;justify-content:center;gap:7px;padding:11px;border-radius:12px;border:1px solid rgba(232,193,26,.35);background:transparent;color:var(--white);text-decoration:none;font-size:13px;font-weight:700}
        .preview-btn.gold{background:linear-gradient(135deg,var(--gold),var(--gold-dk));color:var(--navy);border:none}
    </style>
</head>
<body>
<div class="bg-stadium"></div>
<div class="corner tl"><img src="{{ asset('images/logo-offside.png') }}" alt="Offside Club"></div>
<div class="corner tr"><img src="{{ asset('images/2026_FIFA_World_Cup_emblem.svg.png') }}" alt="FIFA World Cup 2026"></div>

<div class="page">

    {{-- Share Card --}}
    <div class="share-card">
        <div class="card-head">
            <div class="card-wc">
                <img class="card-wc-logo" src="{{ asset('images/2026_FIFA_World_Cup_emblem.svg.png') }}" alt="FIFA World Cup 2026">
            </div>
            <div class="card-teams-row">
                <div class="card-team">
                    <img class="card-crest"
                         src="{{ $match->homeTeam?->crest_url ?? asset('images/default-crest.png') }}"
                         alt="{{ $match->homeTeam?->name ?? $match->home_team }}"
                         onerror="this.src='{{ asset('images/default-crest.png') }}'">
                    <div class="card-tname">{{ $match->homeTeam?->name ?? $match->home_team }}</div>
                </div>
                <div class="card-vs">VS</div>
                <div class="card-team">
                    <img class="card-crest"
                         src="{{ $match->awayTeam?->crest_url ?? asset('images/default-crest.png') }}"
                         alt="{{ $match->awayTeam?->name ?? $match->away_team }}"
                         onerror="this.src='{{ asset('images/default-crest.png') }}'">
                    <div class="card-tname">{{ $match->awayTeam?->name ?? $match->away_team }}</div>
                </div>
            </div>
            <div class="card-date">
                <i class="far fa-calendar-alt" style="margin-right:5px"></i>
                {{ \Carbon\Carbon::parse($match->date)->timezone(auth()->user()?->timezone ?? 'UTC')->isoFormat('D [de] MMMM · HH:mm') }}
            </div>
        </div>
        <div class="card-body">
            <div class="pick-label">🏅 Mi predicción</div>
            <div class="pick-value">{{ $votedOption }}</div>
            <div class="branding">
                <img class="branding-logo" src="{{ asset('images/logo-offside.png') }}" alt="Offside Club">
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="actions">
        <button class="btn-share" id="shareBtn" onclick="shareResult()">
            <i class="fas fa-share-alt"></i> Compartir mi predicción
        </button>
        <button class="btn-outline" onclick="downloadShareImage()">
            <i class="fas fa-image"></i> Descargar imagen
        </button>
        <hr class="divider">
        <a href="{{ route('wc.hoy') }}" class="btn-outline">
            <i class="fas fa-futbol"></i> Predecir más partidos
        </a>
        @if($wcGroup)
        <a href="{{ route('groups.show', $wcGroup->code) }}" class="btn-ghost">
            <i class="fas fa-trophy"></i> Ver ranking del Mundial
        </a>
        @endif
        <a href="{{ route('wc.match', $match->id) }}" class="btn-ghost">
            <i class="fas fa-arrow-left"></i> Volver al partido
        </a>
    </div>

</div>

<div class="toast" id="toast">✓ Copiado</div>

<div class="preview-modal" id="previewModal" aria-hidden="true">
    <div class="preview-card">
        <p class="preview-title">Si no aparece el menu nativo, manten presionada la imagen para guardar o compartir</p>
        <div class="preview-image-wrap">
            <img id="previewImage" class="preview-image" alt="Vista previa de tu prediccion">
        </div>
        <div class="preview-actions">
            <a id="previewOpenLink" class="preview-btn" href="#" onclick="return openImageFromPreview(event)">
                <i class="fas fa-external-link-alt"></i> Abrir imagen
            </a>
            <button type="button" class="preview-btn gold" onclick="closePreviewModal()">
                <i class="fas fa-check"></i> Listo
            </button>
        </div>
    </div>
</div>

<script>
    const matchUrl = "{{ route('wc.match', $match->id) }}";
    const shareText = "⚽ Predije \u00ab{{ $votedOption }}\u00bb en {{ $match->homeTeam?->name ?? $match->home_team }} vs {{ $match->awayTeam?->name ?? $match->away_team }} \u2014 Mundial 2026.\n\u00bfY t\u00fa? Predice en Offside Club:";
    const shareFileName = "prediccion-mundial-{{ $match->id }}.png";
    let previewDataUrl = '';

    function drawRoundedRect(ctx, x, y, w, h, r){
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.arcTo(x + w, y, x + w, y + h, r);
        ctx.arcTo(x + w, y + h, x, y + h, r);
        ctx.arcTo(x, y + h, x, y, r);
        ctx.arcTo(x, y, x + w, y, r);
        ctx.closePath();
    }

    async function loadCanvasImage(src){
        return await new Promise(resolve => {
            const img = new Image();
            img.onload = () => resolve(img);
            img.onerror = () => resolve(null);
            img.src = src;
        });
    }

    async function buildShareCanvas(){
        const canvas = document.createElement('canvas');
        canvas.width = 1080;
        canvas.height = 1920;
        const ctx = canvas.getContext('2d');
        ctx.textAlign = 'center';

        // Fallback solid background
        ctx.fillStyle = '#0b1e3a';
        ctx.fillRect(0, 0, 1080, 1920);

        // Stadium image
        await new Promise(resolve => {
            const img = new Image();
            img.onload = () => {
                const scale = Math.max(1080 / img.naturalWidth, 1920 / img.naturalHeight);
                const w = img.naturalWidth * scale;
                const h = img.naturalHeight * scale;
                ctx.drawImage(img, (1080 - w) / 2, (1920 - h) / 2, w, h);
                resolve();
            };
            img.onerror = resolve;
            img.src = '{{ asset("images/estadio.avif") }}';
        });

        // Dark gradient overlay (same as CSS)
        const ov = ctx.createLinearGradient(0, 0, 0, 1920);
        ov.addColorStop(0,    'rgba(11,30,58,0.82)');
        ov.addColorStop(0.55, 'rgba(11,30,58,0.22)');
        ov.addColorStop(1,    'rgba(11,30,58,0.98)');
        ctx.fillStyle = ov;
        ctx.fillRect(0, 0, 1080, 1920);

        // Header logo
        const wcLogo = await loadCanvasImage('{{ asset("images/2026_FIFA_World_Cup_emblem.svg.png") }}');
        if (wcLogo) {
            const maxW = 640;
            const maxH = 240;
            const scale = Math.min(maxW / wcLogo.naturalWidth, maxH / wcLogo.naturalHeight);
            const w = wcLogo.naturalWidth * scale;
            const h = wcLogo.naturalHeight * scale;
            ctx.drawImage(wcLogo, (canvas.width - w) / 2, 40, w, h);
        } else {
            ctx.fillStyle = '#e8c11a';
            ctx.font = '700 44px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('FIFA World Cup 2026', canvas.width / 2, 160);
        }

        // Match block
        ctx.fillStyle = 'rgba(255,255,255,0.06)';
        drawRoundedRect(ctx, 90, 320, 900, 280, 32);
        ctx.fill();
        ctx.strokeStyle = 'rgba(232,193,26,0.35)';
        ctx.lineWidth = 3;
        ctx.stroke();

        ctx.fillStyle = '#ffffff';
        ctx.font = '700 56px sans-serif';
        ctx.fillText('{{ $match->home_team }}', canvas.width / 2, 420);
        ctx.fillStyle = '#9ab0cc';
        ctx.font = '700 34px sans-serif';
        ctx.fillText('VS', canvas.width / 2, 480);
        ctx.fillStyle = '#ffffff';
        ctx.font = '700 56px sans-serif';
        ctx.fillText('{{ $match->away_team }}', canvas.width / 2, 550);

        // Pick block
        ctx.fillStyle = 'rgba(232,193,26,0.10)';
        drawRoundedRect(ctx, 90, 700, 900, 420, 36);
        ctx.fill();
        ctx.strokeStyle = 'rgba(232,193,26,0.5)';
        ctx.lineWidth = 3;
        ctx.stroke();

        ctx.fillStyle = '#9ab0cc';
        ctx.font = '700 36px sans-serif';
        ctx.fillText('MI PREDICCION', canvas.width / 2, 800);

        ctx.fillStyle = '#e8c11a';
        ctx.font = '900 64px sans-serif';

        const lines = [];
        const words = '{{ $votedOption }}'.split(' ');
        let line = '';
        for (const word of words){
            const test = line ? (line + ' ' + word) : word;
            if (ctx.measureText(test).width > 820){
                lines.push(line);
                line = word;
            } else {
                line = test;
            }
        }
        if (line) lines.push(line);

        const startY = 910 - ((lines.length - 1) * 44 / 2);
        lines.forEach((l, i) => ctx.fillText(l, canvas.width / 2, startY + i * 88));

        // Footer logo
        const offsideLogo = await loadCanvasImage('{{ asset("images/logo-offside.png") }}');
        if (offsideLogo) {
            const maxW = 980;
            const maxH = 520;
            const scale = Math.min(maxW / offsideLogo.naturalWidth, maxH / offsideLogo.naturalHeight);
            const w = offsideLogo.naturalWidth * scale;
            const h = offsideLogo.naturalHeight * scale;
            ctx.drawImage(offsideLogo, (canvas.width - w) / 2, 1160, w, h);
        } else {
            ctx.fillStyle = '#9ab0cc';
            ctx.font = '600 32px sans-serif';
            ctx.fillText('Jugado en Offside Club', canvas.width / 2, 1120);
        }
        return canvas;
    }

    async function getShareImageBlob(){
        const canvas = await buildShareCanvas();
        return await new Promise(resolve => canvas.toBlob(resolve, 'image/png', 1));
    }

    async function blobToBase64(blob){
        return await new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onloadend = () => {
                const result = reader.result || '';
                const base64 = String(result).split(',')[1] || '';
                resolve(base64);
            };
            reader.onerror = reject;
            reader.readAsDataURL(blob);
        });
    }

    function isLikelyInAppBrowser(){
        const ua = (navigator.userAgent || '').toLowerCase();
        return ua.includes('instagram') || ua.includes('fb_iab') || ua.includes('fbav') || ua.includes('line/') || ua.includes('micromessenger');
    }

    function isMobileDevice(){
        return /android|iphone|ipad|ipod/i.test(navigator.userAgent || '');
    }

    async function openPreviewModal(blob){
        const modal = document.getElementById('previewModal');
        const img = document.getElementById('previewImage');
        const link = document.getElementById('previewOpenLink');
        if (!modal || !img || !link) return;

        previewDataUrl = `data:image/png;base64,${await blobToBase64(blob)}`;
        img.src = previewDataUrl;
        link.href = previewDataUrl;
        link.setAttribute('download', shareFileName);
        modal.style.display = 'flex';
        modal.setAttribute('aria-hidden', 'false');
    }

    function openImageFromPreview(event){
        if (event) event.preventDefault();
        if (!previewDataUrl) return false;

        if (isLikelyInAppBrowser() || isMobileDevice()) {
            window.location.href = previewDataUrl;
            return false;
        }

        window.open(previewDataUrl, '_blank', 'noopener,noreferrer');
        return false;
    }

    function closePreviewModal(){
        const modal = document.getElementById('previewModal');
        if (modal) {
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
        }
    }

    async function attemptClassicDownload(blob){
        try {
            const dataUrl = `data:image/png;base64,${await blobToBase64(blob)}`;
            const a = document.createElement('a');
            a.href = dataUrl;
            a.download = shareFileName;
            a.rel = 'noopener';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            return true;
        } catch {
            return false;
        }
    }

    async function tryWebShare(blob){
        if (!blob || !navigator.share) return false;

        const file = new File([blob], shareFileName, { type: 'image/png' });

        if (navigator.canShare && navigator.canShare({ files: [file] })) {
            await navigator.share({
                title: '⚽ Mi predicción — Mundial 2026',
                text: shareText,
                files: [file],
            });
            return true;
        }

        await navigator.share({
            title: '⚽ Mi predicción — Mundial 2026',
            text: shareText,
            url: matchUrl
        });
        return true;
    }

    async function tryCapacitorShare(blob){
        const plugins = window.Capacitor?.Plugins;
        const Share = plugins?.Share;
        if (!Share?.share) return false;

        try {
            const Filesystem = plugins?.Filesystem;
            if (blob && Filesystem?.writeFile) {
                const path = `offside-share/${shareFileName}`;
                const data = await blobToBase64(blob);

                await Filesystem.writeFile({
                    path,
                    data,
                    directory: 'CACHE',
                    recursive: true,
                });

                let fileUri = null;
                if (Filesystem.getUri) {
                    const uri = await Filesystem.getUri({ path, directory: 'CACHE' });
                    fileUri = uri?.uri || null;
                }

                if (fileUri) {
                    await Share.share({
                        title: '⚽ Mi predicción — Mundial 2026',
                        text: shareText,
                        files: [fileUri],
                        dialogTitle: 'Compartir mi predicción'
                    });
                    return true;
                }
            }
        } catch (err) {
            console.warn('Capacitor share con imagen no disponible, usando fallback de texto/url', err);
        }

        try {
            await Share.share({
                title: '⚽ Mi predicción — Mundial 2026',
                text: `${shareText}\n${matchUrl}`,
                dialogTitle: 'Compartir mi predicción'
            });
            return true;
        } catch {
            return false;
        }
    }

    async function shareResult(){
        const btn = document.getElementById('shareBtn');
        const old = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando imagen...';

        try {
            const blob = await getShareImageBlob();
            if (await tryWebShare(blob)) return;
            if (await tryCapacitorShare(blob)) return;
        } catch(e) {
            if (e && e.name === 'AbortError') return;
            console.warn('No se pudo compartir con APIs nativas/web:', e);
        } finally {
            btn.disabled = false;
            btn.innerHTML = old;
        }

        try {
            const blob = await getShareImageBlob();
            if (blob) {
                await openPreviewModal(blob);
                showToast('Imagen lista. Usa "Abrir imagen" o manten presionada para compartir');
                return;
            }
        } catch (_) {}

        if (isLikelyInAppBrowser()) {
            showToast('Navegador interno detectado: descarga la imagen y compartela desde tu galeria');
        } else {
            showToast('No se pudo abrir el menu nativo. Copiamos el texto para compartir');
        }
        copyLink();
    }

    async function downloadShareImage(){
        try {
            const blob = await getShareImageBlob();
            if (!blob) throw new Error('No blob');

            if (await tryCapacitorShare(blob)) {
                showToast('✓ Se abrio el menu de compartir');
                return;
            }

            const attempted = await attemptClassicDownload(blob);

            if (isLikelyInAppBrowser() || isMobileDevice()) {
                await openPreviewModal(blob);
                showToast('Imagen lista. Usa "Abrir imagen" para guardar/compartir');
                return;
            }

            if (attempted) {
                showToast('✓ Descarga iniciada');
                return;
            }

            await openPreviewModal(blob);
            showToast('No se pudo descargar automatico. Abre la imagen para guardarla');
        } catch {
            showToast('No se pudo generar la imagen');
        }
    }
    function copyLink(){
        const t=shareText+'\n'+matchUrl;
        navigator.clipboard?.writeText(t).then(()=>showToast('✓ Texto copiado')).catch(()=>{const el=document.createElement('textarea');el.value=t;document.body.appendChild(el);el.select();document.execCommand('copy');document.body.removeChild(el);showToast('✓ Texto copiado')});
    }
    function showToast(m){const t=document.getElementById('toast');t.textContent=m;t.classList.add('show');setTimeout(()=>t.classList.remove('show'),2500);}

    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('previewModal');
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closePreviewModal();
            });
        }
    });
</script>
</body>
</html>
