<!-- Analysis Results Section -->
<div class="section-title">RESULTADOS ANALÍTICOS</div>

<div class="info-section">
    <div class="section-header">
        Dados de Análise Laboratorial
    </div>
    <div class="section-content" style="padding: 0;">
        <table class="data-table" style="margin: 0;">
            <thead>
                <tr>
                    <th style="padding: 12px 10px; text-align: left; border-bottom: 2px solid #1e3a8a;">
                        <div class="label" style="color: white; font-size: 11px; font-weight: 600;">
                            ENSAIO
                        </div>
                        <div class="muted small-text" style="color: rgba(255,255,255,0.9); font-weight: 400;">
                            Parameter
                        </div>
                    </th>
                    <th style="padding: 12px 10px; text-align: left; border-bottom: 2px solid #1e3a8a;">
                        <div class="label" style="color: white; font-size: 11px; font-weight: 600;">
                            MÉTODO
                        </div>
                        <div class="muted small-text" style="color: rgba(255,255,255,0.9); font-weight: 400;">
                            Method
                        </div>
                    </th>
                    <th style="padding: 12px 10px; text-align: center; border-bottom: 2px solid #1e3a8a;">
                        <div class="label" style="color: white; font-size: 11px; font-weight: 600;">
                            RESULTADO & U
                        </div>
                        <div class="muted small-text" style="color: rgba(255,255,255,0.9); font-weight: 400;">
                            Results and Uncertainty
                        </div>
                    </th>
                    <th style="padding: 12px 10px; text-align: center; border-bottom: 2px solid #1e3a8a;">
                        <div class="label" style="color: white; font-size: 11px; font-weight: 600;">
                            UNIDADE
                        </div>
                        <div class="muted small-text" style="color: rgba(255,255,255,0.9); font-weight: 400;">
                            Units
                        </div>
                    </th>
                    <th colspan="2" style="padding: 12px 10px; text-align: center; border-bottom: 2px solid #1e3a8a;">
                        <div class="label" style="color: white; font-size: 11px; font-weight: 600;">
                            LIMITES DE REFERÊNCIA
                        </div>
                        <div class="muted small-text" style="color: rgba(255,255,255,0.9); font-weight: 400;">
                            Reference Limits
                        </div>
                    </th>
                </tr>
                <tr style="background-color: #f9fafb;">
                    <td colspan="6" style="padding: 10px 12px; border-bottom: 1px solid #e5e7eb;">
                        <div class="small-text" style="font-weight: 500; color: #1e3a8a;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 6px;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            Referência: {{ $model->collection->code->results->pluck('ref_val_origin')->unique()->implode(', ') }}
                        </div>
                    </td>
                </tr>
            </thead>
            <tbody>
                @if($model->collection->code->results->count() > 0)
                    @foreach($model->collection->code->samples as $key => $sample)
                        @if($sample->results)
                            @foreach($sample->results as $result)
                                <tr>
                                    <td style="padding: 12px 10px; border-bottom: 1px solid #f3f4f6; vertical-align: middle;">
                                        <div class="value" style="font-weight: 500; font-size: 11px;">
                                            {!! str_replace('º', '°', $result->parameter_label) !!}
                                        </div>
                                    </td>
                                    <td style="padding: 12px 10px; border-bottom: 1px solid #f3f4f6; vertical-align: middle;">
                                        <div class="value" style="font-size: 10px; color: #6b7280;">
                                            {!! $result->protocol_label !!}
                                        </div>
                                    </td>
                                    <td style="padding: 12px 10px; border-bottom: 1px solid #f3f4f6; text-align: center; vertical-align: middle;">
                                        <div class="value" style="font-weight: 600; font-size: 11px; color: #1e3a8a;">
                                            {{-- @include("PDFs.includes.analysisreport.results.{$result->type_id}", ['result' => $result]) --}}
                                            {{ $result->approved_value }}
                                        </div>
                                    </td>
                                    <td style="padding: 12px 10px; border-bottom: 1px solid #f3f4f6; text-align: center; vertical-align: middle;">
                                        <div class="value" style="font-size: 10px; font-weight: 500;">
                                            {!! $result->unit_label !!}
                                        </div>
                                    </td>
                                    <td style="padding: 12px 10px; border-bottom: 1px solid #f3f4f6; text-align: center; vertical-align: middle; background-color: #f9fafb;">
                                        <div class="value" style="font-size: 10px;">
                                            @if($result->min_ref_value)
                                                <div style="font-weight: 500;">{{ $result->min_ref_value }}</div>
                                                <div class="muted small-text" style="font-size: 9px;">Mínimo</div>
                                            @else
                                                <span class="muted small-text">-</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td style="padding: 12px 10px; border-bottom: 1px solid #f3f4f6; text-align: center; vertical-align: middle; background-color: #f9fafb;">
                                        <div class="value" style="font-size: 10px;">
                                            @if($result->max_ref_value)
                                                <div style="font-weight: 500;">
                                                    {{ $result->max_ref_value }}
                                                    @if($result->ref_val_origin)
                                                        <span class="muted small-text" style="font-size: 9px; margin-left: 2px;">
                                                            ({{ substr($result->ref_val_origin, 0, 1) }})
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="muted small-text" style="font-size: 9px;">Máximo</div>
                                            @else
                                                <span class="muted small-text">-</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="6" style="padding: 20px; text-align: center; border-bottom: 1px solid #f3f4f6;">
                                    <div class="small-text muted" style="font-style: italic;">
                                        Sem resultados disponíveis para esta amostra
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                @else
                    <tr>
                        <td colspan="6" style="padding: 40px; text-align: center;">
                            <div class="small-text muted" style="font-style: italic;">
                                Nenhum resultado analítico disponível
                            </div>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
        
        <!-- Legend for Reference Codes -->
        @if($model->collection->code->results->whereNotNull('ref_val_origin')->count() > 0)
        <div style="margin-top: 15px; padding: 10px 15px; background-color: #f9fafb; border-top: 1px solid #e5e7eb;">
            <div class="small-text" style="font-weight: 600; color: #374151; margin-bottom: 5px;">
                Legenda das Referências:
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                @php
                    $references = $model->collection->code->results
                        ->whereNotNull('ref_val_origin')
                        ->pluck('ref_val_origin')
                        ->unique()
                        ->map(function($ref) {
                            return [
                                'code' => substr($ref, 0, 1),
                                'full' => $ref
                            ];
                        });
                @endphp
                
                @foreach($references as $ref)
                <div style="display: inline-flex; align-items: center; gap: 4px;">
                    <span class="parameter-tag" style="font-size: 9px; padding: 2px 6px;">
                        ({{ $ref['code'] }})
                    </span>
                    <span class="small-text" style="color: #6b7280;">
                        {{ $ref['full'] }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Results Conclusion -->
<div style="margin: 20px 0; padding: 15px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px;">
    <div style="text-align: center;">
        <div class="h4" style="color: #1e3a8a; margin-bottom: 5px;">
            FIM DOS RESULTADOS ANALÍTICOS
        </div>
        <div class="small-text muted">
            End of analytical results
        </div>
        
        <!-- Status Summary -->
        @php
            $totalResults = $model->collection->code->results->count();
            $conformResults = 0; // You'll need to define your conformity logic
        @endphp
        
        @if($totalResults > 0)
        <div style="margin-top: 15px; padding-top: 10px; border-top: 1px dashed #e5e7eb;">
            <div class="small-text" style="display: inline-block; margin-right: 20px;">
                <span style="font-weight: 600; color: #374151;">Total de Parâmetros:</span>
                <span class="value highlight-value" style="margin-left: 5px;">{{ $totalResults }}</span>
            </div>
            <div class="small-text" style="display: inline-block;">
                <span style="font-weight: 600; color: #374151;">Data de Análise:</span>
                <span class="value" style="margin-left: 5px;">
                    {{ $model->collection->code->results->first()->updated_at->format('d/m/Y') ?? 'N/A' }}
                </span>
            </div>
        </div>
        @endif
    </div>
</div>