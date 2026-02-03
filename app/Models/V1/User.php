<?php

namespace App\Models\V1;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'company_id',
        'branch_id',
        'department',
        'position',
        'national_number',
        'municipality_certificate',
        'driving_license',
        'personal_photo',
        'certificate_expiry',
        'license_expiry',
        'id_expiry',
        'is_certificate_valid',
        'is_license_valid',
        'is_id_valid',
        'birth_date',
        'gender',
        'status',
        'must_change_password',
        'created_by',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'birth_date' => 'date',
        'certificate_expiry' => 'date',
        'license_expiry' => 'date',
        'id_expiry' => 'date',
        'last_login_at' => 'datetime',
        'is_certificate_valid' => 'boolean',
        'is_license_valid' => 'boolean',
        'is_id_valid' => 'boolean',
        'must_change_password' => 'boolean',
        'status' => 'integer',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function managedBranch()
    {
        return $this->hasOne(Branch::class, 'manager_user_id');
    }

    // Scopes
    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeAuditors($query)
    {
        return $query->where('role', 'auditor');
    }

    public function scopeWithExpiredDocuments($query)
    {
        $today = now()->format('Y-m-d');
        return $query->where('role', 'auditor')
            ->where(function($q) use ($today) {
                $q->where('certificate_expiry', '<=', $today)
                  ->orWhere('license_expiry', '<=', $today)
                  ->orWhere('id_expiry', '<=', $today);
            });
    }

    // Methods
    public function isSuperAdmin()
    {
        return $this->role === 'super_admin';
    }

    public function isAuditor()
    {
        return $this->role === 'auditor';
    }

    public function isCompanyAdmin()
    {
        return $this->role === 'company_admin';
    }

    public function isBranchManager()
    {
        return $this->role === 'branch_manager';
    }

    public function updateLastLogin($ip)
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);
    }

    public function checkDocumentsValidity()
    {
        if ($this->role !== 'auditor') {
            return false;
        }

        $today = now();
        $updates = [];

        // Check certificate expiry
        if ($this->certificate_expiry) {
            $updates['is_certificate_valid'] = $today->lte($this->certificate_expiry);
        }

        // Check license expiry
        if ($this->license_expiry) {
            $updates['is_license_valid'] = $today->lte($this->license_expiry);
        }

        // Check ID expiry
        if ($this->id_expiry) {
            $updates['is_id_valid'] = $today->lte($this->id_expiry);
        }

        if (!empty($updates)) {
            $this->update($updates);
            return true;
        }

        return false;
    }
}

