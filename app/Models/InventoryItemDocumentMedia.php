<?php

namespace App\Models;

use Database\Factories\InventoryItemDocumentMediaFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class InventoryItemDocumentMedia extends Media
{
    /** @use HasFactory<InventoryItemDocumentMediaFactory> */
    use HasFactory, SoftDeletes;

    /** @var array<string,mixed> */
    private array $creationIntent = [];

    private ?self $creationEvidence = null;

    /** @param array<string,mixed> $attributes */
    public function setInventoryCreationIntentAttribute(array $attributes): void
    {
        $this->creationIntent = $attributes;
    }

    public function save(array $options = []): bool
    {
        if ($this->exists || $this->creationIntent === []) {
            return parent::save($options);
        }
        $this->setCreatedAt($this->freshTimestamp());
        $this->setUpdatedAt($this->created_at);
        $intended = clone $this;
        $intended->forceFill($this->creationIntent);
        $saved = parent::save($options);
        abort_unless($saved && $this->exists && $this->getKey(), 409, 'Não foi possível guardar o documento do item.');
        $intended->setAttribute($this->getKeyName(), $this->getKey());
        $this->creationEvidence = $intended;
        $this->assertCreationEvidence();

        return true;
    }

    public function frozenCreationEvidence(): self
    {
        abort_unless($this->creationEvidence, 409, 'Não foi possível guardar o documento do item.');

        return clone $this->creationEvidence;
    }

    public function assertCreationEvidence(): void
    {
        $expected = $this->frozenCreationEvidence();
        $row = $this->newQuery()->toBase()->find($expected->id);
        abort_unless($row, 409, 'Não foi possível guardar o documento do item.');
        $stored = new self;
        $stored->setRawAttributes((array) $row, true);
        foreach (array_keys($expected->getAttributes()) as $field) {
            $intended = $expected->getAttribute($field);
            $actual = $stored->getAttribute($field);
            if ($intended instanceof DateTimeInterface) {
                $intended = $intended->format('Y-m-d H:i:s.u');
            }
            if ($actual instanceof DateTimeInterface) {
                $actual = $actual->format('Y-m-d H:i:s.u');
            }
            abort_unless($actual === $intended, 409, 'O documento guardado difere do documento submetido.');
        }
    }

    public function getUrl(string $conversionName = ''): string
    {
        if ($this->model_type === (new InventoryItem)->getMorphClass() && $this->collection_name === 'documents') {
            return route('vap-inventory.items.attachments.download-single', ['model_id' => $this->id]);
        }

        return parent::getUrl($conversionName);
    }
}
