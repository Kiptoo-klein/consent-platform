<?php

namespace App\Models;

use App\Notifications\QuotaResetPassword;
use App\Notifications\QuotaVerifyEmail;
use App\Services\EmailQuotaService;
use Database\Factories\UserFactory;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use HasRoles;
    use Notifiable;
    use SoftDeletes;

    /**
     * The authentication guard used by Spatie Permission.
     */
    protected string $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'platform_role_id',
        'name',
        'email',
        'password',
        'is_active',
    ];

    /**
     * The attributes that should be hidden.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * A user belongs to one organization.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * A user may belong to one platform role.
     */
    public function platformRole(): BelongsTo
    {
        return $this->belongsTo(PlatformRole::class);
    }

    /**
     * The organization subscription for which this user is Billing Owner.
     */
    public function billingSubscription(): HasOne
    {
        return $this->hasOne(
            OrganizationSubscription::class,
            'billing_owner_user_id'
        );
    }

    /**
     * Subscription bypasses approved by this Platform Admin.
     *
     * This relationship is for audit history and does not subject
     * Platform Admins to organization subscription restrictions.
     */
    public function subscriptionBypassApprovals(): HasMany
    {
        return $this->hasMany(
            OrganizationSubscription::class,
            'bypass_approved_by_user_id'
        );
    }

    public function sendEmailVerificationNotification(): void
    {
        $notification =
            app(EmailQuotaService::class)
                ->shouldQueue()
                    ? new QuotaVerifyEmail()
                    : new VerifyEmail();

        $this->notify($notification);
    }

    public function sendPasswordResetNotification(
        $token
    ): void {
        $notification =
            app(EmailQuotaService::class)
                ->shouldQueue()
                    ? new QuotaResetPassword($token)
                    : new ResetPassword($token);

        $this->notify($notification);
    }

}
