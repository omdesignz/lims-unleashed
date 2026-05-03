<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    
    /* Base Styles - Aligned with Visual System */
    body {
      font-family: 'Inter', Arial, sans-serif;
      font-size: 10pt;
      margin: 0;
      padding: 10mm;
      color: #374151;
      line-height: 1.3;
      background-color: white;
    }
    
    /* Label Grid Layout */
    .labels-container {
      display: grid;
      grid-template-columns: repeat(2, 1fr); /* 2 columns for A4 */
      gap: 8mm;
      max-width: 210mm; /* A4 width */
      margin: 0 auto;
    }
    
    @media print {
      .labels-container {
        grid-template-columns: repeat(2, 1fr);
        gap: 7mm;
      }
    }
    
    /* Individual Label Styling */
    .label-card {
      background: white;
      border: 2px solid #1e3a8a;
      border-radius: 8px;
      padding: 6mm;
      height: 60mm; /* Fixed height for consistency */
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
      position: relative;
      overflow: hidden;
      page-break-inside: avoid;
    }
    
    /* Label Header */
    .label-header {
      background: linear-gradient(to right, #1e3a8a, #1e40af);
      color: white;
      padding: 4mm 6mm;
      margin: -6mm -6mm 6mm -6mm;
      border-radius: 6px 6px 0 0;
      text-align: center;
      font-size: 11pt;
      font-weight: 600;
      letter-spacing: 0.3px;
      position: relative;
    }
    
    .label-header:after {
      content: '';
      position: absolute;
      bottom: -2px;
      left: 0;
      right: 0;
      height: 2px;
      background: linear-gradient(to right, #1d4ed8, #3b82f6);
    }
    
    /* Label Content Layout */
    .label-content {
      display: flex;
      gap: 5mm;
      height: calc(100% - 25mm);
    }
    
    /* QR Code Section */
    .qr-section {
      flex-shrink: 0;
      width: 35mm;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }
    
    .qr-container {
      padding: 2mm;
      background: white;
      border: 1px solid #e5e7eb;
      border-radius: 4px;
      text-align: center;
      margin-bottom: 3mm;
    }
    
    .qr-code {
      width: 30mm !important;
      height: 30mm !important;
    }
    
    .qr-label {
      font-size: 8pt;
      color: #6b7280;
      font-weight: 500;
      margin-top: 1mm;
    }
    
    /* Information Section */
    .info-section {
      flex-grow: 1;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    
    /* Info Grid */
    .info-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 2mm;
      margin-bottom: 3mm;
    }
    
    .info-item {
      display: flex;
      flex-direction: column;
    }
    
    .info-label {
      font-size: 6pt;
      font-weight: 600;
      color: #6b7280;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 1mm;
    }
    
    .info-value {
      font-size: 8pt;
      font-weight: 600;
      color: #111827;
      background: #f9fafb;
      padding: 2mm 3mm;
      border-radius: 4px;
      border: 1px solid #e5e7eb;
      min-height: 8mm;
      display: flex;
      align-items: center;
    }
    
    .info-value.highlight {
      color: #1e3a8a;
      background: #f0f9ff;
      border-color: #dbeafe;
    }
    
    /* Status Badge */
    .status-badge {
      display: inline-block;
      padding: 1mm 3mm;
      background-color: #d1fae5;
      color: #065f46;
      font-size: 8pt;
      font-weight: 600;
      border-radius: 4px;
      border: 1px solid #a7f3d0;
      text-align: center;
      margin-top: 2mm;
    }
    
    /* Parameters Section */
    .parameters-section {
      margin-top: 3mm;
      padding-top: 3mm;
      border-top: 1px dashed #e5e7eb;
    }
    
    .parameters-title {
      font-size: 7pt;
      font-weight: 600;
      color: #374151;
      margin-bottom: 2mm;
    }
    
    .parameters-list {
      display: flex;
      flex-wrap: wrap;
      gap: 1mm;
    }
    
    .parameter-tag {
      display: inline-block;
      padding: 1mm 2mm;
      background-color: #f0f9ff;
      color: #1e40af;
      font-size: 5pt;
      font-weight: 500;
      border-radius: 3px;
      border: 1px solid #dbeafe;
    }
    
    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 40mm 20mm;
      background-color: #f9fafb;
      border: 2px dashed #e5e7eb;
      border-radius: 12px;
      margin: 20mm auto;
      max-width: 80%;
    }
    
    .empty-state-icon {
      font-size: 24pt;
      color: #9ca3af;
      margin-bottom: 6mm;
    }
    
    .empty-state-title {
      font-size: 12pt;
      font-weight: 600;
      color: #6b7280;
      margin-bottom: 4mm;
    }
    
    /* Footer Information */
    .label-footer {
      margin-top: 3mm;
      padding-top: 2mm;
      border-top: 1px solid #f3f4f6;
      font-size: 5pt;
      color: #9ca3af;
      text-align: center;
    }
    
    /* Page Break Control */
    @page {
      size: A4;
      margin: 10mm;
    }
    
    @page :first {
      margin-top: 10mm;
    }
    
    /* Print Optimizations */
    @media print {
      body {
        padding: 0;
      }
      
      .label-card {
        border: 1.5pt solid #1e3a8a;
        box-shadow: none;
      }
      
      .empty-state {
        display: none;
      }
    }
  </style>
</head>
<body>

  @if($model->samples->count() > 0)
  <div class="labels-container">
    @foreach ($model->samples as $sample)
    <div class="label-card">
      <!-- Department Header -->
      <div class="label-header">
        {{ $sample->analysis->department->name }}
      </div>
      
      <!-- Label Content -->
      <div class="label-content">
        <!-- QR Code Section -->
        <div class="qr-section">
          <div class="qr-container">
            <img src="{{ $model->collection->qr }}" alt="QR Code" class="qr-code">
          </div>
          <div class="qr-label">
            SCAN ME
          </div>
        </div>
        
        <!-- Information Section -->
        <div class="info-section">
          <div class="info-grid">
            <div class="info-item">
              <div class="info-label">Código</div>
              <div class="info-value highlight">{{ $model->code }}</div>
            </div>
            
            <div class="info-item">
              <div class="info-label">Amostra</div>
              <div class="info-value">{{ $model->collection->product->name }}</div>
            </div>
            
            <div class="info-item">
              <div class="info-label">Data</div>
              <div class="info-value">
                {{ \Carbon\Carbon::parse($model->collection->collection_date)->format('d/m/Y') }}
              </div>
            </div>
            
            <div class="info-item">
              <div class="info-label">Lote</div>
              <div class="info-value">{{ $model->collection->lot ?? 'N/A' }}</div>
            </div>
          </div>
          
          <!-- Dilution Information -->
          @if($sample->analysis->profile->parameters->first())
          <div class="info-item">
            <div class="info-label">Diluição</div>
            <div class="info-value">
              {{ $sample->analysis->profile->parameters->first()->pivot->dilutions ?? '1:1' }}
            </div>
          </div>
          @endif
          
          <!-- Status Badge -->
          <div class="status-badge">
            AGUARDANDO ANÁLISE
          </div>
        </div>
      </div>
      
      <!-- Parameters List -->
      @if($sample->analysis->profile->parameters->count() > 0)
      <div class="parameters-section">
        <div class="parameters-title">Parâmetros:</div>
        <div class="parameters-list">
          @foreach($sample->analysis->profile->parameters->take(6) as $parameter)
          <span class="parameter-tag">
            {{ $parameter->code ?: $parameter->description }}
          </span>
          @endforeach
          @if($sample->analysis->profile->parameters->count() > 6)
          <span class="parameter-tag">
            +{{ $sample->analysis->profile->parameters->count() - 6 }} mais
          </span>
          @endif
        </div>
      </div>
      @endif
      
      <!-- Footer -->
      <div class="label-footer">
        Gerado em: {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}
      </div>
    </div>
    @endforeach
  </div>
  
  @else
  <!-- Empty State -->
  <div class="empty-state">
    <div class="empty-state-icon">⚠️</div>
    <div class="empty-state-title">Amostra não colocada em análise</div>
    <div class="body-text" style="color: #6b7280; text-align: center; max-width: 300px; margin: 0 auto;">
      A amostra ainda não foi colocada em análise ou não possui dados de parâmetros associados.
    </div>
    <div class="status-badge status-pending" style="margin-top: 20px;">
      STATUS: AGUARDANDO ANÁLISE
    </div>
  </div>
  @endif

</body>
</html>