@props(['label'])<label class="block text-sm font-medium text-gray-700"><span>{{ $label }}</span><select {{ $attributes->merge(['class'=>'io-input mt-2']) }}>{{ $slot }}</select></label>
