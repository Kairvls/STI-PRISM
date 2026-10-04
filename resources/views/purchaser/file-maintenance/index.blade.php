@php
    $tabMeta = [
        'brands' => ['title' => 'Brands'],
        'items' => ['title' => 'Items'],
        'uom' => ['title' => 'UOM'],
        'categories' => ['title' => 'Categories'],
        'subcategories' => ['title' => 'Sub Categories'],
    ];
    $current = $tabMeta[$tab] ?? $tabMeta['brands'];
@endphp

@extends($procurementLayout ?? 'layouts.purchaser-layout')

@section('page-title', 'File Maintenance')
@section('page-subtitle', $current['title'])

@section('content')

    @if($tab === 'brands')
        @include('purchaser.file-maintenance.partials.brands')
    @elseif($tab === 'items')
        @include('purchaser.file-maintenance.partials.items')
    @elseif($tab === 'uom')
        @include('purchaser.file-maintenance.partials.uom')
    @elseif($tab === 'categories')
        @include('purchaser.file-maintenance.partials.categories')
    @else
        @include('purchaser.file-maintenance.partials.subcategories')
    @endif

@endsection
