<?php

namespace App\Enums;

/**
 * Opportunity pipeline stage (M05).
 */
enum OpportunityStage: string
{
    case Qualification = 'qualification';
    case Proposal = 'proposal';
    case Negotiation = 'negotiation';
    case Won = 'won';
    case Lost = 'lost';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Qualification => 'Qualification',
            self::Proposal => 'Proposal Sent',
            self::Negotiation => 'Negotiation',
            self::Won => 'Won',
            self::Lost => 'Lost',
        };
    }

    /**
     * Stages still in play; closed stages are read-only.
     */
    public function isOpen(): bool
    {
        return in_array($this, [self::Qualification, self::Proposal, self::Negotiation], true);
    }

    /**
     * Default win probability used when the user does not override it.
     */
    public function defaultProbability(): int
    {
        return match ($this) {
            self::Qualification => 25,
            self::Proposal => 50,
            self::Negotiation => 75,
            self::Won => 100,
            self::Lost => 0,
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Qualification => 'bg-info-transparent',
            self::Proposal => 'bg-primary-transparent',
            self::Negotiation => 'bg-warning-transparent',
            self::Won => 'bg-success-transparent',
            self::Lost => 'bg-danger-transparent',
        };
    }
}
