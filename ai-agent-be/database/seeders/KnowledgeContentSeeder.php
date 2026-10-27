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

        KnowledgeContent::query()
            ->where(function ($query): void {
                $query->where(function ($query): void {
                    $query->where('slug', 'like', 'northstar-nexus-%')
                        ->where('slug', '!=', 'northstar-nexus-fictional-company-profile');
                })
                    ->orWhereIn('slug', [
                        'refund-and-returns-policy',
                        'shipping-and-delivery',
                        'account-security-and-passwords',
                        'privacy-and-data-retention',
                        'using-the-ai-knowledge-assistant',
                        'contacting-support',
                    ]);
            })
            ->delete();

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
                'title' => 'Northstar Nexus: Fictional Company Profile',
                'slug' => 'northstar-nexus-fictional-company-profile',
                'body' => <<<'MARKDOWN'
Northstar Nexus is a completely fictional B2B software company created only for AI Knowledge Agent and RAG demonstrations. It does not exist. Every employee, customer, office, product, policy, commercial term, and financial figure below is invented.

## Company profile

Founded in 2018, Northstar Nexus builds operations software for mid-market logistics and manufacturing companies. Its fictional headquarters are in Amman, Jordan, with small fictional offices in Barcelona and Singapore. The company has 168 employees as of December 2024 and serves 84 customers.

Northstar Nexus helps operations teams forecast demand, coordinate suppliers, identify delivery risk, and report operational performance. Its mission is to help complex teams make calm, evidence-based decisions.

## Leadership and organization

Lina Rahal is the fictional Chief Executive Officer and co-founder. Omar Vale is Chief Operating Officer, Priya Sen is Chief Product Officer, Mateo Klein is Chief Technology Officer, and Elena Cruz is Chief Financial Officer.

The company has six departments: Product and Design with 31 employees, Engineering with 57, Customer Operations with 28, Revenue with 24, Finance and People with 15, and Executive and Strategy with 13. Engineering has three squads: OrbitFlow, PulseSight, and Platform Foundations.

## Products

OrbitFlow is a supply-planning workspace that combines purchase orders, inventory forecasts, supplier lead times, and production plans. Its main users are supply planners and operations managers.

PulseSight is an operations analytics product with dashboards for on-time delivery, forecast accuracy, inventory turns, supplier reliability, and cost variance.

VaultGrid is a controlled repository for supplier contracts, quality certificates, and operating procedures. It is sold as an add-on to OrbitFlow.

## Employees and policies

Northstar Nexus uses a hybrid work model. Employees may work remotely up to three days per week, while each department selects two collaboration days. The company provides a fictional home-office equipment budget of 900 USD every three years.

Employees receive 24 business days of annual leave, 10 paid public holidays based on employment location, five paid wellness days, and 12 weeks of paid parental leave. Performance reviews occur in June and December and assess delivery, collaboration, customer impact, and learning objectives.

The fictional workforce is 46 percent women, 51 percent men, and 3 percent employees who self-describe or prefer not to say. Employees represent 22 nationalities. The 2025 hiring plan adds 22 roles: eight in Engineering, four in Customer Operations, four in Revenue, three in Product and Design, two in Finance, and one in People Operations. The target year-end headcount is 190.

The fictional voluntary attrition rate was 8.1 percent in 2024. The People team aims to keep voluntary attrition below 10 percent in 2025 while improving internal mobility.

## Customers and commercial model

Northstar Nexus serves logistics, industrial manufacturing, food distribution, and specialty retail customers. Its largest fictional customer is Meridian Cargo Cooperative, which represents 7.4 percent of annual recurring revenue. No single customer represents more than 10 percent of recurring revenue.

OrbitFlow starts at 48,000 USD per year for a 25-user team. PulseSight starts at 24,000 USD per year. VaultGrid starts at 12,000 USD per year. Enterprise contracts are typically three years and billed annually in advance.

Fictional 2024 net revenue retention was 116 percent. Gross customer retention was 94 percent. Expansion revenue was driven mainly by PulseSight adoption among existing OrbitFlow customers.

## Multi-year financial performance

All financial figures are fictional and expressed in USD millions. Northstar Nexus uses a December 31 financial year-end.

| Financial year | Revenue | Year-over-year growth | Annual recurring revenue | Gross margin | Operating result |
| --- | ---: | ---: | ---: | ---: | ---: |
| FY2020 | 2.1 | Not applicable | 1.8 | 54% | -3.8 |
| FY2021 | 3.9 | 86% | 3.4 | 59% | -4.5 |
| FY2022 | 7.2 | 85% | 6.5 | 65% | -4.1 |
| FY2023 | 14.0 | 94% | 15.2 | 69% | -3.4 |
| FY2024 | 18.4 | 31% | 20.1 | 72% | -2.6 |
| FY2025 plan | 25.0 | 36% | 27.5 | 74% | -0.9 |
| FY2026 forecast | 31.8 | 27% | 35.4 | 76% | 0.6 |

Subscription revenue represented 91 percent of FY2024 revenue, while professional services contributed 9 percent. The company’s fictional operating loss narrowed from 4.5 million USD in FY2021 to 2.6 million USD in FY2024 as gross margin improved.

The 2025 plan assumes stronger PulseSight adoption, lower implementation time, and continued expansion revenue from existing OrbitFlow customers. Finance expects the company to reach operating break-even during the fourth quarter of 2026, with a full-year fictional operating profit of 0.6 million USD forecast for FY2026.

## 2025 strategy

Northstar Nexus has four fictional priorities for 2025. First, improve OrbitFlow forecast accuracy by introducing supplier-risk signals and scenario planning, targeting a 15 percent improvement for customers using the new workflow. Second, grow PulseSight adoption from 38 percent to 55 percent of the customer base. Third, reduce standard implementation time from 14 weeks to 9 weeks. Fourth, expand into the Gulf Cooperation Council region through two implementation partners.

The company will not pursue consumer products or a broad self-service product line in 2025. Its focus remains mid-market operational teams with complex supply chains.
MARKDOWN,
                'source_url' => 'https://northstar-nexus.example/company-profile',
                'metadata' => [
                    'category' => 'Company',
                    'tags' => ['fictional company', 'employees', 'revenue', 'products', 'strategy'],
                ],
            ],
        ];
    }
}
