@props([
    'match' => null,
    'isDark' => true,
])

@php
    // Dark theme colors
    if ($isDark) {
        $textPrimary = '#f1fff8';
        $textSecondary = '#9bcfcc';
        $bgSecondary = '#10302d';
        $bgTertiary = '#08201d';
        $borderColor = '#1d4f4a';
        $accentColor = '#00deb0';
        $overlayBg = 'rgba(0, 0, 0, 0.55)';
        $hoverBg = 'rgba(255,255,255,0.08)';
        $accentBg = 'rgba(0, 222, 176, 0.12)';
        $surfaceShadow = '0 14px 40px rgba(0, 0, 0, 0.55)';
    } else {
        // Light theme colors
        $textPrimary = '#1a1a1a';
        $textSecondary = '#666666';
        $bgSecondary = '#f5f5f5';
        $bgTertiary = '#eeeeee';
        $borderColor = '#ddd';
        $accentColor = '#00b893';
        $overlayBg = 'rgba(0, 0, 0, 0.3)';
        $hoverBg = 'rgba(0, 184, 147, 0.05)';
        $accentBg = 'rgba(0, 184, 147, 0.1)';
        $surfaceShadow = '0 12px 34px rgba(0, 0, 0, 0.15)';
    }
@endphp

<!-- Modal de Grupos del Partido -->
<div id="matchGroupsModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: {{ $overlayBg }}; display: none; align-items: center; justify-content: center; z-index: 9999; padding: 16px;">
    <div style="background: {{ $bgSecondary }}; border: 1px solid {{ $borderColor }}; border-radius: 16px; width: 100%; max-width: 500px; max-height: 80vh; overflow-y: auto; box-shadow: {{ $surfaceShadow }};">
        <!-- Header -->
        <div style="padding: 24px; border-bottom: 1px solid {{ $borderColor }}; display: flex; justify-content: space-between; align-items: flex-start; position: sticky; top: 0; background: {{ $bgSecondary }}; z-index: 10;">
            <div style="flex: 1;">
                <h3 id="modalTitle" style="margin: 0; font-size: 18px; font-weight: 700; color: {{ $textPrimary }}; margin-bottom: 4px;">
                    <i class="fas fa-users"></i> {{ __('views.groups.available_groups') }}
                </h3>
                <p id="matchInfo" style="margin: 0; font-size: 13px; color: {{ $textSecondary }};"></p>
            </div>
            <button id="closeMatchGroupsModal" type="button" style="background: none; border: none; font-size: 24px; color: {{ $textSecondary }}; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 6px; transition: all 0.2s ease; flex-shrink: 0;"
                    onmouseover="this.style.background='{{ $hoverBg }}'" onmouseout="this.style.background='none'">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Body -->
        <div id="modalContent" style="padding: 24px;">
            <!-- Loading State -->
            <div id="loadingState" style="text-align: center; padding: 40px 20px;">
                <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid {{ $borderColor }}; border-top-color: {{ $accentColor }}; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                <p style="color: {{ $textSecondary }}; margin-top: 16px; font-size: 14px;">{{ __('views.groups.loading_groups') }}</p>
            </div>

            <!-- Groups List (hidden initially) -->
            <div id="groupsList" style="display: none;"></div>

            <!-- No Groups State -->
            <div id="noGroupsState" style="display: none; text-align: center; padding: 40px 20px;">
                <div style="width: 60px; height: 60px; background: {{ $accentBg }}; border-radius: 12px; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-inbox" style="font-size: 28px; color: {{ $accentColor }};"></i>
                </div>
                <h4 style="color: {{ $textPrimary }}; font-size: 16px; font-weight: 600; margin: 0 0 8px 0;">{{ __('views.groups.no_groups_found') }}</h4>
                <p style="color: {{ $textSecondary }}; font-size: 13px; margin: 0 0 24px 0;">{{ __('views.groups.no_groups_description') }}</p>
                <a id="createGroupBtn" href="#" style="display: inline-block; padding: 12px 24px; background: {{ $accentColor }}; color: #000; border: none; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; text-decoration: none; transition: all 0.2s ease;"
                   onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    <i class="fas fa-plus" style="margin-right: 6px;"></i> {{ __('views.groups.create_group') }}
                </a>
            </div>

            <!-- Error State -->
            <div id="errorState" style="display: none; text-align: center; padding: 40px 20px;">
                <div style="width: 60px; height: 60px; background: rgba(255, 100, 100, 0.1); border-radius: 12px; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-exclamation-circle" style="font-size: 28px; color: #ff6464;"></i>
                </div>
                <h4 style="color: {{ $textPrimary }}; font-size: 16px; font-weight: 600; margin: 0 0 8px 0;">{{ __('views.groups.error_loading_groups') }}</h4>
                <p id="errorMessage" style="color: {{ $textSecondary }}; font-size: 13px; margin: 0;"></p>
            </div>
        </div>

        <!-- Footer -->
        <div id="footerSection" style="padding: 16px 24px; border-top: 1px solid {{ $borderColor }}; display: none; text-align: right;">
            <button id="closeModalBtn" type="button" style="padding: 10px 20px; border: 1px solid {{ $borderColor }}; border-radius: 8px; background: transparent; color: {{ $textSecondary }}; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s ease;"
                    onmouseover="this.style.background='{{ $hoverBg }}'" onmouseout="this.style.background='transparent'">
                Cerrar
            </button>
        </div>
    </div>
