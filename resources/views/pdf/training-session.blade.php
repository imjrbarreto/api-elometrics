<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <title>{{ $session->title }}</title>

    <style>
        @page {
            margin: 40px;
        }

        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #222;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        h2 {
            font-size: 16px;
            margin-top: 24px;
        }

        .context {
            color: #666;
            margin-bottom: 24px;
        }

        .description {
            white-space: pre-wrap;
        }

        a {
            color: #245c42;
            word-wrap: break-word;
        }
    </style>
</head>

<body>
    <p class="context">
        Workspace: {{ $workspace->title }}
    </p>

    <h1>{{ $session->title }}</h1>

    @if ($session->category)
        <p>
            <strong>Categoría:</strong> {{ $session->category }}
        </p>
    @endif

    @if ($session->description)
        <h2>Descripción</h2>

        <p class="description">{{ $session->description }}</p>
    @endif

    @if ($session->resource)
        <h2>Recurso</h2>

        <p>
            <a href="{{ $session->resource }}">
                {{ $session->resource }}
            </a>
        </p>
    @endif
</body>
</html>