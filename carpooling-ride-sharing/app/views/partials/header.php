<?php
/** @var string|null $pageTitle */
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Dashboard') ?> &middot; TransitPool</title>
<link rel="icon" href="data:,">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    sky:  { DEFAULT: '#0EA5E9', dark: '#0369A1', 50: '#E6F6FE' },
                    lime: { DEFAULT: '#84CC16', dark: '#4D7C0F', 50: '#F2FCE3' },
                    ink: '#101828',
                    canvas: '#F7F9FB',
                    line: '#E1E7EE',
                    slateink: '#5B6472',
                    amber: '#B9791A',
                    rose: '#D93A4E',
                },
                fontFamily: {
                    display: ['"Space Grotesk"', 'sans-serif'],
                    body: ['"Inter"', 'sans-serif'],
                    mono: ['"JetBrains Mono"', 'monospace'],
                },
            },
        },
    };
</script>
<link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="font-body bg-canvas text-ink">
