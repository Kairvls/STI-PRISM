@extends('layouts.maintenance-layout')

@section('content')

<div class="p-8 max-w-7xl mx-auto bg-slate-50/50 min-h-screen">

    <div class="mb-8 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-[#005EA6] tracking-tight">
                Infrastructure Dashboard
            </h1>
            <p class="text-sm text-slate-500">Overview of the physical assets, campus levels, and rooms at STI College Ormoc.</p>
        </div>
        
        <div class="h-2 w-32 bg-gradient-to-r from-[#005EA6] to-[#FFF200] rounded-full hidden md:block"></div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">

        <div class="bg-white rounded-2xl p-6 shadow-sm border-l-4 border-[#005EA6] transition hover:shadow-md">
            <div class="flex justify-between items-center mb-2">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Buildings</p>
                <span class="text-xl">🏢</span>
            </div>
            <h2 class="text-3xl font-black text-slate-900">
                {{ $totalBuildings }}
            </h2>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border-l-4 border-amber-500 transition hover:shadow-md">
            <div class="flex justify-between items-center mb-2">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Floors</p>
                <span class="text-xl">📍</span>
            </div>
            <h2 class="text-3xl font-black text-slate-900">
                {{ $totalFloors }}
            </h2>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border-l-4 border-[#FFF200] transition hover:shadow-md">
            <div class="flex justify-between items-center mb-2">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Rooms</p>
                <span class="text-xl">🚪</span>
            </div>
            <h2 class="text-3xl font-black text-slate-900">
                {{ $totalRooms }}
            </h2>
        </div>

    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-8">

        <div class="border-b border-slate-100 pb-4 mb-6 flex items-center gap-2">
            <div class="w-2.5 h-6 bg-[#005EA6] rounded-full"></div>
            <h2 class="text-xl font-bold text-slate-900">
                Campus Structural Breakdown
            </h2>
        </div>

        <div class="space-y-6">
            @foreach($buildings as $building)
                <div class="bg-slate-50/50 border border-slate-100 rounded-2xl p-5 hover:bg-slate-50 transition">
                    
                    <h3 class="font-bold text-lg text-[#005EA6] flex items-center gap-2 select-none">
                        <span class="bg-[#005EA6]/10 text-[#005EA6] p-1.5 rounded-lg text-sm">🏢</span> 
                        {{ $building->building_name }}
                    </h3>

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 pl-4 border-l-2 border-slate-200">
                        @foreach($floors->where('floor_building_id', $building->building_id) as $floor)
                            
                            <div class="bg-white p-4 rounded-xl border border-slate-100 shadow-2xs">
                                <h4 class="font-bold text-sm text-slate-800 flex items-center gap-1.5">
                                    <span class="text-amber-500">📍</span> 
                                    {{ $floor->floor_level }}
                                </h4>

                                <div class="mt-3">
                                    <ul class="flex flex-wrap gap-2">
                                        @forelse($rooms->where('room_floor_id', $floor->floor_id) as $room)
                                            <li class="bg-slate-50 border border-slate-200 hover:border-[#005EA6]/40 hover:bg-[#005EA6]/5 text-slate-700 text-xs font-medium px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition select-none cursor-default">
                                                <span class="text-[10px]">🚪</span> 
                                                {{ $room->room_name }}
                                            </li>
                                        @empty
                                            <li class="text-xs text-slate-400 italic">No rooms registered on this floor.</li>
                                        @endforelse
                                    </ul>
                                </div>
                            </div>

                        @endforeach
                    </div>

                </div>
            @endforeach
        </div>

    </div>

</div>

@endsection