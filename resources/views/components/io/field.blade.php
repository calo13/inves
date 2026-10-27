@props(['label','type'=>'text'])<label class="block text-sm font-medium text-gray-700"><span>{{ $label }}</span><input type="{{ $type }}" {{ $attributes->merge(['class'=>'io-input mt-2']) }}></label>
