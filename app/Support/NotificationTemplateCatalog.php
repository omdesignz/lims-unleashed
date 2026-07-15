<?php

namespace App\Support;

use App\Models\NotificationTemplate;
use Illuminate\Support\Collection;

class NotificationTemplateCatalog
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function definitions(): array
    {
        return [
            'lab.collection.processed' => $this->definition('Amostra colocada em análise', 'laboratory', 'A amostra {{sample_code}} entrou na fila de análise.', 'Acompanhar amostra', '{{sample_url}}', 'view_analysis'),
            'lab.results.inserted' => $this->definition('Resultados inseridos', 'laboratory', 'Foram inseridos resultados para {{sample_code}}. A verificação está pendente.', 'Rever resultados', '{{results_url}}', 'verify_results'),
            'lab.results.verified' => $this->definition('Resultados verificados', 'laboratory', 'Os resultados de {{sample_code}} foram verificados e aguardam aprovação.', 'Abrir resultados', '{{results_url}}', 'approve_results'),
            'lab.results.approved' => $this->definition('Resultados aprovados', 'laboratory', 'Os resultados de {{sample_code}} foram aprovados.', 'Abrir resultados', '{{results_url}}', 'view_quality_certificates'),
            'lab.results.validated' => $this->definition('Boletim validado', 'laboratory', 'O boletim {{document_number}} foi validado e está disponível.', 'Abrir boletim', '{{document_url}}', 'view_quality_certificates', ['database', 'broadcast', 'mail'], 'high'),
            'lab.counter_results.inserted' => $this->definition('Contra-análise inserida', 'laboratory', 'Foram inseridos resultados de contra-análise para {{sample_code}}.', 'Rever contra-análise', '{{results_url}}', 'verify_results'),
            'lab.counter_results.verified' => $this->definition('Contra-análise verificada', 'laboratory', 'A contra-análise de {{sample_code}} foi verificada.', 'Abrir contra-análise', '{{results_url}}', 'approve_results'),
            'lab.counter_results.approved' => $this->definition('Contra-análise aprovada', 'laboratory', 'A contra-análise de {{sample_code}} foi aprovada.', 'Abrir contra-análise', '{{results_url}}', 'view_counter_analysis'),
            'lab.sample.retention_due' => $this->definition('Prazo de retenção de amostra', 'laboratory', 'A amostra {{sample_code}} está {{retention_status}}. Prazo: {{due_date}}.', 'Abrir amostra', '{{document_url}}', 'view_samples', ['database', 'broadcast', 'mail'], 'high'),
            'lab.sample.linked' => $this->definition('Amostra integrada no fluxo', 'laboratory', 'A amostra {{sample_code}} foi integrada em {{collection_code}} com {{parameter_count}} parâmetros previstos.', 'Abrir amostras', '{{document_url}}', 'view_analysis'),
            'lab.sample.created' => $this->definition('Nova amostra registada', 'laboratory', 'A amostra {{sample_code}} foi registada e aguarda validação operacional.', 'Abrir amostra', '{{document_url}}', 'view_samples'),
            'lab.sample.status_updated' => $this->definition('Estado da amostra actualizado', 'laboratory', 'A amostra {{sample_code}} mudou de {{previous_status}} para {{status}}.', 'Abrir amostra', '{{document_url}}', 'view_samples'),
            'lab.sample.quality_decision' => $this->definition('Decisão de CQ interno registada', 'laboratory', 'A decisão final de CQ interno da amostra {{sample_code}} foi registada como {{decision_label}}.', 'Abrir amostra', '{{document_url}}', 'view_samples', ['database', 'broadcast'], 'high'),
            'lab.sample.stale' => $this->definition('Amostra sem avanço', 'laboratory', 'A amostra {{sample_code}} permanece em {{status}} desde {{last_updated_at}} e requer acompanhamento.', 'Abrir amostras', '{{document_url}}', 'view_analysis', ['database', 'broadcast', 'mail'], 'high'),
            'lab.results.stale' => $this->definition('Resultados pendentes', 'laboratory', 'A amostra {{sample_code}} tem resultados pendentes de {{stage}} há demasiado tempo.', 'Abrir análises', '{{document_url}}', 'view_analysis', ['database', 'broadcast', 'mail'], 'high'),
            'lab.counter_analysis.requested' => $this->definition('Contra-análise solicitada', 'laboratory', 'Foi solicitada uma contra-análise para {{parameter_name}} da amostra {{sample_code}}.', 'Abrir contra-análises', '{{document_url}}', 'view_counter_analysis', ['database', 'broadcast'], 'high'),
            'lab.counter_analysis.stale' => $this->definition('Contra-análise sem avanço', 'laboratory', 'A contra-análise da amostra {{sample_code}} continua pendente e requer acompanhamento.', 'Abrir contra-análises', '{{document_url}}', 'view_counter_analysis', ['database', 'broadcast', 'mail'], 'high'),
            'inventory.order.updated' => $this->definition('Pedido de inventário actualizado', 'inventory', 'O pedido {{order_number}} passou para {{status}}.', 'Abrir pedido', '{{order_url}}', 'view_iorders'),
            'inventory.order.delivered' => $this->definition('Pedido entregue', 'inventory', 'O pedido {{order_number}} foi marcado como entregue.', 'Abrir pedido', '{{order_url}}', 'view_iorders', ['database', 'broadcast', 'mail'], 'high'),
            'inventory.stock.updated' => $this->definition('Movimento de stock', 'inventory', '{{item_name}} teve um movimento de {{quantity}} {{unit}}.', 'Abrir inventário', '{{inventory_url}}', 'view_inventory'),
            'inventory.reagent.consumed' => $this->definition('Reagente consumido', 'inventory', 'Foi registado o consumo de {{quantity}} de {{item_name}}.', 'Abrir consumo', '{{inventory_url}}', 'view_inventory'),
            'inventory.need.updated' => $this->definition('Necessidade de inventário actualizada', 'inventory', 'A necessidade {{need_reference}} passou para {{status}}. {{detail}}', 'Abrir necessidade', '{{document_url}}', 'view_iorders'),
            'inventory.low_stock' => $this->definition('Existência abaixo do mínimo', 'inventory', '{{item_name}} tem {{quantity}} em stock; mínimo definido: {{minimum}}.', 'Abrir inventário', '{{document_url}}', 'view_inventory', ['database', 'broadcast', 'mail'], 'high'),
            'inventory.supplier_assessment' => $this->definition('Avaliação de fornecedor requer atenção', 'inventory', '{{supplier_name}} está com estado {{status}} e risco {{risk_level}}. {{detail}}', 'Abrir fornecedores', '{{document_url}}', 'view_isuppliers', ['database', 'broadcast', 'mail'], 'high'),
            'commercial.invoice.created' => $this->definition('Factura emitida', 'commercial', 'A factura {{document_number}} foi emitida para {{customer_name}} no valor de {{total}}.', 'Abrir factura', '{{document_url}}', 'view_invoices'),
            'commercial.invoice.paid' => $this->definition('Pagamento confirmado', 'commercial', 'A factura {{document_number}} foi liquidada.', 'Abrir factura', '{{document_url}}', 'view_invoices', ['database', 'broadcast'], 'high'),
            'commercial.quote.created' => $this->definition('Cotação emitida', 'commercial', 'A cotação {{document_number}} foi emitida para {{customer_name}}.', 'Abrir cotação', '{{document_url}}', 'view_quotes'),
            'commercial.quote.converted' => $this->definition('Cotação convertida', 'commercial', 'A cotação {{document_number}} foi convertida em factura.', 'Abrir cotação', '{{document_url}}', 'view_quotes'),
            'commercial.credit_note.created' => $this->definition('Nota de crédito emitida', 'commercial', 'A nota de crédito {{document_number}} foi emitida para {{customer_name}}.', 'Abrir nota', '{{document_url}}', 'view_credit_notes', ['database', 'broadcast'], 'high'),
            'commercial.receipt.created' => $this->definition('Recibo emitido', 'commercial', 'O recibo {{document_number}} foi emitido para {{customer_name}}.', 'Abrir recibos', '{{document_url}}', 'view_receipts'),
            'commercial.proposal.updated' => $this->definition('Proposta actualizada', 'commercial', 'A proposta {{document_number}} passou para {{status}}. {{detail}}', 'Abrir proposta', '{{document_url}}', 'view_proposals'),
            'commercial.proposal.sent_customer' => $this->definition('Proposta disponível', 'commercial', 'A proposta {{document_number}} está disponível para revisão e aceitação.', 'Ver proposta', '{{document_url}}', null, ['database', 'mail'], 'high'),
            'commercial.proposal.compliance_acknowledged' => $this->definition('Acordo de conformidade aceite', 'commercial', 'O acordo de conformidade da proposta {{document_number}} foi aceite.', 'Ver proposta', '{{document_url}}', null, ['database', 'mail'], 'high'),
            'commercial.portal_request.created' => $this->definition('Novo pedido do portal', 'commercial', '{{customer_name}} submeteu o pedido {{document_number}} do tipo {{request_type}}.', 'Abrir pedidos', '{{document_url}}', null, ['database', 'broadcast'], 'high'),
            'trade.import_certificate.created' => $this->definition('Certificado de importação criado', 'trade', 'O certificado {{document_number}} foi criado para {{customer_name}}.', 'Abrir certificado', '{{document_url}}', 'view_import_certificates'),
            'trade.export_certificate.created' => $this->definition('Certificado de exportação criado', 'trade', 'O certificado {{document_number}} foi criado para {{customer_name}}.', 'Abrir certificado', '{{document_url}}', 'view_export_certificates'),
            'quality.certificate.validated' => $this->definition('Certificado de qualidade validado', 'quality', 'O certificado {{document_number}} foi validado por {{actor_name}}.', 'Abrir certificado', '{{document_url}}', 'view_quality_certificates', ['database', 'broadcast', 'mail'], 'high'),
            'quality.nonconformity.updated' => $this->definition('Não conformidade actualizada', 'quality', 'A não conformidade {{document_number}} passou para {{status}}.', 'Abrir registo', '{{document_url}}', 'view_occurrences', ['database', 'broadcast'], 'high'),
            'quality.occurrence.overdue' => $this->definition('Ocorrência em atraso', 'quality', 'A ocorrência {{document_number}} ultrapassou o prazo definido.', 'Abrir ocorrência', '{{document_url}}', 'view_occurrences', ['database', 'broadcast', 'mail'], 'urgent'),
            'quality.rating.received' => $this->definition('Nova avaliação recebida', 'quality', 'Foi recebida uma avaliação {{channel}} para {{rateable_type}} #{{rateable_id}}.', 'Abrir avaliações', '{{document_url}}', 'view_ratings'),
            'quality.rating.requested' => $this->definition('Novo pedido de avaliação', 'quality', 'Tem um novo item pendente de avaliação: {{rateable_type}} #{{rateable_id}}.', 'Avaliar agora', '{{document_url}}', null, ['database', 'broadcast', 'mail']),
            'quality.nonconformity.created' => $this->definition('Não conformidade registada', 'quality', '{{document_number}} foi registada com severidade {{severity}} e estado {{status}}.', 'Abrir registo', '{{document_url}}', 'view_occurrences', ['database', 'broadcast', 'mail'], 'high'),
            'quality.proficiency_test.updated' => $this->definition('Ensaio de proficiência actualizado', 'quality', '{{document_number}} passou para {{status}} com resultado {{outcome}}. {{detail}}', 'Abrir ensaios', '{{document_url}}', 'view_analysis', ['database', 'broadcast', 'mail'], 'high'),
            'quality.complaint.created' => $this->definition('Nova reclamação registada', 'quality', 'A reclamação {{document_number}} foi registada com severidade {{severity}} e requer análise.', 'Abrir reclamações', '{{document_url}}', null, ['database', 'broadcast', 'mail'], 'high'),
            'quality.management_review.scheduled' => $this->definition('Revisão pela gestão agendada', 'quality', 'A revisão {{document_number}} foi agendada para {{review_date}}.', 'Abrir revisões', '{{document_url}}', null, ['database', 'broadcast', 'mail'], 'high'),
            'quality.management_review.completed' => $this->definition('Revisão pela gestão concluída', 'quality', 'A revisão {{document_number}} foi concluída com decisões e acções registadas.', 'Abrir revisões', '{{document_url}}', null, ['database', 'broadcast', 'mail'], 'high'),
            'maintenance.reminder' => $this->definition('Manutenção próxima', 'maintenance', '{{equipment_name}} tem manutenção prevista para {{due_date}}.', 'Abrir manutenção', '{{document_url}}', 'view_maintenance_tasks', ['database', 'broadcast', 'mail'], 'high'),
            'maintenance.overdue' => $this->definition('Manutenção em atraso', 'maintenance', '{{equipment_name}} ultrapassou o prazo de manutenção em {{due_date}}.', 'Abrir manutenção', '{{document_url}}', 'view_maintenance_tasks', ['database', 'broadcast', 'mail'], 'urgent'),
            'maintenance.completed' => $this->definition('Manutenção concluída', 'maintenance', 'A manutenção de {{equipment_name}} foi concluída.', 'Abrir manutenção', '{{document_url}}', 'view_maintenance_tasks'),
            'documents.shared' => $this->definition('Documento enviado', 'documents', '{{document_label}} {{document_number}} foi enviado para {{recipients}}.', 'Abrir documento', '{{document_url}}', null, ['database', 'broadcast']),
            'documents.share_failed' => $this->definition('Falha no envio do documento', 'documents', 'Não foi possível enviar {{document_label}}. {{detail}}', 'Rever envios', '{{document_url}}', null, ['database', 'broadcast'], 'urgent'),
            'documents.controlled_file.shared' => $this->definition('Documento controlado partilhado', 'documents', '{{actor_name}} concedeu acesso {{access_level}} ao documento {{document_label}}.', 'Abrir gestor documental', '{{document_url}}', null),
            'documents.export.ready' => $this->definition('Exportação pronta', 'documents', '{{document_label}} está pronto para transferência.', 'Transferir', '{{document_url}}', null, ['database', 'broadcast', 'mail']),
            'system.import.completed' => $this->definition('Importação concluída', 'system', 'A importação de {{document_label}} terminou com sucesso.', 'Abrir registos', '{{document_url}}', null),
            'system.import.failed' => $this->definition('Falha na importação', 'system', 'A importação de {{document_label}} falhou. {{detail}}', 'Rever importação', '{{document_url}}', null, ['database', 'broadcast', 'mail'], 'high'),
            'system.message.received' => $this->definition('Nova mensagem recebida', 'system', '{{actor_name}} enviou: {{message_excerpt}}', 'Abrir mensagens', '{{document_url}}', null, ['database', 'broadcast', 'mail']),
        ];
    }

    /**
     * @return Collection<int, NotificationTemplate>
     */
    public function synchronize(): Collection
    {
        return collect($this->definitions())->map(function (array $definition, string $key): NotificationTemplate {
            $template = NotificationTemplate::query()->firstOrNew(['key' => $key]);

            if (! $template->exists) {
                $template->fill(['key' => $key, ...$definition]);
            } else {
                $template->fill(collect($definition)->only([
                    'name',
                    'category',
                    'description',
                    'audience_permission',
                    'variables',
                ])->all());
            }

            $template->save();

            return $template;
        })->values();
    }

    /**
     * @param  array<int, string>  $channels
     * @return array<string, mixed>
     */
    private function definition(
        string $name,
        string $category,
        string $message,
        string $actionLabel,
        string $actionUrl,
        ?string $audiencePermission,
        array $channels = ['database', 'broadcast'],
        string $priority = 'normal'
    ): array {
        return [
            'name' => $name,
            'category' => $category,
            'description' => $message,
            'audience_permission' => $audiencePermission,
            'title_template' => $name,
            'in_app_template' => $message,
            'email_subject_template' => $name.' · {{lab_name}}',
            'email_template' => $message,
            'action_label_template' => $actionLabel,
            'action_url_template' => $actionUrl,
            'channels' => $channels,
            'variables' => $this->variables($message.' '.$actionUrl),
            'priority' => $priority,
            'enabled' => true,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function variables(string $text): array
    {
        preg_match_all('/{{\s*([a-zA-Z0-9_.-]+)\s*}}/', $text, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }
}
