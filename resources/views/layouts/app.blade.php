<!DOCTYPE html>
<html lang="eu">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('izenburua') · Bergarako Antzokia</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 960px; margin: 0 auto; padding: 1rem; color: #222; }
        nav a { margin-right: 1rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: .4rem; border-bottom: 1px solid #ddd; }
        .errorea { background: #fde8e8; border: 1px solid #e0a0a0; padding: .75rem; border-radius: 4px; }
        .arrakasta { background: #e6f6e6; border: 1px solid #9c9; padding: .75rem; border-radius: 4px; }
        .eremu-errorea { color: #b00; font-size: .9rem; }
    </style>
</head>
<body>
    <nav>
        <a href="/">Hasiera</a>
        <a href="{{ route('partnerrak.index') }}">Partner-ak</a>
        <a href="{{ route('bezeroak.create') }}">Bezero berria</a>
    </nav>

    <h1>@yield('izenburua')</h1>

    @if (session('arrakasta'))
        <p class="arrakasta">{{ session('arrakasta') }}</p>
    @endif

    @yield('edukia')
</body>
</html>
