{{-- DEBUG VERSION - Simplified Pre Match Modal --}}

<div id="createPreMatchModal"
     style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 1rem;">

    <div style="background: #ffffff; border-radius: 16px; width: 100%; max-width: 500px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 50px rgba(0,0,0,0.3);">

        <!-- Header -->
        <div style="padding: 24px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 20px; font-weight: 700; margin: 0;">
                🧪 DEBUG Pre Match Modal
            </h2>
            <button type="button" onclick="debugCloseModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">
                ✕
            </button>
        </div>

        <!-- Body -->
        <div style="padding: 24px;">

            <!-- Test 1: Search Input -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 700; margin-bottom: 8px;">
                    🔍 Search Input
                </label>
                <input type="text"
                       id="debugSearchInput"
                       placeholder="Type to search..."
                       style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
            </div>

            <!-- Test 2: Results Dropdown -->
            <div id="debugResults"
                 style="padding: 8px; max-height: 150px; overflow-y: auto; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 20px; background: #f9f9f9; display: none;">
            </div>

            <!-- Test 3: Hidden Input (the problem element) -->
            <div style="margin-bottom: 20px; padding: 12px; background: #f0f0f0; border-radius: 4px;">
                <p style="margin: 0 0 10px 0; font-weight: 700;">Hidden Input Status:</p>
                <input type="hidden" id="debugMatchInput" value="">
                <p id="debugStatus" style="margin: 0; font-size: 12px; color: #666;">Value: <strong id="debugValue">(empty)</strong></p>
            </div>

            <!-- Test 4: Selected Display -->
            <div id="debugDisplay" style="padding: 12px; border: 1px solid #00deb0; border-radius: 4px; margin-bottom: 20px; display: none; background: #e5f3f0;">
            </div>

            <!-- Buttons -->
            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="debugCloseModal()"
                        style="padding: 10px 20px; border: 1px solid #ccc; border-radius: 4px; background: #f5f5f5; cursor: pointer;">
                    Cancel
                </button>
                <button type="button" onclick="debugSubmit()"
                        style="padding: 10px 20px; border: none; border-radius: 4px; background: #ff6b6b; color: white; cursor: pointer; font-weight: 600;">
                    Submit
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-open modal on page load
window.addEventListener('load', function() {
    debugOpenModal();
    console.log('%c🧪 DEBUG MODAL OPENED', 'color: blue; font-size: 16px; font-weight: bold');
});

// Global test data
window.debugMatches = [
    { id: 1, home: 'Real Madrid', away: 'Barcelona', time: '19:00', comp: 'La Liga' },
    { id: 2, home: 'Liverpool', away: 'Manchester', time: '16:00', comp: 'Premier League' },
    { id: 3, home: 'PSG', away: 'Monaco', time: '20:00', comp: 'Ligue 1' }
];

window.debugOpenModal = function() {
    console.log('=== debugOpenModal ===');
    const modal = document.getElementById('createPreMatchModal');
    if (modal) {
        modal.style.display = 'flex';
        // Initialize search
        debugInitSearch();
    } else {
        console.error('❌ Modal not found!');
    }
};

window.debugCloseModal = function() {
    const modal = document.getElementById('createPreMatchModal');
    if (modal) {
        modal.style.display = 'none';
    }
};

function debugInitSearch() {
    console.log('=== debugInitSearch ===');
    const input = document.getElementById('debugSearchInput');
    if (!input) {
        console.error('❌ Search input not found');
        return;
    }

    input.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        const resultsDiv = document.getElementById('debugResults');

        if (!query) {
            resultsDiv.style.display = 'none';
            return;
        }

        const filtered = window.debugMatches.filter(m => {
            const text = `${m.home} ${m.away} ${m.comp}`.toLowerCase();
            return text.includes(query);
        });

        console.log('Filtered matches:', filtered);

        if (filtered.length === 0) {
            resultsDiv.innerHTML = '<div style="padding: 8px;">No matches found</div>';
            resultsDiv.style.display = 'block';
            return;
        }

        resultsDiv.innerHTML = filtered.map(m => `
            <div style="padding: 8px; border-bottom: 1px solid #ddd; cursor: pointer; background: white; margin: 4px 0;"
                 onclick="debugSelectMatch(${m.id}, '${m.home}', '${m.away}', '${m.time}')">
                <strong>${m.home} vs ${m.away}</strong><br>
                <small>${m.time} · ${m.comp}</small>
            </div>
        `).join('');

        resultsDiv.style.display = 'block';
    });
}

window.debugSelectMatch = function(matchId, home, away, time) {
    console.log('=== debugSelectMatch called ===');
    console.log('matchId:', matchId, 'type:', typeof matchId);

    const hiddenInput = document.getElementById('debugMatchInput');
    const display = document.getElementById('debugDisplay');
    const valueSpan = document.getElementById('debugValue');

    hiddenInput.value = matchId;

    console.log('Set .value to:', hiddenInput.value);
    console.log('Via getAttribute:', hiddenInput.getAttribute('value'));

    display.textContent = `✅ ${home} vs ${away} (${time})`;
    display.style.display = 'block';

    valueSpan.textContent = hiddenInput.value || '(empty)';

    // Hide results
    document.getElementById('debugResults').style.display = 'none';
    document.getElementById('debugSearchInput').value = `${home} vs ${away}`;
};

window.debugSubmit = function() {
    console.log('=== debugSubmit called ===');
    const hiddenInput = document.getElementById('debugMatchInput');
    const value = hiddenInput.value;

    console.log('Hidden input .value:', value);
    console.log('Type:', typeof value);
    console.log('Length:', value.length);
    console.log('Is truthy:', !!value);
    console.log('Is falsy:', !value);

    if (!value) {
        alert('❌ Value is empty or falsy! Value: "' + value + '"');
        return;
    }

    alert('✅ SUCCESS! Value: ' + value);
};
</script>
