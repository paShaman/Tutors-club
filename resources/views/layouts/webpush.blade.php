<script src="https://cdn.onesignal.com/sdks/OneSignalSDK.js" async=""></script>
<script>
    var OneSignal = window.OneSignal || [];
    webPushHandlerInit('#webpush-subscribe-button', '{{ env('ONESIGNAL_APP') }}');
</script>