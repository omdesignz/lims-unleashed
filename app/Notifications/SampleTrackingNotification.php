<?php

namespace App\Notifications;

use App\Models\VAPSampleEntry;

class SampleTrackingNotification extends GlobalNotification
{
    public VAPSampleEntry $sample;

    public function __construct(VAPSampleEntry $sample, string $title, string $message, $sender)
    {
        parent::__construct($title, $message, $sender);

        $this->sample = $sample;
    }

    public function toDatabase($notifiable)
    {
        $analysisId = $this->sample->collectionProduct?->code?->analysis()?->value('analysis.id');
        $collectionProductId = $this->sample->collection_product_id;
        $requiredParameterCount = data_get($this->sample->client_submitted_info, 'required_parameter_count', 0);

        return array_merge(parent::toDatabase($notifiable), [
            'sample_id' => $this->sample->id,
            'sample_code' => $this->sample->code,
            'sample_status' => $this->sample->status,
            'customer_id' => $this->sample->customer_id,
            'warehouse_id' => $this->sample->warehouse_id,
            'conditioning_status' => data_get($this->sample->client_submitted_info, 'conditioning_status'),
            'required_parameter_count' => $requiredParameterCount,
            'resolved_profiles' => data_get($this->sample->client_submitted_info, 'resolved_profiles', []),
            'collection_product_id' => $collectionProductId,
            'collection_url' => $collectionProductId ? route('directcollections.show', ['collection' => $collectionProductId]) : null,
            'analysis_url' => $analysisId ? route('analysis.edit', $analysisId) : null,
        ]);
    }
}
