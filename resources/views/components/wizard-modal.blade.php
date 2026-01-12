{{-- Welcome Wizard Modal Component --}}
<div id="welcomeWizard" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); display: none; align-items: center; justify-content: center; z-index: 9999; padding: 20px;">
    <div style="background: #ffffff; border-radius: 24px; width: 100%; max-width: 420px; padding: 0; position: relative; overflow: hidden; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);">

        {{-- Botón cerrar --}}
        <button onclick="closeWizard()" style="position: absolute; top: 16px; right: 16px; background: rgba(0, 0, 0, 0.05); border: none; width: 40px; height: 40px; border-radius: 50%; color: #999; font-size: 24px; cursor: pointer; z-index: 10; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-times"></i>
        </button>

        {{-- Indicadores de página (dots) --}}
        <div style="background: #f5f5f5; padding: 16px; display: flex; justify-content: center; gap: 10px;">
            <div class="wizard-dot active" data-step="1" onclick="goToWizardStep(1)" style="width: 8px; height: 8px; border-radius: 50%; background: #00deb0; cursor: pointer; transition: all 0.3s ease;"></div>
            <div class="wizard-dot" data-step="2" onclick="goToWizardStep(2)" style="width: 8px; height: 8px; border-radius: 50%; background: rgba(0, 222, 176, 0.2); cursor: pointer; transition: all 0.3s ease;"></div>
            <div class="wizard-dot" data-step="3" onclick="goToWizardStep(3)" style="width: 8px; height: 8px; border-radius: 50%; background: rgba(0, 222, 176, 0.2); cursor: pointer; transition: all 0.3s ease;"></div>
            <div class="wizard-dot" data-step="4" onclick="goToWizardStep(4)" style="width: 8px; height: 8px; border-radius: 50%; background: rgba(0, 222, 176, 0.2); cursor: pointer; transition: all 0.3s ease;"></div>
        </div>

        {{-- Contenedor de steps --}}
        <div style="padding: 40px 24px; text-align: center; min-height: 380px; display: flex; flex-direction: column; justify-content: center;">

            {{-- Step 1: Bienvenida --}}
            <div class="wizard-step" data-step="1" style="animation: fadeIn 0.5s ease;">
                <div style="font-size: 80px; margin-bottom: 24px; line-height: 1;">
                    <img src="{{ asset('images/logo_white_bg.png') }}" alt="" style="width: 60%">
                </div>
                <h2 style="font-size: 28px; font-weight: 700; color: #333; margin: 0 0 12px 0;">{{ __('views.wizard.welcome') }}</h2>
                <p style="color: #999; font-size: 15px; margin: 0; line-height: 1.7;">
                    {{ __('views.wizard.welcome_text') }}
                </p>
            </div>

            {{-- Step 2: Cómo jugar --}}
            <div class="wizard-step" data-step="2" style="display: none; animation: fadeIn 0.5s ease;">
                <div style="font-size: 80px; margin-bottom: 24px; line-height: 1;">
                    <img src="{{ asset('images/ranking.svg') }}" alt="" style="width: 60%">
                </div>
                <h2 style="font-size: 28px; font-weight: 700; color: #333; margin: 0 0 16px 0;">{{ __('views.wizard.earn_points') }}</h2>
                <div style="color: #999; font-size: 14px; margin: 0; line-height: 1.8;">
                    <div style="margin-bottom: 10px;"><strong style="color: #333;">{{ __('views.wizard.correct_300') }}</strong></div>
                    <div style="margin-bottom: 10px;"><strong style="color: #333;">{{ __('views.wizard.correct_600') }}</strong></div>
                    <div><strong style="color: #333;">{{ __('views.wizard.scale_ranking') }}</strong></div>
                </div>
            </div>

            {{-- Step 3: Compite --}}
            <div class="wizard-step" data-step="3" style="display: none; animation: fadeIn 0.5s ease;">
                <div style="font-size: 80px; margin-bottom: 24px; line-height: 1;">
                    <img src="{{ asset('images/wizard_1.png') }}" alt="" style="width: 60%">
                </div>
                <h2 style="font-size: 28px; font-weight: 700; color: #333; margin: 0 0 16px 0;">{{ __('views.wizard.predict_friends') }}</h2>
                <p style="color: #999; font-size: 15px; margin: 0; line-height: 1.7;">
                    {{ __('views.wizard.predict_text') }}
                </p>
            </div>

            {{-- Step 4: Comienza --}}
            <div class="wizard-step" data-step="4" style="display: none; animation: fadeIn 0.5s ease;">
                {{-- <div style="font-size: 80px; margin-bottom: 24px; line-height: 1;">
                    <i class="fas fa-user-circle" style="color: #00deb0;"></i>
                </div> --}}
                <h2 style="font-size: 28px; font-weight: 700; color: #333; margin: 0 0 20px 0;">{{ __('views.wizard.league_awaits') }}</h2>

                <div style="margin-bottom: 24px;">
                    <p style="color: #999; font-size: 13px; margin: 0 0 12px 0; line-height: 1.6;">{{ __('views.wizard.unique_experience') }}</p>
                    <button onclick="closeWizardAndGoTo('/profile')" style="width: 100%; padding: 12px 16px; background: #00deb0; border: none; border-radius: 8px; color: white; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 14px;">
                        <i class="fas fa-user-circle"></i>
                        {{ __('views.wizard.complete_profile') }}
                    </button>
                </div>

                <div style="padding: 16px 0; border-top: 1px solid #e0e0e0; border-bottom: 1px solid #e0e0e0; margin-bottom: 20px;">
                    <p style="color: #999; font-size: 13px; margin: 0; line-height: 1.6;">{{ __('views.wizard.connect_friends') }}</p>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <button onclick="closeWizardAndGoTo('/groups/create')" style="width: 100%; padding: 12px 16px; background: #00deb0; border: none; border-radius: 8px; color: white; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 14px;">
                        <i class="fas fa-users"></i>
                        {{ __('views.wizard.join_group') }}
                    </button>
                    <button onclick="closeWizard()" style="width: 100%; padding: 12px 16px; background: transparent; border: 2px solid #00deb0; border-radius: 8px; color: #00deb0; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 14px;">
                        <i class="fas fa-plus"></i>
                        {{ __('views.wizard.create_group') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Botones de navegación --}}
        <div style="background: #f5f5f5; padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; gap: 16px;">
            <button onclick="previousWizardStep()" style="width: 50px; height: 50px; border-radius: 50%; background: rgba(0, 222, 176, 0.1); border: 2px solid #00deb0; color: #00deb0; font-size: 20px; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-chevron-left"></i>
            </button>
            <div style="flex: 1; text-align: center;">
                <span style="color: #999; font-size: 13px;">
                    <span id="currentStep">1</span> / 4
                </span>
            </div>
            <button onclick="nextWizardStep()" style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #17b796, #00deb0); border: none; color: white; font-size: 20px; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
