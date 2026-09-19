@props(['design', 'register' => 'light'])
{{-- Everything a block draws from, derived in one place. Three callers used to
     build this by hand and one of them read the motif off a different object. --}}
@php ['t' => $t, 'motif' => $motif, 'shell' => $shell] = \App\Design\Blocks::context($design); @endphp
{{-- The shell alone, with an empty state where content would go. Shows the regions from 02 without a dashboard's noise. --}}
@include('components.block.parts.shell', ['page' => 'components.block.parts.shell-body'])
