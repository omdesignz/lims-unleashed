@php
    try {
        $letterheadSettings = $settings ?? app(\App\Settings\GeneralSettings::class);
    } catch (\Throwable) {
        $letterheadSettings = null;
    }

    $letterheadName = $letterheadSettings?->app_client_lab_name
        ?: $letterheadSettings?->app_name
        ?: config('app.name', 'Laboratory workspace');
    $letterheadOrganization = $letterheadSettings?->app_client_name;
    $letterheadDetails = array_values(array_filter([
        $letterheadSettings?->app_client_address,
        $letterheadSettings?->app_client_contact,
        $letterheadSettings?->app_client_email,
        $letterheadSettings?->app_client_nif ? 'NIF '.$letterheadSettings->app_client_nif : null,
    ]));
@endphp

<table width="100%" cellpadding="0" cellspacing="0" style="border-bottom: 0.35mm solid {{ $documentPrimaryColor ?? '#143d37' }}; margin-bottom: 5mm; padding-bottom: 3mm;">
    <tr>
        <td width="18%" valign="middle">
            @include('PDFs.partials.brand-logo', ['settings' => $letterheadSettings, 'width' => '58px', 'style' => 'max-height: 52px; height: auto;'])
        </td>
        <td width="82%" valign="middle" style="text-align: right;">
            <div style="color: {{ $documentPrimaryColor ?? '#143d37' }}; font-size: 12px; font-weight: 800; line-height: 1.25;">{{ mb_strtoupper($letterheadName) }}</div>
            @if($letterheadOrganization && $letterheadOrganization !== $letterheadName)
                <div style="margin-top: 2px; color: #475569; font-size: 9px; font-weight: 700;">{{ mb_strtoupper($letterheadOrganization) }}</div>
            @endif
            @if($letterheadDetails !== [])
                <div style="margin-top: 3px; color: #64748b; font-size: 8px; line-height: 1.45;">{{ implode(' | ', $letterheadDetails) }}</div>
            @endif
        </td>
    </tr>
</table>
