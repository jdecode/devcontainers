<?php

namespace App\Models;

use App\Enums\ProfileImageUploadStatusEnum;
use App\Services\UserService;
use App\Traits\Searchable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

use function Illuminate\Events\queueable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use HasRoles;
    use Billable;
    use LogsActivity;
    use Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'email_verified_at',
        'image_filename',
        'image_upload_status'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'updated_at',
        'roles',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->logExcept([
                'password',
                'remember_token',
                'created_at',
                'updated_at',
                'image_upload_status'
            ]);
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'trial_ends_at' => 'date',
        'image_upload_status' => ProfileImageUploadStatusEnum::class
    ];

    protected static function booted(): void
    {
        static::updated(queueable(function (User $customer) {
            if ($customer->hasStripeId() && app()->environment() !== 'testing') {
                $customer->syncStripeCustomerDetails();
            }
        }));
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => trim($this->first_name . ' ' . $this->last_name)
        );
    }

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn () => (new UserService())->getUserImage($this)
        );
    }

    protected function imageThumbnail(): Attribute
    {
        return Attribute::make(
            get: fn () => (new UserService())->getUserImage($this, true)
        );
    }

    protected function initials(): Attribute
    {
        return Attribute::make(
            get: function () {
                $firstLetter = $this->first_name ? mb_strtoupper($this->first_name[0]) : 'X';
                $secondLetter = 'X';
                if ($this->last_name) {
                    $secondLetter = mb_strtoupper($this->last_name[0]);
                } elseif ($this->first_name && strlen($this->first_name) > 1) {
                    $secondLetter = $this->first_name[1];
                }
                $firstLetter = $firstLetter === '?' ? 'X' : $firstLetter;
                $secondLetter = $secondLetter === '?' ? 'X' : $secondLetter;
                return $firstLetter . $secondLetter;
            }
        );
    }

    public function searchableAttributes(): array
    {
        return [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email
        ];
    }
}
