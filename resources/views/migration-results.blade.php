<!DOCTYPE html>
<html>
<head>
    <title>Migration Results</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .container { background: #fff; padding: 20px; border-radius: 5px; max-width: 800px; margin: 0 auto; }
        pre { background: #f9f9f9; padding: 15px; border-radius: 5px; border: 1px solid #ddd; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        h1 { color: #333; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Migration Results</h1>
        <pre>
@foreach($results as $result)
{{ $result }}
@endforeach

@if($success)
<span class="success">✅ All migrations completed successfully!</span>

<span class="warning">⚠️  IMPORTANT: Delete the /run-migrations route from routes/web.php for security!</span>
@else
<span class="error">❌ Migration failed. Please check the errors above.</span>
@endif
        </pre>
        <p><a href="{{ url('/') }}">← Back to Home</a></p>
    </div>
</body>
</html>
