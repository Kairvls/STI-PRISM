@php
    $tabMeta = [
        'brands' => ['title' => 'Brands', 'subtitle' => 'Add, update, and remove brand records used across purchasing.'],
        'uom' => ['title' => 'UOM', 'subtitle' => 'Maintain units of measure used on RIS line items.'],
        'categories' => ['title' => 'Categories', 'subtitle' => 'Procurement item categories, separate from maintenance equipment categories.'],
        'subcategories' => ['title' => 'Sub Categories', 'subtitle' => 'Group sub categories under a parent procurement category.'],
    ];
    $current = $tabMeta[$tab] ?? $tabMeta['brands'];
@endphp

@extends('layouts.purchaser-layout')

@section('page-title', 'File Maintenance')
@section('page-subtitle', $current['title'])

@section('content')

    @if(session('success'))
        <div class="pur-alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="pur-alert-error">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="pur-alert-error">
            <ul class="list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-5">
        <p class="pur-page-kicker">File Maintenance</p>
        <h1 class="pur-page-title">{{ $current['title'] }}</h1>
    </div>

    <nav class="pur-tabs" aria-label="File maintenance lookups">
        <a href="{{ route('purchaser.file-maintenance.index', ['tab' => 'brands']) }}" class="pur-tab {{ $tab === 'brands' ? 'is-active' : '' }}">Brands</a>
        <a href="{{ route('purchaser.file-maintenance.index', ['tab' => 'uom']) }}" class="pur-tab {{ $tab === 'uom' ? 'is-active' : '' }}">UOM</a>
        <a href="{{ route('purchaser.file-maintenance.index', ['tab' => 'categories']) }}" class="pur-tab {{ $tab === 'categories' ? 'is-active' : '' }}">Categories</a>
        <a href="{{ route('purchaser.file-maintenance.index', ['tab' => 'subcategories']) }}" class="pur-tab {{ $tab === 'subcategories' ? 'is-active' : '' }}">Sub Categories</a>
    </nav>

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
