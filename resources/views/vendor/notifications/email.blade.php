@php
    $defaults = \App\Support\WhiteLabelMessageDefaults::current();
@endphp

<x-mail::message>
{{-- Greeting --}}
@if (! empty($greeting))
# {{ $greeting }}
@else
@if ($level === 'error')
# @lang('Whoops!')
@else
# @lang('Hello!')
@endif
@endif

{{-- Intro Lines --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Action Button --}}
@isset($actionText)
<?php
    $color = match ($level) {
        'success', 'error' => $level,
        default => 'primary',
    };
?>
<x-mail::button :url="$actionUrl" :color="$color">
{{ $actionText }}
</x-mail::button>
@endisset

{{-- Outro Lines --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

@if ($defaults->mailFooter())
{{ $defaults->mailFooter() }}

@endif

{{-- Salutation --}}
@if (! empty($salutation))
{{ $salutation }}
@else
{!! nl2br(e($defaults->salutationWithSignature()), false) !!}
@endif

{{-- Subcopy --}}
@isset($actionText)
<x-slot:subcopy>
{{ $defaults->mailSubcopy("Se tiver dificuldades em clicar no botão \"{$actionText}\", copie e cole o URL abaixo no seu navegador:") }}
<span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset
</x-mail::message>
