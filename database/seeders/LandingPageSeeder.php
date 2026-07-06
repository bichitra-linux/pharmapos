<?php

namespace Database\Seeders;

use App\Models\LandingPage;
use Illuminate\Database\Seeder;

class LandingPageSeeder extends Seeder
{
    public function run(): void
    {
        $content = [
            'sections' => [
                [
                    'type' => 'nav',
                    'id' => 'nav',
                    'data' => [
                        'logo_text' => 'PharmaPOS',
                        'cta_text' => 'Start Free Trial',
                        'cta_url' => '/register',
                        'signin_text' => 'Sign In',
                        'signin_url' => '/login',
                    ],
                ],
                [
                    'type' => 'hero',
                    'id' => 'hero',
                    'data' => [
                        'eyebrow' => 'Made for Nepal Pharmacies',
                        'heading' => 'The pharmacy POS built for Nepal',
                        'subheading' => 'Batch tracking, prescription management, narcotics register, and digital payments in one purpose-built system for the Nepali pharmaceutical market.',
                        'primary_cta_text' => 'Start Free Trial',
                        'primary_cta_url' => '/register',
                        'secondary_cta_text' => 'See How It Works',
                        'secondary_cta_url' => '#how-it-works',
                        'trust_signals' => ['Schedule-aware', 'VAT + PAN invoiced', 'eSewa + Khalti + Fonepay', 'BS + AD dates'],
                    ],
                ],
                [
                    'type' => 'compliance_bar',
                    'id' => 'compliance',
                    'data' => [
                        'items' => [
                            ['label' => 'DDA ready', 'detail' => 'Registers and schedules kept close to the sale.'],
                            ['label' => 'PAN/VAT', 'detail' => 'Invoices are ready for Nepali accounting.'],
                            ['label' => 'Schedule H/H1/X', 'detail' => 'Sensitive medicines stay visible.'],
                            ['label' => 'BS + AD dates', 'detail' => 'Staff can read both calendar systems.'],
                        ],
                    ],
                ],
                [
                    'type' => 'module_showcase',
                    'id' => 'features',
                    'data' => [
                        'heading' => 'The organized pharmacy shelf, digitized',
                        'subheading' => 'Counter work, stock work, and compliance stay in the same flow.',
                        'modules' => [
                            ['number' => '01', 'title' => 'Dispense at counter', 'description' => 'Scan, check schedule status, attach prescription, and take payment without switching screens.'],
                            ['number' => '02', 'title' => 'Track batch inventory', 'description' => 'See expiry, stock, purchase price, selling price, and reorder state before the sale.'],
                            ['number' => '03', 'title' => 'Keep compliance ready', 'description' => 'Narcotics register, VAT invoices, and audit trails are created as work happens.'],
                        ],
                    ],
                ],
                [
                    'type' => 'how_it_works',
                    'id' => 'how-it-works',
                    'data' => [
                        'heading' => 'How it works',
                        'steps' => [
                            ['title' => 'Set up your pharmacy', 'description' => 'Add your pharmacy details, import your medicine catalog, and configure payment methods in minutes.'],
                            ['title' => 'Start dispensing', 'description' => 'Scan barcodes, check prescriptions, and process sales in under 60 seconds.'],
                            ['title' => 'Stay compliant', 'description' => 'Automatic narcotics register, expiry alerts, and ready-to-print government reports.'],
                        ],
                    ],
                ],
                [
                    'type' => 'workflow_diagram',
                    'id' => 'workflow',
                    'data' => [
                        'heading' => 'One line from purchase to report',
                        'nodes' => [
                            ['label' => 'Receive purchase'],
                            ['label' => 'Track expiry'],
                            ['label' => 'Scan sale'],
                            ['label' => 'Collect payment'],
                            ['label' => 'Update ledger'],
                            ['label' => 'File reports'],
                        ],
                    ],
                ],
                [
                    'type' => 'pricing',
                    'id' => 'pricing',
                    'data' => [
                        'heading' => 'Simple, transparent pricing',
                        'subheading' => 'No hidden fees. No setup costs.',
                    ],
                ],
                [
                    'type' => 'operator_signal',
                    'id' => 'operator-signal',
                    'data' => [
                        'heading' => 'Built for counter speed',
                        'quote' => 'The safest sale is the one where stock, expiry, prescription, and payment are checked in one place.',
                        'attribution_rows' => [
                            ['name' => 'Pharmacist workflow', 'pharmacy' => 'Counter-first dispensing', 'location' => 'Nepal', 'since' => 'Daily use'],
                        ],
                    ],
                ],
                [
                    'type' => 'faq',
                    'id' => 'faq',
                    'data' => [
                        'heading' => 'Frequently asked questions',
                        'items' => [
                            ['question' => 'Do I need special hardware?', 'answer' => 'Just a computer, a thermal receipt printer, and a barcode scanner. We provide a full hardware guide.'],
                            ['question' => 'Is PharmaPOS compliant with Nepal drug regulations?', 'answer' => 'Yes. The system is designed for DDA compliance with digital narcotics registers, Schedule H/X tracking, and IRD-compliant invoices.'],
                            ['question' => 'Can I use it for multiple outlets?', 'answer' => 'Yes. Our Professional and Enterprise plans support multiple outlets with centralized reporting.'],
                            ['question' => 'What payment gateways are supported?', 'answer' => 'eSewa, Khalti, IME Pay, Fonepay, and ConnectIPS. Cash and card are also supported.'],
                            ['question' => 'Is there a free trial?', 'answer' => 'Yes. Start with a 14-day free trial. No credit card required.'],
                        ],
                    ],
                ],
                [
                    'type' => 'cta',
                    'id' => 'cta',
                    'data' => [
                        'heading' => 'Ready to transform your pharmacy?',
                        'subheading' => 'Start your free trial today. No credit card needed.',
                        'button_text' => 'Start Free Trial',
                        'button_url' => '/register',
                    ],
                ],
                [
                    'type' => 'footer',
                    'id' => 'footer',
                    'data' => [
                        'logo_text' => 'PharmaPOS',
                        'description' => 'A cloud-based pharmacy Point-of-Sale and inventory management system built for the Nepali pharmaceutical retail market.',
                        'links' => [
                            ['label' => 'Privacy Policy', 'url' => '#'],
                            ['label' => 'Terms of Service', 'url' => '#'],
                            ['label' => 'Contact', 'url' => '#'],
                        ],
                    ],
                ],
            ],
        ];

        LandingPage::firstOrCreate(
            ['slug' => 'default'],
            [
                'content' => $content,
                'meta_title' => 'PharmaPOS: Pharmacy POS for Nepal',
                'meta_description' => 'Cloud-based pharmacy Point-of-Sale and inventory management system built for the Nepali pharmaceutical retail market.',
                'published_at' => now(),
            ]
        );
    }
}
