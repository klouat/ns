@extends('panel.layout')

@section('content')

<!-- Header -->
<div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-4 border-b-2 border-dashed border-black pb-6">
    <div>
        <h1 class="text-4xl font-black text-gray-900 tracking-tighter uppercase italic">Server Time</h1>
        <p class="mt-2 font-bold text-gray-500 font-mono">Current server clock and timezone information.</p>
    </div>
</div>

<!-- Time Display -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

    <!-- Clock Card -->
    <div class="md:col-span-2 bg-white border-2 border-black shadow-neo p-8">
        <p class="text-xs font-black uppercase tracking-widest text-gray-500 mb-4">Current Time</p>
        <div class="flex items-baseline gap-4">
            <span id="time" class="text-6xl font-black text-gray-900 tabular-nums tracking-tight">{{ $server_time->format('H:i:s') }}</span>
            <span class="text-lg font-bold text-gray-400 uppercase">{{ $server_time->format('A') }}</span>
        </div>
        <div id="date" class="text-xl font-bold text-gray-600 mt-3">{{ $server_time->format('l, d F Y') }}</div>
    </div>

    <!-- Timezone Card -->
    <div class="bg-[#FFDA55] border-2 border-black shadow-neo p-8 flex flex-col justify-between">
        <div>
            <p class="text-xs font-black uppercase tracking-widest text-black/60 mb-2">Timezone</p>
            <div class="text-2xl font-black text-black">{{ $server_time->format('e') }}</div>
            <div class="text-lg font-bold text-black/70 mt-1">UTC{{ $server_time->format('P') }}</div>
        </div>
        <div class="mt-6 pt-4 border-t-2 border-black/20">
            <p class="text-xs font-black uppercase tracking-widest text-black/60 mb-1">Unix Timestamp</p>
            <div id="unix" class="text-lg font-bold font-mono text-black">{{ $server_time->timestamp }}</div>
        </div>
    </div>
</div>

<!-- Details Grid -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div class="bg-white border-2 border-black shadow-neo-sm p-5">
        <p class="text-xs font-black uppercase tracking-widest text-gray-400 mb-2">Day of Year</p>
        <div class="text-3xl font-black text-gray-900">{{ $server_time->dayOfYear }}</div>
        <div class="text-xs font-bold text-gray-500 mt-1">of 365</div>
    </div>
    <div class="bg-white border-2 border-black shadow-neo-sm p-5">
        <p class="text-xs font-black uppercase tracking-widest text-gray-400 mb-2">Week</p>
        <div class="text-3xl font-black text-gray-900">{{ $server_time->weekOfYear }}</div>
        <div class="text-xs font-bold text-gray-500 mt-1">of 52</div>
    </div>
    <div class="bg-white border-2 border-black shadow-neo-sm p-5">
        <p class="text-xs font-black uppercase tracking-widest text-gray-400 mb-2">Quarter</p>
        <div class="text-3xl font-black text-gray-900">Q{{ $server_time->quarter }}</div>
        <div class="text-xs font-bold text-gray-500 mt-1">{{ $server_time->format('F') }}</div>
    </div>
    <div class="bg-[#FF90E8] border-2 border-black shadow-neo-sm p-5">
        <p class="text-xs font-black uppercase tracking-widest text-black/60 mb-2">Uptime</p>
        <div id="uptime" class="text-3xl font-black text-black">0s</div>
        <div class="text-xs font-bold text-black/60 mt-1">since page load</div>
    </div>
</div>

<script>
    let ts = {{ $server_time->timestamp }};
    let uptime = 0;

    function pad(n) { return String(n).padStart(2, '0'); }

    setInterval(() => {
        ts++;
        uptime++;

        const d = new Date(ts * 1000);
        document.getElementById('time').textContent =
            pad(d.getUTCHours() + {{ $server_time->offsetHours }}) + ':' +
            pad(d.getUTCMinutes()) + ':' +
            pad(d.getUTCSeconds());

        document.getElementById('unix').textContent = ts;

        const h = Math.floor(uptime / 3600);
        const m = Math.floor((uptime % 3600) / 60);
        const s = uptime % 60;
        document.getElementById('uptime').textContent =
            (h > 0 ? h + 'h ' : '') + (m > 0 ? m + 'm ' : '') + s + 's';
    }, 1000);
</script>

@endsection
