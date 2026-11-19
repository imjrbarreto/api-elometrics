<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">

    <title>{{ $session->title }}</title>

    <style>
        @page {
            margin: 40px 40px 60px 40px;
        }

        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #222;
        }

        .header {
            border-bottom: 2px solid #245c42;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .context {
            margin: 0 0 4px 0;
            font-size: 10px;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #666;
        }

        h1 {
            margin: 0 0 8px 0;
            font-size: 24px;
            line-height: 1.25;
        }

        .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 10px;
            background: #e8f1ec;
            color: #245c42;
            font-size: 11px;
            font-weight: bold;
        }

        .meta {
            margin: 0;
        }

        .author {
            margin-left: 8px;
            font-size: 11px;
            color: #666;
        }

        h2 {
            margin: 24px 0 6px 0;
            padding-bottom: 3px;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
            color: #245c42;
        }

        .description {
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        a {
            color: #245c42;
            word-wrap: break-word;
        }

        .footer {
            position: fixed;
            bottom: -30px;
            left: 0;
            right: 0;
            padding-top: 6px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #888;
        }

        .footer .page:before {
            content: counter(page);
        }
    </style>
</head>

<body>
    @php
        $categoryLabel = $session->category
            ? (\App\Enums\TrainingCategory::tryFrom($session->category)?->label() ?? $session->category)
            : null;
    @endphp

    <div class="footer">
        Generado el {{ now()->format('d/m/Y') }} &middot; Página <span class="page"></span>
    </div>

    <div class="header">
        <p class="context">Workspace: {{ $workspace->title }}</p>

        <h1>{{ $session->title }}</h1>

        <p class="meta">
            @if ($categoryLabel)
                <span class="badge">{{ $categoryLabel }}</span>
            @endif
            <span class="author">Creada por {{ $session->user->name }}</span>
        </p>
    </div>

    @if ($session->description)
        <h2>Descripción</h2>

        <p class="description">{{ $session->description }}</p>
    @endif

    @if ($session->resource)
        <h2>Recurso</h2>

        <p>
            @if (\Illuminate\Support\Str::startsWith($session->resource, ['http://', 'https://']))
                <a href="{{ $session->resource }}">{{ $session->resource }}</a>
            @else
                {{ $session->resource }}
            @endif
        </p>
    @endif
</body>
</html>