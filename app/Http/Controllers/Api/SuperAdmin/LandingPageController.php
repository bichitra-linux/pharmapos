<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use App\Models\LandingPageRevision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    public function show(): JsonResponse
    {
        $page = LandingPage::firstOrCreate(
            ['slug' => 'default'],
            ['content' => $this->defaultContent()]
        );

        $page->loadCount('revisions');

        return $this->success($page);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'content' => 'required|array',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'theme_overrides' => 'nullable|array',
        ]);

        $page = LandingPage::where('slug', 'default')->firstOrFail();

        $page->createRevision($request->user()->id);

        $page->update($request->only('content', 'meta_title', 'meta_description', 'theme_overrides'));

        return $this->success($page->fresh(), 'Landing page updated.');
    }

    public function publish(): JsonResponse
    {
        $page = LandingPage::where('slug', 'default')->firstOrFail();
        $page->publish();

        return $this->success($page->fresh(), 'Landing page published.');
    }

    public function revisions(): JsonResponse
    {
        $page = LandingPage::where('slug', 'default')->firstOrFail();

        $revisions = $page->revisions()
            ->with('createdBy:id,name')
            ->latest()
            ->paginate(20);

        return $this->paginated($revisions);
    }

    public function restore(Request $request, LandingPageRevision $revision): JsonResponse
    {
        $page = $revision->landingPage;
        $page->createRevision($request->user()->id);
        $page->update(['content' => $revision->content]);

        return $this->success($page->fresh(), 'Revision restored.');
    }

    private function defaultContent(): array
    {
        return [
            'sections' => [
                [
                    'type' => 'nav',
                    'id' => 'nav',
                    'data' => [
                        'logo_url' => '',
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
                        'subheading' => 'Batch tracking, prescription management, narcotics register, digital payments — all in one purpose-built system for the Nepali pharmaceutical market.',
                        'primary_cta_text' => 'Start Free Trial',
                        'primary_cta_url' => '/register',
                        'secondary_cta_text' => 'See How It Works',
                        'secondary_cta_url' => '#how-it-works',
                        'trust_bullets' => ['Barcode scanning at counter', 'Schedule H1/X compliance', 'eSewa, Khalti, Fonepay ready'],
                    ],
                ],
                [
                    'type' => 'trusted_by',
                    'id' => 'trusted-by',
                    'data' => [
                        'heading' => 'Trusted by pharmacy chains across Nepal',
                        'logos' => [],
                    ],
                ],
                [
                    'type' => 'features',
                    'id' => 'features',
                    'data' => [
                        'heading' => 'Everything a pharmacy needs',
                        'subheading' => 'From batch tracking to government compliance.',
                        'items' => [
                            ['icon' => 'package', 'title' => 'Batch & Expiry Tracking', 'description' => 'Track every medicine by batch number, manufacturing date, and expiry. Never sell an expired drug.'],
                            ['icon' => 'file-text', 'title' => 'Prescription Management', 'description' => 'Upload, validate, and archive prescriptions. Partial dispensing for chronic patients.'],
                            ['icon' => 'shield', 'title' => 'Narcotics Register', 'description' => 'Digital Schedule X register meeting DDA requirements. Ready for government inspection.'],
                            ['icon' => 'credit-card', 'title' => 'Nepal Payment Gateways', 'description' => 'eSewa, Khalti, IME Pay, Fonepay, ConnectIPS. All major Nepal wallets.'],
                            ['icon' => 'file-text', 'title' => 'VAT Compliant Invoicing', 'description' => 'IRD-compliant invoices with PAN, drug license, and 13% VAT breakdown.'],
                            ['icon' => 'bar-chart', 'title' => 'Reports & Analytics', 'description' => 'Profit & loss, expiry report, dead stock, sales by schedule type.'],
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
                    'type' => 'stats',
                    'id' => 'stats',
                    'data' => [
                        'heading' => 'Used by pharmacies across Nepal',
                        'items' => [
                            ['label' => 'Prescriptions Dispensed', 'key' => 'prescriptions_dispensed', 'suffix' => '+'],
                            ['label' => 'Active Pharmacies', 'key' => 'active_pharmacies', 'suffix' => '+'],
                            ['label' => 'Medicines Tracked', 'key' => 'medicines_tracked', 'suffix' => '+'],
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
                    'type' => 'testimonials',
                    'id' => 'testimonials',
                    'data' => [
                        'heading' => 'What pharmacists say',
                        'items' => [
                            ['quote' => 'PharmaPOS saved us hours of manual narcotics register work. The batch tracking alone is worth it.', 'author' => 'Ram Thapa', 'title' => 'Pharmacist, Kathmandu'],
                            ['quote' => 'The digital payment integration means our customers can pay with eSewa or Khalti. They love it.', 'author' => 'Sita Sharma', 'title' => 'Owner, Pokhara Pharmacy'],
                            ['quote' => 'Setting up was incredibly fast. We imported 2,000 medicines from an Excel file in five minutes.', 'author' => 'Anil Gurung', 'title' => 'Manager, Biratnagar'],
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
    }
}
