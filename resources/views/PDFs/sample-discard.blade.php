{{-- resources/views/pdf/sample-discard.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sample Discard Certificate - {{ $discard->sample->code }}</title>
    <style>
        @page {
            margin: 20px;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            border-bottom: 3px solid #dc2626;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo h1 {
            color: #1e3a8a;
            font-size: 24px;
            margin: 0;
        }
        .certificate-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            color: #dc2626;
            margin-bottom: 30px;
        }
        .warning-box {
            background-color: #fef2f2;
            border: 2px solid #dc2626;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
        }
        .warning-icon {
            color: #dc2626;
            font-size: 24px;
            margin-bottom: 10px;
        }
        .warning-text {
            color: #7f1d1d;
            font-weight: bold;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            background-color: #dc2626;
            color: white;
            padding: 8px 15px;
            font-weight: bold;
            font-size: 14px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 10px;
        }
        .info-item {
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            color: #4b5563;
        }
        .info-value {
            color: #111827;
            border-bottom: 1px dotted #d1d5db;
            padding-bottom: 3px;
        }
        .footer {
            margin-top: 50px;
            padding-top: 15px;
            border-top: 1px solid #d1d5db;
            text-align: center;
            color: #6b7280;
            font-size: 10px;
        }
        .signature-area {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #d1d5db;
        }
        .signature-line {
            width: 200px;
            border-bottom: 1px solid #333;
            margin: 0 auto;
            padding-top: 40px;
        }
        .signature-label {
            text-align: center;
            font-size: 11px;
            color: #6b7280;
            margin-top: 5px;
        }
        .barcode {
            text-align: center;
            margin: 20px 0;
            font-family: 'Libre Barcode 128', cursive;
            font-size: 36px;
        }
        .method-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            margin-left: 10px;
        }
        .method-incineration {
            background-color: #fef3c7;
            color: #92400e;
        }
        .method-chemical {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .method-autoclave {
            background-color: #d1fae5;
            color: #065f46;
        }
        .stamp {
            position: absolute;
            right: 20px;
            bottom: 100px;
            width: 150px;
            height: 150px;
            border: 3px solid #dc2626;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transform: rotate(-15deg);
            opacity: 0.7;
        }
        .stamp-text {
            text-align: center;
            color: #dc2626;
            font-weight: bold;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <h1>LABORATORY INFORMATION MANAGEMENT SYSTEM</h1>
            <p>Sample Discard Certificate</p>
        </div>
        
        <div class="certificate-title">
            DISCARD CERTIFICATE #DISC-{{ str_pad($discard->id, 6, '0', STR_PAD_LEFT) }}
        </div>
    </div>

    <div class="warning-box">
        <div class="warning-icon">⚠️</div>
        <div class="warning-text">SAMPLE PERMANENTLY DISCARDED - IRREVERSIBLE ACTION</div>
    </div>

    <div class="barcode">
        *DISC-{{ str_pad($discard->id, 6, '0', STR_PAD_LEFT) }}*
    </div>

    <div class="section">
        <div class="section-title">Discard Information</div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Discard Method:</div>
                <div class="info-value">
                    {{ $discard->discard_method }}
                    <span class="method-badge method-{{ strtolower(str_replace(' ', '-', $discard->discard_method)) }}">
                        {{ $discard->discard_method }}
                    </span>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Quantity:</div>
                <div class="info-value">{{ $discard->qty }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Discarded Date:</div>
                <div class="info-value">{{ $discard->discarded_at->format('d/m/Y H:i') }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Discarded By:</div>
                <div class="info-value">{{ $discard->discardedBy->name ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Certificate ID:</div>
                <div class="info-value">DISC-{{ str_pad($discard->id, 6, '0', STR_PAD_LEFT) }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Original Sample Information</div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Sample Code:</div>
                <div class="info-value">{{ $discard->sample->code ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Sample Name:</div>
                <div class="info-value">{{ $discard->sample->name ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Sample Type:</div>
                <div class="info-value">{{ $discard->sample->sample_type ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Original Status:</div>
                <div class="info-value">{{ $discard->sample->status ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Customer:</div>
                <div class="info-value">{{ $discard->sample->customer->name ?? 'N/A' }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Received Date:</div>
                <div class="info-value">{{ $discard->sample->received_at ? $discard->sample->received_at->format('d/m/Y H:i') : 'N/A' }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Laboratory Information</div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Laboratory:</div>
                <div class="info-value">{{ $discard->lab->name ?? ($discard->sample->lab->name ?? 'N/A') }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Department:</div>
                <div class="info-value">{{ $discard->department->name ?? ($discard->sample->department->name ?? 'N/A') }}</div>
            </div>
        </div>
    </div>

    <div class="signature-area">
        <div class="signature-line"></div>
        <div class="signature-label">Authorized by: {{ $discard->discardedBy->name ?? 'N/A' }}</div>
    </div>

    <div class="stamp">
        <div class="stamp-text">
            DISCARDED<br>
            {{ $discard->discarded_at->format('d/m/Y') }}<br>
            FINAL
        </div>
    </div>

    <div class="footer">
        <p>Document generated on {{ $date }} at {{ $time }}</p>
        <p>This certificate confirms that the sample has been permanently discarded according to laboratory protocols.</p>
        <p>This is a computer-generated document. No signature is required.</p>
    </div>
</body>
</html>