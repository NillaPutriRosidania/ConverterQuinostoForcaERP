<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Quinos Tools</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        * {
            box-sizing: border-box;
            font-family: "Inter", system-ui, sans-serif;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a, #1e293b);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #f8fafc;
        }

        .container {
            text-align: center;
            max-width: 600px;
            width: 100%;
            padding: 20px;
        }

        .logo {
            width: 85px;
            height: 85px;
            border-radius: 20px;
            background: linear-gradient(135deg, #2563eb, #22c55e);
            margin: 0 auto 20px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 42px;
            font-weight: 800;
            color: #ffffff;
            box-shadow: 0 0 0 5px rgba(255, 255, 255, 0.08);
        }

        h1 {
            font-size: 28px;
            margin-bottom: 6px;
            font-weight: 700;
        }

        p.subtitle {
            font-size: 14px;
            color: #cbd5e1;
            margin-bottom: 30px;
        }

        .buttons {
            display: grid;
            gap: 16px;
            margin-top: 20px;
        }

        .btn {
            padding: 14px 20px;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            color: #f8fafc;
            display: block;
            background: rgba(255, 255, 255, 0.09);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            transition: 0.18s ease;
        }

        .btn:hover {
            background: rgba(255, 255, 255, 0.17);
            transform: translateY(-2px);
        }

        .btn:active {
            transform: scale(0.97);
        }

        .btn-green {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border: none;
        }

        .footer {
            margin-top: 40px;
            font-size: 11px;
            color: #94a3b8;
        }
    </style>
</head>

<body>
    <div class="container">

        <div class="logo">Q</div>

        <h1>QUINOS CONVERTER</h1>
        <p class="subtitle">
            Pilih lokasi cabang untuk memulai proses konversi data.
        </p>

        <div class="buttons">

            <a href="{{ route('converterSQ.index') }}" class="btn btn-green">
                SQ
            </a>

            <a href="{{ route('converter.lantai12.index') }}" class="btn btn-green">
                Lantai 12
            </a>


            <a href="{{ route('converter.index') }}" class="btn btn-green">
                Gresik
            </a>
        </div>

        <div class="footer">
            © {{ date('Y') }} Quinos Tools – Automated POS Migration Suite Nilla
        </div>
    </div>
</body>

</html>
