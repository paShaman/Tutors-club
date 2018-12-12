@extends('layouts.app')

@section('content')

    <div class="container">
        <h1>{{ $page['title'] }}</h1>

        <div class="pb-3 mb-5 text-right border-bottom">
            <a href="#" class="<?=App\Common::BTN?> btn-primary" data-toggle="modal" data-target="#modalAddStudent"><i class="fa fa-plus"></i> {{ lng('add_student') }}</a>
        </div>

        @if (!empty($students))
            <div class="card-columns">
            @foreach ($students as $student)
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ $student['name'] }}</h5>
                        <p class="card-text">{{ $student['description'] }}</p>
                    </div>
                    <div class="card-footer text-right text-muted">

                    </div>
                </div>
            @endforeach
            </div>
        @else
            <div class="alert alert-warning">
                {{ lng('empty.students') }}
            </div>
        @endif

    </div>

    {!! $modalAddStudent !!}
@endsection