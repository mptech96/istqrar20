<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Checklist;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // إنشاء المستخدم المسؤول
        $admin = User::create([
            'name' => 'مدير النظام',
            'email' => 'admin@quality.com',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
            'status' => true,
            'email_verified_at' => now()
        ]);

        // إنشاء شركات تجريبية
        $companies = [
            [
                'name' => 'شركة التقنية المتطورة',
                'email' => 'info@tech.com',
                'phone' => '0512345678',
                'commercial_registration' => 'CR1234567890',
                'city' => 'الرياض',
                'contact_person' => 'أحمد محمد',
                'subscription_start' => now(),
                'subscription_end' => now()->addYear(),
                'subscription_status' => 'active',
                'status' => true,
                'is_verified' => true
            ],
            [
                'name' => 'مؤسسة البناء الحديث',
                'email' => 'info@modernbuilding.com',
                'phone' => '0523456789',
                'city' => 'جدة',
                'contact_person' => 'محمد علي',
                'subscription_status' => 'active',
                'status' => true,
                'is_verified' => true
            ]
        ];

        foreach ($companies as $companyData) {
            $company = Company::create($companyData);

            // إنشاء فروع لكل شركة
            $branches = [
                [
                    'name' => 'الفرع الرئيسي - الرياض',
                    'city' => 'الرياض',
                    'compliance_score' => 92.5,
                    'category' => 'A',
                    'total_visits' => 5,
                    'last_visit_date' => now()->subDays(15),
                    'next_scheduled_visit' => now()->addDays(75),
                    'status' => true
                ],
                [
                    'name' => 'فرع الشمال',
                    'city' => 'الرياض',
                    'compliance_score' => 78.0,
                    'category' => 'C',
                    'total_visits' => 3,
                    'last_visit_date' => now()->subDays(30),
                    'next_scheduled_visit' => now()->addDays(60),
                    'status' => true
                ]
            ];

            foreach ($branches as $branchData) {
                $branchData['company_id'] = $company->id;
                Branch::create($branchData);
            }

            // إنشاء مدير شركة
            User::create([
                'name' => 'مدير ' . $company->name,
                'email' => 'manager@' . explode('@', $company->email)[1],
                'password' => Hash::make('password123'),
                'role' => 'company_admin',
                'company_id' => $company->id,
                'status' => true
            ]);
        }

        // إنشاء مراجعين
        User::create([
            'name' => 'مراجع أول',
            'email' => 'auditor1@quality.com',
            'password' => Hash::make('password123'),
            'role' => 'auditor',
            'status' => true
        ]);

        User::create([
            'name' => 'مراجع ثاني',
            'email' => 'auditor2@quality.com',
            'password' => Hash::make('password123'),
            'role' => 'auditor',
            'status' => true
        ]);

        $this->command->info('✅ تم إنشاء البيانات التجريبية بنجاح!');
        $this->command->info('👤 حساب المسؤول: admin@quality.com / password123');
    }
}