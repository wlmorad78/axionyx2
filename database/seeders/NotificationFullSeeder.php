<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use App\Models\Notification;
use App\Models\NotificationChannel;
use App\Models\NotificationDelivery;
use App\Models\NotificationEvent;
use App\Models\NotificationGroup;
use App\Models\NotificationGroupMember;
use App\Models\NotificationPreference;
use App\Models\NotificationRecipient;
use App\Models\NotificationRule;
use App\Models\NotificationRuleRecipient;
use App\Models\NotificationTemplate;
use App\Models\NotificationType;
use App\Models\ScheduledNotification;
use Illuminate\Database\Seeder;

class NotificationFullSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        // Notification Types (global)
        $types = [
            ['type_code' => 'NT-INFO', 'type_name' => 'معلومة - Information', 'description' => 'إشعارات معلوماتية'],
            ['type_code' => 'NT-WARNING', 'type_name' => 'تنبيه - Warning', 'description' => 'إشعارات تحذيرية'],
            ['type_code' => 'NT-ERROR', 'type_name' => 'خطأ - Error', 'description' => 'إشعارات أخطاء'],
            ['type_code' => 'NT-SUCCESS', 'type_name' => 'نجاح - Success', 'description' => 'إشعارات نجاح'],
        ];

        foreach ($types as $t) {
            NotificationType::updateOrCreate(['type_code' => $t['type_code']], ['type_name' => $t['type_name'], 'description' => $t['description'], 'is_active' => true]);
        }

        // Notification Channels (global)
        $channels = [
            ['channel_code' => 'NCH-EMAIL', 'channel_name' => 'بريد إلكتروني - Email'],
            ['channel_code' => 'NCH-SMS', 'channel_name' => 'رسالة نصية - SMS'],
            ['channel_code' => 'NCH-PUSH', 'channel_name' => 'إشعار فوري - Push Notification'],
            ['channel_code' => 'NCH-IN_APP', 'channel_name' => 'داخل التطبيق - In-App'],
        ];

        foreach ($channels as $c) {
            NotificationChannel::updateOrCreate(['channel_code' => $c['channel_code']], ['channel_name' => $c['channel_name'], 'is_active' => true]);
        }

        foreach ($companies as $company) {
            $adminUser = User::where('company_id', $company->id)->first();

            // Notification Events
            $events = [
                ['event_code' => 'NE-SALES_ORDER', 'event_name' => 'إنشاء أمر بيع - Sales Order Created', 'entity_type' => 'SalesOrder'],
                ['event_code' => 'NE-PURCHASE_ORDER', 'event_name' => 'إنشاء أمر شراء - Purchase Order Created', 'entity_type' => 'PurchaseOrder'],
                ['event_code' => 'NE-INVOICE', 'event_name' => 'إنشاء فاتورة - Invoice Created', 'entity_type' => 'SalesInvoice'],
                ['event_code' => 'NE-STOCK_LOW', 'event_name' => 'انخفاض المخزون - Low Stock Alert', 'entity_type' => 'Item'],
            ];

            foreach ($events as $e) {
                NotificationEvent::updateOrCreate(
                    ['event_code' => $e['event_code']],
                    ['company_id' => $company->id, 'event_name' => $e['event_name'], 'entity_type' => $e['entity_type'], 'is_active' => true]
                );
            }

            // Notification Templates
            $emailChannel = NotificationChannel::where('channel_code', 'NCH-EMAIL')->first();
            $notifType = NotificationType::where('type_code', 'NT-INFO')->first();
            $template = NotificationTemplate::updateOrCreate(
                ['template_code' => 'NTPL-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                [
                    'company_id' => $company->id,
                    'notification_type_id' => $notifType?->id,
                    'template_name' => 'قالب إشعار أمر البيع - Sales Order Notification Template',
                    'subject' => 'تم إنشاء أمر بيع جديد',
                    'title' => 'تم إنشاء أمر بيع جديد',
                    'message_body' => 'تم إنشاء أمر بيع جديد بقيمة {{amount}}',
                    'channel' => 'email',
                    'channel_id' => $emailChannel?->id,
                ]
            );

            // Notification Rules
            $event = NotificationEvent::where('event_code', 'NE-SALES_ORDER')->first();
            if ($event) {
                $rule = NotificationRule::create([
                    'company_id' => $company->id,
                    'notification_event_id' => $event->id,
                    'notification_template_id' => $template->id,
                    'priority' => 'NORMAL',
                    'is_active' => true,
                ]);

                NotificationRuleRecipient::create([
                    'notification_rule_id' => $rule->id,
                    'recipient_type' => 'user',
                    'recipient_value' => (string) ($adminUser?->id),
                ]);
            }

            // Notifications
            if ($adminUser) {
                $notification = Notification::create([
                    'user_id' => $adminUser->id,
                    'company_id' => $company->id,
                    'notification_type_id' => $notifType?->id,
                    'title' => 'تم إنشاء أمر بيع جديد',
                    'message' => 'تم إنشاء أمر بيع رقم INV-001 بقيمة 10,000 ج.م',
                    'is_read' => false,
                    'priority' => 'NORMAL',
                    'status' => 'PENDING',
                ]);

                // Notification Preferences
                $emailChannel = NotificationChannel::where('channel_code', 'NCH-EMAIL')->first();
                $pushChannel = NotificationChannel::where('channel_code', 'NCH-PUSH')->first();
                if ($notifType) {
                    if ($emailChannel) {
                        NotificationPreference::updateOrCreate(
                            ['user_id' => $adminUser->id, 'notification_type_id' => $notifType->id, 'channel_id' => $emailChannel->id],
                            ['is_enabled' => true]
                        );
                    }
                    if ($pushChannel) {
                        NotificationPreference::updateOrCreate(
                            ['user_id' => $adminUser->id, 'notification_type_id' => $notifType->id, 'channel_id' => $pushChannel->id],
                            ['is_enabled' => true]
                        );
                    }
                }

                // Notification Groups
                $group = NotificationGroup::updateOrCreate(
                    ['group_code' => 'NG-' . str_pad($company->id, 3, '0', STR_PAD_LEFT) . '-001'],
                    ['company_id' => $company->id, 'group_name' => 'مجموعة الإشعارات المالية - Finance Notification Group', 'description' => 'إشعارات مالية']
                );

                NotificationGroupMember::create([
                    'notification_group_id' => $group->id,
                    'user_id' => $adminUser->id,
                ]);

                // Notification Recipients
                NotificationRecipient::create([
                    'notification_id' => $notification->id,
                    'user_id' => $adminUser->id,
                    'delivery_status' => 'PENDING',
                ]);

                // Notification Deliveries
                if ($emailChannel) {
                    NotificationDelivery::create([
                        'notification_id' => $notification->id,
                        'channel_id' => $emailChannel->id,
                        'delivery_status' => 'PENDING',
                    ]);
                }

                // Scheduled Notifications
                if ($template) {
                    ScheduledNotification::create([
                        'company_id' => $company->id,
                        'notification_template_id' => $template->id,
                        'schedule_type' => 'ONCE',
                        'schedule_time' => '09:00:00',
                        'is_active' => true,
                    ]);
                }
            }
        }
    }
}
