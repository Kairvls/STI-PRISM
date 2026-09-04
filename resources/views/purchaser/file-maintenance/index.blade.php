@php
    $tabMeta = [
        'brands' => ['title' => 'Brands'],
        'uom' => ['title' => 'UOM'],
        'categories' => ['title' => 'Categories'],
        'subcategories' => ['title' => 'Sub Categories'],
    ];
    $current = $tabMeta[$tab] ?? $tabMeta['brands'];
@endphp

@extends('layouts.purchaser-layout')

@section('page-title', 'File Maintenance')
@section('page-subtitle', $current['title'])

@section('content')

    @if($tab === 'brands')
        @include('purchaser.file-maintenance.partials.brands')
    @elseif($tab === 'uom')
        @include('purchaser.file-maintenance.partials.uom')
    @elseif($tab === 'categories')
        @include('purchaser.file-maintenance.partials.categories')
    @else
        @include('purchaser.file-maintenance.partials.subcategories')
    @endif

@endsection
