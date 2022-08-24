<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Document</title>
    {{-- <script src="https://js.pusher.com/5.0/pusher.min.js"></script> --}}
</head>

<body>
    <h1>Pusher Test</h1>
    <p>
        Try publishing an event to channel <code>my-channel</code>
        with event name <code>my-event</code>.
    </p>

    <div id="app">
        <example-component>
            <example-component>
    </div>
</body>
<script type="text/javascript" src="{{ asset('js/app.js') }}"></script>

</html>