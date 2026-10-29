<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup required | Romy and Allit Food Haven</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; box-sizing: border-box; background: #f7f4ec; color: #202b26; font: 16px/1.6 system-ui, sans-serif; }
        main { width: min(560px, 100%); padding: 36px; box-sizing: border-box; background: #fff; border: 1px solid #e7e2d8; border-radius: 12px; }
        h1 { margin: 0 0 12px; font-size: 28px; line-height: 1.2; }
        p { color: #58645f; }
        code { padding: 2px 5px; background: #f2efe8; border-radius: 4px; }
        li { margin: 8px 0; }
    </style>
</head>
<body>
<main>
    <h1>One setup step is still needed.</h1>
    @if ($issue === 'app-key')
        <p>Laravel is missing its encryption key. Add <code>APP_KEY</code> to the Render service's Environment Variables, then redeploy.</p>
    @else
        <p>Laravel could not read its database. In the Render service's Environment Variables, set <code>DB_HOST</code>, <code>DB_DATABASE</code>, <code>DB_USERNAME</code>, and <code>DB_PASSWORD</code> for a MySQL database that Render can reach.</p>
        <p>Then run the project migrations against that database. A local XAMPP database at <code>localhost</code> is not reachable from Render.</p>
    @endif
</main>
</body>
</html>
