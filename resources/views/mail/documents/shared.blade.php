<x-mail::message>
# {{ $document['label'] }} {{ $document['number'] }}

{{ $mailMessage }}

<x-mail::button :url="$document['url']">
Abrir na plataforma
</x-mail::button>

O documento PDF segue em anexo para consulta e arquivo.

{{ app(\App\Support\WhiteLabelMessageDefaults::class)->salutationWithSignature() }}
</x-mail::message>
