<?php

namespace App\Services;

use App\Models\PreMatch;
use App\Models\PreMatchProposition;
use App\Models\PreMatchEvent;

class PreMatchEventService
{
    /**
     * Generar evento cuando se crea una nueva proposición
     */
    public static function propositionCreated(PreMatchProposition $proposition): PreMatchEvent
    {
        return PreMatchEvent::create([
            'pre_match_id' => $proposition->pre_match_id,
            'event_type' => 'proposition.created',
            'payload' => [
                'proposition_id' => $proposition->id,
                'user_id' => $proposition->user_id,
                'action' => $proposition->action,
                'user_name' => $proposition->user->name,
            ],
        ]);
    }

    /**
     * Generar evento cuando se elimina una proposición
     */
    public static function propositionDeleted(int $preMatchId, int $propositionId, string $userName): PreMatchEvent
    {
        return PreMatchEvent::create([
            'pre_match_id' => $preMatchId,
            'event_type' => 'proposition.deleted',
            'payload' => [
                'proposition_id' => $propositionId,
                'user_name' => $userName,
            ],
        ]);
    }

    /**
     * Generar evento cuando se vota en una proposición
     */
    public static function voteCreated(PreMatchProposition $proposition, int $userId, bool $approved): PreMatchEvent
    {
        return PreMatchEvent::create([
            'pre_match_id' => $proposition->pre_match_id,
            'event_type' => 'vote.created',
            'payload' => [
                'proposition_id' => $proposition->id,
                'user_id' => $userId,
                'approved' => $approved,
                'approval_percentage' => $proposition->approval_percentage,
            ],
        ]);
    }

    /**
     * Generar evento cuando una proposición es aprobada unánimemente
     */
    public static function propositionAutoApproved(PreMatchProposition $proposition): PreMatchEvent
    {
        return PreMatchEvent::create([
            'pre_match_id' => $proposition->pre_match_id,
            'event_type' => 'proposition.auto_approved',
            'payload' => [
                'proposition_id' => $proposition->id,
                'user_name' => $proposition->user->name,
                'approval_percentage' => $proposition->approval_percentage,
            ],
        ]);
    }

    /**
     * Generar evento cuando cambia el estado del pre-match
     */
    public static function statusChanged(PreMatch $preMatch, string $oldStatus, string $newStatus): PreMatchEvent
    {
        // Map de estados más legibles para eventos
        $statusLabels = [
            'pending' => '⏳ Pendiente',
            'active' => '🔴 Activo',
            'completed' => '✅ Completado',
            'cancelled' => '❌ Cancelado',
        ];

        $eventType = match ($newStatus) {
            'active' => 'status.pending_to_active',
            'completed' => 'status.resolved',
            'cancelled' => 'status.cancelled',
            default => 'status.changed',
        };

        return PreMatchEvent::create([
            'pre_match_id' => $preMatch->id,
            'event_type' => $eventType,
            'payload' => [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'new_status_label' => $statusLabels[$newStatus] ?? $newStatus,
            ],
        ]);
    }

    /**
     * Generar evento genérico
     */
    public static function eventOccurred(PreMatch $preMatch, string $eventType, array $payload = []): PreMatchEvent
    {
        return PreMatchEvent::create([
            'pre_match_id' => $preMatch->id,
            'event_type' => $eventType,
            'payload' => $payload,
        ]);
    }
}