</div>

<style>
    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .group-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px;
        background: {{ $bgTertiary }};
        border: 1px solid {{ $borderColor }};
        border-radius: 12px;
        margin-bottom: 12px;
        transition: all 0.2s ease;
    }

    .group-item:hover {
        background: {{ $isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 184, 147, 0.08)' }};
        border-color: {{ $accentColor }};
    }

    .group-info {
        flex: 1;
    }

    .group-name {
        font-size: 15px;
        font-weight: 600;
        color: {{ $textPrimary }};
        margin: 0 0 4px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .group-members {
        font-size: 12px;
        color: {{ $textSecondary }};
        margin: 0;
    }

    .group-action {
        padding: 8px 16px;
        background: {{ $accentColor }};
        color: #000;
        border: none;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .group-action:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('matchGroupsModal');
        const closeBtn = document.getElementById('closeMatchGroupsModal');
        const closeModalBtn = document.getElementById('closeModalBtn');

        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                modal.style.display = 'none';
            });
        }

        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', () => {
                modal.style.display = 'none';
            });
        }

        // Close modal when clicking outside
        window.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });

        // Expose function to open modal
        window.openMatchGroupsModal = function(matchId, matchTeams, competitionName) {
            document.getElementById('matchInfo').textContent = matchTeams + ' • ' + competitionName;
            showLoadingState();
            modal.style.display = 'flex';

            // Fetch groups for this match's competition
            fetch(`/groups/by-match/${matchId}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.groups && data.groups.length > 0) {
                        showGroupsList(data.groups, data.competitionName);
                    } else {
                        showNoGroupsState(matchId, data.competitionId);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorState('{{ __('views.groups.error_loading_groups_retry') }}');
                });
        };

        function showLoadingState() {
            document.getElementById('loadingState').style.display = 'block';
            document.getElementById('groupsList').style.display = 'none';
            document.getElementById('noGroupsState').style.display = 'none';
            document.getElementById('errorState').style.display = 'none';
            document.getElementById('footerSection').style.display = 'none';
        }

        function showGroupsList(groups, competitionName) {
            const groupsList = document.getElementById('groupsList');
            groupsList.innerHTML = '';

            groups.forEach(group => {
                const groupEl = document.createElement('div');
                groupEl.className = 'group-item';
                groupEl.innerHTML = `
                    <div class="group-info">
                        <p class="group-name">
                            <i class="fas fa-shield-alt"></i>
                            ${group.name}
                        </p>
                        <p class="group-members">
                            <i class="fas fa-users"></i> ${group.members_count} {{ __('views.groups.members_count') }}
                        </p>
                    </div>
                    <a href="/groups/${group.id}" class="group-action">
                        <i class="fas fa-arrow-right"></i> Ir
                    </a>
                `;
                groupsList.appendChild(groupEl);
            });

            document.getElementById('loadingState').style.display = 'none';
            document.getElementById('groupsList').style.display = 'block';
            document.getElementById('noGroupsState').style.display = 'none';
            document.getElementById('errorState').style.display = 'none';
            document.getElementById('footerSection').style.display = 'block';
        }

        function showNoGroupsState(matchId, competitionId) {
            const createBtn = document.getElementById('createGroupBtn');
            createBtn.href = `/groups/create?competition_id=${competitionId}&match_id=${matchId}`;

            document.getElementById('loadingState').style.display = 'none';
            document.getElementById('groupsList').style.display = 'none';
            document.getElementById('noGroupsState').style.display = 'block';
            document.getElementById('errorState').style.display = 'none';
            document.getElementById('footerSection').style.display = 'none';
        }

        function showErrorState(message) {
            document.getElementById('errorMessage').textContent = message;
            document.getElementById('loadingState').style.display = 'none';
            document.getElementById('groupsList').style.display = 'none';
            document.getElementById('noGroupsState').style.display = 'none';
            document.getElementById('errorState').style.display = 'block';
            document.getElementById('footerSection').style.display = 'block';
        }
    });
</script>
