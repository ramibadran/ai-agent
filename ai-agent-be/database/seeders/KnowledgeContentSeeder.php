<?php

namespace Database\Seeders;

use App\Models\KnowledgeContent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class KnowledgeContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $administrator = User::query()->updateOrCreate(
            ['email' => 'admin@example.test'],
            [
                'name' => 'Demo Administrator',
                'password' => Hash::make('password'),
                'is_admin' => true,
            ],
        );

        foreach ($this->contents() as $content) {
            KnowledgeContent::query()->updateOrCreate(
                ['slug' => $content['slug']],
                [
                    'created_by' => $administrator->id,
                    'title' => $content['title'],
                    'body' => $content['body'],
                    'source_url' => $content['source_url'],
                    'status' => 'published',
                    'version' => 1,
                    'content_checksum' => hash('sha256', $content['title']."\n".$content['body']),
                    'metadata' => $content['metadata'],
                    'published_at' => now(),
                    'indexed_at' => null,
                ],
            );
        }
    }

    /**
     * @return list<array{title: string, slug: string, body: string, source_url: string, metadata: array{category: string, tags: list<string>}}>
     */
    private function contents(): array
    {
        return [
            [
                'title' => 'Refund and Returns Policy',
                'slug' => 'refund-and-returns-policy',
                'body' => <<<'MARKDOWN'
Customers can request a refund within 30 calendar days of delivery. The item must be unused, in its original packaging, and include all accessories.

To start a return, provide the order number and the email address used when placing the order. We send a prepaid return label within one business day after approving the request.

Refunds are issued to the original payment method after the returned item is received and inspected. Card providers can take five to ten business days to show the refund.

Damaged, defective, or incorrect items must be reported within seven calendar days of delivery. These cases are eligible for a replacement or full refund, including return shipping.
MARKDOWN,
                'source_url' => 'https://example.test/help/refunds',
                'metadata' => [
                    'category' => 'Orders',
                    'tags' => ['refunds', 'returns', 'damaged items'],
                ],
            ],
            [
                'title' => 'Shipping and Delivery',
                'slug' => 'shipping-and-delivery',
                'body' => <<<'MARKDOWN'
Standard shipping is free for orders of 50 USD or more. Orders below 50 USD have a flat standard-shipping fee of 6 USD.

Orders placed before 2:00 PM local warehouse time on business days are processed on the same day. Orders placed after that time, on weekends, or on public holidays are processed on the next business day.

Standard delivery normally takes three to five business days after processing. Express delivery normally takes one to two business days and is available at checkout for eligible destinations.

When an order ships, the customer receives an email with the carrier and tracking number. Address changes are possible only before the order enters the packing stage.
MARKDOWN,
                'source_url' => 'https://example.test/help/shipping',
                'metadata' => [
                    'category' => 'Orders',
                    'tags' => ['shipping', 'delivery', 'tracking'],
                ],
            ],
            [
                'title' => 'Account Security and Passwords',
                'slug' => 'account-security-and-passwords',
                'body' => <<<'MARKDOWN'
Use a unique password with at least 12 characters. A password manager is recommended for generating and storing strong passwords.

If you forget your password, select "Forgot password" on the sign-in page. We send a password-reset link to the account email address. The link expires after 60 minutes and can be used once.

If you believe someone accessed your account, reset the password immediately and contact support with the account email address. Support will invalidate active sessions after confirming account ownership.

Never share a password, reset link, one-time code, or payment details in a chat message.
MARKDOWN,
                'source_url' => 'https://example.test/help/account-security',
                'metadata' => [
                    'category' => 'Account',
                    'tags' => ['password', 'security', 'account access'],
                ],
            ],
            [
                'title' => 'Privacy and Data Retention',
                'slug' => 'privacy-and-data-retention',
                'body' => <<<'MARKDOWN'
We use account information to provide the service, process orders, provide support, and improve product features. We do not sell personal information.

Users can request an export of their account data from account settings. Exports are prepared within seven business days and are available for download for 14 days.

Users can request account deletion from account settings. We delete or anonymize personal data within 30 days unless a longer retention period is required for legal, tax, fraud-prevention, or dispute-resolution purposes.

Support conversations are retained for 24 months to help resolve follow-up requests and maintain service quality.
MARKDOWN,
                'source_url' => 'https://example.test/help/privacy',
                'metadata' => [
                    'category' => 'Privacy',
                    'tags' => ['privacy', 'data export', 'account deletion'],
                ],
            ],
            [
                'title' => 'Using the AI Knowledge Assistant',
                'slug' => 'using-the-ai-knowledge-assistant',
                'body' => <<<'MARKDOWN'
The AI Knowledge Assistant searches published help content to answer questions. It shows the source articles used for an answer so users can verify the information.

The assistant can summarize and explain the published knowledge base, but it should not be used to share passwords, payment details, private customer data, or other confidential information.

If the assistant cannot find a reliable answer in the knowledge base, it should say so instead of guessing. A future version may search approved public web sources and will label those sources separately.

Conversation history is visible only to the signed-in user who owns the conversation. Administrators can review system-level timing, token, and retrieval data without exposing secret credentials.
MARKDOWN,
                'source_url' => 'https://example.test/help/ai-assistant',
                'metadata' => [
                    'category' => 'Product',
                    'tags' => ['ai assistant', 'sources', 'conversation history'],
                ],
            ],
            [
                'title' => 'Contacting Support',
                'slug' => 'contacting-support',
                'body' => <<<'MARKDOWN'
Support is available Monday through Friday, 9:00 AM to 5:00 PM in the company time zone. The standard response target is one business day.

For order questions, include the order number. For account-access questions, include the account email address but never include a password or reset code.

For urgent security concerns, choose the "Account security" topic in the support form. These requests are reviewed before general product questions.
MARKDOWN,
                'source_url' => 'https://example.test/help/contact-support',
                'metadata' => [
                    'category' => 'Support',
                    'tags' => ['support', 'contact', 'security'],
                ],
            ],
        ];
    }
}
