<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateNotificationPreferencesRequest;
use App\Models\NotificationPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationPreferenceController extends Controller
{
    public function edit(Request $request): Response
    {
        $saved = $request->user()->notificationPreferences()->get()->keyBy('category');

        return Inertia::render('Profile/NotificationPreferences', [
            'preferences' => collect($this->categories())->map(function (array $category, string $key) use ($saved): array {
                $preference = $saved->get($key);

                return [
                    'category' => $key,
                    'label' => $category['label'],
                    'description' => $category['description'],
                    'database_enabled' => $preference?->database_enabled ?? true,
                    'broadcast_enabled' => $preference?->broadcast_enabled ?? true,
                    'mail_enabled' => $preference?->mail_enabled ?? true,
                    'quiet_hours_start' => $preference?->quiet_hours_start ? substr($preference->quiet_hours_start, 0, 5) : null,
                    'quiet_hours_end' => $preference?->quiet_hours_end ? substr($preference->quiet_hours_end, 0, 5) : null,
                    'timezone' => $preference?->timezone ?? config('app.timezone'),
                ];
            })->values(),
        ]);
    }

    public function update(UpdateNotificationPreferencesRequest $request): RedirectResponse
    {
        collect($request->validated('preferences'))->each(function (array $preference) use ($request): void {
            NotificationPreference::query()->updateOrCreate(
                ['user_id' => $request->user()->id, 'category' => $preference['category']],
                $preference
            );
        });

        return back()->with('toast', [
            'variant' => 'success',
            'title' => 'Preferências guardadas',
            'message' => 'Os próximos alertas respeitarão os canais e o período de silêncio definidos.',
        ]);
    }

    /** @return array<string, array{label: string, description: string}> */
    private function categories(): array
    {
        return [
            'laboratory' => ['label' => 'Operações laboratoriais', 'description' => 'Amostras, resultados, verificações e aprovações.'],
            'inventory' => ['label' => 'Inventário e reagentes', 'description' => 'Stock, consumos, entregas e encomendas.'],
            'quality' => ['label' => 'Qualidade', 'description' => 'Não conformidades, certificados e acções correctivas.'],
            'maintenance' => ['label' => 'Equipamento e manutenção', 'description' => 'Calibração, manutenção e prazos técnicos.'],
            'commercial' => ['label' => 'Documentos comerciais', 'description' => 'Facturas, cotações, notas de crédito e recibos.'],
            'trade' => ['label' => 'Importação e exportação', 'description' => 'Certificados e processos de comércio externo.'],
            'documents' => ['label' => 'Documentos partilhados', 'description' => 'Confirmações de envio e entrega de PDFs.'],
            'system' => ['label' => 'Processos do sistema', 'description' => 'Importações, exportações e tarefas em segundo plano.'],
        ];
    }
}
