<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ActivityLogger
{
    /**
     * Record an activity in the audit log.
     *
     * @param string $action
     * A machine-friendly action name such as:
     * user.created, user.updated, user.archived.
     *
     * @param string $description
     * A readable explanation shown to administrators.
     *
     * @param Model|null $subject
     * The model affected by the activity.
     *
     * @param int|null $organizationId
     * The organization related to the activity.
     *
     * @param array<string, mixed> $properties
     * Optional structured details such as old and new values.
     */
    public function log(
        string $action,
        string $description,
        ?Model $subject = null,
        ?int $organizationId = null,
        array $properties = []
    ): ActivityLog {
        /** @var Request $request */
        $request = request();

        return ActivityLog::create([
            'organization_id' => $organizationId,
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'properties' => $properties ?: null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
