<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') · Vwajèn</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>body{background:#000;margin:0}.embed-bar{position:absolute;left:0;right:0;bottom:0;display:flex;align-items:center;gap:.5rem;padding:.45rem .7rem;background:linear-gradient(transparent,rgba(0,0,0,.8));color:#fff;font-size:.85rem}.embed-bar a{color:#fff}</style>
</head>
<body>
    @yield('content')
</body>
</html>
