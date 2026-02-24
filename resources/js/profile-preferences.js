/**
 * Profile Preferences Dynamic Loading
 * Handles dynamic loading of clubs and national teams
 */

document.addEventListener('DOMContentLoaded', function() {
    const competitionSelect = document.getElementById('favorite_competition_id');
    const clubSelect = document.getElementById('favorite_club_id');
    const nationalTeamSelect = document.getElementById('favorite_national_team_id');

    // Load national teams on page load
    if (nationalTeamSelect) {
        loadNationalTeams();
    }

    // Load clubs when competition changes
    if (competitionSelect) {
        competitionSelect.addEventListener('change', function() {
            if (this.value) {
                loadClubsByCompetition(this.value);
            } else {
                // Clear clubs if no competition selected
                if (clubSelect) {
                    clubSelect.innerHTML = '<option value="">{{ __("views.profile.select_club") }}</option>';
                }
            }
        });

        // Load clubs on page load if a competition is already selected
        if (competitionSelect.value) {
            loadClubsByCompetition(competitionSelect.value);
        }
    }

    /**
     * Load clubs by competition
     */
    function loadClubsByCompetition(competitionId) {
        if (!clubSelect) return;

        // Show loading state
        clubSelect.innerHTML = '<option value="">Cargando...</option>';
        clubSelect.disabled = true;

        fetch(`/profile/clubs/${competitionId}`)
            .then(response => response.json())
            .then(data => {
                clubSelect.innerHTML = '<option value="">{{ __("views.profile.select_club") }}</option>';

                if (data.length > 0) {
                    data.forEach(club => {
                        const option = document.createElement('option');
                        option.value = club.id;
                        option.textContent = club.name;
                        clubSelect.appendChild(option);
                    });
                } else {
                    const option = document.createElement('option');
                    option.disabled = true;
                    option.textContent = 'No hay clubes disponibles';
                    clubSelect.appendChild(option);
                }

                clubSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error loading clubs:', error);
                clubSelect.innerHTML = '<option value="">Error al cargar</option>';
                clubSelect.disabled = false;
            });
    }

    /**
     * Load national teams
     */
    function loadNationalTeams() {
        if (!nationalTeamSelect) return;

        fetch('/profile/national-teams')
            .then(response => response.json())
            .then(data => {
                nationalTeamSelect.innerHTML = '<option value="">{{ __("views.profile.select_national_team") }}</option>';

                if (data.length > 0) {
                    data.forEach(team => {
                        const option = document.createElement('option');
                        option.value = team.id;
                        option.textContent = team.name;
                        nationalTeamSelect.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading national teams:', error);
            });
    }
});
