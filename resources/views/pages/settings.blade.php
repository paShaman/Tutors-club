@extends('layouts.app')

@section('content')

    <div class="container">
        <h1>{{ $page['title'] }}</h1>

        @include('forms.settings')

        <div class="mt-5 border-top pt-5">
            <h2>{{ lng('social_networks') }}</h2>

            <div>
                <div class="mb-1" social="vkontakte">
                    <div @if (empty($socialNetworks['vkontakte'])) style="display: none" @endif>
                        <span class="btn waves-effect waves-light btn-sm btn-danger" onclick="socialDisconnect('vkontakte')"><i class="fab fa-vk fa-fw"></i> {{ lng('social_off') }}</span>
                    </div>
                    <div @if (!empty($socialNetworks['vkontakte'])) style="display: none" @endif>
                        <a href="/login/vkontakte" class="btn waves-effect waves-light btn-sm btn-success"><i class="fab fa-vk fa-fw"></i> {{ lng('social_on') }}</a>
                    </div>
                </div>

                <div class="mb-1" social="facebook">
                    <div @if (empty($socialNetworks['facebook'])) style="display: none" @endif>
                        <span class="btn waves-effect waves-light btn-sm btn-danger" onclick="socialDisconnect('facebook')"><i class="fab fa-facebook-f fa-fw"></i> {{ lng('social_off') }}</span>
                    </div>
                    <div @if (!empty($socialNetworks['facebook'])) style="display: none" @endif>
                        <a href="/login/facebook" class="btn waves-effect waves-light btn-sm btn-success"><i class="fab fa-facebook-f fa-fw"></i> {{ lng('social_on') }}</a>
                    </div>
                </div>

                <div class="mb-1" social="google">
                    <div @if (empty($socialNetworks['google'])) style="display: none" @endif>
                        <span class="btn btn-sm btn-danger waves-effect waves-light" onclick="socialDisconnect('google')"><i class="fab fa-google fa-fw"></i> {{ lng('social_off') }}</span>
                    </div>
                    <div @if (!empty($socialNetworks['google'])) style="display: none" @endif>
                        <a href="/login/google" class="btn waves-effect waves-light btn-sm btn-success"><i class="fab fa-google fa-fw"></i> {{ lng('social_on') }}</a>
                    </div>
                </div>

            </div>
        </div>

        <div class="mt-5 border-top pt-5">
            <h2>{{ lng('subscriptions') }}</h2>

            <a href="#" id="webpush-subscribe-button" style="display: none;"></a>
        </div>
    </div>

    <script>
        function socialDisconnect(social)
        {
            $.post('/social/disconnect', {social: social}, function (data) {
                if (data.success) {
                    message(true, data.data)

                    $('[social="'+ social +'"] > div').toggle();
                } else {
                    errorMessages(data);
                }
            });
        }
    </script>

@endsection