</div>

<style>
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .wizard-step {
        animation: fadeIn 0.5s ease;
    }

    .wizard-dot {
        transition: all 0.3s ease !important;
    }

    .wizard-dot.active {
        background: #00deb0 !important;
    }
</style>

<script>
    let currentWizardStep = 1;
    const totalWizardSteps = 4;

    function goToWizardStep(step) {
        currentWizardStep = step;
        updateWizardDisplay();
    }

    function updateWizardDisplay() {
        // Ocultar todos los steps
        document.querySelectorAll('.wizard-step').forEach(el => el.style.display = 'none');

        // Mostrar el step actual
        const activeStep = document.querySelector(`.wizard-step[data-step="${currentWizardStep}"]`);
        if (activeStep) {
            activeStep.style.display = 'block';
        }

        // Actualizar dots
        document.querySelectorAll('.wizard-dot').forEach((dot, index) => {
            const stepNum = index + 1;
            if (stepNum === currentWizardStep) {
                dot.classList.add('active');
                dot.style.background = '#00deb0';
            } else {
                dot.classList.remove('active');
                dot.style.background = 'rgba(0, 222, 176, 0.3)';
            }
        });

        // Actualizar contador
        document.getElementById('currentStep').textContent = currentWizardStep;
    }

    function nextWizardStep() {
        if (currentWizardStep < totalWizardSteps) {
            currentWizardStep++;
            updateWizardDisplay();
        } else {
            closeWizard();
        }
    }

    function previousWizardStep() {
        if (currentWizardStep > 1) {
            currentWizardStep--;
            updateWizardDisplay();
        }
    }

    function openWizard() {
        document.getElementById('welcomeWizard').style.display = 'flex';
        currentWizardStep = 1;
        updateWizardDisplay();
    }

    function closeWizard() {
        document.getElementById('welcomeWizard').style.display = 'none';
        localStorage.setItem('wizardShown', 'true');
    }

    function closeWizardAndGoTo(url) {
        closeWizard();
        window.location.href = url;
    }

    // Mostrar wizard si es la primera vez y no hay grupos
    window.addEventListener('load', function() {
        const groupsContainer = document.querySelector('[data-groups-count]');
        const groupsCount = groupsContainer ? parseInt(groupsContainer.getAttribute('data-groups-count')) : 0;

        if (!localStorage.getItem('wizardShown') && groupsCount === 0) {
            openWizard();
        }
    });
</script>
