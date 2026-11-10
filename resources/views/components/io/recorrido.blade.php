@php
    $recorrido = [
        'pasos' => \App\Support\Recorrido::pasos(auth()->user()),
        'iniciar' => session()->pull('recorrido_pendiente', false),
        'usuario' => auth()->id(),
    ];
@endphp
<div x-data="ioRecorrido(@js($recorrido))" @io-recorrido-abrir.window="comenzar()" @keydown.window="tecla($event)">
    <div x-show="abierto" x-cloak class="io-tour" data-tour-overlay>
        <div x-show="!foco" class="absolute inset-0 bg-gray-900/80" aria-hidden="true"></div>
        <div x-show="foco" class="io-tour-foco" aria-hidden="true"
             :style="foco ? { left: foco.left+'px', top: foco.top+'px', width: foco.width+'px', height: foco.height+'px' } : {}"></div>
        <section x-ref="dialogo" role="dialog" aria-modal="true" aria-labelledby="io-tour-title" aria-describedby="io-tour-description"
                 tabindex="-1" class="io-tour-dialogo" :data-lado="posicion?.lado"
                 :style="posicion ? { left: posicion.left+'px', top: posicion.top+'px', '--io-tour-max': posicion.maxHeight+'px', '--io-tour-ancla': posicion.ancla+'px' } : {}">
            <div class="flex shrink-0 items-center justify-between gap-2 border-b border-gray-100 px-4 py-3 sm:px-5">
                <div class="flex items-center gap-2"><x-io.escudo class="size-8!"/><div><p class="text-xs font-semibold tracking-wide text-brand-600">RECORRIDO GUIADO</p><p class="mt-0.5 text-xs text-gray-500" x-text="'Paso '+(indice+1)+' de '+pasos.length"></p></div></div>
                <button type="button" @click="cerrar()" class="shrink-0 rounded-lg px-2 py-2 text-xs font-medium text-gray-600 hover:bg-gray-100 focus-visible:ring-2 focus-visible:ring-brand-500">Omitir recorrido</button>
            </div>
            <div x-ref="cuerpo" class="custom-scrollbar min-h-0 overflow-y-auto overscroll-contain px-4 py-4 sm:px-5" aria-live="polite" aria-atomic="true">
                <p x-show="paso.objetivos.length" class="mb-2 text-xs font-medium text-academic-red" x-text="foco ? 'Mira la zona iluminada' : 'En este módulo'"></p>
                <h2 id="io-tour-title" class="text-xl font-semibold leading-6 text-brand-900" x-text="paso.titulo"></h2>
                <p id="io-tour-description" class="mt-2 text-sm leading-5 text-gray-600" x-text="paso.descripcion"></p>
                <ol class="mt-3 space-y-2">
                    <template x-for="(accion, numero) in paso.acciones" :key="indice+'-'+numero">
                        <li class="flex gap-2 text-sm leading-5 text-gray-700"><span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand-600" x-text="numero+1"></span><span x-text="accion"></span></li>
                    </template>
                </ol>
                <p class="mt-3 rounded-lg border border-brand-100 bg-brand-50 p-3 text-xs leading-5 text-brand-800" x-text="paso.nota"></p>
            </div>
            <div class="shrink-0 border-t border-gray-100 px-4 py-3 sm:px-5">
                <div role="progressbar" aria-label="Avance del recorrido" aria-valuemin="1" :aria-valuemax="pasos.length" :aria-valuenow="indice+1" class="mb-3 h-1 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full bg-brand-500 transition-all motion-reduce:transition-none" :style="{ width: ((indice+1)/pasos.length*100)+'%' }"></div></div>
                <div class="flex items-center justify-between gap-3">
                    <x-ui.button variant="outline" size="sm" @click="mover(-1)" x-bind:disabled="indice === 0" class="disabled:cursor-not-allowed disabled:opacity-40">Anterior</x-ui.button>
                    <x-ui.button size="sm" @click="ultimo ? cerrar() : mover(1)" x-text="ultimo ? 'Finalizar recorrido' : (indice === 0 ? 'Comenzar' : 'Siguiente')"></x-ui.button>
                </div>
            </div>
        </section>
    </div>
</div>
