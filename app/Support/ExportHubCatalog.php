<?php

namespace App\Support;

use App\Models\User;

class ExportHubCatalog
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function directDatasets(): array
    {
        return [
            'activity_log' => $this->definition('export_activity_log', 'Registo de actividade', 'Eventos técnicos e administrativos, actores, entidades e alterações registadas.', 'Sistema', 'activity_log', 'activity', 'registo-actividade', 'Registo de actividade', '24536B'),
            'customers' => $this->definition('export_customers', 'Clientes', 'Carteira de clientes, classificação, locais, contactos e estado do registo.', 'Cadastros', 'customers', 'customers', 'clientes', 'Clientes', '0F766E'),
            'warehouses' => $this->definition('export_warehouses', 'Locais de clientes', 'Locais operacionais e de facturação, contactos, NIF e relação com o cliente.', 'Cadastros', 'warehouses', 'warehouses', 'locais-clientes', 'Locais', '0F766E'),
            'products' => $this->definition('export_products', 'Produtos laboratoriais', 'Portefólio, matrizes, preços, regras fiscais, retenções e isenções.', 'Configuração laboratorial', 'products', 'products', 'produtos', 'Produtos', '374151'),
            'parameters' => $this->definition('export_parameters', 'Parâmetros', 'Catálogo analítico, preços, tributação, fórmulas e configuração de resultados.', 'Configuração laboratorial', 'parameters', 'parameters', 'parametros', 'Parâmetros', '0369A1'),
            'profiles' => $this->definition('export_profiles', 'Perfis analíticos', 'Perfis de ensaio, categorias, composição de parâmetros e preços calculados.', 'Configuração laboratorial', 'profiles', 'profiles', 'perfis-analiticos', 'Perfis', '0369A1'),
            'matrixes' => $this->definition('export_matrixes', 'Matrizes', 'Matrizes laboratoriais, perfis associados, produtos e regras comerciais.', 'Configuração laboratorial', 'matrixes', 'matrixes', 'matrizes', 'Matrizes', '0369A1'),
            'invoices' => $this->definition('export_invoices', 'Facturas', 'Facturas emitidas, estado de pagamento, valores, clientes e rastreabilidade.', 'Comercial', 'invoices', 'invoices', 'facturas', 'Facturas', '1D4ED8', 'Data do documento'),
            'quotes' => $this->definition('export_quotes', 'Cotações', 'Propostas comerciais, validade, conversão em factura, valores e clientes.', 'Comercial', 'quotes', 'quotes', 'cotacoes', 'Cotações', '1D4ED8', 'Data do documento'),
            'credit_notes' => $this->definition('export_credit_notes', 'Notas de crédito', 'Rectificações e anulações associadas a facturas, clientes e valores.', 'Comercial', 'credit_notes', 'credit_notes', 'notas-credito', 'Notas de crédito', 'BE123C', 'Data do documento'),
            'receipts' => $this->definition('export_receipts', 'Recibos', 'Pagamentos recebidos, facturas liquidadas, meios de pagamento e valores.', 'Comercial', 'receipts', 'receipts', 'recibos', 'Recibos', '047857', 'Data do documento'),
            'contract_guides' => $this->definition('export_contract_guides', 'Guias contratuais', 'Guias de circulação, referências logísticas, clientes e pontos de entrada.', 'Logística e comércio externo', 'contract_guides', 'contract_guides', 'guias-contratuais', 'Guias contratuais', '7C3AED', 'Data da guia'),
            'import_certificates' => $this->definition('export_import_certificates', 'Certificados de importação', 'Operações de importação, intervenientes, transporte, custos e facturação.', 'Logística e comércio externo', 'import_certificates', 'trade_certificates', 'certificados-importacao', 'Importações', '7C3AED', 'Data do certificado'),
            'export_certificates' => $this->definition('export_export_certificates', 'Certificados de exportação', 'Operações de exportação, destinos, transporte, expedição e facturação.', 'Logística e comércio externo', 'export_certificates', 'trade_certificates', 'certificados-exportacao', 'Exportações', '7C3AED', 'Data do certificado'),
            'quality_certificates' => $this->definition('export_quality_certificates', 'Certificados de qualidade', 'Certificados emitidos, amostras, clientes, validação e versões controladas.', 'Qualidade', 'quality_certificates', 'quality_certificates', 'certificados-qualidade', 'Certificados', '0E7490'),
            'customer_requests' => $this->definition('export_customer_requests', 'Solicitações de clientes', 'Pedidos recebidos, prioridade, estado, prazos, responsáveis e resolução.', 'Atendimento', 'customer_requests', 'customer_requests', 'solicitacoes-clientes', 'Solicitações', 'B45309'),
            'occurrences' => $this->definition('export_occurrences', 'Ocorrências', 'Ocorrências da qualidade, origem, categoria, responsáveis e acções correctivas.', 'Qualidade', 'occurrences', 'occurrences', 'ocorrencias', 'Ocorrências', 'B91C1C', 'Data reportada'),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function directKeys(): array
    {
        return array_keys($this->directDatasets());
    }

    public function isDirect(string $dataset): bool
    {
        return array_key_exists($dataset, $this->directDatasets());
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $dataset): array
    {
        return $this->directDatasets()[$dataset] ?? abort(404);
    }

    public function firstPermitted(User $user): ?string
    {
        foreach ($this->directDatasets() as $key => $dataset) {
            if ($user->can($dataset['permission'])) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @return array<int, array{key: string, label: string, width: int, type?: string, wrap?: bool}>
     */
    public function columns(string $dataset): array
    {
        return match ($dataset) {
            'parameters' => [
                $this->column('id', 'ID', 10, 'integer'), $this->column('code', 'Código', 16), $this->column('name', 'Parâmetro', 28), $this->column('description', 'Descrição', 42, wrap: true),
                $this->column('price', 'Preço', 14, 'float'), $this->column('active', 'Activo', 12, 'yes_no'), $this->column('charge_tax', 'Tributado', 12, 'yes_no'), $this->column('tax_percentage', 'Taxa (%)', 12, 'float'),
                $this->column('tax_category', 'Categoria fiscal', 22), $this->column('withhold_tax', 'Retenção', 12, 'yes_no'), $this->column('exemption_code', 'Código de isenção', 18), $this->column('result_type', 'Tipo de resultado', 18),
                $this->column('result_is_qualitative', 'Qualitativo', 12, 'yes_no'), $this->column('decimal_places', 'Casas decimais', 15, 'integer'), $this->column('optimal_analysis_time', 'Tempo óptimo', 18), $this->column('requires_calculation', 'Requer cálculo', 15, 'yes_no'),
                $this->column('formula', 'Fórmula', 24), $this->column('profile_count', 'Perfis', 10, 'integer'), $this->column('deleted_at', 'Estado', 12, 'record_status'), $this->column('created_at', 'Criado em', 20), $this->column('updated_at', 'Actualizado em', 20),
            ],
            'profiles' => [
                $this->column('id', 'ID', 10, 'integer'), $this->column('code', 'Código', 16), $this->column('name', 'Perfil', 28), $this->column('description', 'Descrição', 42, wrap: true),
                $this->column('category_code', 'Categoria', 18), $this->column('category', 'Designação da categoria', 28), $this->column('department', 'Departamento', 24), $this->column('configured_price', 'Preço configurado', 16, 'float'),
                $this->column('calculated_price', 'Preço dos parâmetros', 18, 'float'), $this->column('parameter_count', 'Parâmetros', 12, 'integer'), $this->column('matrix_count', 'Matrizes', 10, 'integer'), $this->column('deleted_at', 'Estado', 12, 'record_status'),
                $this->column('created_at', 'Criado em', 20), $this->column('updated_at', 'Actualizado em', 20),
            ],
            'matrixes' => [
                $this->column('id', 'ID', 10, 'integer'), $this->column('code', 'Código', 16), $this->column('description', 'Matriz', 38, wrap: true), $this->column('price', 'Preço', 14, 'float'),
                $this->column('fixed_price', 'Preço fixo', 14, 'float'), $this->column('charge_tax', 'Tributado', 12, 'yes_no'), $this->column('tax_percentage', 'Taxa (%)', 12, 'float'), $this->column('tax_category', 'Categoria fiscal', 22),
                $this->column('withhold_tax', 'Retenção', 12, 'yes_no'), $this->column('exemption_code', 'Código de isenção', 18), $this->column('profile_count', 'Perfis', 10, 'integer'), $this->column('product_count', 'Produtos', 10, 'integer'),
                $this->column('deleted_at', 'Estado', 12, 'record_status'), $this->column('created_at', 'Criado em', 20), $this->column('updated_at', 'Actualizado em', 20),
            ],
            'warehouses' => [
                $this->column('id', 'ID', 10, 'integer'), $this->column('code', 'Código', 16), $this->column('name', 'Local', 28), $this->column('customer_code', 'Código do cliente', 18),
                $this->column('customer', 'Cliente', 28), $this->column('nif', 'NIF', 18), $this->column('address', 'Endereço', 40, wrap: true), $this->column('municipality', 'Município', 20),
                $this->column('province', 'Província', 20), $this->column('primary_phone', 'Telefone', 18), $this->column('alternative_phone', 'Telefone alternativo', 20), $this->column('email', 'Email', 28),
                $this->column('invoicing_email', 'Email de facturação', 28), $this->column('focal_point', 'Ponto focal', 24), $this->column('focal_point_contact', 'Contacto do ponto focal', 22), $this->column('focal_point_email', 'Email do ponto focal', 28),
                $this->column('deleted_at', 'Estado', 12, 'record_status'), $this->column('created_at', 'Criado em', 20), $this->column('updated_at', 'Actualizado em', 20),
            ],
            'invoices' => $this->commercialColumns('inv_no', 'Factura', true),
            'quotes' => $this->commercialColumns('quote_no', 'Cotação', false, true),
            'credit_notes' => $this->creditNoteColumns(),
            'receipts' => [
                $this->column('id', 'ID', 10, 'integer'), $this->column('rec_no', 'Recibo', 20), $this->column('date', 'Data', 14), $this->column('customer_code', 'Código do cliente', 18),
                $this->column('customer', 'Cliente', 28), $this->column('warehouse', 'Local', 26), $this->column('payment_method', 'Meio de pagamento', 22), $this->column('invoice_count', 'Facturas', 10, 'integer'),
                $this->column('paid_amount', 'Valor recebido', 16, 'float'), $this->column('description', 'Descrição', 34, wrap: true), $this->column('issued_by', 'Emitido por', 24), $this->column('exported_saft', 'Exportado SAF-T', 16, 'yes_no'),
                $this->column('deleted_at', 'Estado', 12, 'record_status'), $this->column('created_at', 'Criado em', 20), $this->column('updated_at', 'Actualizado em', 20),
            ],
            'contract_guides' => [
                $this->column('id', 'ID', 10, 'integer'), $this->column('guide_no', 'Guia', 20), $this->column('date', 'Data', 14), $this->column('ref_no', 'Referência', 18),
                $this->column('customer_code', 'Código do cliente', 18), $this->column('customer', 'Cliente', 28), $this->column('warehouse', 'Local', 26), $this->column('nif', 'NIF', 18),
                $this->column('entry_point', 'Ponto de entrada', 22), $this->column('collection_point', 'Ponto de recolha', 22), $this->column('du_no', 'N.º DU', 18), $this->column('bl', 'BL', 18),
                $this->column('lab_code', 'Código laboratorial', 20), $this->column('item_count', 'Itens', 10, 'integer'), $this->column('contact', 'Contacto', 20), $this->column('email', 'Email', 28),
                $this->column('issued_by', 'Emitido por', 24), $this->column('obs', 'Observações', 38, wrap: true), $this->column('deleted_at', 'Estado', 12, 'record_status'), $this->column('created_at', 'Criado em', 20),
            ],
            'import_certificates' => [
                $this->column('id', 'ID', 10, 'integer'), $this->column('cert_no', 'Certificado', 20), $this->column('date', 'Data', 14), $this->column('importer', 'Importador', 28),
                $this->column('importer_site', 'Local do importador', 26), $this->column('exporter', 'Exportador', 28), $this->column('exporter_site', 'Local do exportador', 26), $this->column('transport', 'Transporte', 20),
                $this->column('port_exit', 'Porto de saída', 20), $this->column('port_entry', 'Porto de entrada', 20), $this->column('destination_country', 'País de destino', 22), $this->column('currency', 'Moeda', 12),
                $this->column('cost_freight', 'Frete', 14, 'float'), $this->column('cost_insurance', 'Seguro', 14, 'float'), $this->column('vat', 'IVA (%)', 12, 'float'), $this->column('vat_cost', 'IVA', 14, 'float'),
                $this->column('cost_final', 'Custo final', 16, 'float'), $this->column('item_count', 'Itens', 10, 'integer'), $this->column('invoiced', 'Facturado', 12, 'yes_no'), $this->column('invoice_no', 'Factura', 20),
                $this->column('authorized_personnel', 'Pessoal autorizado', 24), $this->column('issued_by', 'Emitido por', 24), $this->column('deleted_at', 'Estado', 12, 'record_status'), $this->column('created_at', 'Criado em', 20),
            ],
            'export_certificates' => [
                $this->column('id', 'ID', 10, 'integer'), $this->column('cert_no', 'Certificado', 20), $this->column('date', 'Data', 14), $this->column('exporter', 'Exportador', 28),
                $this->column('exporter_site', 'Local do exportador', 26), $this->column('transport', 'Transporte', 20), $this->column('origin_country', 'País de origem', 22), $this->column('origin_city', 'Cidade de origem', 20),
                $this->column('destination_country', 'País de destino', 22), $this->column('destination_city', 'Cidade de destino', 20), $this->column('expedition_date', 'Data de expedição', 18), $this->column('expedition_location', 'Local de expedição', 24),
                $this->column('item_count', 'Itens', 10, 'integer'), $this->column('invoiced', 'Facturado', 12, 'yes_no'), $this->column('invoice_no', 'Factura', 20), $this->column('authorized_personnel', 'Pessoal autorizado', 24),
                $this->column('issued_by', 'Emitido por', 24), $this->column('obs', 'Observações', 38, wrap: true), $this->column('deleted_at', 'Estado', 12, 'record_status'), $this->column('created_at', 'Criado em', 20),
            ],
            'quality_certificates' => [
                $this->column('id', 'ID', 10, 'integer'), $this->column('code', 'Certificado', 20), $this->column('lab_code', 'Código laboratorial', 20), $this->column('customer_code', 'Código do cliente', 18),
                $this->column('customer', 'Cliente', 28), $this->column('warehouse', 'Local', 26), $this->column('product', 'Produto', 28), $this->column('invoice_no', 'Factura', 20),
                $this->column('status', 'Aprovado', 12, 'yes_no'), $this->column('validated_at', 'Validado em', 20), $this->column('validated_by', 'Validado por', 24), $this->column('validated_on_behalf_of', 'Em representação', 16, 'yes_no'),
                $this->column('revision_count', 'Revisões', 10, 'integer'), $this->column('current_version', 'Versão actual', 14), $this->column('issued_by', 'Emitido por', 24), $this->column('obs', 'Observações', 38, wrap: true),
                $this->column('deleted_at', 'Estado', 12, 'record_status'), $this->column('created_at', 'Criado em', 20), $this->column('updated_at', 'Actualizado em', 20),
            ],
            'customer_requests' => [
                $this->column('id', 'ID', 10, 'integer'), $this->column('reference', 'Referência', 18), $this->column('title', 'Solicitação', 30), $this->column('request_type', 'Tipo', 18),
                $this->column('status', 'Estado do fluxo', 18), $this->column('priority', 'Prioridade', 14), $this->column('category', 'Categoria', 22), $this->column('customer_code', 'Código do cliente', 18),
                $this->column('customer', 'Cliente', 28), $this->column('warehouse', 'Local', 26), $this->column('contact', 'Contacto', 20), $this->column('email', 'Email', 28),
                $this->column('preferred_date', 'Data preferida', 16), $this->column('submitted_at', 'Submetido em', 20), $this->column('resolved_at', 'Resolvido em', 20), $this->column('answered', 'Respondido', 12, 'yes_no'),
                $this->column('description', 'Descrição', 42, wrap: true), $this->column('deleted_at', 'Estado', 12, 'record_status'), $this->column('created_at', 'Criado em', 20),
            ],
            'occurrences' => [
                $this->column('id', 'ID', 10, 'integer'), $this->column('occurrence_no', 'Ocorrência', 18), $this->column('date_reported', 'Reportada em', 16), $this->column('status', 'Estado', 18),
                $this->column('category', 'Categoria', 22), $this->column('origin', 'Origem', 22), $this->column('department', 'Departamento', 24), $this->column('responsible_name', 'Responsável', 24),
                $this->column('responsible_user', 'Responsável interno', 24), $this->column('issue_description', 'Descrição', 42, wrap: true), $this->column('analysis', 'Análise', 42, wrap: true), $this->column('corrective_action', 'Acção correctiva', 42, wrap: true),
                $this->column('implementation_date', 'Implementação', 16), $this->column('date_resolved', 'Resolvida em', 16), $this->column('date_closed', 'Encerrada em', 16), $this->column('was_effective', 'Eficaz', 12, 'yes_no'),
                $this->column('client_acceptance', 'Aceite pelo cliente', 18, 'yes_no'), $this->column('update_risk_matrix', 'Actualizar matriz de risco', 22, 'yes_no'), $this->column('deleted_at', 'Estado do registo', 16, 'record_status'), $this->column('created_at', 'Criado em', 20),
            ],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function definition(string $permission, string $title, string $description, string $category, string $table, string $filterGroup, string $filename, string $sheet, string $color, string $dateLabel = 'Criado'): array
    {
        return compact('permission', 'title', 'description', 'category', 'table', 'filterGroup', 'filename', 'sheet', 'color', 'dateLabel');
    }

    /**
     * @return array{key: string, label: string, width: int, type?: string, wrap?: bool}
     */
    private function column(string $key, string $label, int $width, ?string $type = null, bool $wrap = false): array
    {
        return array_filter(compact('key', 'label', 'width', 'type', 'wrap'), fn (mixed $value): bool => $value !== null && $value !== false);
    }

    /**
     * @return array<int, array{key: string, label: string, width: int, type?: string, wrap?: bool}>
     */
    private function commercialColumns(string $numberKey, string $numberLabel, bool $withPaymentStatus, bool $withConversion = false): array
    {
        $columns = [
            $this->column('id', 'ID', 10, 'integer'), $this->column($numberKey, $numberLabel, 20), $this->column('date', 'Data', 14), $this->column('due_date', 'Vencimento', 14),
            $this->column('customer_code', 'Código do cliente', 18), $this->column('customer', 'Cliente', 28), $this->column('warehouse', 'Local', 26), $this->column('document_type', 'Tipo', 18),
            $this->column('item_count', 'Itens', 10, 'integer'), $this->column('sub_total', 'Subtotal', 14, 'float'), $this->column('tax', 'Imposto', 14, 'float'), $this->column('discount', 'Desconto', 14, 'float'),
            $this->column('withholding_tax_amount', 'Retenção', 14, 'float'), $this->column('total', 'Total', 16, 'float'),
        ];

        if ($withPaymentStatus) {
            $columns[] = $this->column('amount_due', 'Por pagar', 16, 'float');
            $columns[] = $this->column('payment_status', 'Pagamento', 16, 'payment_status');
            $columns[] = $this->column('paid_date', 'Pago em', 14);
            $columns[] = $this->column('payment_method', 'Meio de pagamento', 22);
        }

        if ($withConversion) {
            $columns[] = $this->column('converted_to_invoice', 'Convertida', 14, 'yes_no');
            $columns[] = $this->column('invoice_no', 'Factura', 20);
        }

        return [...$columns,
            $this->column('issued_by', 'Emitido por', 24), $this->column('exported_saft', 'Exportado SAF-T', 16, 'yes_no'), $this->column('internal_ref', 'Referência interna', 20), $this->column('description', 'Descrição', 34, wrap: true),
            $this->column('deleted_at', 'Estado do registo', 16, 'record_status'), $this->column('created_at', 'Criado em', 20), $this->column('updated_at', 'Actualizado em', 20),
        ];
    }

    /**
     * @return array<int, array{key: string, label: string, width: int, type?: string, wrap?: bool}>
     */
    private function creditNoteColumns(): array
    {
        return [
            $this->column('id', 'ID', 10, 'integer'), $this->column('note_no', 'Nota de crédito', 20), $this->column('date', 'Data', 14), $this->column('reason', 'Motivo', 16, 'credit_reason'),
            $this->column('invoice_no', 'Factura', 20), $this->column('customer_code', 'Código do cliente', 18), $this->column('customer', 'Cliente', 28), $this->column('warehouse', 'Local', 26),
            $this->column('item_count', 'Itens', 10, 'integer'), $this->column('sub_total', 'Subtotal', 14, 'float'), $this->column('total', 'Total', 16, 'float'), $this->column('amount', 'Montante', 16, 'float'),
            $this->column('issued_by', 'Emitido por', 24), $this->column('exported_saft', 'Exportado SAF-T', 16, 'yes_no'), $this->column('internal_ref', 'Referência interna', 20), $this->column('obs', 'Observações', 38, wrap: true),
            $this->column('deleted_at', 'Estado do registo', 16, 'record_status'), $this->column('created_at', 'Criado em', 20), $this->column('updated_at', 'Actualizado em', 20),
        ];
    }
}
