@extends('layouts.app')

@section('content')

    <div class="row">
        <div class="col-2"></div>
        <div class="col-8">
            @include('forms.auth')
        </div>
        <div class="col-2"></div>
    </div>

@endsection