<!DOCTYPE html>
<html dir="ltr" lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}" type="image/x-icon" />

    @vite(['resources/css/app.css'])

    <title>@yield('title', config('app.name') . ' — USSD & Communications Platform')</title>
</head>

<body class="vertical bg-secondary/5">
