<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'due_date',
        'priority',
        'status',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    // Priority order for sorting (high → medium → low)
    public static array $priorityOrder = ['high' => 1, 'medium' => 2, 'low' => 3];

    // Valid status transitions
    public static array $statusFlow = [
        'pending'     => 'in_progress',
        'in_progress' => 'done',
    ];

    /**
     * Check if this task can transition to the given status.
     */
    public function canTransitionTo(string $newStatus): bool
    {
        return isset(self::$statusFlow[$this->status])
            && self::$statusFlow[$this->status] === $newStatus;
    }

    /**
     * Scope: filter by optional status.
     */
    public function scopeByStatus($query, ?string $status)
    {
        if ($status) {
            $query->where('status', $status);
        }
        return $query;
    }

    /**
     * Scope: sort by priority (high→low) then due_date asc.
     */
    public function scopePrioritySorted($query)
    {
        return $query->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
                     ->orderBy('due_date', 'asc');
    }
}
