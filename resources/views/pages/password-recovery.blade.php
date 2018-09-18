@extends('layouts.app')

@section('content')

    <div class="row mt-5">
        <div class="col-2"></div>
        <div class="col-8">
            <div class="mw-500 ml-auto mr-auto">
                <h1>{{ $title }}</h1>

                @include('forms.password-recovery')
            </div>
        </div>
        <div class="col-2"></div>
    </div>

@endsection