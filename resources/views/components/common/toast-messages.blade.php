<script>
    // Función para mostrar toasts - esperar a que todo esté listo
    (function() {
        const showToasts = () => {
            console.log('🔔 showToasts ejecutándose...');
            console.log('showSuccessToast disponible:', typeof window.showSuccessToast);
            console.log('showErrorToast disponible:', typeof window.showErrorToast);

            @if($errors->any())
                console.log('❌ Errores detectados:', @json($errors->all()));
                @foreach($errors->all() as $error)
                    if (typeof window.showErrorToast === 'function') {
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        window.showErrorToast('{{ addslashes($error) }}');
                    } else {
                        console.error('showErrorToast no disponible');
                    }
                @endforeach
            @endif

            @if(session('success'))
                console.log('✅ Session success:', '{{ addslashes(session('success')) }}');
                if (typeof window.showSuccessToast === 'function') {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    window.showSuccessToast('{{ addslashes(session('success')) }}');
                    console.log('✅ Toast mostrado');
                } else {
                    console.error('showSuccessToast no disponible');
                }
            @else
                console.log('ℹ️ Session success: vacío');
            @endif

            @if(session('error'))
                console.log('❌ Session error:', '{{ addslashes(session('error')) }}');
                if (typeof window.showErrorToast === 'function') {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    window.showErrorToast('{{ addslashes(session('error')) }}');
                } else {
                    console.error('showErrorToast no disponible');
                }
            @endif

            @if(session('warning'))
                console.log('⚠️ Session warning:', '{{ addslashes(session('warning')) }}');
                if (typeof window.showWarningToast === 'function') {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    window.showWarningToast('{{ addslashes(session('warning')) }}');
                } else {
                    console.error('showWarningToast no disponible');
                }
            @endif

            @if(session('info'))
                console.log('ℹ️ Session info:', '{{ addslashes(session('info')) }}');
                if (typeof window.showInfoToast === 'function') {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    window.showInfoToast('{{ addslashes(session('info')) }}');
                } else {
                    console.error('showInfoToast no disponible');
                }
            @endif
        };

        // Intenta ejecutar inmediatamente
        console.log('🚀 Toast component cargado, esperando 100ms...');
        setTimeout(showToasts, 100);

        // Y también cuando el window esté completamente cargado
        window.addEventListener('load', () => {
            console.log('🚀 Window load event, ejecutando toasts nuevamente...');
            showToasts();
        });
    })();
</script>
