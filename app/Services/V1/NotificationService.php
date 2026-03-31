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
        $users = User::where('role', 'super_admin')
            ->orWhere('id', $entity->company->manager->id)
            ->orWhere('id', $entity->branch->manager_user_id)
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

        (new NotificationController)->store(new Request([
            'user_id' => $user->id,
            'notification_template_id' => $template->id,
            //'title' => '',
            'body' => [
                'visits_count' => $user->visits->count(),
            ],
            'scheduled_date' => now(),
            'entity_id' => $user->id,
            'entity_type' => \App\Models\V1\User::class,
        ]));
        
    }

    public function subscription_expiry_reminder($entity)
    {
        $template = NotificationTemplate::active()->where('type', 'reminder_visit_tomorrow')->first();
        $user = $entity->manager;

        (new NotificationController)->store(new Request([
            'user_id' => $user->id,
            'notification_template_id' => $template->id,
            //'title' => '',
            'body' => [
                'company_name' => $entity->name,
                'days_left' => $entity->getRemainingSubscriptionDays(),
                'expiry_date' => $entity->subscription_end,
            ],
            'scheduled_date' => now(),
            'entity_id' => $entity->id,
            'entity_type' => \App\Models\V1\Company::class,
        ]));
        
    }

    public function subscription_expired($entity)
    {
        $template = NotificationTemplate::active()->where('type', 'subscription_expired')->first();
        $user = $entity->manager;

        (new NotificationController)->store(new Request([
            'user_id' => $user->id,
            'notification_template_id' => $template->id,
            //'title' => '',
            'body' => [
                'company_name' => $entity->name,
                'expiry_date' => $entity->subscription_end,
            ],
            'scheduled_date' => now(),
            'entity_id' => $entity->id,
            'entity_type' => \App\Models\V1\Company::class,
        ]));
        
    }
}