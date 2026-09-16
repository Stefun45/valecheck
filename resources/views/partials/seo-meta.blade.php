@php
    $canonical = $canonical ?? url()->current();
@endphp
<meta name="description" content="{{ $description }}">
<link rel="canonical" href="{{ $canonical }}">
@if ($noindex ?? false)
    <meta name="robots" content="noindex, nofollow">
@endif

<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ config('app.name', 'ValeCheck') }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonical }}">

<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
