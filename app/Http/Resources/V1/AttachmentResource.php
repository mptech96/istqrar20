<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            
            // معلومات الملف
            'original_name' => $this->original_name,
            'file_name' => $this->file_name,
            'file_path' => $this->file_path,
            'file_url' => $this->file_url,
            
            // المواصفات
            'file_type' => $this->file_type,
            'file_type_label' => $this->getFileTypeLabel(),
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'file_size_formatted' => $this->getFormattedFileSize(),
            'file_extension' => $this->file_extension,
            
            // للملفات المرئية
            'width' => $this->width,
            'height' => $this->height,
            'duration' => $this->duration,
            
            // الوصف
            'title' => $this->title,
            'description' => $this->description,
            'alt_text' => $this->alt_text,
            
            // التخزين السحابي
            'storage_driver' => $this->storage_driver,
            'cloud_provider' => $this->cloud_provider,
            'cloud_public_id' => $this->cloud_public_id,
            'cloud_version' => $this->cloud_version,
            
            // المالك والإذن
            'uploaded_by' => $this->uploaded_by,
            'upload_source' => $this->upload_source,
            'upload_source_text' => $this->getUploadSourceText(),
            'is_public' => (bool) $this->is_public,
            'is_public_text' => $this->is_public ? 'عام' : 'خاص',
            'access_token' => $this->access_token,
            
            // الحالة
            'is_active' => (bool) $this->is_active,
            'is_active_text' => $this->is_active ? 'نشط' : 'غير نشط',
            'is_deleted' => (bool) $this->is_deleted,
            
            // التواريخ
            'created_at' => $this->created_at?->format('Y-m-d | h:i:s A'),
            'updated_at' => $this->updated_at?->format('Y-m-d | h:i:s A'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d | h:i:s A'),
            
            // العلاقات
            'uploader' => new UserResource($this->whenLoaded('uploader')),
            
            // معلومات إضافية
            'is_image' => $this->isImage(),
            'is_document' => $this->isDocument(),
            'is_video' => $this->isVideo(),
            'is_audio' => $this->isAudio(),
        ];
    }

    private function getFileTypeLabel(): string
    {
        return match ($this->file_type) {
            'image' => 'صورة',
            'document' => 'مستند',
            'video' => 'فيديو',
            'audio' => 'صوت',
            default => 'ملف',
        };
    }

    private function getFormattedFileSize(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' جيجابايت';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' ميجابايت';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' كيلوبايت';
        } else {
            return $bytes . ' بايت';
        }
    }

    private function getUploadSourceText(): string
    {
        return match ($this->upload_source) {
            'web' => 'ويب',
            'mobile' => 'موبايل',
            'api' => 'API',
            default => 'غير معروف',
        };
    }

    private function isImage(): bool
    {
        return $this->file_type === 'image';
    }

    private function isDocument(): bool
    {
        return $this->file_type === 'document';
    }

    private function isVideo(): bool
    {
        return $this->file_type === 'video';
    }

    private function isAudio(): bool
    {
        return $this->file_type === 'audio';
    }
}
