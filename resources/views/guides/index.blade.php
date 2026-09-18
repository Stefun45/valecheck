@extends('layouts.legal', [
    'title' => 'Vehicle Buying Guides',
    'description' => 'Straightforward guides to buying a used car safely in the UK: write-off categories, checking for outstanding finance and how vehicle history checks actually work.',
])

@section('content')
    <div class="space-y-4">
        @foreach ([
            ['route' => 'guides.write-off-check', 'title' => 'How to Check If a Car Is Written Off', 'summary' => 'What a write-off marker means, why the V5C alone isn\'t enough, and how to actually check.'],
            ['route' => 'guides.write-off-categories', 'title' => 'Car Write-Off Categories Explained', 'summary' => 'Category A, B, S and N (and the older Cat C/D system) explained in full.'],
            ['route' => 'guides.finance-check', 'title' => 'How to Check a Car for Outstanding Finance', 'summary' => 'What the law says about buying a car on finance, and why it still pays to check first.'],
        ] as $guide)
            <a href="{{ route($guide['route']) }}" class="block border border-gray-100 rounded-xl p-5 hover:border-gray-200 hover:bg-gray-50 transition">
                <h2 class="font-display font-semibold text-vale-navy">{{ $guide['title'] }}</h2>
                <p class="text-sm text-gray-500 mt-1">{{ $guide['summary'] }}</p>
            </a>
        @endforeach
    </div>
@endsection
