<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['operacao', 'payload', 'user_id', 'ip', 'created_at'])]
class AuditLog extends Model
{
    protected $table = 'audit_logs';

    /**
     * A tabela possui apenas created_at; não há updated_at.
     */
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
