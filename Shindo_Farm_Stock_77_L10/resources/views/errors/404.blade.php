<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Tidak Ditemukan - SHINDO FARM 77</title>
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
            background: var(--color-secondary);
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
            background: var(--color-secondary);
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
        .btn-primary { background: var(--color-secondary); color: #ffffff; }
        .btn-secondary { background: var(--color-card); color: var(--color-dark); }

        .footer { text-align: center; font-size: 10.5px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: #a8a598; margin-top: 1.5rem; }
    </style>
</head>

<body>
    <main class="card" role="main">
        <div class="brand">SHINDO FARM <span>77</span></div>

        <div class="icon-box" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
        </div>

        <div class="e-code-wrap"><span class="e-code">Error 404</span></div>
        <h1>Halaman tidak ditemukan</h1>
        <p class="desc">Halaman yang kamu cari tidak ada atau sudah dipindahkan.<br>Cek kembali alamat yang kamu ketik.</p>

        <div class="trigger-box">
            <p class="trigger-label">Kenapa ini terjadi?</p>
            <ul class="trigger-list">
                <li>Alamat halaman (URL) salah ketik</li>
                <li>Halaman sudah dihapus atau dipindahkan</li>
                <li>Link yang diklik sudah tidak berlaku</li>
            </ul>
        </div>

        <div class="section">
            <p class="section-label">Yang bisa dilakukan</p>
            <ul class="steps">
                <li><span class="num">1</span> Periksa ulang alamat yang kamu ketik di browser</li>
                <li><span class="num">2</span> Kembali ke halaman sebelumnya</li>
                <li><span class="num">3</span> Kembali ke beranda dan cari dari sana</li>
            </ul>
        </div>

        <div class="actions">
            <a href="javascript:history.back()" class="btn btn-primary">&#8592; Kembali</a>
           <a href="{{ route('dashboard.index') }}" class="btn btn-secondary">Ke Dashboard</a>
        </div>

        <p class="footer">Error 404 &middot; Not Found</p>
    </main>
</body>

</html>
