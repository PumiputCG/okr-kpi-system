<?php

namespace App\Models;

use App\Casts\PlainText;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AppUser extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\AppUserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'app_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_code',
        'password',
        'full_name_th',
        'full_name_en',
        'employee_type',
        'position',
        'department',
        'dept_abbr_hr',
        'dept_abbr_qms',
        'email',
        'id_thai_hash',
        'role',
        'profile_picture',
        'reset_token',
        'reset_token_expiry',
        'session_id',
        'is_registered',
        'registered_at',
        'remember_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'remember_token',
        'reset_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_registered' => 'boolean',
            'employee_code' => PlainText::class,
            'full_name_th' => PlainText::class.':nullable',
            'full_name_en' => PlainText::class.':nullable',
            'employee_type' => PlainText::class.':nullable',
            'position' => PlainText::class.':nullable',
            'department' => PlainText::class.':nullable',
            'dept_abbr_hr' => PlainText::class.':nullable',
            'dept_abbr_qms' => PlainText::class.':nullable',
            'registered_at' => 'datetime',
            'reset_token_expiry' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected function role(): Attribute
    {
        return Attribute::make(
            set: static fn (?string $value): string => strtolower(trim((string) $value)) === 'admin' ? 'admin' : 'user',
        );
    }
}
