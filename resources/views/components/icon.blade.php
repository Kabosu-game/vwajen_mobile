@props(['name', 'label' => null])
<svg {{ $attributes->merge(['class' => 'icon']) }} viewBox="0 0 24 24" @if($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif focusable="false">{!! \App\Support\Icons::path($name) !!}</svg>
