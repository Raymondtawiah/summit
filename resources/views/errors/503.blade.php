<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance - {{ config('app.name') }}</title>
    <style>
        body { font-family: 'Instrument Sans', system-ui, sans-serif; background: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .container { text-align: center; padding: 2rem; }
        h1 { font-size: 6rem; color: #1e293b; margin: 0; }
        p { color: #64748b; font-size: 1.25rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1>503</h1>
        <p>We are currently performing maintenance. Please check back soon.</p>
    </div>
</body>
</html>
