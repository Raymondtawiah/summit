<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket - {{ $ticket->ticket_number }}</title>
    <style>
        @page {
            size: A4;
            margin: 0;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #f5f5f5;
        }
        .ticket-container {
            width: 100%;
            max-width: 400px;
            background: white;
            border: 2px solid #000;
            border-radius: 8px;
            padding: 32px;
            margin: 20px;
        }
        .ticket-header {
            text-align: center;
            border-bottom: 2px dashed #000;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .ticket-header h1 {
            font-size: 24px;
            font-weight: bold;
            color: #000;
        }
        .ticket-header p {
            font-size: 14px;
            color: #666;
            margin-top: 4px;
        }
        .ticket-body {
            space-y: 16px;
        }
        .ticket-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 14px;
        }
        .ticket-label {
            color: #666;
            font-weight: 500;
        }
        .ticket-value {
            color: #000;
            font-weight: 600;
        }
        .ticket-footer {
            margin-top: 24px;
            padding-top: 16px;
            border-top: 2px dashed #000;
            text-align: center;
        }
        .qr-code {
            display: inline-block;
            padding: 16px;
            background: white;
            border: 1px solid #eee;
        }
        .ticket-footer p {
            font-size: 10px;
            color: #666;
            margin-top: 8px;
            word-break: break-all;
        }
        @media print {
            body {
                background: white;
            }
            .ticket-container {
                border: 2px solid #000;
                box-shadow: none;
                margin: 0;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="ticket-container">
        <div class="ticket-header">
            <h1>LDS SUMMITPASS</h1>
            <p>Official Summit Pass</p>
        </div>

        <div class="ticket-body">
            <div class="ticket-row">
                <span class="ticket-label">Participant:</span>
                <span class="ticket-value">{{ $ticket->participant->full_name }}</span>
            </div>
            <div class="ticket-row">
                <span class="ticket-label">Registration No:</span>
                <span class="ticket-value">{{ $ticket->participant->registration_number }}</span>
            </div>
            <div class="ticket-row">
                <span class="ticket-label">Ticket No:</span>
                <span class="ticket-value">{{ $ticket->ticket_number }}</span>
            </div>
            <div class="ticket-row">
                <span class="ticket-label">Unit:</span>
                <span class="ticket-value">{{ $ticket->participant->unit ?? 'N/A' }}</span>
            </div>
            <div class="ticket-row">
                <span class="ticket-label">Stake/District:</span>
                <span class="ticket-value">{{ $ticket->participant->stake_district ?? 'N/A' }}</span>
            </div>
            <div class="ticket-row">
                <span class="ticket-label">Shirt Size:</span>
                <span class="ticket-value">{{ $ticket->participant->shirt_size ?? 'N/A' }}</span>
            </div>
            <div class="ticket-row">
                <span class="ticket-label">Status:</span>
                <span class="ticket-value">{{ strtoupper($ticket->status) }}</span>
            </div>
        </div>

        <div class="ticket-footer">
                        <div class="qr-code">
                            <img src="data:image/png;base64,{{ $ticket->qrCodeImage(150) }}" width="150" height="150" alt="QR Code" />
                        </div>
            <p>{{ $ticket->qr_token }}</p>
        </div>
    </div>
</body>
</html>
