<?php

declare(strict_types=1);

namespace App\Entity;

use DomainException;

/** Domain representation of a Sales Order's identity, owner, and lifecycle state. */
final readonly class SalesOrder
{
    private const TRANSITIONS = [
        'Draft' => ['PendingApproval', 'Cancelled'],
        'PendingApproval' => ['Approved', 'Cancelled'],
        'Approved' => ['Fulfilled', 'Cancelled'],
        'Fulfilled' => [],
        'Cancelled' => [],
    ];

    public function __construct(
        public int $id,
        public int $createdBy,
        public string $status
    ) {
        if (!isset(self::TRANSITIONS[$status])) {
            throw new DomainException('Status Sales Order tidak valid.');
        }
    }

    public static function fromRecord(array $record): self
    {
        return new self(
            (int) ($record['id'] ?? 0),
            (int) ($record['created_by'] ?? 0),
            (string) ($record['status'] ?? '')
        );
    }

    public function transitionTo(string $nextStatus): self
    {
        if (!in_array($nextStatus, self::TRANSITIONS[$this->status], true)) {
            throw new DomainException('Transisi status Sales Order tidak diizinkan.');
        }
        return new self($this->id, $this->createdBy, $nextStatus);
    }
}
