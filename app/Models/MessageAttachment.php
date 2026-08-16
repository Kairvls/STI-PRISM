<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageAttachment extends Model
{
    // =====================================================
    // TABLE
    // =====================================================

    protected $table = 'message_attachments';

    protected $primaryKey = 'message_attachment_id';

    public $timestamps = true;


    // =====================================================
    // FILLABLE FIELDS
    // =====================================================

    protected $fillable = [
        'message_id',

        'attachment_name',
        'attachment_path',
        'attachment_url',

        'attachment_type',
        'attachment_extension',
        'attachment_size',
    ];

    protected $appends = [
        'name',
        'path',
        'url',
        'type',
        'extension',
        'size',
    ];

    public function getNameAttribute(): string
    {
        return $this->attachment_name;
    }

    public function getPathAttribute(): string
    {
        return $this->attachment_path;
    }

    public function getUrlAttribute(): ?string
    {
        if (! empty($this->attachment_url)) {
            return $this->attachment_url;
        }

        if (! empty($this->attachment_path)) {
            return asset('storage/'.ltrim($this->attachment_path, '/'));
        }

        return null;
    }

    public function getTypeAttribute(): ?string
    {
        return $this->attachment_type;
    }

    public function getExtensionAttribute(): ?string
    {
        return $this->attachment_extension;
    }

    public function getSizeAttribute(): ?int
    {
        return $this->attachment_size !== null
            ? (int) $this->attachment_size
            : null;
    }


    // =====================================================
    // MESSAGE THIS ATTACHMENT BELONGS TO
    // =====================================================

    public function message(): BelongsTo
    {
        return $this->belongsTo(
            Message::class,
            'message_id',
            'message_id'
        );
    }
}