<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">

    <title>{{ $session->title }}</title>

    <style>
        @page {
            margin: 30pt 30pt 45pt 30pt;
        }

        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #262626;
        }

        .header {
            border-bottom: 2px solid #722f37;
            padding-bottom: 8pt;
            margin-bottom: 12pt;
        }

        .brand {
            margin: 0 0 6pt 0;
            font-size: 11pt;
            font-weight: bold;
            color: #722f37;
        }

        .context {
            margin: 0 0 4pt 0;
            font-size: 8pt;
            letter-spacing: 0.8pt;
            text-transform: uppercase;
            color: #737373;
        }

        h1 {
            margin: 0 0 6pt 0;
            font-size: 20pt;
            line-height: 1.2;
            color: #722f37;
        }

        .badge {
            display: inline-block;
            padding: 2pt 8pt;
            border-radius: 8pt;
            background: #f2e8ea;
            color: #722f37;
            font-size: 8pt;
            font-weight: bold;
        }

        .meta {
            margin: 0;
        }

        .author {
            margin-left: 6pt;
            font-size: 9pt;
            color: #737373;
        }

        h2 {
            margin: 14pt 0 6pt 0;
            padding-bottom: 3pt;
            border-bottom: 1px solid #e5e5e5;
            font-size: 12pt;
            color: #722f37;
            page-break-after: avoid;
        }

        .description {
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        a {
            color: #722f37;
            word-wrap: break-word;
        }

        /* Games */

        .games-grid {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }

        .game-cell {
            width: 50%;
            vertical-align: top;
            padding: 6pt;
        }

        .game {
            margin-bottom: 8pt;
            padding-bottom: 6pt;
            border-bottom: 1px solid #e5e5e5;
        }

        .game h3 {
            margin: 0 0 5pt 0;
            font-size: 11pt;
            color: #722f37;
            page-break-after: avoid;
        }

        .players {
            font-size: 10pt;
            font-weight: bold;
            margin: 0 0 6pt;
        }

        .pgn {
            margin: 0;
            font-size: 9pt;
            line-height: 1.4;
            white-space: normal;
            word-wrap: break-word;
        }

        /* Chessboard */

        .chessboard {
            border-collapse: collapse;
            table-layout: fixed;
            margin: 6pt auto;
        }

        .chessboard td {
            width: 24pt;
            height: 24pt;
            padding: 0;
            text-align: center;
            vertical-align: middle;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 20pt;
            line-height: 24pt;
            color: #111;
        }

        .light-square {
            background: #f0e6d8;
        }

        .dark-square {
            background: #a56c63;
        }

        .chessboard td.coordinate {
            font-size: 8pt;
            line-height: 12pt;
            color: #737373;
            background: #fff;
        }

        .chessboard tr td.coordinate:first-child {
            width: 12pt;
        }

        .chessboard tr:last-child td.coordinate {
            height: 12pt;
        }

        .board-turn {
            text-align: center;
            font-size: 9pt;
            font-weight: bold;
            margin: 4pt 0 8pt;
        }

        /* Footer */

        .footer {
            position: fixed;
            bottom: -25pt;
            left: 0;
            right: 0;
            padding-top: 5pt;
            border-top: 1px solid #e5e5e5;
            font-size: 8pt;
            color: #737373;
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
        EloMetrics &middot;
        Generado el {{ now()->format('d/m/Y') }} &middot;
        Página <span class="page"></span>
    </div>

    <div class="header">
        <p class="brand">EloMetrics</p>

        <p class="context">
            Workspace: {{ $workspace->title }}
        </p>

        <h1>{{ $session->title }}</h1>

        <p class="meta">
            @if ($categoryLabel)
                <span class="badge">{{ $categoryLabel }}</span>
            @endif

            <span class="author">
                Creada por {{ $session->user->name }}
            </span>
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
                <a href="{{ $session->resource }}">
                    {{ $session->resource }}
                </a>
            @else
                {{ $session->resource }}
            @endif
        </p>
    @endif

    @if ($games->isNotEmpty())
        <h2>Partidas</h2>

        @foreach ($games->chunk(2) as $pair)
            <table class="games-grid">
                <tr>
                    @foreach ($pair as $game)
                        <td class="game-cell">
                            <div class="game">
                                <h3>
                                    Partida {{ $loop->parent->index * 2 + $loop->iteration }}
                                </h3>

                                @if ($game['white'] && $game['black'])
                                    <p class="players">
                                        {{ $game['white'] }} — {{ $game['black'] }}
                                    </p>
                                @endif

                                @if ($game['has_diagram'])
                                    @include('pdf.partials.chessboard', [
                                        'board' => $game['board'],
                                        'turn' => $game['turn'],
                                    ])
                                @endif

                                @if ($game['notation'])
                                    <p class="pgn">
                                        {{ $game['notation'] }}
                                    </p>
                                @endif
                            </div>
                        </td>
                    @endforeach

                    @if ($pair->count() === 1)
                        <td class="game-cell"></td>
                    @endif
                </tr>
            </table>
        @endforeach
    @endif
</body>
</html>