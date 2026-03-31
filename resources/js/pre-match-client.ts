/**
 * Pre Match Module - TypeScript Client Handler
 * Integración con Capacitor, WebSockets y API REST
 */

interface PreMatchData {
    id: number;
    match_id: number;
    group_id: number;
    status: 'DRAFT' | 'OPEN' | 'LOCKED' | 'RESOLVED';
    penalty_type: 'POINTS' | 'SOCIAL' | 'REVENGE';
    penalty: string;
    penalty_points?: number;
    propositions: PropositionData[];
    created_at: string;
    match_starts_at: string;
}

interface PropositionData {
    id: number;
    pre_match_id: number;
    user_id: number;
    action: string;
    description?: string;
    validation_status: 'PENDING' | 'ACCEPTED' | 'REJECTED';
    approval_percentage: number;
    probability?: number;
    votes: VoteData[];
}

interface VoteData {
    id: number;
    proposition_id: number;
    user_id: number;
    approved: boolean;
    user_name: string;
}

interface ActionTemplate {
    id: number;
    action: string;
    description: string;
    probability: number;
    category: string;
}

/**
 * PreMatchClient - Maneja toda la lógica del Pre Match
 */
class PreMatchClient {
    private apiBaseUrl = '/api';
    private wsUrl = 'wss://' + window.location.hostname + ':6001';
    private currentGroupId: number | null = null;
    private autoRefreshInterval: NodeJS.Timeout | null = null;

    constructor() {
        this.init();
    }

    /**
     * Inicialización del cliente
     */
    private init(): void {
        console.log('🎮 Pre Match Client Initialized');
        this.setupEventListeners();
    }

    /**
     * Configurar event listeners globales
     */
    private setupEventListeners(): void {
        // Delegación de eventos para botones dinámicos
        document.addEventListener('click', (e: Event) => {
            const target = e.target as HTMLElement;

            // Vote buttons
            if (target.dataset.action === 'vote-yes') {
                const propId = target.dataset.propositionId;
                if (propId) this.voteProposition(parseInt(propId), true);
            }
            if (target.dataset.action === 'vote-no') {
                const propId = target.dataset.propositionId;
                if (propId) this.voteProposition(parseInt(propId), false);
            }

            // Open modals
            if (target.dataset.action === 'open-proposal-modal') {
                const matchId = target.dataset.preMatchId;
                if (matchId) this.openProposalModal(parseInt(matchId));
            }

            // Resolve proposition
            if (target.dataset.action === 'resolve-proposition') {
                const propId = target.dataset.propositionId;
                const preMatchId = target.dataset.preMatchId;
                if (propId && preMatchId) {
                    this.openResolutionModal(parseInt(preMatchId), parseInt(propId));
                }
            }
        });
    }

    /**
     * Cargar todas las pre-matches de un grupo
     */
    async loadPreMatches(groupId: number): Promise<PreMatchData[]> {
        try {
            const response = await fetch(`${this.apiBaseUrl}/pre-matches?group_id=${groupId}`);
            const data = await response.json();
            return data.data || [];
        } catch (error) {
            console.error('❌ Error loading pre-matches:', error);
            return [];
        }
    }

    /**
     * Crear una nueva Pre Match
     */
    async createPreMatch(matchId: number, groupId: number, penaltyType: string, penalty: string, penaltyPoints?: number): Promise<PreMatchData | null> {
        try {
            const response = await fetch(`${this.apiBaseUrl}/pre-matches`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken()
                },
                body: JSON.stringify({
                    match_id: matchId,
                    group_id: groupId,
                    penalty_type: penaltyType,
                    penalty,
                    penalty_points: penaltyPoints || 0
                })
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            this.notifySuccess('✅ Pre Match creado!');
            return data.data;
        } catch (error) {
            console.error('❌ Error creating pre-match:', error);
            this.notifyError('Error al crear Pre Match');
            return null;
        }
    }

    /**
     * Añadir proposición a una Pre Match
     */
    async addProposition(preMatchId: number, action: string, description?: string): Promise<PropositionData | null> {
        try {
            const response = await fetch(`${this.apiBaseUrl}/pre-matches/${preMatchId}/propositions`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken()
                },
                body: JSON.stringify({
                    action,
                    description: description || ''
                })
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            this.notifySuccess('✅ Propuesta creada!');
            return data.data;
        } catch (error) {
            console.error('❌ Error adding proposition:', error);
            this.notifyError('Error al crear propuesta');
            return null;
        }
    }

    /**
     * Votar en una proposición
     */
    async voteProposition(propositionId: number, approved: boolean): Promise<boolean> {
        try {
            const response = await fetch(`${this.apiBaseUrl}/pre-match-propositions/${propositionId}/vote`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken()
                },
                body: JSON.stringify({ approved })
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            this.notifySuccess(approved ? '✅ Votaste a favor!' : '❌ Votaste en contra!');
            // Auto-refresh después de votar
            this.autoRefreshPreMatch();
            return true;
        } catch (error) {
            console.error('❌ Error voting:', error);
            this.notifyError('Error al votar');
            return false;
        }
    }

