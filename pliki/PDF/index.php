<?php
$src = $_GET['src'] ?? './pagespeed_14-07.pdf';
$title = $_GET['title'] ?? basename($src);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?= htmlspecialchars($title) ?></title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="./assets/css/pdf-viewer.css?v=2.1">
<style>
html,body { height:100%; margin:0; }
body { background: var(--mode-bg, #FDF9F5); }
.pdf-viewer-inline { margin:0; border:0; border-radius:0; height:100vh; height:100dvh; box-shadow:none; }
.pdf-viewer-inline .pdf-viewer-frame { height: calc(100vh - 88px); height: calc(100dvh - 88px); }
@media (max-width:768px) {
  .pdf-viewer-inline .pdf-viewer-frame { height: calc(100vh - 88px); height: calc(100dvh - 88px); }
}
</style>
</head>
<body class="pdf-viewer-root">
<div class="pdf-viewer" data-src="<?= htmlspecialchars($src) ?>" data-pdf-title="<?= htmlspecialchars($title) ?>"></div>
<script src="./assets/js/pdf-viewer.js?v=2.1"></script>
</body>
</html>
