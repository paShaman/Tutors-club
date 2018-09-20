@extends('layouts.app')

@section('content')

    <div class="row mt-5">
        <div class="col-sm-2 col-auto"></div>
        <div class="col-sm-8 col-12">
            <div class="mw-500 ml-auto mr-auto">
                <h1>{{ $page['title'] }}</h1>

                @include('forms.password-recovery')
            </div>
        </div>
        <div class="col-sm-2 col-auto"></div>
    </div>

@endsection