<?php

declare(strict_types=1);

namespace App\Structures\Api;

class ApiMockupGeneratorTask
{
    public ?int $id;
    public ?string $status;
    public array $catalogVariantMockups = [];

    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }

    public static function fromArray(array $data): self
    {
        $self = new self();
        $self->id = $data['id'] ?? null;
        $self->status = $data['status'] ?? null;
        $self->catalogVariantMockups = $data['catalog_variants_mockups'] ?? [];

        return $self;
    }
}
