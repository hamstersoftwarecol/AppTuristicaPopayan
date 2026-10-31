<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comprobante local · {{ $booking->reference }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f5f5f1; color: #173c32; font: 15px/1.6 system-ui, sans-serif; }
        .actions { max-width: 850px; margin: 24px auto; padding: 0 24px; display: flex; flex-wrap: wrap; gap: 16px; align-items: center; }
        button { border: 0; border-radius: 8px; padding: 12px 20px; background: #173c32; color: white; font: inherit; cursor: pointer; }
        .sheet { max-width: 850px; margin: 24px auto 48px; padding: 48px; background: white; border: 1px solid #dce2dc; border-radius: 12px; }
        .eyebrow { font-size: 12px; letter-spacing: .15em; text-transform: uppercase; }
        h1 { margin: 8px 0 16px; font-size: 30px; line-height: 1.2; }
        h2 { font-size: 19px; margin: 24px 0 12px; }
        .status { display: inline-block; padding: 6px 12px; background: #edf2ed; border-radius: 6px; font-weight: 600; }
        .reference { overflow-wrap: anywhere; color: #53685f; font-size: 13px; }
        dl { margin: 24px 0; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; }
        dt { font-size: 12px; color: #53685f; }
        dd { margin: 4px 0 0; overflow-wrap: anywhere; }
        .conditions { white-space: pre-wrap; overflow-wrap: anywhere; }
        .total { font-size: 26px; font-weight: 700; }
        .foot { margin-top: 32px; padding-top: 20px; border-top: 1px solid #dce2dc; color: #53685f; font-size: 12px; }
        @media (max-width: 600px) { .sheet { padding: 24px; margin: 16px; } dl { grid-template-columns: 1fr; } h1 { font-size: 25px; } }
        @media print { @page { size: A4; margin: 18mm; } body { background: white; color: black; font-size: 11pt; } .actions { display: none; } .sheet { border: 0; border-radius: 0; padding: 0; margin: 0; max-width: none; } dl > div { break-inside: avoid; } h2 { break-after: avoid; } .status { border: 1px solid #ccc; } }
    </style>
</head>
<body>
    <nav class="actions" aria-label="Acciones del comprobante"><button id="print" type="button">Imprimir / Guardar como PDF</button><span>Usa las opciones de impresión del navegador.</span></nav>
    <main class="sheet">
        <p class="eyebrow">{{ config('app.name') }} · Operación propia</p>
        <h1>Comprobante de reserva local</h1>
        <p class="reference">Referencia: {{ $booking->reference }}</p>
        <p class="status">{{ $status }}</p>
        <h2>{{ $booking->service_title }}</h2>
        <dl>
            <div><dt>Proveedor</dt><dd>{{ $booking->provider_name }}</dd></div>
            <div><dt>Fecha de la salida</dt><dd>{{ $booking->service_date->format('d/m/Y') }}</dd></div>
            <div><dt>Titular de la cuenta</dt><dd>{{ $booking->customer?->name ?? 'Cuenta no disponible' }}</dd></div>
            <div><dt>Correo de la cuenta</dt><dd>{{ $booking->customer?->email ?? 'No disponible' }}</dd></div>
            <div><dt>Número de viajeros</dt><dd>{{ $booking->travelers }}</dd></div>
            <div><dt>Tarifa por viajero</dt><dd>$ {{ number_format($booking->unit_price, 0, ',', '.') }} COP</dd></div>
            <div><dt>Total de la reserva local</dt><dd class="total">$ {{ number_format($booking->total, 0, ',', '.') }} COP</dd></div>
            <div><dt>Cobros reales registrados en esta plataforma</dt><dd>Ninguno. Los intentos Wompi de esta entrega son pruebas sandbox.</dd></div>
        </dl>
        @if ($booking->status === 'pending')<p>Vencimiento de la solicitud: {{ $booking->expires_at->format('d/m/Y H:i') }} (hora de Colombia).</p>@endif
        @if ($booking->confirmed_at)<p>Confirmación local: {{ $booking->confirmed_at->format('d/m/Y H:i') }} (hora de Colombia).</p>@endif
        @if ($booking->cancelled_at)<p>Cancelación: {{ $booking->cancelled_at->format('d/m/Y H:i') }} (hora de Colombia).</p>@endif
        @if ($booking->cancellation_reason)<p class="conditions">Motivo de cancelación: {{ $booking->cancellation_reason }}</p>@endif
        <h2>Condiciones aceptadas al solicitar</h2>
        <p class="conditions">{{ $booking->terms }}</p>
        <p>La cancelación desde el panel está disponible hasta el día anterior a la salida. Las solicitudes vencidas liberan los cupos retenidos.</p>
        <footer class="foot"><p>Generado el {{ $generatedAt->format('d/m/Y H:i') }} (hora de Colombia). Refleja el estado del registro local al momento de consultarlo.</p><p>Este comprobante no acredita un pago, no es una factura y no sustituye tiquetes o vouchers emitidos por proveedores externos.</p></footer>
    </main>
    <script nonce="{{ $nonce }}">document.getElementById('print').addEventListener('click', () => window.print());</script>
</body>
</html>
