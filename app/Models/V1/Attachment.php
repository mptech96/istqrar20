<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attachment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'original_name',
        'file_name',
        'file_path',
        'file_url',
        'file_type',
        'mime_type',
        'file_size',
        'file_extension',
        'width',
        'height',
        'duration',
        'title',
        'description',
        'alt_text',
        'storage_driver',
        'cloud_provider',
        'cloud_public_id',
        'cloud_version',
        'uploaded_by',
        'upload_source',
        'is_public',
        'access_token',
        'is_active',
        'is_deleted',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'duration' => 'integer',
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    public function entity()
    {
        return $this->morphTo();
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeImages($query)
    {
        return $query->where('file_type', 'image');
    }

    public function scopeDocuments($query)
    {
        return $query->where('file_type', 'document');
    }

    public function scopeVideos($query)
    {
        return $query->where('file_type', 'video');
    }

    public function scopeAudios($query)
    {
        return $query->where('file_type', 'audio');
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getFileTypeLabel()
    {
        return match ($this->file_type) {
            'image' => 'صورة',
            'document' => 'مستند',
            'video' => 'فيديو',
            'audio' => 'صوت',
            default => 'ملف',
        };
    }

    public function getFormattedFileSize()
    {
        $bytes = $this->file_size;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    public function isImage()
    {
        return $this->file_type === 'image';
    }

    public function isDocument()
    {
        return $this->file_type === 'document';
    }

    public function isVideo()
    {
        return $this->file_type === 'video';
    }

    public function isAudio()
    {
        return $this->file_type === 'audio';
    }
}
