<!DOCTYPE html>
<html lang="es">
<head>
 <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
 <title>Proyecto IO · Universidad Mariano Gálvez</title><link rel="icon" type="image/png" href="{{ asset('images/umg/escudo-umg.png') }}">
 @vite(['resources/css/app.css','resources/js/app.js']) @livewireStyles
</head>
<body class="bg-gray-50 font-outfit text-gray-700" x-data="{ menu: false }">
<div class="min-h-screen xl:flex">
 <aside class="fixed inset-y-0 start-0 z-50 flex w-64 flex-col border-e border-gray-200 bg-white transition-transform xl:translate-x-0" :class="menu ? 'translate-x-0' : 'max-xl:-translate-x-full'">
  <a href="{{ route('dashboard') }}" class="flex items-center gap-3 border-b border-gray-100 px-5 py-6"><x-io.escudo class="h-14 w-14"/><span><span class="block text-xl font-semibold text-brand-900">UMG · IO</span><span class="mt-1 block text-xs leading-4 text-gray-500">Investigación de<br>Operaciones</span></span></a>
  <div class="px-6 pt-6 pb-3 text-xs font-medium tracking-widest text-gray-400">PROYECTO ACADÉMICO</div>
  <nav class="space-y-1 px-4" aria-label="Navegación principal">
  @php($links=['dashboard'=>['Panel ejecutivo','M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z'],'tickets'=>['Mesa de Entrada','M4 5h16v14H4z M8 9h8 M8 13h5'],'recursos'=>['Gestores y ausencias','M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 3a4 4 0 1 0 0 8a4 4 0 0 0 0-8 M17 4a4 4 0 0 1 0 8 M22 21v-2a4 4 0 0 0-3-3.87'],'simulador'=>['Cambio de docente','M4 7h15 M15 3l4 4-4 4 M20 17H5 M9 13l-4 4 4 4'],'calendario'=>['Calendario','M4 5h16v16H4z M16 3v4 M8 3v4 M4 11h16'],'importacion'=>['Importar Excel','M12 16V3 M7 8l5-5 5 5 M3 15v6h18v-6'],'calidad'=>['Calidad de datos','M12 3l10 18H2z M12 9v5 M12 17h.01'],'pruebas'=>['Pruebas de validación','M9 3h6 M10 3v6L4 19v2h16v-2L14 9V3 M8 15h8'],'parametros'=>['Configuración','M12 8a4 4 0 1 0 0 8a4 4 0 0 0 0-8 M12 2v3 M12 19v3 M2 12h3 M19 12h3 M5 5l2 2 M17 17l2 2 M5 19l2-2 M17 7l2-2']])
  @foreach($links as $key=>[$label,$icon])
  @if((!in_array($key,['recursos','parametros','pruebas'])||auth()->user()->hasRole('admin'))&&(!in_array($key,['importacion','calidad','simulador'])||auth()->user()->hasAnyRole(['admin','mesa_entrada'])))
   <a href="{{ $key==='dashboard'?route('dashboard'):route('modulo',$key) }}" class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium {{ request()->path()===($key==='dashboard'?'/':$key)?'bg-brand-50 text-brand-600':'text-gray-600 hover:bg-gray-50' }}"><svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}"/></svg>{{ $label }}</a>
  @endif @endforeach
  </nav>
  <div class="mx-5 mt-auto mb-5 rounded-xl border border-gray-100 bg-gray-50 p-4 text-xs leading-5 text-gray-500"><p class="font-medium text-gray-800">{{ config('academico.estudiante') }}</p><p>{{ config('academico.semestre') }} · {{ config('academico.tipo') }}</p><p class="mt-2 border-t border-gray-200 pt-2">Entrega: {{ config('academico.entrega') }}</p></div>
 </aside>
 <div class="min-w-0 flex-1 xl:ms-64">
  <header class="flex min-h-20 items-center justify-between gap-4 border-b border-gray-200 bg-white px-5 py-3 lg:px-9">
   <div class="flex items-center gap-4"><button @click="menu=!menu" class="rounded-lg border border-gray-200 p-2 xl:hidden" aria-label="Abrir menú">☰</button><x-io.escudo class="h-10 w-10 xl:hidden"/><div><p class="text-sm font-medium text-gray-800">{{ config('academico.universidad') }}</p><p class="mt-1 text-xs text-gray-500">{{ config('academico.curso') }} <span class="mx-1 text-gray-300">/</span> {{ config('academico.semestre') }}</p></div></div>
   <div class="flex shrink-0 items-center gap-3"><span class="hidden max-w-52 text-end text-sm sm:block">{{ auth()->user()->name }}<span class="mt-1 block text-xs text-gray-400">{{ auth()->user()->hasRole('admin')?'Administrador':auth()->user()->getRoleNames()->join(', ') }}</span></span><span class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-50 text-brand-600">{{ mb_substr(auth()->user()->name,0,1) }}</span><form method="post" action="{{ route('logout') }}">@csrf<button class="text-xs text-gray-500 hover:text-brand-600">Salir</button></form></div>
  </header>
  <main class="mx-auto max-w-screen-2xl p-5 lg:p-9">{{ $slot }}</main>
  <footer class="px-9 py-6 text-xs leading-5 text-gray-400">{{ config('academico.tipo') }} · {{ config('academico.curso') }} · {{ config('academico.anio') }}<br>{{ config('academico.estudiante') }} · {{ config('academico.semestre') }} · UMG</footer>
 </div>
</div>@livewireScripts
</body></html>
