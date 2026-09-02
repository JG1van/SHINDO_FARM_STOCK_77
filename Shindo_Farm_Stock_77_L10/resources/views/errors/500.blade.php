<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terjadi Kesalahan - SHINDO FARM 77</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --color-primary: #ffc93c;
            --color-secondary: #e8871e;
            --color-accent: #fff4d6;
            --color-bg: #fffbeb;
            --color-card: #ffffff;
            --color-dark: #1a1a1a;
            --color-border: #1a1a1a;
            --color-danger: #ff6b6b;
            --border-width: 3px;
            --shadow: 5px 5px 0px var(--color-dark);
            --shadow-small: 3px 3px 0px var(--color-dark);
            --radius: 4px;
            --font-main: 'Space Grotesk', sans-serif;
            --font-brand: 'DM Serif Display', serif;
        }

        body {
            font-family: var(--font-main);
            background: var(--color-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            color: var(--color-dark);
        }

        .card {
            background: var(--color-card);
            border: var(--border-width) solid var(--color-border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            max-width: 480px;
            width: 100%;
            padding: 2rem 1.75rem 1.75rem;
        }

        .brand {
            text-align: center;
            font-family: var(--font-brand);
            font-size: 1.15rem;
            color: var(--color-dark);
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: var(--border-width) solid var(--color-border);
        }
        .brand span { color: var(--color-secondary); }

        .icon-box {
            width: 60px;
            height: 60px;
            border-radius: var(--radius);
            background: var(--color-danger);
            border: var(--border-width) solid var(--color-border);
            box-shadow: var(--shadow-small);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem;
        }

        .icon-box svg { width: 26px; height: 26px; stroke: var(--color-dark); }

        .e-code-wrap { display: flex; justify-content: center; margin-bottom: 0.75rem; }
        .e-code {
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--color-dark);
            background: var(--color-danger);
            border: var(--border-width) solid var(--color-border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-small);
            padding: 4px 12px;
        }

        h1 { font-size: 20px; font-weight: 700; text-align: center; margin-bottom: 0.5rem; }

        .desc { font-size: 13px; color: #4a4a45; text-align: center; line-height: 1.65; margin-bottom: 1.25rem; }

        .trigger-box, .section {
            background: var(--color-card);
            border: var(--border-width) solid var(--color-border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-small);
            padding: 0.9rem 1rem;
            margin-bottom: 0.75rem;
        }
        .trigger-box { background: var(--color-accent); }

        .trigger-label, .section-label {
            font-size: 10.5px; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase;
            color: var(--color-dark); margin-bottom: 0.6rem;
        }

        .trigger-list, .steps { list-style: none; display: flex; flex-direction: column; gap: 7px; }
        .trigger-list li { font-size: 12.5px; color: var(--color-dark); display: flex; gap: 6px; line-height: 1.5; }
        .trigger-list li::before { content: '▸'; flex-shrink: 0; font-weight: 700; }

        .steps li { display: flex; align-items: flex-start; gap: 9px; font-size: 13px; color: var(--color-dark); line-height: 1.5; }
        .num {
            min-width: 20px; height: 20px; border-radius: var(--radius);
            background: var(--color-primary); border: 2px solid var(--color-border);
            display: flex; align-items: center; justify-content: center;
            font-size: 10px; font-weight: 700; color: var(--color-dark); flex-shrink: 0; margin-top: 1px;
        }

        .actions { display: flex; gap: 10px; margin-top: 1.5rem; }

        .btn {
            flex: 1;
            font-family: var(--font-main);
            padding: 10px 14px;
            border: var(--border-width) solid var(--color-border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-small);
            font-size: 13px;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            cursor: pointer;
            transition: transform 0.1s ease, box-shadow 0.1s ease;
        }
        .btn:hover { transform: translate(2px, 2px); box-shadow: 1px 1px 0px var(--color-dark); }
        .btn-primary { background: var(--color-danger); color: var(--color-dark); }
        .btn-secondary { background: var(--color-card); color: var(--color-dark); }

        .footer { text-align: center; font-size: 10.5px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: #a8a598; margin-top: 1.5rem; }
    </style>
</head>

<body>
    <main class="card" role="main">
        <div class="brand">🥚 SHINDO FARM <span>77</span></div>

        <div class="icon-box" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 12.75c1.148 0 2.278.08 3.383.237 1.037.146 1.866.966 1.866 2.013 0 3.728-2.35 6.75-5.25 6.75S6.75 18.728 6.75 15c0-1.046.83-1.867 1.866-2.013A24.204 24.204 0 0112 12.75zm0 0c2.883 0 5.647.508 8.207 1.44a23.91 23.91 0 01-1.152 6.06M12 12.75c-2.883 0-5.647.508-8.208 1.44a23.916 23.916 0 001.152 6.061M12 12.75c-1.794 0-3.54.16-5.232.466m10.464 0A48.78 48.78 0 0112 13.5a48.78 48.78 0 00-5.232-.284m10.464.75A48.78 48.78 0 0112 13.5m-5.232-.284A48.78 48.78 0 0112 12.75m0 0V5.25m0 7.5v-7.5m0 0a2.25 2.25 0 00-2.25-2.25H7.5A2.25 2.25 0 005.25 7.5v2.25" />
            </svg>
        </div>

        <div class="e-code-wrap"><span class="e-code">Error 500</span></div>
        <h1>Terjadi kesalahan pada sistem</h1>
        <p class="desc">Ada masalah di dalam sistem yang sedang kami tangani.<br>Ini bukan kesalahan kamu — tim kami sudah diberitahu.</p>

        <div class="trigger-box">
            <p class="trigger-label">Apa artinya ini?</p>
            <ul class="trigger-list">
                <li>Ada bug atau error pada kode sistem</li>
                <li>Server mengalami gangguan sementara</li>
                <li>Masalah sedang ditangani oleh tim teknis</li>
            </ul>
        </div>

        <div class="section">
            <p class="section-label">Yang bisa dilakukan</p>
            <ul class="steps">
                <li><span class="num">1</span> Tunggu beberapa saat lalu coba lagi</li>
                <li><span class="num">2</span> Muat ulang halaman</li>
                <li><span class="num">3</span> Jika masih terjadi, hubungi tim kami</li>
            </ul>
        </div>

        <div class="actions">
            <a href="javascript:location.reload()" class="btn btn-primary">&#8635; Muat Ulang</a>
            <a href="/" class="btn btn-secondary">Ke Beranda</a>
        </div>

        <p class="footer">Error 500 &middot; Internal Server Error</p>
    </main>
</body>

</html>
