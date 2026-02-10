<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Browse Arsip</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: system-ui, -apple-system, Segoe UI, sans-serif
        }

        body {
            margin: 0;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            padding: 24px
        }

        .wrap {
            max-width: 980px;
            width: 100%
        }

        a {
            color: #93c5fd;
            text-decoration: none
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 14px
        }

        .muted {
            color: #94a3b8;
            font-size: 12px
        }

        .card {
            background: rgba(255, 255, 255, .06);
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 16px;
            padding: 16px
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px
        }

        @media(max-width:900px) {
            .grid {
                grid-template-columns: repeat(2, 1fr)
            }
        }

        @media(max-width:600px) {
            .grid {
                grid-template-columns: 1fr
            }
        }

        .tile {
            padding: 12px;
            border-radius: 14px;
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .12)
        }

        .tile:hover {
            background: rgba(255, 255, 255, .13)
        }

        .file {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, .08)
        }

        .file:last-child {
            border-bottom: none
        }

        .btn {
            padding: 8px 12px;
            border-radius: 10px;
            background: rgba(255, 255, 255, .10);
            border: 1px solid rgba(255, 255, 255, .12);
            color: #e2e8f0
        }

        .ok {
            background: rgba(34, 197, 94, .12);
            border: 1px solid rgba(34, 197, 94, .25);
            padding: 10px;
            border-radius: 12px;
            margin-bottom: 12px
        }
    </style>
</head>

<body>
    <div class="wrap">

        <div class="top">
            <div>
                <div style="font-size:18px;font-weight:750">📁 Browse Arsip</div>
                <div class="muted">Lokasi: <strong>{{ $path }}</strong></div>
            </div>
            <div style="text-align:right">
                <a href="{{ route('archive.index') }}">＋ Upload</a><br>
                <a href="{{ route('welcome') }}">← Kembali</a>
                <a href="{{ route('archive.browse') }}">🏠 Root</a>
            </div>
        </div>

        @if (session('ok'))
            <div class="ok">{{ session('ok') }}</div>
        @endif

        <div class="card">
            @if (!empty($folders))
                <div class="muted" style="margin-bottom:10px">Folder</div>
                <div class="grid" style="margin-bottom:16px">
                    @foreach ($folders as $f)
                        @php $name = basename($f); @endphp

                        {{-- Navigasi: root -> branch -> year -> month --}}
                        @php
                            $next = ['branch' => $branch, 'year' => $year, 'month' => $month];
                            if (!$branch) {
                                $next['branch'] = $name;
                            } elseif (!$year) {
                                $next['year'] = $name;
                            } elseif (!$month) {
                                $next['month'] = $name;
                            }
                        @endphp

                        <a class="tile" href="{{ route('archive.browse', $next) }}">
                            <div style="font-weight:700">
                                @php
                                    $isMonthFolder = $branch && $year && !$month; // lagi di level month (sebelum masuk bulan)
                                    $monthKey = str_pad($name, 2, '0', STR_PAD_LEFT);
                                @endphp

                                @if ($isMonthFolder && isset($months[$monthKey]))
                                    {{ $monthKey }} - {{ $months[$monthKey] }}
                                @else
                                    {{ $name }}
                                @endif
                            </div>

                            <div class="muted">{{ $f }}</div>
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="muted" style="margin-bottom:10px">Files</div>
            @if (empty($files))
                <div class="muted">Belum ada file di folder ini.</div>
            @else
                @foreach ($files as $file)
                    <div class="file">
                        <div>
                            <div style="font-weight:650">{{ basename($file) }}</div>
                            <div class="muted">{{ $file }}</div>
                        </div>
                        <div style="display:flex; gap:8px">
                            <a class="btn" href="{{ route('archive.download', ['path' => $file]) }}">
                                Download
                            </a>

                            <form action="{{ route('archive.delete') }}" method="POST"
                                onsubmit="return confirm('Yakin ingin menghapus file ini?')">
                                @csrf
                                <input type="hidden" name="path" value="{{ $file }}">
                                <button class="btn"
                                    style="background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3)">
                                    Hapus
                                </button>
                            </form>
                        </div>

                    </div>
                @endforeach
            @endif
        </div>

    </div>
</body>

</html>
