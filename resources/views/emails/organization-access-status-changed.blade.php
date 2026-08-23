@extends('emails.layouts.transactional')

@section(
    'title',
    $status === 'archived'
        ? 'Organization archived'
        : 'Organization restored'
)

@section(
    'preheader',
    $status === 'archived'
        ? 'Access to your eConsent organization has been archived.'
        : 'Access to your eConsent organization has been restored.'
)

@section(
    'eyebrow',
    'Organization access'
)

@section('headline')
    @if ($status === 'archived')
        {{ $organizationName }} has been archived
    @else
        {{ $organizationName }} has been restored
    @endif
@endsection

@section('content')
    <p
        style="
            margin:0 0 18px;
            font-size:15px;
            line-height:1.7;
            color:#374151;
        "
    >
        Hello {{ $recipientName }},
    </p>

    @if ($status === 'archived')
        <p
            style="
                margin:0 0 18px;
                font-size:15px;
                line-height:1.7;
                color:#374151;
            "
        >
            A Platform Super Admin has archived
            <strong>{{ $organizationName }}</strong>.
            Organization users can no longer access the workspace,
            and public signing stations and consent signing links are
            unavailable while the organization remains archived.
        </p>

        @if ($archiveReason)
            <div
                style="
                    margin:22px 0;
                    padding:16px 18px;
                    border:1px solid #fecaca;
                    border-radius:12px;
                    background:#fef2f2;
                    color:#7f1d1d;
                "
            >
                <p
                    style="
                        margin:0 0 6px;
                        font-size:12px;
                        font-weight:700;
                        text-transform:uppercase;
                        letter-spacing:.06em;
                    "
                >
                    Archive reason
                </p>

                <p
                    style="
                        margin:0;
                        font-size:14px;
                        line-height:1.7;
                    "
                >
                    {{ $archiveReason }}
                </p>
            </div>
        @endif

        <p
            style="
                margin:0 0 18px;
                font-size:14px;
                line-height:1.7;
                color:#475569;
            "
        >
            Existing users, roles, subscriptions, templates and
            consent records remain preserved.
        </p>
    @else
        <p
            style="
                margin:0 0 18px;
                font-size:15px;
                line-height:1.7;
                color:#374151;
            "
        >
            A Platform Super Admin has restored
            <strong>{{ $organizationName }}</strong>.
            Organization users can sign in again, and existing public
            signing stations and consent links can operate according
            to their previous status.
        </p>
    @endif

    <table
        role="presentation"
        width="100%"
        cellspacing="0"
        cellpadding="0"
        style="
            width:100%;
            margin:22px 0;
            border:1px solid #e2e8f0;
            border-radius:12px;
            background:#f8fafc;
        "
    >
        <tr>
            <td style="padding:16px 18px;">
                <p
                    style="
                        margin:0 0 6px;
                        font-size:13px;
                        line-height:1.6;
                        color:#475569;
                    "
                >
                    <strong>Organization:</strong>
                    {{ $organizationName }}
                </p>

                <p
                    style="
                        margin:0 0 6px;
                        font-size:13px;
                        line-height:1.6;
                        color:#475569;
                    "
                >
                    <strong>Changed by:</strong>
                    {{ $performedByName }}
                </p>

                <p
                    style="
                        margin:0;
                        font-size:13px;
                        line-height:1.6;
                        color:#475569;
                    "
                >
                    <strong>Changed:</strong>
                    {{ $changedAt }}
                </p>
            </td>
        </tr>
    </table>
@endsection
