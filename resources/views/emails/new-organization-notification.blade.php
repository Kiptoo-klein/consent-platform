<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Organization Created</title>
</head>
<body>
    <h2>New organization created</h2>

    <p>
        A new organization has been created on eConsent.
    </p>

    <p>
        <strong>Organization:</strong>
        {{ $organization->name }}
    </p>

    @if ($organization->email)
        <p>
            <strong>Email:</strong>
            {{ $organization->email }}
        </p>
    @endif

    @if ($organization->phone)
        <p>
            <strong>Phone:</strong>
            {{ $organization->phone }}
        </p>
    @endif

    <p>
        <strong>Created:</strong>
        {{ $organization->created_at?->format('F j, Y g:i A') }}
    </p>

    <p>
        Log in to the eConsent platform to view the organization.
    </p>
</body>
</html>
