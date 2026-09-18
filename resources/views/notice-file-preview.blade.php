<!DOCTYPE html>
<html lang="{{ ($lang ?? 'en') === 'th' ? 'th' : 'en' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'File Preview' }}</title>
    <style>
        :root {
            --pink: #e83e8c;
            --ink: #111111;
            --muted: #6b7280;
            --bg: #fff8fc;
            --card: #ffffff;
            --line: #e5e7eb;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Noto Sans Thai", "Inter", Arial, sans-serif;
            color: var(--ink);
            background: var(--bg);
        }

        .wrap {
            max-width: 1400px;
            margin: 0 auto;
            padding: 18px;
        }

        .head {
            background: var(--card);
            border: 1px solid var(--line);
            border-left: 6px solid var(--pink);
            padding: 14px 16px;
        }

        .title {
            margin: 0;
            font-size: 18px;
            word-break: break-word;
        }

        .hint {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 13px;
            white-space: pre-wrap;
        }

        .content {
            margin-top: 12px;
            background: var(--card);
            border: 1px solid var(--line);
            overflow: auto;
            max-height: calc(100vh - 150px);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        th,
        td {
            border: 1px solid var(--line);
            padding: 8px 10px;
            vertical-align: top;
            text-align: left;
            white-space: pre-wrap;
            word-break: break-word;
            font-size: 13px;
        }

        th {
            background: #fdf2f8;
            color: #9d174d;
            font-weight: 700;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .slide-list {
            display: grid;
            gap: 10px;
            padding: 12px;
        }

        .slide {
            border: 1px solid var(--line);
            background: #ffffff;
            padding: 12px;
        }

        .slide h3 {
            margin: 0 0 8px;
            color: #9d174d;
            font-size: 15px;
        }

        .slide p {
            margin: 0;
            white-space: pre-wrap;
            font-size: 14px;
            line-height: 1.5;
        }

        .empty {
            padding: 22px;
            color: var(--muted);
            font-size: 14px;
        }
    </style>
</head>
<body>
    @php
        $isTh = ($lang ?? 'en') === 'th';
        $pageTitle = (string) ($title ?? ($isTh ? 'ดูไฟล์' : 'File Preview'));
        $type = (string) ($type ?? 'error');
        $rows = is_array($rows ?? null) ? $rows : [];
        $slides = is_array($slides ?? null) ? $slides : [];
        $messageText = trim((string) ($message ?? ''));
    @endphp
    <div class="wrap">
        <section class="head">
            <h1 class="title">{{ $pageTitle }}</h1>
            @if ($messageText !== '')
                <p class="hint">{{ $messageText }}</p>
            @endif
        </section>

        <section class="content">
            @if ($type === 'spreadsheet')
                @if (count($rows) > 0)
                    <table>
                        <tbody>
                            @foreach ($rows as $rowIndex => $row)
                                <tr>
                                    @foreach (($rowIndex === 0 ? $row : $row) as $cell)
                                        @if ($rowIndex === 0)
                                            <th>{{ $cell !== '' ? $cell : '-' }}</th>
                                        @else
                                            <td>{{ $cell !== '' ? $cell : '-' }}</td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="empty">{{ $isTh ? 'ไม่พบข้อมูลในไฟล์นี้' : 'No data found in this file.' }}</p>
                @endif
            @elseif ($type === 'slides')
                @if (count($slides) > 0)
                    <div class="slide-list">
                        @foreach ($slides as $slide)
                            <article class="slide">
                                <h3>{{ $isTh ? 'สไลด์ที่' : 'Slide' }} {{ (int) ($slide['no'] ?? 0) }}</h3>
                                <p>{{ (string) ($slide['text'] ?? '-') }}</p>
                            </article>
                        @endforeach
                    </div>
                @else
                    <p class="empty">{{ $isTh ? 'ไม่พบข้อความในสไลด์' : 'No slide text found.' }}</p>
                @endif
            @else
                <p class="empty">{{ $messageText !== '' ? $messageText : ($isTh ? 'ไม่สามารถแสดงตัวอย่างไฟล์นี้ได้' : 'Unable to preview this file.') }}</p>
            @endif
        </section>
    </div>
</body>
</html>
