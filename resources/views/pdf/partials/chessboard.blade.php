<table class="chessboard">
    @foreach ($board as $rowIndex => $row)
        <tr>
            <td class="coordinate">
                {{ 8 - $rowIndex }}
            </td>

            @foreach ($row as $columnIndex => $piece)
                <td class="{{ ($rowIndex + $columnIndex) % 2 === 0 ? 'light-square' : 'dark-square' }}">
                    {{ $piece }}
                </td>
            @endforeach
        </tr>
    @endforeach

    <tr>
        <td class="coordinate"></td>

        @foreach (range('a', 'h') as $file)
            <td class="coordinate">{{ $file }}</td>
        @endforeach
    </tr>
</table>

<p class="board-turn">{{ $turn }} juegan</p>