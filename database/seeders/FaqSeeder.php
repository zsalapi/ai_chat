<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Faq;

class FaqSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'password',
                'answer' => 'You can reset your password by clicking on the "Forgot Password" link on the login page.',
            ],
            [
                'question' => 'create event',
                'answer' => 'To create an event, log in to your account, navigate to the Events page, and click "Create New Event".',
            ],
            [
                'question' => 'payment',
                'answer' => 'We accept major credit cards (Visa, MasterCard) and PayPal for ticket purchases.',
            ],
            [
                'question' => 'refund',
                'answer' => 'Refunds are available up to 48 hours before the event starts. Please contact support for assistance.',
            ],
            [
                'question' => 'contact',
                'answer' => 'You can contact our support team directly through this chat by clicking "Ask Agent", or email us at support@eventpro.com.',
            ],
            [
                'question' => 'ticket',
                'answer' => 'Your tickets are sent to your email address immediately after purchase. You can also view them in your dashboard.',
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::create($faq);
        }
    }
}
