<?php

namespace Database\Factories;

use App\Models\NotificationTemplate;
use App\Models\User;
use App\Models\VAPLab;
use App\Support\NotificationTemplateCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationTemplateFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'lab_id' => VAPLab::factory(), 'updated_by_id' => User::factory(),
            'key' => 'commercial.invoice.paid',
            ...collect(app(NotificationTemplateCatalog::class)->definitions()['commercial.invoice.paid'])
                ->only(NotificationTemplate::EDITABLE_FIELDS)->all(),
        ];
    }
}
