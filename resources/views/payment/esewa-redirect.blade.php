<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Redirecting to eSewa...</title>
</head>
<body style="font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #f8fafc;">
    <div style="text-align: center;">
        <p>Redirecting you to eSewa to complete your payment&hellip;</p>
        <p><small>Please do not close this window.</small></p>
    </div>

    <form id="esewa-form" action="{{ $gatewayUrl }}" method="POST">
        @foreach ($fields as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
    </form>

    <script>
        document.getElementById('esewa-form').submit();
    </script>
</body>
</html>
