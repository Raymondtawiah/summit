<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Server Error - {{ config('app.name') }}</title>
    <style>
        body { font-family: 'Instrument Sans', system-ui, sans-serif; background: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .container { text-align: center; padding: 2rem; }
        h1 { font-size: 6rem; color: #1e293b; margin: 0; }
        p { color: #64748b; font-size: 1.25rem; }
        a { color: #2563eb; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>500</h1>
        <p>Something went wrong on our end. Please try again later.</p>
        <a href="{{ route('home') }}">Return to home</a>
    </div>
</body>
</html>
