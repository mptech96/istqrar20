<?php

namespace App\Services\V1;

use App\Models\User;
use App\Models\V1\NotificationTemplate;
use Illuminate\Http\Request;
use App\Http\Controllers\V1\NotificationController;

class NotificationService
{
    public function service_request($entity)
    {
        $template = NotificationTemplate::active()->where('type', 'service_request')->first();
        $users = User::where('role', 'super_admin')->get();
        
        foreach($users as $user){
            (new NotificationController)->store(new Request([
                'user_id' => $user->id,
                'notification_template_id' => $template->id,
                //'title' => '',
                'body' => [
                    'customer_name' => $entity->customer_name,
                    'request_type' => $entity->request_type,
                    'request_id' => $entity->id,
                ],
                'scheduled_date' => now(),
                'entity_id' => $entity->id,
                'entity_type' => get_class($entity),
            ]));
        }
        
    }

    public function visit_scheduled($entity)
    {
        $template = NotificationTemplate::active()->where('type', 'visit_scheduled')->first();
        $user = $entity->auditor;
        
        (new NotificationController)->store(new Request([
            'user_id' => $user->id,
            'notification_template_id' => $template->id,
            //'title' => '',
            'body' => [
                'branch_name' => $entity->branch->name,
                'visit_date' => $entity->scheduled_date,
                'visit_time' => $entity->scheduled_time,
            ],
            'scheduled_date' => now(),
            'entity_id' => $entity->id,
            'entity_type' => get_class($entity),
        ]));

    }
    
    public function visit_started($entity)
    {
        $template = NotificationTemplate::active()->where('type', 'visit_started')->first();
        $users = User::where('role', 'super_admin')->get();
        
        foreach($users as $user){
            (new NotificationController)->store(new Request([
                'user_id' => $user->id,
                'notification_template_id' => $template->id,
                //'title' => '',
                'body' => [
                    'auditor_name' => $entity->auditor->name,
                    'branch_name' => $entity->branch->name,
                    'start_time' => $entity->actual_start_time,
                ],
                'scheduled_date' => now(),
                'entity_id' => $entity->id,
                'entity_type' => get_class($entity),
            ]));
        }
        
    }

    public function visit_completed($entity)
    {
        $template = NotificationTemplate::active()->where('type', 'visit_completed')->first();
        if (!$template) return;
        $ids = array_filter([
            optional(optional($entity->company)->manager)->id,
            optional($entity->branch)->manager_user_id,
        ]);
        $users = User::where('role', 'super_admin')
            ->when($ids, fn ($q) => $q->orWhereIn('id', $ids))
            ->get();
        
        foreach($users as $user){
            (new NotificationController)->store(new Request([
                'user_id' => $user->id,
                'notification_template_id' => $template->id,
                //'title' => '',
                'body' => [
                    'auditor_name' => $entity->auditor->name,
                    'branch_name' => $entity->branch->name,
                    'score' => $entity->total_score,
                    'max_score' => $entity->max_possible_score,
                    'percentage' => $entity->percentage,
                ],
                'scheduled_date' => now(),
                'entity_id' => $entity->id,
                'entity_type' => get_class($entity),
            ]));
        }
        
    }

    public function reminder_visit_tomorrow($entity)
    {
        $template = NotificationTemplate::active()->where('type', 'reminder_visit_tomorrow')->first();
        $user = $entity->auditor;
        if (!$template || !$user) return;
        (new NotificationController)->store(new Request([
            'user_id' => $user->id,
            'notification_template_id' => $template->id,
            'body' => [
                // عدد زيارات الغد لهذا المفتش (محسوبة في scopeTomorrowVisits)
                'visits_count' => $entity->total_visits ?? 0,
            ],
            'scheduled_date' => now(),
            'entity_id' => $user->id,
            'entity_type' => \App\Models\V1\User::class,
        ]));
    }

    /**
     * مستلمو إشعارات الاشتراك: مدير المنشأة + جميع مدراء النظام (حسب العقد: إشعار المدير العام بحالات الاشتراك المنتهية)
     */
    private function subscriptionRecipients($company)
    {
        $users = User::where('role', 'super_admin')->get();
        if ($company->manager) {
            $users->push($company->manager);
        }
        return $users->unique('id');
    }

    private function notifyCompany(string $type, $company, array $body, bool $includeAdmins = true)
    {
        $template = NotificationTemplate::active()->where('type', $type)->first();
        if (!$template) return;
        $users = $includeAdmins ? $this->subscriptionRecipients($company) : collect([$company->manager])->filter();
        foreach ($users as $user) {
            (new NotificationController)->store(new Request([
                'user_id' => $user->id,
                'notification_template_id' => $template->id,
                'body' => $body,
                'scheduled_date' => now(),
                'entity_id' => $company->id,
                'entity_type' => \App\Models\V1\Company::class,
            ]));
        }
    }

    public function subscription_expiry_reminder($entity)
    {
        $this->notifyCompany('subscription_expiry_reminder', $entity, [
            'company_name' => $entity->name,
            'days_left' => $entity->getRemainingSubscriptionDays(),
            'expiry_date' => optional($entity->subscription_end)->toDateString(),
        ]);
    }

    public function subscription_expired($entity)
    {
        $this->notifyCompany('subscription_expired', $entity, [
            'company_name' => $entity->name,
            'expiry_date' => optional($entity->subscription_end)->toDateString(),
        ]);
    }

    public function subscription_renewed($entity)
    {
        $this->notifyCompany('subscription_renewed', $entity, [
            'company_name' => $entity->name,
            'expiry_date' => optional($entity->subscription_end)->toDateString(),
        ]);
    }
}