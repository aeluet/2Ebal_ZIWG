@extends('layouts.app')

@section('izenburua', 'Bezero berria')

@section('edukia')
    @if (session('errorea'))
        <p class="errorea">{{ session('errorea') }}</p>
    @endif

    <form method="POST" action="{{ route('bezeroak.store') }}">
        @csrf

        <p>
            <label for="izena">Izena</label><br>
            <input type="text" id="izena" name="izena" value="{{ old('izena') }}">
            @error('izena') <br><span class="eremu-errorea">{{ $message }}</span> @enderror
        </p>

        <p>
            <label for="emaila">Emaila</label><br>
            <input type="email" id="emaila" name="emaila" value="{{ old('emaila') }}">
            @error('emaila') <br><span class="eremu-errorea">{{ $message }}</span> @enderror
        </p>

        <p>
            <label for="telefonoa">Telefonoa (aukerakoa)</label><br>
            <input type="text" id="telefonoa" name="telefonoa" value="{{ old('telefonoa') }}">
            @error('telefonoa') <br><span class="eremu-errorea">{{ $message }}</span> @enderror
        </p>

        <button type="submit">Gorde</button>
    </form>
@endsection
