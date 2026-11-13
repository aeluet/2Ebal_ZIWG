@extends('layouts.app')

@section('izenburua', 'Odoo-ko partner-ak')

@section('edukia')
    @isset($errorea)
        <p class="errorea">{{ $errorea }}</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Izena</th>
                    <th>Emaila</th>
                    <th>Telefonoa</th>
                </tr>
            </thead>
            <tbody>
                {{-- Odoo-n eremu hutsak "false" dira, ez "null" --}}
                @forelse ($partnerrak as $partnerra)
                    <tr>
                        <td>{{ $partnerra['name'] }}</td>
                        <td>{{ $partnerra['email'] ?: '—' }}</td>
                        <td>{{ $partnerra['phone'] ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">Ez dago partner-ik.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endisset
@endsection
