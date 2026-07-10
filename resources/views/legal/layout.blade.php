<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — بريق (Bariq)</title>
    <!-- Google Fonts for Breeq -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet" />
    
    <link rel="icon" type="image/png" href="/favicon-16x16.png" sizes="16x16">
    <link rel="icon" type="image/png" href="/favicon-32x32.png" sizes="32x32">
    <link rel="shortcut icon" href="/favicon.ico">

    <style>
        :root {
            color-scheme: light dark;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: 'IBM Plex Sans Arabic', 'Poppins', system-ui, sans-serif;
            line-height: 1.8;
            color: #1f2937;
            background-color: oklch(0.985 0.003 286);
            background-image: radial-gradient(
                color-mix(in oklab, oklch(0.58 0.23 299) 5%, transparent) 1.5px,
                transparent 1.5px
            );
            background-size: 24px 24px;
        }
        .wrap {
            max-width: 800px;
            margin: 0 auto;
            padding: 60px 20px 80px;
        }
        .card {
            background: #ffffff;
            border: 1px solid oklch(0.92 0.008 286);
            border-radius: 28px;
            padding: 40px 48px;
            box-shadow: 0 20px 50px -12px rgba(76, 29, 149, 0.05);
            margin-bottom: 24px;
        }
        header {
            border-bottom: 1px solid oklch(0.92 0.008 286);
            padding-bottom: 24px;
            margin-bottom: 32px;
        }
        h1 {
            font-size: 2.2rem;
            font-weight: 800;
            margin: 16px 0 8px;
            background: linear-gradient(135deg, #1f2937, oklch(0.58 0.23 299));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        h2 {
            font-size: 1.4rem;
            font-weight: 700;
            margin: 36px 0 14px;
            color: oklch(0.58 0.23 299);
        }
        .logo-wrapper {
            display: flex;
            align-items: center;
        }
        .logo-link {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        .logo-img {
            width: 44px;
            height: 44px;
            object-fit: contain;
            border-radius: 12px;
            border: 1px solid oklch(0.92 0.008 286);
            padding: 4px;
            background: #ffffff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03);
        }
        .logo-text {
            font-size: 1.35rem;
            font-weight: 900;
            background: linear-gradient(90deg, #1f2937, oklch(0.58 0.23 299));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: .5px;
        }
        .muted {
            color: #6b7280;
            font-size: .85rem;
            font-weight: 500;
        }
        a {
            color: oklch(0.58 0.23 299);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }
        a:hover {
            color: oklch(0.48 0.23 296);
            text-decoration: underline;
        }
        ul {
            padding-inline-start: 24px;
        }
        li {
            margin: 8px 0;
            padding-inline-start: 4px;
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            font-weight: 700;
            color: oklch(0.58 0.23 299);
            margin-bottom: 24px;
            transition: transform 0.2s ease;
            text-decoration: none;
        }
        .back-btn:hover {
            transform: translateX(-4px);
            text-decoration: none;
        }
        footer {
            margin-top: 32px;
            padding-top: 24px;
            font-size: .85rem;
            color: #6b7280;
            text-align: center;
        }
        
        @media (prefers-color-scheme: dark) {
            body {
                color: oklch(0.985 0.005 290);
                background-color: oklch(0.13 0.012 286);
                background-image: radial-gradient(
                    color-mix(in oklab, oklch(0.62 0.21 299) 5%, transparent) 1.5px,
                    transparent 1.5px
                );
            }
            .card {
                background: oklch(0.17 0.015 286);
                border-color: oklch(0.24 0.03 290);
                box-shadow: 0 20px 50px -12px rgba(0, 0, 0, 0.4);
            }
            header {
                border-color: oklch(0.24 0.03 290);
            }
            h1 {
                background: linear-gradient(135deg, oklch(0.985 0.005 290), oklch(0.62 0.21 299));
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
            h2 {
                color: oklch(0.62 0.21 299);
            }
            .logo-text {
                background: linear-gradient(90deg, oklch(0.985 0.005 290), oklch(0.62 0.21 299));
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
            .logo-img {
                border-color: oklch(0.24 0.03 290);
                background: #ffffff;
            }
            .muted, footer {
                color: oklch(0.7 0.01 290);
            }
            a {
                color: oklch(0.62 0.21 299);
            }
            a:hover {
                color: oklch(0.72 0.18 300);
            }
            .back-btn {
                color: oklch(0.62 0.21 299);
            }
        }
        @media (max-width: 640px) {
            .card {
                padding: 30px 24px;
                border-radius: 20px;
            }
            h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <a href="/" class="back-btn">
            <span>←</span> العودة للرئيسية
        </a>
        <div class="card">
            <header>
                <div class="logo-wrapper">
                    <a href="/" class="logo-link">
                        <img src="/image.png" alt="بريق" class="logo-img" />
                        <span class="logo-text">بريق · Bariq</span>
                    </a>
                </div>
                <h1>@yield('title')</h1>
                <div class="muted">آخر تحديث: {{ now()->format('Y-m-d') }}</div>
            </header>

            @yield('content')
        </div>

        <footer>
            &copy; {{ now()->format('Y') }} بريق (Bariq) — منصة أتمتة الردود على فيسبوك وإنستغرام.<br>
            جميع الحقوق محفوظة.
        </footer>
    </div>
</body>
</html>
