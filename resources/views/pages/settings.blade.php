@extends('layouts.app')

@section('content')

    <div class="container">
        <h1>{{ $page['title'] }}</h1>

        @include('forms.settings')

        <div class="mt-5 border-top pt-5">
            <h2>{{ lng('social_networks') }}</h2>

            <div>
                <div class="mb-1">
                    @if (!empty($socialNetworks['vkontakte']))
                        <span class="btn waves-effect waves-light btn-sm btn-danger"><i class="fab fa-vk fa-fw"></i> {{ lng('social_off') }}</span>
                    @else
                        <a href="/login/vkontakte" class="btn waves-effect waves-light btn-sm btn-success"><i class="fab fa-vk fa-fw"></i> {{ lng('social_on') }}</a>
                    @endif
                </div>

                <div class="mb-1">
                    @if (!empty($socialNetworks['facebook']))
                        <span class="btn waves-effect waves-light btn-sm btn-danger"><i class="fab fa-facebook-f fa-fw"></i> {{ lng('social_off') }}</span>
                    @else
                        <a href="/login/facebook" class="btn waves-effect waves-light btn-sm btn-success"><i class="fab fa-facebook-f fa-fw"></i>{{ lng('social_on') }}</a>
                    @endif
                </div>

                <div class="mb-1">
                    @if (!empty($socialNetworks['google']))
                        <span class="btn btn-sm btn-danger waves-effect waves-light"><i class="fab fa-google fa-fw"></i> {{ lng('social_off') }}</span>
                    @else
                        <a href="/login/google" class="btn waves-effect waves-light btn-sm btn-success"><i class="fab fa-google fa-fw"></i> {{ lng('social_on') }}</a>
                    @endif
                </div>

            </div>
        </div>

        <div class="mt-5 border-top pt-5">
            <h2>{{ lng('subscriptions') }}</h2>

            <a href="#" id="webpush-subscribe-button" style="display: none;"></a>
        </div>
    </div>

@endsection