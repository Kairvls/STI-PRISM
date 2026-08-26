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
