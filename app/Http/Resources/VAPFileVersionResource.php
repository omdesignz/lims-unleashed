<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VAPFileVersionResource extends JsonResource
{
    /**
     * Notes the system itself once wrote in English on versions already kept.
     * They are shown in the application's language; the record is left as it is.
     */
    private const SYSTEM_NOTES = [
        'Initial version' => 'Versão inicial',
        'Initial issue' => 'Primeira emissão',
        'File updated' => 'Ficheiro actualizado',
        'Content updated' => 'Conteúdo actualizado',
        'Version restored' => 'Versão restaurada',
        'Version restored for controlled use' => 'Versão restaurada para uso controlado',
    ];

    private static function note(?string $note): ?string
    {
        return $note === null ? null : (self::SYSTEM_NOTES[$note] ?? $note);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'file_id' => $this->file_id,
            'revision_code' => $this->revision_code,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'checksum' => $this->checksum,
            'created_by' => $this->created_by,
            'comment' => self::note($this->comment),
            'change_reason' => self::note($this->change_reason),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'creator' => new UserResource($this->whenLoaded('creator')),
        ];
    }
}
