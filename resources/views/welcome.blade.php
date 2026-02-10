<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <title>Kopi Kilen – Sales Tools</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" href="{{ asset('storage/brand/logo2.png') }}?v={{ time() }}">
    <link rel="apple-touch-icon" href="{{ asset('storage/brand/logo2.png') }}">
    <meta property="og:title" content="Kopi Kilen – Sales Tools">
    <meta property="og:description" content="Converter Quinos → FORCA • BOM • Revenue • Bahan Baku">
    <meta property="og:image" content="{{ asset('storage/brand/og-image.png') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{{ asset('storage/brand/og-image.png') }}">



    <style>
        * {
            box-sizing: border-box;
            font-family: "Inter", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg1: #0b1220;
            --bg2: #121c2f;
            --card: rgba(255, 255, 255, 0.06);
            --card2: rgba(255, 255, 255, 0.08);
            --border: rgba(255, 255, 255, 0.12);
            --text: #f8fafc;
            --muted: #cbd5e1;
            --muted2: #94a3b8;

            --kilen1: #f59e0b;
            /* amber */
            --kilen2: #22c55e;
            /* green */
            --kilen3: #2563eb;
            /* blue */

            --radius: 18px;
        }

        body {
            min-height: 100vh;
            background:
                radial-gradient(900px 500px at 20% 10%, rgba(245, 158, 11, 0.18), transparent 60%),
                radial-gradient(900px 500px at 80% 30%, rgba(34, 197, 94, 0.15), transparent 60%),
                linear-gradient(135deg, var(--bg1), var(--bg2));
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 22px;
            color: var(--text);
        }

        .container {
            width: 100%;
            max-width: 840px;
            padding: 18px;
        }

        .top {
            display: flex;
            gap: 14px;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .brand {
            display: flex;
            gap: 14px;
            align-items: center;
        }

        .logo {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            background: linear-gradient(135deg, var(--kilen1), var(--kilen2));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            font-weight: 900;
            color: #111827;
            box-shadow:
                0 0 0 5px rgba(255, 255, 255, 0.06),
                0 14px 40px rgba(0, 0, 0, 0.35);
            user-select: none;
        }

        .brand h1 {
            font-size: 22px;
            line-height: 1.15;
            font-weight: 800;
            letter-spacing: 0.2px;
        }

        .brand p {
            margin-top: 3px;
            font-size: 12.5px;
            color: var(--muted);
        }

        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
        }

        .pill {
            padding: 8px 10px;
            border-radius: 999px;
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, 0.05);
            font-size: 11.5px;
            color: var(--muted);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            backdrop-filter: blur(4px);
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 99px;
            background: var(--kilen2);
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.15);
        }

        .hero {
            margin: 10px 0 18px 0;
            padding: 16px;
            border-radius: var(--radius);
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.10), rgba(37, 99, 235, 0.07));
            border: 1px solid rgba(255, 255, 255, 0.10);
        }

        .hero .subtitle {
            font-size: 13px;
            color: var(--muted);
            line-height: 1.55;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
        }

        @media (min-width: 860px) {
            .grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        .section {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 16px;
            backdrop-filter: blur(6px);
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.25);
        }

        .section-title {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 12px;
        }

        .section-title strong {
            font-size: 13px;
            letter-spacing: 0.10em;
            text-transform: uppercase;
            color: #e2e8f0;
        }

        .section-title .hint {
            font-size: 11.5px;
            color: var(--muted2);
        }

        .buttons {
            display: grid;
            gap: 12px;
        }

        .btn {
            padding: 14px 14px;
            border-radius: 14px;
            text-decoration: none;
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            background: var(--card2);
            border: 1px solid var(--border);
            transition: 0.18s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 0.14);
        }

        .btn:active {
            transform: scale(0.985);
        }

        .btn .left {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-weight: 900;
            color: #0b1220;
            flex: 0 0 auto;
        }

        .icon.green {
            background: linear-gradient(135deg, var(--kilen2), #16a34a);
        }

        .icon.blue {
            background: linear-gradient(135deg, var(--kilen3), #1d4ed8);
        }

        .icon.amber {
            background: linear-gradient(135deg, var(--kilen1), #f97316);
        }

        .btn .title {
            font-size: 15px;
            font-weight: 750;
            line-height: 1.1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .btn .desc {
            margin-top: 3px;
            font-size: 12px;
            font-weight: 500;
            color: rgba(248, 250, 252, 0.82);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .btn .right {
            font-size: 12px;
            color: rgba(248, 250, 252, 0.75);
            flex: 0 0 auto;
        }

        .footer {
            margin-top: 16px;
            font-size: 11.5px;
            color: var(--muted2);
            text-align: center;
            padding: 10px 0;
        }

        /* Override khusus logo image: hilangkan gradien ijo */
        .logo.logo-img {
            background: rgba(255, 255, 255, 0.10) !important;
            /* atau #fff kalau mau solid */
            border: 1px solid rgba(255, 255, 255, 0.18);
            padding: 10px;
        }

        .logo.logo-img img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 14px;
            filter: none;
        }
    </style>
</head>

<body>
    <div class="container">

        <div class="top">
            <div class="brand">
                <div class="logo logo-img">
                    <img src="{{ asset('storage/brand/logo.png') }}" alt="Kopi Kilen">
                </div>

                <div>
                    <h1>Kopi Kilen – Sales Tools</h1>
                    <p>Converter Quinos → FORCA ERP • BOM • Revenue • Pemakaian Bahan Baku</p>
                </div>
            </div>

            <div class="meta">
                <span class="pill"><span class="dot"></span> Online</span>
                <span class="pill">Tahun: {{ date('Y') }}</span>
                <span class="pill">POS: Quinos</span>
            </div>
        </div>

        <div class="hero">
            <p class="subtitle">
                Gunakan menu di bawah untuk konversi data penjualan dari Quinos, lalu hitung BOM / Revenue / Total
                pemakaian bahan baku.
                <br />
                Tips: mulai dari <b>Converter</b> → lanjut ke <b>Perhitungan</b>.
            </p>
        </div>

        <div class="grid">

            <!-- SECTION: CONVERTER -->
            <div class="section">
                <div class="section-title">
                    <strong>Converter</strong>
                    <span class="hint">Upload Quinos → download template FORCA</span>
                </div>

                <div class="buttons">
                    <a href="{{ route('converterSQ.index') }}" class="btn">
                        <div class="left">
                            <div class="icon amber">⇄</div>
                            <div>
                                <div class="title">Converter SQ</div>
                                <div class="desc">Konversi file Quinos (SQ) ke format FORCA</div>
                            </div>
                        </div>
                        <div class="right">Buka →</div>
                    </a>

                    <a href="{{ route('converter.lantai12.index') }}" class="btn">
                        <div class="left">
                            <div class="icon amber">⇄</div>
                            <div>
                                <div class="title">Converter Lantai 12</div>
                                <div class="desc">Konversi file Quinos cabang Lantai 12</div>
                            </div>
                        </div>
                        <div class="right">Buka →</div>
                    </a>

                    <a href="{{ route('converter.index') }}" class="btn">
                        <div class="left">
                            <div class="icon amber">⇄</div>
                            <div>
                                <div class="title">Converter Gresik</div>
                                <div class="desc">Konversi file Quinos cabang Gresik</div>
                            </div>
                        </div>
                        <div class="right">Buka →</div>
                    </a>
                </div>
            </div>

            <!-- SECTION: PERHITUNGAN -->
            <div class="section">
                <div class="section-title">
                    <strong>Perhitungan</strong>
                    <span class="hint">Hitung dari hasil mapping / BOM</span>
                </div>

                <div class="buttons">
                    <a href="{{ route('sales.upload') }}" class="btn">
                        <div class="left">
                            <div class="icon blue">☕</div>
                            <div>
                                <div class="title">Perhitungan BOM</div>
                                <div class="desc">Lihat pemakaian bahan baku dari penjualan</div>
                            </div>
                        </div>
                        <div class="right">Buka →</div>
                    </a>

                    <a href="{{ route('revenue.upload') }}" class="btn">
                        <div class="left">
                            <div class="icon blue">Rp</div>
                            <div>
                                <div class="title">Perhitungan Revenue</div>
                                <div class="desc">Subtotal, Discount, Net Sales, Tax, Rounding</div>
                            </div>
                        </div>
                        <div class="right">Buka →</div>
                    </a>

                    <a href="{{ route('raw.upload') }}" class="btn">
                        <div class="left">
                            <div class="icon blue">∑</div>
                            <div>
                                <div class="title">Perhitungan Bahan Baku</div>
                                <div class="desc">Total pemakaian bahan baku (rekap)</div>
                            </div>
                        </div>
                        <div class="right">Buka →</div>
                    </a>

                    <a href="{{ route('archive.index') }}" class="btn">
                        <div class="left">
                            <div class="icon green">⛁</div>
                            <div>
                                <div class="title">Arsip File</div>
                                <div class="desc">Simpan file apapun untuk kebutuhan audit</div>
                            </div>
                        </div>
                        <div class="right">Buka →</div>
                    </a>

                    <a href="{{ route('posidMaterial.upload') }}" class="btn">
                        <div class="left">
                            <div class="icon blue">🔎</div>
                            <div>
                                <div class="title">Cari POS ID</div>
                                <div class="desc">
                                    Cari POS ID berdasarkan penggunaan / non-penggunaan bahan baku
                                </div>
                            </div>
                        </div>
                        <div class="right">Buka →</div>
                    </a>

                </div>
            </div>

        </div>

        <div class="footer">
            © {{ date('Y') }} Kopi Kilen • Quinos Tools • dibuat oleh Nilla
        </div>

    </div>
</body>

</html>
