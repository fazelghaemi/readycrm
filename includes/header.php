<?php
  // Ready Studio CRM - Header (Material/Modern)
  // This header is RTL-ready and plugs into your PHP pages.
  if (!defined('READYCRM_UI')) define('READYCRM_UI', true);
  // If your project needs session/auth checks, include them above this header.
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="theme-color" content="#00b0a4" />
  <title><?php echo isset($page_title) ? $page_title . " — " : ""; ?>Ready Studio CRM</title>

  <!-- Favicon (optional) -->
  <link rel="icon" href="public/assets/images/favicon.png" type="image/png" />

  <!-- Google Fonts: Poppins (you can add IRANSans locally in fonts.css) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />

  <!-- Font Awesome 6 -->
  <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" rel="stylesheet" />

  <!-- Ready Studio Theme (colors: #00b0a4 / #098b82, dark: #1a1a1a) -->
  <!-- If your project already has these files, paths below match the common ReadyCRM structure -->
  <link href="public/assets/css/fonts.css" rel="stylesheet" />
  <link href="public/assets/readystudio-theme.css" rel="stylesheet" />

  <!-- Optional: additional admin UI styles -->
  <style>
    :root{
      --rs-primary:#00b0a4; --rs-secondary:#098b82; --rs-dark:#1a1a1a;
    }
    body{ font-family: "IRANSans","Poppins",system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans",sans-serif; background:#f7f9fb; }
    .rs-topbar{ position:sticky; top:0; z-index:1030; background:#fff; border-bottom:1px solid #eef2f5; padding:.75rem 1rem; }
    .rs-sidebar{ background:#1a1a1a; color:#cbd5e1; }
    .btn-primary{ background:var(--rs-primary); border-color:var(--rs-primary); }
    .btn-primary:hover{ background:var(--rs-secondary); border-color:var(--rs-secondary); }
    a{ color:var(--rs-primary); } a:hover{ color:var(--rs-secondary); }
  </style>
</head>
<body>
