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
        $users = $entity->auditor;
        
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
}