    /**
     * Resolver una Pre Match (admin only)
     */
    async resolvePreMatch(preMatchId: number, propositionId: number, wasFulfilled: boolean, adminNotes: string, loserUserId?: number): Promise<boolean> {
        try {
            const response = await fetch(`${this.apiBaseUrl}/pre-matches/${preMatchId}/resolve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken()
                },
                body: JSON.stringify({
                    proposition_id: propositionId,
                    was_fulfilled: wasFulfilled,
                    admin_notes: adminNotes,
                    loser_user_id: loserUserId || null
                })
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            this.notifySuccess('✅ Acción validada!');
            return true;
        } catch (error) {
            console.error('❌ Error resolving:', error);
            this.notifyError('Error al validar acción');
            return false;
        }
    }

    /**
     * Obtener sugerencia aleatoria de acción
     */
    async getSuggestedAction(): Promise<ActionTemplate | null> {
        try {
            const response = await fetch(`${this.apiBaseUrl}/action-templates/random`);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return await response.json();
        } catch (error) {
            console.error('❌ Error fetching suggestion:', error);
            return null;
        }
    }

    /**
     * Cargar castigos de un grupo
     */
    async loadPenalties(groupId: number) {
        try {
            const response = await fetch(`${this.apiBaseUrl}/pre-matches/${groupId}/penalties`);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return await response.json();
        } catch (error) {
            console.error('❌ Error loading penalties:', error);
            return null;
        }
    }

    /**
     * Marcar castigo como cumplido
     */
    async markPenaltyFulfilled(penaltyId: number): Promise<boolean> {
        try {
            const response = await fetch(`${this.apiBaseUrl}/penalties/${penaltyId}/fulfill`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken()
                }
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            this.notifySuccess('✅ Castigo marcado como cumplido!');
            return true;
        } catch (error) {
            console.error('❌ Error marking fulfilled:', error);
            this.notifyError('Error al actualizar');
            return false;
        }
    }

    /**
     * Abrir modal de propuesta (helper UI)
     */
    openProposalModal(preMatchId: number): void {
        const modal = document.getElementById('createProposalModal');
        if (modal) {
            document.getElementById('preMatchId')?.setAttribute('value', preMatchId.toString());
            modal.classList.remove('hidden');
        }
    }

    /**
     * Abrir modal de resolución (helper UI)
     */
    openResolutionModal(preMatchId: number, propositionId: number): void {
        const modal = document.getElementById('resolutionModal');
        if (modal) {
            // Los datos se cargan dinámicamente en el modal
            modal.classList.remove('hidden');
        }
    }

    /**
     * Auto-refresh de Pre Matches (útil para WebSocket)
     */
    private autoRefreshPreMatch(interval: number = 5000): void {
        if (this.autoRefreshInterval) return;

        this.autoRefreshInterval = setInterval(() => {
            const preMatchId = document.body.dataset.preMatchId;
            if (preMatchId) {
                // Aquí puedes disparar evento de auto-refresh
                window.dispatchEvent(new CustomEvent('pre-match:refresh', { detail: { id: preMatchId } }));
            }
        }, interval);
    }

    /**
     * Conectar a WebSocket para actualizaciones en tiempo real
     */
    connectWebSocket(preMatchId: number): void {
        try {
            // Nota: Requiere configuración de Pusher/Laravel Broadcasting
            const ws = new WebSocket(`${this.wsUrl}/ws/pre-match/${preMatchId}`);

            ws.onopen = () => {
                console.log('🔌 WebSocket conectado');
            };

            ws.onmessage = (event) => {
                const data = JSON.parse(event.data);
                if (data.type === 'new-vote') {
                    this.notifyUpdate('Nueva votación registrada!');
                    window.dispatchEvent(new CustomEvent('pre-match:vote-update', { detail: data }));
                }
            };

            ws.onerror = (error) => {
                console.error('❌ WebSocket error:', error);
            };

            ws.onclose = () => {
                console.log('WebSocket cerrado');
            };
        } catch (error) {
            console.error('❌ Error connecting WebSocket:', error);
        }
    }

    /**
     * Obtener token CSRF
     */
    private getCsrfToken(): string {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        return token || '';
    }

    /**
     * Notificaciones
     */
    private notifySuccess(message: string): void {
        console.log(message);
        this.showNotification(message, 'success');
    }

    private notifyError(message: string): void {
        console.error(message);
        this.showNotification(message, 'error');
    }

    private notifyUpdate(message: string): void {
        console.log(message);
        this.showNotification(message, 'info');
    }

    private showNotification(message: string, type: 'success' | 'error' | 'info' = 'info'): void {
        // Implementar con Toast si existe
        const colors = {
            success: '#22c55e',
            error: '#ef4444',
            info: '#3b82f6'
        };

        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px;
            background-color: ${colors[type]};
            color: white;
            border-radius: 8px;
            z-index: 9999;
            animation: slideIn 0.3s ease;
        `;
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => notification.remove(), 3000);
    }
}

// Instancia global
declare global {
    interface Window {
        preMatchClient: PreMatchClient;
    }
}

window.preMatchClient = new PreMatchClient();
export default PreMatchClient;
