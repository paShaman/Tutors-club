@extends('layouts.app')

@section('content')

    <h1>{{ $page['title'] }}</h1>

    <ul class="nav nav-pills mb-3">
        <li class="nav-item">
            <a class="nav-link active" data-toggle="pill" href="#admin-users">Пользователи</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="pill" href="#admin-test">Тест</a>
        </li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="admin-users">
            @include('pages.admin.users')
        </div>
        <div class="tab-pane fade" id="admin-test">test</div>
    </div>

@endsection