<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Maintenance Mode Enabled</title>

    <style>
        body {
            margin: 0;
            padding: 40px 20px;
            background: #f3f4f6;
            color: #111827;
            font-family: Arial, sans-serif;
        }

        .card {
            max-width: 760px;
            margin: 0 auto;
            padding: 32px;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            background: white;
            box-shadow: 0 12px 40px rgba(0, 0, 0, .08);
        }

        code {
            display: block;
            overflow-wrap: anywhere;
            padding: 16px;
            border-radius: 12px;
            background: #111827;
            color: #a7f3d0;
        }

        a {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 18px;
            border-radius: 10px;
            background: #4f46e5;
            color: white;
            font-weight: bold;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="card">
        <h1>Maintenance mode enabled</h1>

        <p>
            Save this bypass URL before leaving this page.
            It lets the platform administrator access the application
            while other visitors see the maintenance page.
        </p>

        <code>{{ $bypassUrl }}</code>

        <a href="{{ $bypassUrl }}">
            Open administrator bypass
        </a>

        <p style="margin-top: 28px;">
            The server command to restore normal access is:
        </p>

        <code>php artisan up</code>
    </div>
</body>
</html>
