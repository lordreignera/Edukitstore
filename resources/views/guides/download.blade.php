<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EduKit {{ $guide['portalName'] }} User Guide</title>
    <style>
        body { color: #071d4f; font: 16px/1.6 Arial, sans-serif; margin: 0 auto; max-width: 900px; padding: 40px 24px; }
        h1 { margin-bottom: 8px; }
        h2 { color: #c2410c; font-size: 14px; letter-spacing: .12em; text-transform: uppercase; }
        h3 { margin: 0 0 4px; }
        .intro { color: #475569; margin-bottom: 28px; }
        .step { border: 1px solid #fed7aa; border-radius: 8px; margin: 12px 0; padding: 16px; }
        .step-number { background: #071d4f; border-radius: 50%; color: white; display: inline-grid; height: 28px; margin-right: 8px; place-items: center; width: 28px; }
        .notes { background: #f8fafc; border-top: 3px solid #f97316; margin-top: 28px; padding: 16px; }
    </style>
</head>
<body>
    @php
        $visibleSteps = collect($guide['steps'])
            ->filter(fn (array $step): bool => empty($step['permission']) || auth()->user()?->can($step['permission']))
            ->values();
    @endphp

    <h1>EduKit {{ $guide['portalName'] }} User Guide</h1>
    <h2>{{ $guide['eyebrow'] }}</h2>
    <p class="intro">{{ $guide['intro'] }}</p>

    @foreach ($visibleSteps as $index => $step)
        <section class="step">
            <h3><span class="step-number">{{ $index + 1 }}</span>{{ $step['title'] }}</h3>
            <p>{{ $step['description'] }}</p>
        </section>
    @endforeach

    <div class="notes">
        <strong>Important to know</strong>
        <ul>
            @foreach ($guide['notes'] as $note)
                <li>{{ $note }}</li>
            @endforeach
        </ul>
    </div>
</body>
</html>
