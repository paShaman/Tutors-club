@extends('layouts.app')

@section('content')

    <div class="card-body">
        You are logged in!

        @if (!$user->email_verified_at)
            <br>
            <a href="{{ route('verification.resend') }}">{{ route('verification.resend') }}</a>
        @endif
    </div>

@endsection